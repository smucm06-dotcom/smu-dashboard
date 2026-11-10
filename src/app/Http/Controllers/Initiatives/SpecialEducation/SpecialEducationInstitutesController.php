<?php

namespace App\Http\Controllers\Initiatives\SpecialEducation;

use App\Dashboards\Initiatives\SpecialEducation\SpecialEducationDashboard;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Initiatives\SchoolEducation\Concerns\ManagesPsrpVisits;
use App\Imports\Initiatives\SpecialEducation\SpecialEducationVisitImport;
use App\Models\Initiative;
use App\Models\Initiatives\SpecialEducation\SpecialEducationInstitute;
use App\Models\Initiatives\SpecialEducation\SpecialEducationVisit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

// Special Education Institutes: the findings design, with the uploads and the records table
// at the end of the page.
class SpecialEducationInstitutesController extends Controller
{
    // Upload, filters and CSV download work the same as on the PSRP pages.
    use ManagesPsrpVisits;

    private const TABLE = 'special_education_institutes';

    // Every column of our visit file, in its order. The "%" columns are worked out from the counts.
    private const VISIT_COLUMNS = [
        'source_sr' => ['label' => 'Sr', 'type' => 'number'],
        'visited_at' => ['label' => 'Visit date', 'type' => 'date', 'csv_format' => 'Y-m-d'],
        'district' => ['label' => 'District', 'type' => 'text'],
        'emis_code' => ['label' => 'EMIS', 'type' => 'code'],
        'institution_name' => ['label' => 'Institution', 'type' => 'text'],
        'principal_present' => ['label' => 'Principal present', 'type' => 'boolean', 'bad' => false],
        'total_teachers' => ['label' => 'Total teachers', 'type' => 'number'],
        'present_teachers' => ['label' => 'Present teachers', 'type' => 'number'],
        'teachers_present_percent' => ['label' => '% Teachers present', 'type' => 'percent'],
        'total_psychologists' => ['label' => 'Total psychologists', 'type' => 'number'],
        'present_psychologists' => ['label' => 'Present psychologists', 'type' => 'number'],
        'psychologists_present_percent' => ['label' => '% Psychologists present', 'type' => 'percent'],
        'total_admin_staff' => ['label' => 'Total admin staff', 'type' => 'number'],
        'present_admin_staff' => ['label' => 'Present admin staff', 'type' => 'number'],
        'admin_staff_present_percent' => ['label' => '% Admin staff present', 'type' => 'percent'],
        'bus_driver_post_vacant' => ['label' => 'Bus driver post vacant', 'type' => 'boolean', 'bad' => true],
        'students_enrolled' => ['label' => 'Students enrolled', 'type' => 'number'],
        'students_present' => ['label' => 'Students present', 'type' => 'number'],
        'students_present_percent' => ['label' => '% Students present', 'type' => 'percent'],
        'fee_charged' => ['label' => 'Fee charged', 'type' => 'boolean', 'bad' => true],
        'monthly_stipend' => ['label' => 'Monthly stipend', 'type' => 'boolean', 'bad' => false],
        'total_classrooms' => ['label' => 'Classrooms', 'type' => 'number'],
        'total_washrooms' => ['label' => 'Washrooms', 'type' => 'number'],
        'separate_classroom_per_grade' => ['label' => 'Classroom for each grade', 'type' => 'boolean', 'bad' => false],
        'washroom_cleanliness' => ['label' => 'Washroom cleanliness', 'type' => 'text'],
        'classroom_cleanliness' => ['label' => 'Classroom cleanliness', 'type' => 'text'],
        'free_books' => ['label' => 'Free books', 'type' => 'boolean', 'bad' => false],
        'free_uniform' => ['label' => 'Free uniform', 'type' => 'boolean', 'bad' => false],
        'free_transport' => ['label' => 'Free transport', 'type' => 'text'],
        'furniture_available' => ['label' => 'Furniture', 'type' => 'boolean', 'bad' => false],
        'first_aid_available' => ['label' => 'First aid', 'type' => 'boolean', 'bad' => false],
        'clean_drinking_water_available' => ['label' => 'Clean drinking water', 'type' => 'boolean', 'bad' => false],
        'boundary_wall_available' => ['label' => 'Boundary wall', 'type' => 'boolean', 'bad' => false],
        'latitude' => ['label' => 'Latitude', 'type' => 'text'],
        'longitude' => ['label' => 'Longitude', 'type' => 'text'],
    ];

