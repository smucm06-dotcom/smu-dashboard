<?php

namespace App\Http\Controllers\Initiatives\SchoolEducation;

use App\Dashboards\Initiatives\SchoolEducation\AdditionalClassroomsDashboard;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Initiatives\SchoolEducation\Concerns\ManagesPsrpVisits;
use App\Imports\Initiatives\SchoolEducation\AdditionalClassroomVisitImport;
use App\Models\Initiative;
use App\Models\InitiativeFile;
use App\Models\Initiatives\SchoolEducation\AdditionalClassroomSchool;
use App\Models\Initiatives\SchoolEducation\AdditionalClassroomVisit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Construction of Additional Classrooms: the findings design, with the uploads and the records
// table at the end of the page, as on Special Education.
class AdditionalClassroomsController extends Controller
{
    // Upload, filters and CSV download work the same as on the other initiative pages.
    use ManagesPsrpVisits;

    private const TABLE = 'additional_classrooms';

    private const SCHOOLS = 'additional_classrooms_schools';

    // Every column of our visit file, in its order, with the school's name from the department file.
    private const VISIT_COLUMNS = [
        'source_sr' => ['label' => 'SR', 'type' => 'number'],
        'visited_at' => ['label' => 'Visit date', 'type' => 'date', 'csv_format' => 'Y-m-d'],
        'district' => ['label' => 'District', 'type' => 'text'],
        'emis_code' => ['label' => 'EMIS', 'type' => 'code'],
        'school_name' => ['label' => 'School (department file)', 'type' => 'text'],
        'school_level' => ['label' => 'Level', 'type' => 'text'],
        'school_type' => ['label' => 'School type', 'type' => 'text'],
        'students_enrolled' => ['label' => 'Students enrolled', 'type' => 'number'],
        'students_present' => ['label' => 'Students present', 'type' => 'number'],
        'total_teachers' => ['label' => 'Total teachers', 'type' => 'number'],
        'teachers_present' => ['label' => 'Teachers present', 'type' => 'number'],
        'non_teaching_staff' => ['label' => 'Non-teaching staff', 'type' => 'number'],
        'non_teaching_staff_present' => ['label' => 'Non-teaching staff present', 'type' => 'number'],
        'total_classrooms' => ['label' => 'Classrooms', 'type' => 'number'],
        'total_washrooms' => ['label' => 'Washrooms', 'type' => 'number'],
        'approved_classrooms' => ['label' => 'Approved classrooms', 'type' => 'number'],
        'constructed_classrooms' => ['label' => 'Fully constructed', 'type' => 'number'],
        'under_construction_classrooms' => ['label' => 'Under construction', 'type' => 'number'],
        'construction_status' => ['label' => 'Current status', 'type' => 'text'],
        'not_started_reason' => ['label' => 'Reason not started or stopped', 'type' => 'text'],
        'obstruction' => ['label' => 'Any obstruction', 'type' => 'boolean', 'bad' => true],
        'obstruction_type' => ['label' => 'Type of obstruction', 'type' => 'text'],
        'construction_stage' => ['label' => 'Stage of construction', 'type' => 'text'],
        'labour_present' => ['label' => 'Labour present', 'type' => 'boolean', 'bad' => false],
        'inspection_frequency' => ['label' => 'Inspection frequency', 'type' => 'text'],
        'new_classroom_facilities' => ['label' => 'Facilities in new classrooms', 'type' => 'text'],
        'classroom_condition' => ['label' => 'Classroom condition', 'type' => 'text'],
        'washroom_condition' => ['label' => 'Washroom condition', 'type' => 'text'],
        'overall_cleanliness' => ['label' => 'Overall cleanliness', 'type' => 'text'],
        'clean_drinking_water' => ['label' => 'Clean drinking water', 'type' => 'boolean', 'bad' => false],
        'boundary_wall_available' => ['label' => 'Boundary wall', 'type' => 'boolean', 'bad' => false],
        'construction_quality_satisfactory' => ['label' => 'Construction quality satisfactory', 'type' => 'boolean', 'bad' => false],
    ];

