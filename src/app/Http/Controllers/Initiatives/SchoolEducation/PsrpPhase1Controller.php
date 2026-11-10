<?php

namespace App\Http\Controllers\Initiatives\SchoolEducation;

use App\Dashboards\Initiatives\SchoolEducation\PsrpPhase1Dashboard;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Initiatives\SchoolEducation\Concerns\ManagesPsrpVisits;
use App\Imports\Initiatives\SchoolEducation\PsrpPhase1Districts;
use App\Imports\Initiatives\SchoolEducation\PsrpPhase1Import;
use App\Models\Initiative;
use App\Models\Initiatives\SchoolEducation\PsrpPhase1;
use App\Models\Initiatives\SchoolEducation\PsrpPhase1Response;
use App\Models\Initiatives\SchoolEducation\PsrpPhase1School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PsrpPhase1Controller extends Controller
{
    use ManagesPsrpVisits;

    // Each school's latest visit is checked against these rules.
    private const ISSUES = [
        'no_clean_water' => ['label' => 'No clean water', 'rule' => 'clean_drinking_water_available = 0'],
        'enrollment_fell' => ['label' => 'Enrolment fell', 'rule' => 'students_enrolled < students_at_handover'],
        'salary_not_paid' => ['label' => 'Salary not paid', 'rule' => 'staff_salary_status = 0'],
        'dangerous_classrooms' => ['label' => 'Dangerous classrooms', 'rule' => 'dangerous_classrooms > 0'],
        'no_students' => ['label' => 'No students', 'rule' => 'students_enrolled = 0'],
        'not_handed_over' => ['label' => 'Not handed over', 'rule' => 'handover_status = 0'],
        'charging_fees' => ['label' => 'Charging fees', 'rule' => 'students_charged_fees = 1'],
        'no_teacher_present' => ['label' => 'No teacher present', 'rule' => 'teachers_present = 0'],
    ];

    // Visits worth double-checking before their figures are relied on.
    private const DATA_CHECKS = [
        'present_above_enrolled' => [
            'label' => 'Students present above enrolled',
            'rule' => 'students_present > students_enrolled',
        ],
        'teachers_above_total' => [
            'label' => 'Teachers present above total',
            'rule' => 'teachers_present > total_teachers',
        ],
        'dangerous_above_total' => [
            'label' => 'Dangerous classrooms above total',
            'rule' => 'dangerous_classrooms > total_classrooms',
        ],
        'missing_enrollment' => [
            'label' => 'Enrolment not recorded',
            'rule' => 'students_enrolled IS NULL OR students_at_handover IS NULL',
        ],
        'missing_location' => [
            'label' => 'Location not recorded',
            'rule' => 'latitude IS NULL OR longitude IS NULL',
        ],
        'repeat_visits' => [
            'label' => 'Schools visited more than once',
            // The extra SELECT makes MySQL build this list once instead of once per visit.
            'rule' => 'emis_code IN (
                SELECT emis_code FROM (
                    SELECT emis_code FROM public_school_reorganization_phase_1
                    GROUP BY emis_code HAVING COUNT(*) > 1
                ) AS repeated
            )',
        ],
    ];

    private const VISIT_COLUMNS = [
        'source_sr' => ['label' => 'SR', 'type' => 'number'],
        'visited_at' => ['label' => 'Visit date', 'type' => 'date'],
        'district' => ['label' => 'District', 'type' => 'text'],
        'emis_code' => ['label' => 'EMIS', 'type' => 'code'],
        'handover_status' => ['label' => 'Handed over', 'type' => 'boolean', 'bad' => false],
        'students_enrolled' => ['label' => 'Enrolled now', 'type' => 'number'],
        'students_at_handover' => ['label' => 'At handover', 'type' => 'number'],
        'enrollment_change' => ['label' => 'Change', 'type' => 'change'],
        'students_present' => ['label' => 'Present', 'type' => 'number'],
        'total_teachers' => ['label' => 'Teachers', 'type' => 'number'],
        'teachers_present' => ['label' => 'Teachers present', 'type' => 'number'],
        'staff_salary_status' => ['label' => 'Salary paid', 'type' => 'boolean', 'bad' => false],
        'students_charged_fees' => ['label' => 'Charging fees', 'type' => 'boolean', 'bad' => true],
        'pts_council_established' => ['label' => 'PTS council', 'type' => 'boolean'],
        'clean_drinking_water_available' => ['label' => 'Clean water', 'type' => 'boolean', 'bad' => false],
        'dangerous_classrooms' => ['label' => 'Dangerous classrooms', 'type' => 'number'],
    ];

    // Everything recorded for a visit, shown when its row is opened.
    private const VISIT_DETAILS = [
        'school_name' => 'School name',
        'emis_code' => 'EMIS code',
        'district' => 'District',
        'visited_at' => 'Visit date and time',
        'source_sr' => 'SR',
        'handover_status' => 'School handed over',
        'staff_salary_status' => 'Staff salary paid',
        'pts_council_established' => 'PTS council formed',
        'council_meetings_count' => 'Council meetings',
        'students_charged_fees' => 'Students charged fees',
        'students_enrolled' => 'Students enrolled now',
        'students_at_handover' => 'Students at handover',
        'students_present' => 'Students present on visit day',
        'total_teachers' => 'Total teachers',
        'teachers_present' => 'Teachers present at visit',
        'total_classrooms' => 'Total classrooms',
        'dangerous_classrooms' => 'Dangerous classrooms',
        'total_washrooms' => 'Total washrooms',
        'boundary_wall_available' => 'Boundary wall',
        'classroom_condition' => 'Classroom condition',
        'washroom_cleanliness' => 'Washroom cleanliness',
        'school_cleanliness' => 'School cleanliness',
        'clean_drinking_water_available' => 'Clean drinking water',
        'free_textbooks_available' => 'Free textbooks',
        'one_grade_per_classroom' => 'One grade per classroom',
        'furniture_available' => 'Furniture available',
        'first_aid_available' => 'First aid available',
        'teachers_enrolled_pef_training' => 'Teachers enrolled in PEF training',
        'teachers_nominated_pef_cpdp' => 'Teachers nominated for PEF CPDP',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
    ];

    // Every column of the visit file, under its own headings and in its own order.
    private const FILE_COLUMNS = [
        'source_sr' => ['label' => 'SR', 'type' => 'number'],
        // Shows like 2/4/2025, as in the file. The time stays in the cell.
        'visited_at' => ['label' => 'Timestap', 'type' => 'date', 'excel_format' => 'm/d/yyyy'],
        'district' => ['label' => 'District', 'type' => 'text'],
        'emis_code' => ['label' => 'EMIS', 'type' => 'number'],
        'staff_salary_status' => ['label' => 'Staff Salary Status', 'type' => 'boolean'],
        'pts_council_established' => ['label' => 'PTS Council Established', 'type' => 'boolean'],
        'council_meetings_count' => ['label' => 'Council Meetings Count', 'type' => 'number'],
        'students_charged_fees' => ['label' => 'Students being charged fees', 'type' => 'boolean'],
        'students_enrolled' => ['label' => 'Students Enrolled', 'type' => 'number'],
        'students_at_handover' => ['label' => 'Students Handover', 'type' => 'number'],
        'students_present' => ['label' => 'Students present on visit day', 'type' => 'number'],
        'total_teachers' => ['label' => 'Total teachers', 'type' => 'number'],
        'total_classrooms' => ['label' => 'Total classrooms', 'type' => 'number'],
        'total_washrooms' => ['label' => 'Total washrooms', 'type' => 'number'],
        'dangerous_classrooms' => ['label' => 'Dangerous_Classrooms', 'type' => 'number'],
        'handover_status' => ['label' => 'School Handover Status', 'type' => 'boolean'],
        'teachers_present' => ['label' => 'teachers present at visit time', 'type' => 'number'],
        'boundary_wall_available' => ['label' => 'boundary wall around school', 'type' => 'boolean'],
        'classroom_condition' => ['label' => 'Classrooms condition', 'type' => 'text'],
        'washroom_cleanliness' => ['label' => 'Washroom Cleanliness', 'type' => 'text'],
        'school_cleanliness' => ['label' => 'School Cleanliness', 'type' => 'text'],
        'clean_drinking_water_available' => ['label' => 'Clean drinking water', 'type' => 'boolean'],
        'free_textbooks_available' => ['label' => 'Free textbooks from School', 'type' => 'boolean'],
        'one_grade_per_classroom' => ['label' => 'Classroom accommodate only one grade level', 'type' => 'boolean'],
        'furniture_available' => ['label' => 'Furniture available for students', 'type' => 'boolean'],
        'first_aid_available' => ['label' => 'First aid facility available', 'type' => 'boolean'],
        // The visit file writes Nil here, and the upload saves that as blank.
        'teachers_enrolled_pef_training' => ['label' => 'No of teachers has enrolled in PEF’s training courses', 'type' => 'number', 'blank' => 'Nil'],
        'teachers_nominated_pef_cpdp' => ['label' => 'No of teachers has nominated for the PEF CPDP program', 'type' => 'number', 'blank' => 'Nil'],
        'school_name' => ['label' => 'School Name', 'type' => 'text'],
        'latitude' => ['label' => 'Lat', 'type' => 'number'],
        'longitude' => ['label' => 'Long', 'type' => 'number'],
    ];

    private const DISTRICT_COLUMNS = [
        'district' => ['label' => 'District', 'type' => 'text'],
        'visits' => ['label' => 'Visits', 'type' => 'number'],
        'schools' => ['label' => 'Schools', 'type' => 'number'],
        'enrolled' => ['label' => 'Enrolled now', 'type' => 'number'],
        'at_handover' => ['label' => 'At handover', 'type' => 'number'],
        'enrollment_change_percentage' => ['label' => 'Change', 'type' => 'change_percent'],
        'student_attendance_percentage' => ['label' => 'Students present', 'type' => 'percent'],
        'teachers' => ['label' => 'Teachers', 'type' => 'number'],
        'teacher_attendance_percentage' => ['label' => 'Teachers present', 'type' => 'percent'],
        'salary_paid_percentage' => ['label' => 'Salary paid', 'type' => 'percent'],
        'charging_fees' => ['label' => 'Charging fees', 'type' => 'number'],
        'dangerous_schools' => ['label' => 'Dangerous classrooms', 'type' => 'number'],
        'any_issue' => ['label' => 'Schools with issues', 'type' => 'number'],
    ];

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    // Sent by the page when only the records table needs refreshing.
    private const RECORDS_HEADER = 'X-Psrp-Records';

    // Visited schools not on the department's list, found once per request.
    private array $offListCodes = [];

    public function show(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        $filters = $this->filters($request);
        $table = $this->tableOptions($request);

        $records = [
            'initiative' => $initiative,
            'filters' => $filters,
            'table' => $table,
            'totals' => ['visits' => PsrpPhase1::count()],
            'dataChecks' => $this->dataCheckCounts($filters),
            'records' => $table['tab'] === 'visits'
                ? $this->visitRecords($filters, $table)
                    ->paginate($table['per_page'])
                    ->withQueryString()
                    ->fragment('psrp-records')
                : null,
            'districtRows' => $table['tab'] === 'districts'
                ? $this->districtRows($filters, $table)
                : null,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'exportUrl' => route(
                'initiatives.psrp-phase-1.export',
                ['initiative' => $initiative] + $request->except(['page', 'per_page'])
            ),
            'hasDistrictTab' => true,
            'searchHint' => 'EMIS code, district or SR',
            'detailFields' => self::VISIT_DETAILS,
            'detailDateFormat' => 'j M Y, g:i A',
        ];

        if ($request->hasHeader(self::RECORDS_HEADER)) {
            return response()
                ->view('initiatives.school_education.psrp_shared.records', $records)
                ->header('Vary', self::RECORDS_HEADER);
        }

        $files = $initiative->filesByType();

        return response()
            ->view('initiatives.school_education.psrp_phase_1.show', $records + [
                'topbar' => $this->topbar($initiative),
                'files' => $files,
            ])
            ->header('Vary', self::RECORDS_HEADER);
    }

    public function data(Request $request, Initiative $initiative, PsrpPhase1Dashboard $dashboard)
    {
        $this->ensureThisInitiative($initiative);

        return $this->dashboardData($request, $dashboard);
    }

    public function export(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        $filters = $this->filters($request);
        $table = $this->tableOptions($request);

        $rows = $table['tab'] === 'districts'
            ? $this->districtRows($filters, $table)
            : $this->visitRecords($filters, $table)->cursor();

        return $this->downloadCsv($rows, $table['columns'], 'psrp-phase-1-'.$table['tab'].'-'.now()->format('Y-m-d').'.csv');
    }

    public function mapDownload(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        return $this->downloadMapSchools($request, PsrpPhase1::class, self::FILE_COLUMNS, 'psrp-phase-1');
    }

    public function import(Request $request, Initiative $initiative, PsrpPhase1Import $import)
    {
        $this->ensureThisInitiative($initiative);

        return $this->uploadVisits($request, $initiative, $import);
    }

    // Totals for the whole initiative, so the banner stays the same when filters change.
    // The four cards for everything on file. The page's script keeps them in step with the
    // district and date filters, counting the same way as the figures below them.
    private function topbar(Initiative $initiative): array
    {
        // Replies count for schools the page knows: on the department's list or visited.
        $known = PsrpPhase1School::query()->toBase()->select('emis_code')
            ->union(PsrpPhase1::query()->toBase()->select('emis_code'));
        $reported = PsrpPhase1Response::query()->toBase()->whereIn('emis_code', $known);

        return [
            'title' => $initiative->name,
            'description' => 'Monitor field inspection data and implementation progress for this initiative.',
            'stats' => [
                ['label' => 'Total Schools', 'value' => PsrpPhase1School::count(), 'id' => 'c-total'],
                // Visits, as before: a school visited twice counts twice.
                ['label' => 'Visited Schools', 'value' => PsrpPhase1::count(), 'id' => 'c-visited'],
                ['label' => 'Reports Sent to Department', 'value' => (clone $reported)->distinct()->count('emis_code'), 'id' => 'c-reports'],
                ['label' => 'Department Responses', 'value' => (clone $reported)->whereRaw("TRIM(response) <> ''")->distinct()->count('emis_code'), 'id' => 'c-replied'],
            ],
        ];
    }

    private function ensureThisInitiative(Initiative $initiative): void
    {
        abort_unless(
            $initiative->is_active
            && $initiative->table_name === 'public_school_reorganization_phase_1',
            404
        );
    }

    private function tableOptions(Request $request): array
    {
        $tab = $this->text($request, 'tab') === 'districts' ? 'districts' : 'visits';
        $columns = $tab === 'districts' ? self::DISTRICT_COLUMNS : self::VISIT_COLUMNS;

        $sort = $this->text($request, 'sort');
        $check = $this->text($request, 'check');
        $perPage = (int) $this->text($request, 'per_page');

        return [
            'tab' => $tab,
            'columns' => $columns,
            'sort' => array_key_exists($sort, $columns) ? $sort : array_key_first($columns),
            'dir' => $this->text($request, 'dir') === 'desc' ? 'desc' : 'asc',
            'search' => mb_substr($this->text($request, 'search'), 0, 100),
            'check' => array_key_exists($check, self::DATA_CHECKS) ? $check : '',
            'per_page' => in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 25,
        ];
    }

    // Every visit matching the district and date filters.
    private function visits(array $filters): Builder
    {
        return PsrpPhase1::query()
            ->when($filters['district'] !== '', fn ($query) => $query->where(function ($query) use ($filters) {
                $query->whereIn('emis_code', $this->listedSchools($filters['district']))
                    ->orWhereIn('emis_code', $this->offListSchools($filters['district']));
            }))
            ->when($filters['from'] !== '', fn ($query) => $query->where('visited_at', '>=', $filters['from'].' 00:00:00'))
            ->when($filters['to'] !== '', fn ($query) => $query->where('visited_at', '<=', $filters['to'].' 23:59:59'));
    }

    // Districts follow the department's list, as they do in the findings above the table.
    private function listedSchools(string $district): Builder
    {
        return PsrpPhase1School::query()
            ->select('emis_code')
            ->where('district', $this->districtName($district));
    }

    // Schools not on the list use the district from their latest visit, in the list's spelling.
    private function offListSchools(string $district): array
    {
        return $this->offListCodes[$district] ??= PsrpPhase1::query()
            ->toBase()
            ->select('emis_code', 'district')
            ->whereNotIn('emis_code', PsrpPhase1School::query()->select('emis_code'))
            ->orderBy('visited_at')
            ->orderBy('id')
            ->get()
            ->keyBy('emis_code')
            ->filter(fn ($visit) => PsrpPhase1Districts::normalize($visit->district) === $this->districtName($district))
            ->pluck('emis_code')
            ->all();
    }

    private function districtName(string $district): ?string
    {
        return $district === PsrpPhase1Dashboard::NO_DISTRICT ? null : $district;
    }

    // Each school's latest visit in the selection.
    // If timestamps match, use the record with the higher ID.
    private function schools(array $filters): Builder
    {
        $rankedVisits = $this->visits($filters)
            ->select('*')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY emis_code ORDER BY visited_at DESC, id DESC) AS visit_rank');

        return PsrpPhase1::query()
            ->fromSub($rankedVisits, 'records')
            ->where('visit_rank', 1);
    }

    private function anyIssueRule(): string
    {
        return '('.implode(' OR ', array_column(self::ISSUES, 'rule')).')';
    }

    private function figuresSql(): string
    {
        $issueTotals = collect(self::ISSUES)
            ->map(fn ($issue, $key) => "SUM({$issue['rule']}) AS {$key}")
            ->implode(', ');

        // Attendance and enrolment change only use schools with both values recorded.
        return "
            COUNT(*) AS schools,
            COUNT(DISTINCT NULLIF(TRIM(district), '')) AS districts,
            SUM(students_enrolled) AS enrolled,
            SUM(total_teachers) AS teachers,
            SUM(CASE WHEN students_at_handover IS NOT NULL THEN students_enrolled END) AS enrolled_compared,
            SUM(CASE WHEN students_enrolled IS NOT NULL THEN students_at_handover END) AS at_handover,
            SUM(CASE WHEN students_enrolled IS NOT NULL THEN students_present END) AS students_present,
            SUM(CASE WHEN students_present IS NOT NULL THEN students_enrolled END) AS enrolled_counted,
            SUM(CASE WHEN total_teachers IS NOT NULL THEN teachers_present END) AS teachers_present,
            SUM(CASE WHEN teachers_present IS NOT NULL THEN total_teachers END) AS teachers_counted,
            COUNT(staff_salary_status) AS salary_answered,
            SUM(staff_salary_status = 1) AS salary_paid,
            COUNT(students_charged_fees) AS fees_answered,
            COUNT(pts_council_established) AS council_answered,
            SUM(pts_council_established = 1) AS council_formed,
            SUM(dangerous_classrooms) AS dangerous_classroom_total,
            SUM({$this->anyIssueRule()}) AS any_issue,
            {$issueTotals}
        ";
    }

    private function summarize(object $figures): array
    {
        $schools = (int) $figures->schools;

        return [
            'schools' => $schools,
            'districts' => (int) $figures->districts,

            // Keep missing totals as null so the view can show "—".
            'enrolled' => $figures->enrolled,
            'at_handover' => $figures->at_handover,
            'enrollment_change_percentage' => $this->percentage(
                $figures->enrolled_compared - $figures->at_handover,
                $figures->at_handover
            ),

            'students_present' => $figures->students_present,
            'enrolled_counted' => $figures->enrolled_counted,
            'student_attendance_percentage' => $this->percentage(
                $figures->students_present,
                $figures->enrolled_counted
            ),

            'teachers' => $figures->teachers,
            'teachers_present' => $figures->teachers_present,
            'teachers_counted' => $figures->teachers_counted,
            'teacher_attendance_percentage' => $this->percentage(
                $figures->teachers_present,
                $figures->teachers_counted
            ),

            'salary_answered' => (int) $figures->salary_answered,
            'salary_not_paid' => (int) $figures->salary_not_paid,
            'salary_paid_percentage' => $this->percentage(
                $figures->salary_paid,
                $figures->salary_answered
            ),

            'fees_answered' => (int) $figures->fees_answered,
            'charging_fees' => (int) $figures->charging_fees,
            'charging_fees_percentage' => $this->percentage(
                $figures->charging_fees,
                $figures->fees_answered
            ),

            'council_answered' => (int) $figures->council_answered,
            'council_formed' => (int) $figures->council_formed,
            'council_not_recorded' => $schools - (int) $figures->council_answered,
            'council_established_percentage' => $this->percentage(
                $figures->council_formed,
                $figures->council_answered
            ),

            'dangerous_schools' => (int) $figures->dangerous_classrooms,
            'dangerous_schools_percentage' => $this->percentage(
                $figures->dangerous_classrooms,
                $schools
            ),
            'dangerous_classrooms' => $figures->dangerous_classroom_total,

            'any_issue' => (int) $figures->any_issue,
        ];
    }

    private function percentage($numerator, $denominator): ?float
    {
        return $denominator > 0
            ? round(($numerator / $denominator) * 100, 1)
            : null;
    }

    private function dataCheckCounts(array $filters): array
    {
        $totals = collect(self::DATA_CHECKS)
            ->map(fn ($check, $key) => "SUM(({$check['rule']})) AS {$key}")
            ->implode(', ');

        $counts = $this->visits($filters)->toBase()->selectRaw($totals)->first();

        return collect(self::DATA_CHECKS)
            ->map(fn ($check, $key) => [
                'label' => $check['label'],
                'count' => (int) $counts->{$key},
            ])
            ->all();
    }

    private function visitRecords(array $filters, array $table): Builder
    {
        $search = addcslashes($table['search'], '\\%_');

        return $this->visits($filters)
            ->select('*')
            // Enrolment columns are unsigned, so cast them before subtracting.
            ->selectRaw('CAST(students_enrolled AS SIGNED) - CAST(students_at_handover AS SIGNED) AS enrollment_change')
            ->when($table['search'] !== '', function ($query) use ($table, $search) {
                $query->where(function ($query) use ($table, $search) {
                    $query->where('emis_code', 'like', "%{$search}%")
                        ->orWhere('district', 'like', "%{$search}%")
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

    private function districtRows(array $filters, array $table): Collection
    {
        $name = "COALESCE(NULLIF(TRIM(district), ''), 'Not recorded')";

        $visits = $this->visits($filters)
            ->toBase()
            ->selectRaw("{$name} AS district_name, COUNT(*) AS visits")
            ->groupBy('district_name')
            ->pluck('visits', 'district_name');

        return $this->schools($filters)
            ->toBase()
            ->selectRaw("{$name} AS district_name, {$this->figuresSql()}")
            ->groupBy('district_name')
            ->get()
            ->map(fn ($figures) => $this->summarize($figures) + [
                'district' => $figures->district_name,
                'visits' => (int) ($visits[$figures->district_name] ?? 0),
            ])
            ->when($table['search'] !== '', fn ($rows) => $rows->filter(
                fn ($row) => str_contains(mb_strtolower($row['district']), mb_strtolower($table['search']))
            ))
            ->sortBy($table['sort'], SORT_REGULAR, $table['dir'] === 'desc')
            ->values();
    }
}
