<?php

namespace App\Http\Controllers\Initiatives\SchoolEducation;

use App\Dashboards\Initiatives\SchoolEducation\SchoolMealDashboard;
use App\Dashboards\Initiatives\SchoolEducation\SchoolMealDistricts;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Initiatives\SchoolEducation\Concerns\ManagesPsrpVisits;
use App\Imports\Initiatives\SchoolEducation\SchoolMealVisitImport;
use App\Models\Initiative;
use App\Models\InitiativeFile;
use App\Models\Initiatives\SchoolEducation\SchoolMealVisit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Chief Minister's Punjab Schools Meal Program: the findings design, with the uploads and the records
// table at the end of the page, as on the other initiative pages.
class SchoolMealProgramController extends Controller
{
    // Upload, filters and CSV download work the same as on the other initiative pages.
    use ManagesPsrpVisits;

    private const TABLE = 'punjab_schools_meal_program';

    private const SCHOOLS = 'punjab_schools_meal_program_schools';

    // Every column of our visit file, in its order. The headings are ones the visit import knows, so the
    // CSV download can be uploaded again. The table shows the visit's date; the CSV keeps its time too.
    private const VISIT_COLUMNS = [
        'visited_at' => ['label' => 'Visit date', 'type' => 'date'],
        'district_agent' => ['label' => 'District agent', 'type' => 'text'],
        'email' => ['label' => 'Email', 'type' => 'text'],
        'district' => ['label' => 'District', 'type' => 'text'],
        'emis_code' => ['label' => 'EMIS', 'type' => 'code'],
        'school_name' => ['label' => 'School name', 'type' => 'text'],
        'school_opened' => ['label' => 'School opened', 'type' => 'boolean', 'bad' => false],
        'days_open_per_week' => ['label' => 'Days open in a week', 'type' => 'number'],
        'students_enrolled' => ['label' => 'Students enrolled', 'type' => 'number'],
        'students_before_program' => ['label' => 'Students before the program', 'type' => 'number'],
        'students_present' => ['label' => 'Students present', 'type' => 'number'],
        'students_absent' => ['label' => 'Students absent', 'type' => 'number'],
        'students_receiving_milk' => ['label' => 'Students to receive milk', 'type' => 'number'],
        'absent_yesterday_receiving_milk' => ['label' => 'Absent yesterday, to receive milk', 'type' => 'number'],
        'total_staff' => ['label' => 'Total staff', 'type' => 'number'],
        'staff_absent' => ['label' => 'Staff absent', 'type' => 'number'],
        'milk_packs_received' => ['label' => 'Milk packs received', 'type' => 'number'],
        'milk_packs_distributed' => ['label' => 'Milk packs distributed', 'type' => 'number'],
        'milk_cartons_expired' => ['label' => 'Milk cartons expired', 'type' => 'number'],
        'milk_packs_in_stock' => ['label' => 'Milk packs in stock', 'type' => 'number'],
        // Kept as written (2026-02-22), which sorts and uploads again as a date.
        'stock_expiry_date' => ['label' => 'Stock expiry date', 'type' => 'text'],
        'expired_stock_received' => ['label' => 'Expired stock received', 'type' => 'boolean', 'bad' => true],
        'milk_caused_harm' => ['label' => 'Milk caused harm', 'type' => 'boolean', 'bad' => true],
        'milk_delivered_on_time' => ['label' => 'Delivered on time', 'type' => 'boolean', 'bad' => false],
        'delivery_gap_days' => ['label' => 'Days until milk arrives', 'type' => 'number'],
        'milk_stock_ever_received' => ['label' => 'Milk stock ever received', 'type' => 'boolean', 'bad' => false],
        'milk_quality' => ['label' => 'Milk quality satisfactory', 'type' => 'boolean', 'bad' => false],
        'poor_cleanliness' => ['label' => 'Poor cleanliness', 'type' => 'boolean', 'bad' => true],
        'lack_of_disposal_facilities' => ['label' => 'Lack of disposal facilities', 'type' => 'boolean', 'bad' => true],
        'inadequate_storage_facilities' => ['label' => 'Inadequate storage facilities', 'type' => 'boolean', 'bad' => true],
        'large_portion_of_stock_received' => ['label' => 'Large portion of stock received', 'type' => 'boolean', 'bad' => true],
        'milk_used_for_households' => ['label' => 'Milk used for households', 'type' => 'boolean', 'bad' => true],
        'overall_rating' => ['label' => 'Overall rating', 'type' => 'text'],
        'photo_links' => ['label' => 'Photos', 'type' => 'text'],
        'media_links' => ['label' => 'Audio / video', 'type' => 'text'],
        // Shown in full; a number format would round them.
        'latitude' => ['label' => 'Lat', 'type' => 'text'],
        'longitude' => ['label' => 'Long', 'type' => 'text'],
    ];