    // Everything recorded for a visit, shown when its row is opened.
    private const VISIT_DETAILS = [
        'school_name' => 'School (department file)',
        'emis_code' => 'EMIS code',
        'district' => 'District',
        'visited_at' => 'Visit date',
        'source_sr' => 'SR',
        'school_level' => 'School level',
        'school_type' => 'School type',
        'students_enrolled' => 'Total enrolled students (register)',
        'students_present' => 'Students present on visit day',
        'total_teachers' => 'Total teachers',
        'teachers_present' => 'Teachers present at visit',
        'non_teaching_staff' => 'Non-teaching staff',
        'non_teaching_staff_present' => 'Non-teaching staff present',
        'total_classrooms' => 'Total classrooms',
        'total_washrooms' => 'Total washrooms',
        'approved_classrooms' => 'Approved classrooms (total)',
        'constructed_classrooms' => 'Fully constructed classrooms',
        'under_construction_classrooms' => 'Under construction classrooms',
        'construction_status' => 'Current status of additional classrooms',
        'not_started_reason' => 'Reason if construction has not started',
        'obstruction' => 'Any obstruction if construction ongoing',
        'obstruction_type' => 'Type of obstruction',
        'construction_stage' => 'Stage of construction',
        'labour_present' => 'Labour present',
        'inspection_frequency' => 'Inspection frequency by officials',
        'new_classroom_facilities' => 'Facilities in new classrooms',
        'classroom_condition' => 'Classroom condition',
        'washroom_condition' => 'Washrooms / sanitation condition',
        'overall_cleanliness' => 'Overall cleanliness',
        'clean_drinking_water' => 'Clean drinking water',
        'boundary_wall_available' => 'Boundary wall available',
        'construction_quality_satisfactory' => 'Construction quality satisfactory',
    ];

    // Visits worth double-checking before their figures are relied on.
    private const DATA_CHECKS = [
        'present_above_total' => [
            'label' => 'Present above total',
            'rule' => 'students_present > students_enrolled OR teachers_present > total_teachers'
                .' OR non_teaching_staff_present > non_teaching_staff',
        ],
        'approved_differs' => [
            'label' => 'Approved classrooms differ from the department file',
            'rule' => 'additional_classrooms.approved_classrooms <> additional_classrooms_schools.approved_classrooms',
        ],
        'not_in_list' => [
            'label' => 'Not in the department file',
            'rule' => 'additional_classrooms_schools.id IS NULL',
        ],
    ];

    // Every column of our visit file, under its own headings, in its order and with its formats,
    // for the map's download. Blank counts are written "Null" and blank answers "—", as in the file.
    private const FILE_COLUMNS = [
        'source_sr' => ['label' => 'SR', 'type' => 'number'],
        // Shows like 2.09.2026, as in the file.
        'visited_at' => ['label' => 'Date', 'type' => 'date', 'excel_format' => 'd.mm.yyyy'],
        'district' => ['label' => 'District', 'type' => 'text'],
        'emis_code' => ['label' => 'School EMIS Code', 'type' => 'number'],
        'school_level' => ['label' => 'School Level', 'type' => 'text'],
        'school_type' => ['label' => 'School Type (Boys/Girls/Co-educational)', 'type' => 'text'],
        'students_enrolled' => ['label' => 'Total Enrolled Students (Register)', 'type' => 'number', 'blank' => 'Null'],
        'students_present' => ['label' => 'Students Present on Visit Day', 'type' => 'number', 'blank' => 'Null'],
        'total_teachers' => ['label' => 'Total Teachers', 'type' => 'number', 'blank' => 'Null'],
        'teachers_present' => ['label' => 'Teachers Present at Visit', 'type' => 'number', 'blank' => 'Null'],
        'non_teaching_staff' => ['label' => 'Non-Teaching Staff', 'type' => 'number', 'blank' => 'Null'],
        'non_teaching_staff_present' => ['label' => 'Non-Teaching Staff Present', 'type' => 'number', 'blank' => 'Null'],
        'total_classrooms' => ['label' => 'Total Classrooms', 'type' => 'number'],
        'total_washrooms' => ['label' => 'Total Washrooms', 'type' => 'number'],
        'approved_classrooms' => ['label' => 'Approved Classrooms (Total)', 'type' => 'number'],
        'constructed_classrooms' => ['label' => 'Fully Constructed Classrooms', 'type' => 'number'],
        'under_construction_classrooms' => ['label' => 'Under Construction Classrooms', 'type' => 'number'],
        'construction_status' => ['label' => 'Current Status of Additional Classrooms', 'type' => 'text'],
        'not_started_reason' => ['label' => 'Reason if construction of additional classrooms has not started', 'type' => 'text', 'blank' => '—'],
        'obstruction' => ['label' => 'Any Obstruction if Construction Ongoing', 'type' => 'boolean', 'blank' => '—'],
        'obstruction_type' => ['label' => 'Type of Obstruction', 'type' => 'text', 'blank' => '—'],
        'construction_stage' => ['label' => 'Stage of Construction', 'type' => 'text', 'blank' => '—'],
        'labour_present' => ['label' => 'Labor Present?', 'type' => 'boolean'],
        'inspection_frequency' => ['label' => 'Inspection Frequency by Officials', 'type' => 'text'],
        'new_classroom_facilities' => ['label' => 'Facilities in New Classrooms', 'type' => 'text'],
        'classroom_condition' => ['label' => 'Classroom Condition', 'type' => 'text'],
        'washroom_condition' => ['label' => 'Washrooms / Sanitation Condition', 'type' => 'text'],
        'overall_cleanliness' => ['label' => 'Overall Cleanliness', 'type' => 'text'],
        'clean_drinking_water' => ['label' => 'Clean Drinking Water', 'type' => 'boolean'],
        'boundary_wall_available' => ['label' => 'Boundary Wall Available', 'type' => 'boolean'],
        'construction_quality_satisfactory' => ['label' => 'Construction Quality Satisfactory?', 'type' => 'boolean'],
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
            'totals' => ['visits' => AdditionalClassroomVisit::count()],
            'dataChecks' => $this->dataCheckCounts($filters),
            'records' => $this->visitRecords($filters, $table)
                ->paginate($table['per_page'])
                ->withQueryString()
                ->fragment('psrp-records'),
            'districtRows' => null,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'exportUrl' => route(
                'initiatives.additional-classrooms.export',
                ['initiative' => $initiative] + $request->except(['page', 'per_page'])
            ),
            'hasDistrictTab' => false,
            'searchHint' => 'EMIS, school, district or SR',
            'detailFields' => self::VISIT_DETAILS,
            'detailDateFormat' => 'j M Y',
            'nameColumn' => 'school_name',
        ];