    // Everything recorded for a visit, shown when its row is opened.
    private const VISIT_DETAILS = [
        'institution_name' => 'Institution',
        'emis_code' => 'EMIS code',
        'district' => 'District',
        'visited_at' => 'Visit date',
        'source_sr' => 'Sr',
        'principal_present' => 'Principal present',
        'total_teachers' => 'Total teachers',
        'present_teachers' => 'Present teachers',
        'total_psychologists' => 'Total psychologists',
        'present_psychologists' => 'Present psychologists',
        'total_admin_staff' => 'Total administration staff',
        'present_admin_staff' => 'Present administration staff',
        'bus_driver_post_vacant' => 'School bus driver post vacant',
        'students_enrolled' => 'Students enrolled',
        'students_present' => 'Students present',
        'fee_charged' => 'Fee taken from students',
        'monthly_stipend' => 'Monthly stipend',
        'total_classrooms' => 'Total classrooms',
        'total_washrooms' => 'Total washrooms',
        'separate_classroom_per_grade' => 'Separate classroom for each grade',
        'washroom_cleanliness' => 'Washroom cleanliness',
        'classroom_cleanliness' => 'Classroom cleanliness',
        'free_books' => 'Free books',
        'free_uniform' => 'Free uniform',
        'free_transport' => 'Free school transport',
        'furniture_available' => 'Furniture',
        'first_aid_available' => 'First aid',
        'clean_drinking_water_available' => 'Clean drinking water',
        'boundary_wall_available' => 'Boundary wall',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
    ];