    // Everything recorded for a visit, shown when its row is opened, under the visit file's own headings.
    private const VISIT_DETAILS = [
        'school_name' => 'School Name',
        'emis_code' => 'EMIS',
        'district' => 'District',
        'visited_at' => 'Timestamp',
        'district_agent' => 'District Agent',
        'email' => 'Email',
        'school_opened' => 'School Opened',
        'days_open_per_week' => 'How many days school open in a week',
        'students_enrolled' => 'Total students enrolled',
        'students_before_program' => 'Total students before school nutrition program',
        'students_present' => 'Students attendance on visit day',
        'students_absent' => 'Total absent students',
        'students_receiving_milk' => "Total students in the school who are to receive the 'Milk Pack'",
        'absent_yesterday_receiving_milk' => 'Students who were absent yesterday and are to receive a milk pack.',
        'total_staff' => 'Total staff',
        'staff_absent' => 'Total absent staff',
        'milk_packs_received' => 'How many packs of milk have been received so far?',
        'milk_packs_distributed' => 'How many milk packs have been distributed among students to date?',
        'milk_cartons_expired' => 'How many milk cartons have expired to date?',
        'milk_packs_in_stock' => 'How many milk packs are in stock as of today?',
        'stock_expiry_date' => 'Stock Expiry Date',
        'expired_stock_received' => 'Has the school received any expired stock?',
        'milk_caused_harm' => 'Has the milk provided to the students caused any harm? (Such as an illness)',
        'milk_delivered_on_time' => 'Is the milk delivered on time?',
        'delivery_gap_days' => 'How many days later does the school receive milk packets?',
        'milk_stock_ever_received' => 'school ever received milk stock',
        'milk_quality' => 'Was the quality of the milk packets satisfactory?',
        'poor_cleanliness' => 'Poor cleanliness at the school',
        'lack_of_disposal_facilities' => 'Lack of proper disposal facilities for used milk cartons',
        'inadequate_storage_facilities' => 'Inadequate storage facilities for milk packs',
        'large_portion_of_stock_received' => 'A large portion of the stock was received.',
        'milk_used_for_households' => 'Teachers and other influential people use milk packets for their households.',
        'overall_rating' => 'Overall Rating',
        'photo_links' => 'Photo Link',
        'media_links' => 'Media Link',
        'latitude' => 'Lat',
        'longitude' => 'Long',
    ];

    // Visits worth double-checking before their figures are relied on.
    private const DATA_CHECKS = [
        'above_total' => [
            'label' => 'Present or absent above total',
            'rule' => 'students_present > students_enrolled OR students_absent > students_enrolled'
                .' OR staff_absent > total_staff',
        ],
        'distributed_above_received' => [
            'label' => 'More packs given out than received',
            'rule' => 'milk_packs_distributed > milk_packs_received',
        ],
        'not_in_list' => [
            'label' => 'Not in the department file',
            'rule' => 'punjab_schools_meal_program_schools.id IS NULL',
        ],
    ];

