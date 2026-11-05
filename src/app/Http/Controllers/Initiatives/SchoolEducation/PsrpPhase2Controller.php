<?php

namespace App\Http\Controllers\Initiatives\SchoolEducation;

use App\Dashboards\Initiatives\SchoolEducation\PsrpPhase2Dashboard;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Initiatives\SchoolEducation\Concerns\ManagesPsrpVisits;
use App\Imports\Initiatives\SchoolEducation\PsrpPhase2Import;
use App\Models\Initiative;
use App\Models\InitiativeFile;
use App\Models\Initiatives\SchoolEducation\PsrpPhase2;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PsrpPhase2Controller extends Controller
{
    use ManagesPsrpVisits;

    private const VISIT_COLUMNS = [
        'source_sr' => ['label' => 'SR', 'type' => 'number'],
        'visited_at' => ['label' => 'Visit date', 'type' => 'date', 'csv_format' => 'Y-m-d'],
        'district' => ['label' => 'District', 'type' => 'text'],
        'emis_code' => ['label' => 'EMIS', 'type' => 'code'],
        'school_name' => ['label' => 'School name', 'type' => 'text'],
        'handover_status' => ['label' => 'Handed over', 'type' => 'boolean', 'bad' => false],
        'furniture_available' => ['label' => 'Furniture', 'type' => 'boolean', 'bad' => false],
        'students_at_handover' => ['label' => 'At handover', 'type' => 'number'],
        'students_enrolled' => ['label' => 'Enrolled now', 'type' => 'number'],
        'total_classrooms' => ['label' => 'Classrooms', 'type' => 'number'],
        'total_washrooms' => ['label' => 'Washrooms', 'type' => 'number'],
        'total_desks' => ['label' => 'Desks', 'type' => 'number'],
        'total_room_chairs' => ['label' => 'Room chairs', 'type' => 'number'],
        'total_air_fans' => ['label' => 'Air fans', 'type' => 'number'],
        'classroom_condition' => ['label' => 'Classroom condition', 'type' => 'text'],
        'washroom_cleanliness' => ['label' => 'Washroom condition', 'type' => 'text'],
        'school_cleanliness' => ['label' => 'School cleanliness', 'type' => 'text'],
        'clean_drinking_water_available' => ['label' => 'Clean water', 'type' => 'boolean', 'bad' => false],
        'boundary_wall_available' => ['label' => 'Boundary wall', 'type' => 'boolean', 'bad' => false],
        'dangerous_classrooms' => ['label' => 'Dangerous classrooms', 'type' => 'number'],
        'latitude' => ['label' => 'Latitude', 'type' => 'text'],
        'longitude' => ['label' => 'Longitude', 'type' => 'text'],
    ];

    // Everything recorded for a visit, shown when its row is opened.
    private const VISIT_DETAILS = [
        'school_name' => 'School name',
        'emis_code' => 'EMIS code',
        'district' => 'District',
        'visited_at' => 'Visit date',
        'source_sr' => 'SR',
        'handover_status' => 'School handed over',
        'furniture_available' => 'Furniture available',
        'students_at_handover' => 'Students at handover',
        'students_enrolled' => 'Students enrolled now',
        'total_classrooms' => 'Total classrooms',
        'total_washrooms' => 'Total washrooms',
        'total_desks' => 'Total desks',
        'total_room_chairs' => 'Total room chairs',
        'total_air_fans' => 'Total air fans',
        'classroom_condition' => 'Classroom condition',
        'washroom_cleanliness' => 'Washroom condition',
        'school_cleanliness' => 'School cleanliness',
        'clean_drinking_water_available' => 'Clean drinking water',
        'boundary_wall_available' => 'Boundary wall',
        'dangerous_classrooms' => 'Dangerous classrooms',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
    ];

    // Every column of the visit file, under its own headings and in its own order. The two
    // enrollment columns come from the file's Enrollment sheet and go last.
    private const FILE_COLUMNS = [
        'source_sr' => ['label' => 'SR', 'type' => 'number'],
        // Shows like 19.08.2025, as in the file.
        'visited_at' => ['label' => 'Date', 'type' => 'date', 'excel_format' => 'dd.mm.yyyy'],
        'district' => ['label' => 'District', 'type' => 'text'],
        'emis_code' => ['label' => 'EMIS Code', 'type' => 'number'],
        'handover_status' => ['label' => 'Handing Over Status', 'type' => 'boolean'],
        'furniture_available' => ['label' => 'Furniture availability', 'type' => 'boolean'],
        'total_washrooms' => ['label' => 'Total Washrooms', 'type' => 'number'],
        'total_classrooms' => ['label' => 'Total Classrooms', 'type' => 'number'],
        // The visit file writes Nil and N/A here, and the upload saves those as blank.
        'total_desks' => ['label' => 'Total Desks', 'type' => 'number', 'blank' => 'Nil'],
        'total_room_chairs' => ['label' => 'Total Room Chairs', 'type' => 'number'],
        'total_air_fans' => ['label' => 'Total Air Fans', 'type' => 'number'],
        'classroom_condition' => ['label' => 'Classroom Condition', 'type' => 'text'],
        'washroom_cleanliness' => ['label' => 'Washroom Condition / Cleanliness', 'type' => 'text', 'blank' => 'N/A'],
        'school_cleanliness' => ['label' => 'Overall Cleanliness of School', 'type' => 'text'],
        'clean_drinking_water_available' => ['label' => 'Clean Drinking Water Availability', 'type' => 'boolean'],
        'boundary_wall_available' => ['label' => 'Boundary Wall Availability', 'type' => 'boolean'],
        'dangerous_classrooms' => ['label' => 'Dangerous Classrooms', 'type' => 'number'],
        'school_name' => ['label' => 'School Name', 'type' => 'text'],
        'latitude' => ['label' => 'Lat', 'type' => 'number'],
        'longitude' => ['label' => 'Long', 'type' => 'number'],
        'students_at_handover' => ['label' => 'Student enrollment before handing over', 'type' => 'number'],
        'students_enrolled' => ['label' => 'Student enrollment after handing over', 'type' => 'number'],
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
            'totals' => ['visits' => PsrpPhase2::count()],
            'dataChecks' => [],
            'records' => $this->visitRecords($filters, $table)
                ->paginate($table['per_page'])
                ->withQueryString()
                ->fragment('psrp-records'),
            'districtRows' => null,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'exportUrl' => route(
                'initiatives.psrp-phase-2.export',
                ['initiative' => $initiative] + $request->except(['page', 'per_page'])
            ),
            'hasDistrictTab' => false,
            'searchHint' => 'EMIS, school, district or SR',
            'detailFields' => self::VISIT_DETAILS,
            'detailDateFormat' => 'j M Y',
        ];

        if ($request->hasHeader(self::RECORDS_HEADER)) {
            return response()
                ->view('initiatives.school_education.psrp_shared.records', $records)
                ->header('Vary', self::RECORDS_HEADER);
        }

        $files = $initiative->filesByType();

        return response()
            ->view('initiatives.school_education.psrp_phase_2.show', $records + [
                'topbar' => $this->topbar($initiative, $files),
                'files' => $files,
            ])
            ->header('Vary', self::RECORDS_HEADER);
    }

    public function data(Request $request, Initiative $initiative, PsrpPhase2Dashboard $dashboard)
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
            'psrp-phase-2-visits-'.now()->format('Y-m-d').'.csv'
        );
    }

    public function mapDownload(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        return $this->downloadMapSchools($request, PsrpPhase2::class, self::FILE_COLUMNS, 'psrp-phase-2');
    }

    public function import(Request $request, Initiative $initiative, PsrpPhase2Import $import)
    {
        $this->ensureThisInitiative($initiative);

        return $this->uploadVisits($request, $initiative, $import);
    }

    private function topbar(Initiative $initiative, Collection $files): array
    {
        return [
            'title' => $initiative->name,
            'description' => 'Monitor field inspection data and implementation progress for this initiative.',
            'stats' => [
                // Files are newest first, so this is the department's latest list.
                ['label' => 'Total Schools', 'value' => $files->get(InitiativeFile::TYPE_DEPARTMENT_LIST)?->first()?->row_count],
                ['label' => 'Visited Schools', 'value' => PsrpPhase2::query()->distinct()->count('emis_code')],
                ['label' => 'Reports Uploaded', 'value' => $files->get(InitiativeFile::TYPE_OUR_REPORT)?->count() ?? 0],
                ['label' => 'Department Response', 'value' => $files->get(InitiativeFile::TYPE_DEPARTMENT_RESPONSE)?->count() ?? 0],
            ],
        ];
    }

    private function ensureThisInitiative(Initiative $initiative): void
    {
        abort_unless(
            $initiative->is_active
            && $initiative->table_name === 'public_school_reorganization_phase_2',
            404
        );
    }

    private function tableOptions(Request $request): array
    {
        $sort = $this->text($request, 'sort');
        $perPage = (int) $this->text($request, 'per_page');

        return [
            'tab' => 'visits',
            'columns' => self::VISIT_COLUMNS,
            'sort' => array_key_exists($sort, self::VISIT_COLUMNS) ? $sort : array_key_first(self::VISIT_COLUMNS),
            'dir' => $this->text($request, 'dir') === 'desc' ? 'desc' : 'asc',
            'search' => mb_substr($this->text($request, 'search'), 0, 100),
            'check' => '',
            'per_page' => in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 25,
        ];
    }

    private function visitRecords(array $filters, array $table): Builder
    {
        $search = addcslashes($table['search'], '\\%_');

        return PsrpPhase2::query()
            ->when($filters['district'] !== '', fn ($query) => $query->where('district', $filters['district']))
            ->when($filters['from'] !== '', fn ($query) => $query->where('visited_at', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($query) => $query->where('visited_at', '<=', $filters['to']))
            ->when($table['search'] !== '', function ($query) use ($table, $search) {
                $query->where(function ($query) use ($table, $search) {
                    $query->where('emis_code', 'like', "%{$search}%")
                        ->orWhere('district', 'like', "%{$search}%")
                        ->orWhere('school_name', 'like', "%{$search}%")
                        ->when(
                            ctype_digit($table['search']),
                            fn ($query) => $query->orWhere('source_sr', (int) $table['search'])
                        );
                });
            })
            ->orderBy($table['sort'], $table['dir'])
            ->orderBy('id');
    }
}