    // Visits worth double-checking before their figures are relied on.
    private const DATA_CHECKS = [
        'present_above_total' => [
            'label' => 'Present above total',
            'rule' => 'present_teachers > total_teachers OR present_psychologists > total_psychologists'
                .' OR present_admin_staff > total_admin_staff OR students_present > students_enrolled',
        ],
        'missing_location' => [
            'label' => 'Location not recorded',
            'rule' => 'latitude IS NULL OR longitude IS NULL',
        ],
        'repeat_visits' => [
            'label' => 'Institutes visited more than once',
            // The extra SELECT makes MySQL build this list once instead of once per visit.
            'rule' => 'emis_code IN (
                SELECT emis_code FROM (
                    SELECT emis_code FROM special_education_institutes
                    GROUP BY emis_code HAVING COUNT(*) > 1
                ) AS repeated
            )',
        ],
    ];

    // Every column of our visit file, under its own headings, in its order and with its formats,
    // for the map's download.
    private const FILE_COLUMNS = [
        'source_sr' => ['label' => 'Sr', 'type' => 'number'],
        // Shows like 04-17-25, as in the file.
        'visited_at' => ['label' => 'Date', 'type' => 'date', 'excel_format' => 'mm-dd-yy'],
        'district' => ['label' => 'District', 'type' => 'text'],
        'emis_code' => ['label' => 'EMIS Code', 'type' => 'number'],
        'institution_name' => ['label' => 'Name of Institution', 'type' => 'text'],
        'principal_present' => ['label' => 'Principal Present', 'type' => 'boolean'],
        'total_teachers' => ['label' => 'Total Teachers', 'type' => 'number'],
        'present_teachers' => ['label' => 'Present Teachers', 'type' => 'number'],
        'teachers_present_percent' => ['label' => '% Teachers Present', 'type' => 'percent'],
        'total_psychologists' => ['label' => 'Total Psychologist', 'type' => 'number'],
        'present_psychologists' => ['label' => 'Present Psychologist', 'type' => 'number'],
        'psychologists_present_percent' => ['label' => '% Present Psychologist', 'type' => 'percent'],
        'total_admin_staff' => ['label' => 'Total Administration Staff', 'type' => 'number'],
        'present_admin_staff' => ['label' => 'Present Administration Staff', 'type' => 'number'],
        'admin_staff_present_percent' => ['label' => '% Present Administration Staff', 'type' => 'percent'],
        'bus_driver_post_vacant' => ['label' => 'Vacant Position School Buss Driver', 'type' => 'boolean'],
        'students_enrolled' => ['label' => 'Total Enrolled Students', 'type' => 'number'],
        'students_present' => ['label' => 'Present Students', 'type' => 'number'],
        'students_present_percent' => ['label' => '% Present Students', 'type' => 'percent'],
        'fee_charged' => ['label' => 'Fee from Students', 'type' => 'boolean'],
        'monthly_stipend' => ['label' => 'Monthly Stipend', 'type' => 'boolean'],
        'total_classrooms' => ['label' => 'Total Classrooms', 'type' => 'number'],
        'total_washrooms' => ['label' => 'Total Washrooms', 'type' => 'number'],
        'separate_classroom_per_grade' => ['label' => 'Separate Classrooms for each grade', 'type' => 'boolean'],
        'washroom_cleanliness' => ['label' => 'Cleanliness of Washrooms', 'type' => 'text'],
        'classroom_cleanliness' => ['label' => 'Cleanliness of Classrooms', 'type' => 'text'],
        'free_books' => ['label' => 'Free Books', 'type' => 'boolean'],
        'free_uniform' => ['label' => 'Free Uniform', 'type' => 'boolean'],
        // Yes, No or "Transport service not available", as written in the file.
        'free_transport' => ['label' => 'Free School Transport', 'type' => 'text'],
        'furniture_available' => ['label' => 'Furniture', 'type' => 'boolean'],
        'first_aid_available' => ['label' => 'First Aid Facility', 'type' => 'boolean'],
        'clean_drinking_water_available' => ['label' => 'Clean Drinking Water', 'type' => 'boolean'],
        'boundary_wall_available' => ['label' => 'Boundary Wall', 'type' => 'boolean'],
        'latitude' => ['label' => 'Lat', 'type' => 'number'],
        'longitude' => ['label' => 'Long', 'type' => 'number'],
    ];

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    // Sent by the page when only the records table needs refreshing.
    private const RECORDS_HEADER = 'X-Psrp-Records';

    public function show(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        $filters = $this->pageFilters($request);
        $table = $this->tableOptions($request);

        $records = [
            'initiative' => $initiative,
            'filters' => $filters,
            'table' => $table,
            'totals' => ['visits' => SpecialEducationVisit::count()],
            'dataChecks' => $this->dataCheckCounts($filters),
            'records' => $this->visitRecords($filters, $table)
                ->paginate($table['per_page'])
                ->withQueryString()
                ->fragment('psrp-records'),
            'districtRows' => null,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'exportUrl' => route(
                'initiatives.special-education-institutes.export',
                ['initiative' => $initiative] + $request->except(['page', 'per_page'])
            ),
            'hasDistrictTab' => false,
            'searchHint' => 'EMIS, institution, district or Sr',
            'detailFields' => self::VISIT_DETAILS,
            'detailDateFormat' => 'j M Y',
            'nameColumn' => 'institution_name',
        ];

        if ($request->hasHeader(self::RECORDS_HEADER)) {
            return response()
                ->view('initiatives.school_education.psrp_shared.records', $records)
                ->header('Vary', self::RECORDS_HEADER);
        }

        return response()
            ->view('initiatives.special_education.special_education_institutes.show', $records + [
                'topbar' => $this->topbar($initiative, app(SpecialEducationDashboard::class)),
                'files' => $initiative->filesByType(),
            ])
            ->header('Vary', self::RECORDS_HEADER);
    }

    // Everything the page's figures, map and tables are worked out from.
    public function data(Request $request, Initiative $initiative, SpecialEducationDashboard $dashboard)
    {
        $this->ensureThisInitiative($initiative);

        return $this->dashboardData($request, $dashboard);
    }

    public function export(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        $table = $this->tableOptions($request);

        return $this->downloadCsv(
            $this->visitRecords($this->pageFilters($request), $table)->cursor(),
            $table['columns'],
            'special-education-institutes-visits-'.now()->format('Y-m-d').'.csv'
        );
    }

    // The schools on the map for the chosen finding, issue or response, as rows of our visit file.
    public function mapDownload(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        return $this->downloadMapSchools($request, SpecialEducationVisit::class, self::FILE_COLUMNS, 'special-education-institutes');
    }

    public function import(Request $request, Initiative $initiative, SpecialEducationVisitImport $import)
    {
        $this->ensureThisInitiative($initiative);

        return $this->uploadVisits($request, $initiative, $import);
    }

    // The design's five cards, with the line under each. They show all of Punjab here, and the
    // page's script keeps them in step with its filters.
    private function topbar(Initiative $initiative, SpecialEducationDashboard $dashboard): array
    {
        $cards = $dashboard->headline($dashboard->data());
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

    // The district and date filters every page has, and the zone, which the design adds.
    private function pageFilters(Request $request): array
    {
        return $this->filters($request) + ['zone' => mb_substr($this->text($request, 'zone'), 0, 50)];
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

    private function visits(array $filters): Builder
    {
        return SpecialEducationVisit::query()
            // Zones come from the department's list, which gives each district its zone.
            ->when(($filters['zone'] ?? '') !== '', fn ($query) => $query->whereIn(
                'district',
                SpecialEducationInstitute::query()->select('district')->where('zone', $filters['zone'])
            ))
            ->when($filters['district'] !== '', fn ($query) => $query->where('district', $filters['district']))
            ->when($filters['from'] !== '', fn ($query) => $query->where('visited_at', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($query) => $query->where('visited_at', '<=', $filters['to']));
    }

    private function dataCheckCounts(array $filters): array
    {
        $totals = collect(self::DATA_CHECKS)
            ->map(fn ($check, $key) => "SUM(({$check['rule']})) AS {$key}")
            ->implode(', ');

        $counts = $this->visits($filters)->toBase()->selectRaw($totals)->first();

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
                    $query->where('emis_code', 'like', "%{$search}%")
                        ->orWhere('district', 'like', "%{$search}%")
                        ->orWhere('institution_name', 'like', "%{$search}%")
                        ->when(
                            ctype_digit($table['search']),
                            fn ($query) => $query->orWhere('source_sr', (int) $table['search'])
                        );
                });
            })
            ->when(
                $table['check'] !== '',
                fn ($query) => $query->whereRaw('('.self::DATA_CHECKS[$table['check']]['rule'].')')
            )
            ->orderBy($table['sort'], $table['dir'])
            ->orderBy('id');
    }
}