    // Every column of our visit file that is kept (all but Additional Remarks), under its own
    // headings, in its order and with its formats, for the map's download. Blanks are written as
    // the file writes them: "Null".
    private const FILE_COLUMNS = [
        // Shows like 22/Feb, as in the file; the cell keeps the full date and time.
        'visited_at' => ['label' => 'Timestamp', 'type' => 'date', 'excel_format' => '[$-409]d/mmm;@'],
        'district_agent' => ['label' => 'District Agent', 'type' => 'text'],
        'email' => ['label' => 'Email', 'type' => 'text', 'blank' => 'Null'],
        'district' => ['label' => 'District', 'type' => 'text'],
        'emis_code' => ['label' => 'EMIS', 'type' => 'number'],
        'school_name' => ['label' => 'School Name', 'type' => 'text'],
        'school_opened' => ['label' => 'School Opened', 'type' => 'boolean', 'blank' => 'Null'],
        'days_open_per_week' => ['label' => 'How many days school open in a week', 'type' => 'number', 'blank' => 'Null'],
        'students_enrolled' => ['label' => 'Total students enrolled', 'type' => 'number', 'blank' => 'Null'],
        'students_before_program' => ['label' => 'Total students before school nutrition program', 'type' => 'number', 'blank' => 'Null'],
        'students_present' => ['label' => 'Students attendance on visit day', 'type' => 'number', 'blank' => 'Null'],
        'students_absent' => ['label' => 'Total absent students', 'type' => 'number', 'blank' => 'Null'],
        'students_receiving_milk' => ['label' => "Total students in the school who are to receive the 'Milk Pack'", 'type' => 'number', 'blank' => 'Null'],
        'absent_yesterday_receiving_milk' => ['label' => 'Students who were absent yesterday and are to receive a milk pack.', 'type' => 'number', 'blank' => 'Null'],
        'total_staff' => ['label' => 'Total staff', 'type' => 'number', 'blank' => 'Null'],
        'staff_absent' => ['label' => 'Total absent staff', 'type' => 'number', 'blank' => 'Null'],
        'milk_packs_received' => ['label' => 'How many packs of milk have been received so far?', 'type' => 'number', 'blank' => 'Null'],
        'milk_packs_distributed' => ['label' => 'How many milk packs have been distributed among students to date?', 'type' => 'number', 'blank' => 'Null'],
        'milk_cartons_expired' => ['label' => 'How many milk cartons have expired to date?', 'type' => 'number', 'blank' => 'Null'],
        'milk_packs_in_stock' => ['label' => 'How many milk packs are in stock as of today?', 'type' => 'number', 'blank' => 'Null'],
        'stock_expiry_date' => ['label' => 'Stock Expiry Date', 'type' => 'date', 'excel_format' => 'mm-dd-yy', 'blank' => 'Null'],
        'expired_stock_received' => ['label' => 'Has the school received any expired stock?', 'type' => 'boolean', 'blank' => 'Null'],
        'milk_caused_harm' => ['label' => 'Has the milk provided to the students caused any harm? (Such as an illness)', 'type' => 'boolean', 'blank' => 'Null'],
        'milk_delivered_on_time' => ['label' => 'Is the milk delivered on time?', 'type' => 'boolean', 'blank' => 'Null'],
        'delivery_gap_days' => ['label' => 'How many days later does the school receive milk packets?', 'type' => 'number'],
        'milk_stock_ever_received' => ['label' => 'school ever received milk stock', 'type' => 'boolean', 'blank' => 'Null'],
        'milk_quality' => ['label' => 'Was the quality of the milk packets satisfactory?', 'type' => 'boolean', 'blank' => 'Null'],
        'poor_cleanliness' => ['label' => 'Poor cleanliness at the school', 'type' => 'boolean', 'blank' => 'Null'],
        'lack_of_disposal_facilities' => ['label' => 'Lack of proper disposal facilities for used milk cartons', 'type' => 'boolean', 'blank' => 'Null'],
        'inadequate_storage_facilities' => ['label' => 'Inadequate storage facilities for milk packs', 'type' => 'boolean', 'blank' => 'Null'],
        'large_portion_of_stock_received' => ['label' => 'A large portion of the stock was received.', 'type' => 'boolean', 'blank' => 'Null'],
        'milk_used_for_households' => ['label' => 'Teachers and other influential people use milk packets for their households.', 'type' => 'boolean', 'blank' => 'Null'],
        'overall_rating' => ['label' => 'Overall Rating', 'type' => 'text', 'blank' => 'Null'],
        'photo_links' => ['label' => 'Photo Link', 'type' => 'text'],
        'media_links' => ['label' => 'Media Link', 'type' => 'text'],
        'latitude' => ['label' => 'Lat', 'type' => 'number', 'blank' => 'Not Found'],
        'longitude' => ['label' => 'Long', 'type' => 'number', 'blank' => 'Not Found'],
    ];

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    // Sent by the page when only the records table needs refreshing.
    private const RECORDS_HEADER = 'X-Psrp-Records';