        if ($request->hasHeader(self::RECORDS_HEADER)) {
            return response()
                ->view('initiatives.school_education.psrp_shared.records', $records)
                ->header('Vary', self::RECORDS_HEADER);
        }

        // The controller is called with the request and initiative only, so the dashboard is made here.
        return response()
            ->view('initiatives.school_education.additional_classrooms.show', $records + [
                'topbar' => $this->topbar($initiative, app(AdditionalClassroomsDashboard::class)),
                'files' => $initiative->filesByType(),
            ])
            ->header('Vary', self::RECORDS_HEADER);
    }

    // Everything the page's figures, map and tables are worked out from.
    public function data(Request $request, Initiative $initiative, AdditionalClassroomsDashboard $dashboard)
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
            'additional-classrooms-visits-'.now()->format('Y-m-d').'.csv'
        );
    }

    // The schools on the map for the chosen finding, status or response, as rows of our visit file.
    public function mapDownload(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        return $this->downloadMapSchools($request, AdditionalClassroomVisit::class, self::FILE_COLUMNS, 'additional-classrooms');
    }

    public function import(Request $request, Initiative $initiative, AdditionalClassroomVisitImport $import)
    {
        $this->ensureThisInitiative($initiative);

        return $this->uploadVisits($request, $initiative, $import);
    }

    // The design's five cards, with the line under each. They show all of Punjab here, and the
    // page's script keeps them in step with its filters.
    private function topbar(Initiative $initiative, AdditionalClassroomsDashboard $dashboard): array
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

    // Our visits, each with its school's name from the department file.
    private function visits(array $filters): Builder
    {
        return AdditionalClassroomVisit::query()
            ->leftJoin(self::SCHOOLS, self::SCHOOLS.'.emis_code', '=', self::TABLE.'.emis_code')
            ->select(self::TABLE.'.*', self::SCHOOLS.'.school_name')
            // Zones come from the department's list, which gives each district its zone.
            ->when(($filters['zone'] ?? '') !== '', fn ($query) => $query->whereIn(
                self::TABLE.'.district',
                AdditionalClassroomSchool::query()->select('district')->where('zone', $filters['zone'])
            ))
            ->when($filters['district'] !== '', fn ($query) => $query->where(self::TABLE.'.district', $filters['district']))
            ->when($filters['from'] !== '', fn ($query) => $query->where('visited_at', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($query) => $query->where('visited_at', '<=', $filters['to']));
    }

    private function dataCheckCounts(array $filters): array
    {
        $totals = collect(self::DATA_CHECKS)
            ->map(fn ($check, $key) => "SUM(({$check['rule']})) AS {$key}")
            ->implode(', ');

        // Only the counts, in place of the visit and school name columns.
        $counts = $this->visits($filters)->toBase()->select(DB::raw($totals))->first();

        return collect(self::DATA_CHECKS)
            ->map(fn ($check, $key) => ['label' => $check['label'], 'count' => (int) $counts->{$key}])
            ->all();
    }

    private function visitRecords(array $filters, array $table): Builder
    {
        $search = addcslashes($table['search'], '\\%_');
        $sort = $table['sort'] === 'school_name' ? self::SCHOOLS.'.school_name' : self::TABLE.'.'.$table['sort'];

        return $this->visits($filters)
            ->when($table['search'] !== '', function ($query) use ($table, $search) {
                $query->where(function ($query) use ($table, $search) {
                    $query->where(self::TABLE.'.emis_code', 'like', "%{$search}%")
                        ->orWhere(self::TABLE.'.district', 'like', "%{$search}%")
                        ->orWhere(self::SCHOOLS.'.school_name', 'like', "%{$search}%")
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
            ->orderBy($sort, $table['dir'])
            ->orderBy(self::TABLE.'.id');
    }
}