    public function show(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        $filters = $this->filters($request);
        $table = $this->tableOptions($request);

        $records = [
            'initiative' => $initiative,
            'filters' => $filters,
            'table' => $table,
            'totals' => ['visits' => SchoolMealVisit::count()],
            'dataChecks' => $this->dataCheckCounts($filters),
            'records' => $this->visitRecords($filters, $table)
                ->paginate($table['per_page'])
                ->withQueryString()
                ->fragment('psrp-records'),
            'districtRows' => null,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'exportUrl' => route(
                'initiatives.school-meal.export',
                ['initiative' => $initiative] + $request->except(['page', 'per_page'])
            ),
            'hasDistrictTab' => false,
            'searchHint' => 'EMIS, school or district',
            'detailFields' => self::VISIT_DETAILS,
            'detailDateFormat' => 'j M Y, g:i A',
            'nameColumn' => 'school_name',
        ];

        if ($request->hasHeader(self::RECORDS_HEADER)) {
            return response()
                ->view('initiatives.school_education.psrp_shared.records', $records)
                ->header('Vary', self::RECORDS_HEADER);
        }

        // The controller is called with the request and initiative only, so the dashboard is made here.
        return response()
            ->view('initiatives.school_education.school_meal.show', $records + [
                'topbar' => $this->topbar($initiative, app(SchoolMealDashboard::class)),
                'files' => $initiative->filesByType(),
            ])
            ->header('Vary', self::RECORDS_HEADER);
    }

    // Everything the page's figures, map and tables are worked out from.
    public function data(Request $request, Initiative $initiative, SchoolMealDashboard $dashboard)
    {
        $this->ensureThisInitiative($initiative);

        return $this->dashboardData($request, $dashboard);
    }

    public function export(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        $table = $this->tableOptions($request);

        return $this->downloadCsv(
            $this->visitRecords($this->filters($request), $table)->cursor(),
            $table['columns'],
            'school-meal-visits-'.now()->format('Y-m-d').'.csv'
        );
    }

    // The schools on the map for the chosen finding, status or response, as rows of our visit file.
    public function mapDownload(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        // The visit file has no Sr column; its rows are in timestamp order.
        return $this->downloadMapSchools($request, SchoolMealVisit::class, self::FILE_COLUMNS, 'school-meal', 'visited_at');
    }

    public function import(Request $request, Initiative $initiative, SchoolMealVisitImport $import)
    {
        $this->ensureThisInitiative($initiative);

        return $this->uploadVisits($request, $initiative, $import);
    }

    // The design's five cards, with the line under each. They show all of Punjab here, and the
    // page's script keeps them in step with its filters.
    private function topbar(Initiative $initiative, SchoolMealDashboard $dashboard): array
    {
        $cards = $dashboard->headline($dashboard->data());

        // Our report PDFs, uploaded with "Upload report", as on every initiative page. A file
        // covers every district and date, so this one stays the same whatever the filters select.
        $cards['reports'] = [$initiative->files()->where('type', InitiativeFile::TYPE_OUR_REPORT)->count(), null];

        $labels = [
            'total' => 'Total schools',
            'visited' => 'Visited by SMU',
            'reports' => 'Reports sent to department',
            'replied' => 'Department responses',
            'revisit' => 'Visited again',
        ];

        $stats = [];
        foreach ($labels as $key => $label) {
            [$value, $info] = $cards[$key];
            $stats[] = ['label' => $label, 'value' => $value, 'info' => $info, 'id' => 'c-'.$key];
        }

        return [
            'title' => $initiative->name,
            'description' => 'Monitor field inspection data and implementation progress for this initiative.',
            'stats' => $stats,
        ];
    }

    private function ensureThisInitiative(Initiative $initiative): void
    {
        abort_unless($initiative->is_active && $initiative->table_name === self::TABLE, 404);
    }

    private function tableOptions(Request $request): array
    {
        $sort = $this->text($request, 'sort');
        $check = $this->text($request, 'check');
        $perPage = (int) $this->text($request, 'per_page');

        return [
            'tab' => 'visits',
            'columns' => self::VISIT_COLUMNS,
            'sort' => array_key_exists($sort, self::VISIT_COLUMNS) ? $sort : array_key_first(self::VISIT_COLUMNS),
            'dir' => $this->text($request, 'dir') === 'desc' ? 'desc' : 'asc',
            'search' => mb_substr($this->text($request, 'search'), 0, 100),
            'check' => array_key_exists($check, self::DATA_CHECKS) ? $check : '',
            'per_page' => in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 25,
        ];
    }

    // Our visits, joined to the department's list to tell which schools are on it. A district is
    // chosen as the findings show it: the school's district in the department file (or the tehsil the
    // files name as a district), and the visit's own district for a school not in that file.
    private function visits(array $filters): Builder
    {
        return SchoolMealVisit::query()
            ->leftJoin(self::SCHOOLS, self::SCHOOLS.'.emis_code', '=', self::TABLE.'.emis_code')
            ->select(self::TABLE.'.*')
            ->when($filters['district'] !== '', function ($query) use ($filters) {
                [$district, $bindings] = $this->pageDistrict();
                $query->whereRaw($district.' = ?', [...$bindings, $filters['district']]);
            })
            ->when($filters['from'] !== '', fn ($query) => $query->where('visited_at', '>=', $filters['from'].' 00:00:00'))
            ->when($filters['to'] !== '', fn ($query) => $query->where('visited_at', '<=', $filters['to'].' 23:59:59'));
    }

    // The page's district of a visit, as SQL with its bindings.
    private function pageDistrict(): array
    {
        $cases = '';
        $bindings = [];

        foreach (SchoolMealDistricts::tehsilsAsDistricts() as $tehsil => $district) {
            $cases .= ' WHEN ? THEN ?';
            array_push($bindings, $tehsil, $district);
        }

        $listDistrict = $cases === '' ? self::SCHOOLS.'.district'
            : 'CASE '.self::SCHOOLS.'.tehsil'.$cases.' ELSE '.self::SCHOOLS.'.district END';

        return ['COALESCE('.$listDistrict.', '.self::TABLE.'.district)', $bindings];
    }

    private function dataCheckCounts(array $filters): array
    {
        $totals = collect(self::DATA_CHECKS)
            ->map(fn ($check, $key) => "SUM(({$check['rule']})) AS {$key}")
            ->implode(', ');

        // Only the counts, in place of the visit columns.
        $counts = $this->visits($filters)->toBase()->select(DB::raw($totals))->first();

        return collect(self::DATA_CHECKS)
            ->map(fn ($check, $key) => ['label' => $check['label'], 'count' => (int) $counts->{$key}])
            ->all();
    }

    private function visitRecords(array $filters, array $table): Builder
    {
        $search = addcslashes($table['search'], '\\%_');

        return $this->visits($filters)
            ->when($table['search'] !== '', function ($query) use ($table, $search) {
                $query->where(function ($query) use ($table, $search) {
                    $query->where(self::TABLE.'.emis_code', 'like', "%{$search}%")
                        ->orWhere(self::TABLE.'.district', 'like', "%{$search}%")
                        ->orWhere(self::TABLE.'.school_name', 'like', "%{$search}%");
                });
            })
            ->when(
                $table['check'] !== '',
                fn ($query) => $query->whereRaw('('.self::DATA_CHECKS[$table['check']]['rule'].')')
            )
            ->orderBy(self::TABLE.'.'.$table['sort'], $table['dir'])
            ->orderBy(self::TABLE.'.id');
    }
}
