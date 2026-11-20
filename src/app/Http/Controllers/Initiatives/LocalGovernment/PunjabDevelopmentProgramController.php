<?php

namespace App\Http\Controllers\Initiatives\LocalGovernment;

use App\Dashboards\Initiatives\LocalGovernment\PdpDashboard;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Initiatives\SchoolEducation\Concerns\ManagesPsrpVisits;
use App\Imports\Initiatives\LocalGovernment\PdpVisitImport;
use App\Models\Initiative;
use App\Models\Initiatives\LocalGovernment\PdpVisit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

// Chief Minister Punjab Development Program (PDP), under Local Government & Community Development: the
// Punjab Development Program design, with the four uploads and the records table below it, as on the other
// initiative pages.
class PunjabDevelopmentProgramController extends Controller
{
    // Upload and CSV download work the same as on the other initiative pages.
    use ManagesPsrpVisits;

    private const TABLE = 'lgcd_punjab_development_program';

    // Every column of our visit file, in its order. The table uses shorter headings than the file's
    // questions; each is one the visit import knows, so the CSV download can be uploaded again.
    private const VISIT_COLUMNS = [
        'visited_at' => ['label' => 'Report date', 'type' => 'date', 'csv_format' => 'Y-m-d'],
        'district' => ['label' => 'District', 'type' => 'text'],
        'city' => ['label' => 'City', 'type' => 'text'],
        'manholes_without_cover' => ['label' => 'Manholes without cover', 'type' => 'text'],
        'workers_in_uniform' => ['label' => 'Workers in uniform', 'type' => 'text'],
        'workers_using_safety_equipment' => ['label' => 'Workers using safety equipment', 'type' => 'text'],
        'safety_boards_installed' => ['label' => 'Safety boards installed', 'type' => 'text'],
        'construction_waste_removed' => ['label' => 'C and D waste removed', 'type' => 'text'],
        'dust_control_measures' => ['label' => 'Dust control measures', 'type' => 'text'],
        'excavation_sites_secured' => ['label' => 'Excavation sites secured', 'type' => 'text'],
        'materials_organized' => ['label' => 'Materials organized', 'type' => 'text'],
        'slow_or_halted_work' => ['label' => 'Slow or halted work', 'type' => 'text'],
        'trees_protected' => ['label' => 'Trees protected', 'type' => 'text'],
        'traffic_managed' => ['label' => 'Traffic managed', 'type' => 'text'],
        'images' => ['label' => 'Image Path', 'type' => 'text'],
        'videos' => ['label' => 'Videos', 'type' => 'text'],
        // Shown in full; a number format would round them.
        'latitude' => ['label' => 'Latitude', 'type' => 'text'],
        'longitude' => ['label' => 'Longitude', 'type' => 'text'],
        'category' => ['label' => 'Category', 'type' => 'text'],
    ];

    // Everything recorded for a visit, shown when its row is opened, under the visit file's own headings.
    private const VISIT_DETAILS = [
        'city' => 'Cities',
        'district' => 'District',
        'visited_at' => 'Report DATE',
        'category' => 'category',
        'manholes_without_cover' => 'Manholes without cover',
        'workers_in_uniform' => 'Workers wearing proposed uniform',
        'workers_using_safety_equipment' => 'Workers using proper safety equipment',
        'safety_boards_installed' => 'Safety board barriers installation',
        'construction_waste_removed' => 'C and D waste was removed',
        'dust_control_measures' => 'Measures taken to control dust',
        'excavation_sites_secured' => 'Excavation sites properly secured with barriers',
        'materials_organized' => 'Construction materials properly organized and secured',
        'slow_or_halted_work' => 'slow or halted work',
        'trees_protected' => 'Care being taken to protect trees & green',
        'traffic_managed' => 'Traffic movement around the construction site managed',
        'images' => 'Image Path',
        'videos' => 'Videos',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
    ];

    // The form's Yes / No questions.
    private const YES_NO_COLUMNS = [
        'manholes_without_cover', 'workers_in_uniform', 'workers_using_safety_equipment', 'safety_boards_installed',
        'construction_waste_removed', 'dust_control_measures', 'excavation_sites_secured', 'materials_organized',
        'slow_or_halted_work', 'trees_protected', 'traffic_managed',
    ];

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    // Sent by the page when only the records table needs refreshing.
    private const RECORDS_HEADER = 'X-Psrp-Records';

    // The district each visit counts in, worked out once per request.
    private ?array $visitDistricts = null;

    public function __construct(private PdpDashboard $dashboard)
    {
    }

    public function show(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        $filters = $this->filters($request);
        $table = $this->tableOptions($request);

        $records = [
            'initiative' => $initiative,
            'filters' => $filters,
            'table' => $table,
            'totals' => ['visits' => PdpVisit::count()],
            'dataChecks' => $this->dataCheckCounts($filters),
            'records' => $this->visitRecords($filters, $table)
                ->paginate($table['per_page'])
                ->withQueryString()
                ->fragment('psrp-records'),
            'districtRows' => null,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'exportUrl' => route(
                'initiatives.punjab-development-program.export',
                ['initiative' => $initiative] + $request->except(['page', 'per_page'])
            ),
            'hasDistrictTab' => false,
            'searchHint' => 'City, district or category',
            'detailFields' => self::VISIT_DETAILS,
            'detailDateFormat' => 'j M Y',
            'detailDateOnlyFormat' => 'j M Y',
            'nameColumn' => 'city',
            // There is no code for a site: a visit with no city is called by its district.
            'untitledVisit' => static fn ($visit) => self::untitled($visit),
            'visitLabel' => static fn ($row) => $row->city ?: self::untitled($row),
            'tableLabel' => 'Work site visits table',
        ];

        if ($request->hasHeader(self::RECORDS_HEADER)) {
            return response()
                ->view('initiatives.school_education.psrp_shared.records', $records)
                ->header('Vary', self::RECORDS_HEADER);
        }

        return response()
            ->view('initiatives.local_government.punjab_development_program.show', $records + [
                'topbar' => $this->topbar($initiative),
                'files' => $initiative->filesByType(),
            ])
            ->header('Vary', self::RECORDS_HEADER);
    }

    // Everything the page's figures, map and tables are worked out from.
    public function data(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        return $this->dashboardData($request, $this->dashboard);
    }

    // The cities on the map for the chosen finding or response, as rows of our visit file.
    public function mapDownload(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        return $this->downloadMapSchools($request, PdpVisit::class, $this->fileColumns(), 'punjab-development-program', 'visited_at');
    }

    // Every column of the visit file, under its own headings, in its order and formats.
    private function fileColumns(): array
    {
        $columns = [
            'visited_at' => ['label' => 'Report DATE', 'type' => 'date', 'excel_format' => 'yyyy-mm-dd hh:mm:ss'],
            'district' => ['label' => 'District', 'type' => 'text'],
            'city' => ['label' => 'Cities', 'type' => 'text'],
        ];

        foreach (self::YES_NO_COLUMNS as $column) {
            $columns[$column] = ['label' => self::VISIT_DETAILS[$column], 'type' => 'text'];
        }

        // The location as numbers, as in the file.
        return $columns + [
            'images' => ['label' => 'Image Path', 'type' => 'text'],
            'videos' => ['label' => 'Videos', 'type' => 'text'],
            'latitude' => ['label' => 'Latitude', 'type' => 'number'],
            'longitude' => ['label' => 'Longitude', 'type' => 'number'],
            'category' => ['label' => 'category', 'type' => 'text'],
        ];
    }

    public function export(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        $table = $this->tableOptions($request);

        return $this->downloadCsv(
            $this->visitRecords($this->filters($request), $table)->cursor(),
            $table['columns'],
            'pdp-work-site-visits-'.now()->format('Y-m-d').'.csv'
        );
    }

    public function import(Request $request, Initiative $initiative, PdpVisitImport $import)
    {
        $this->ensureThisInitiative($initiative);

        return $this->uploadVisits($request, $initiative, $import);
    }

    // The design's four cards, numbered 1 to 4 by the page's styles, with the design's line under each. They
    // show all of Punjab here, and the page's script keeps them in step with its filters.
    private function topbar(Initiative $initiative): array
    {
        $cards = $this->dashboard->headline($this->dashboard->data());

        $labels = [
            'total' => 'PDP cities and towns',
            'visited' => 'Visited by SMU',
            'reports' => 'SMU reports to the department',
            'replied' => 'Department responses',
        ];

        $stats = [];
        foreach ($labels as $key => $label) {
            [$value, $info] = $cards[$key];
            $stats[] = ['label' => $label, 'value' => $value, 'info' => $info, 'id' => 'c-'.$key];
        }

        return [
            'title' => $initiative->name,
            // The design's opening paragraph, word for word.
            'description' => 'PDP is a large municipal works programme for cities and towns across Punjab: sewerage, storm drains, roads, street lights and parks. '
            .'LG&CD sponsors and carries out the work, and the portfolio is PKR 313.92 billion. SMU field teams inspect the work sites in the project cities. ',
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
            'check' => array_key_exists($check, $this->dataChecks()) ? $check : '',
            'per_page' => in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 25,
        ];
    }

    // Visits worth double-checking before their answers are relied on, as SQL with its bindings.
    private function dataChecks(): array
    {
        $rule = implode(' OR ', array_map(
            fn ($column) => "({$column} IS NOT NULL AND {$column} NOT IN (?, ?))",
            self::YES_NO_COLUMNS
        ));

        return [
            'other_answer' => [
                'label' => 'Answer not Yes or No',
                'rule' => $rule,
                'bindings' => array_merge(...array_fill(0, count(self::YES_NO_COLUMNS), ['Yes', 'No'])),
            ],
            'no_location' => [
                'label' => 'No location',
                'rule' => 'latitude IS NULL OR longitude IS NULL',
                'bindings' => [],
            ],
            // The box around Punjab used for the department lists' coordinates on the other pages. A
            // latitude typed without its decimal point (31165614) is listed here.
            'outside_punjab' => [
                'label' => 'Location outside Punjab',
                'rule' => 'latitude NOT BETWEEN ? AND ? OR longitude NOT BETWEEN ? AND ?',
                'bindings' => [27.7, 34.1, 69.3, 75.5],
            ],
        ];
    }

    // A visit's name when the file gives no city.
    private static function untitled(object $visit): string
    {
        return ($visit->district ?? '') !== '' ? 'Site in '.$visit->district : 'Visit';
    }

    // The district filter follows the page: a visit counts in its city's district in the department's
    // list, whatever district it was typed under ("TTS").
    private function visits(array $filters): Builder
    {
        return PdpVisit::query()
            ->when($filters['district'] !== '', fn ($query) => $query->whereIntegerInRaw('id', $this->visitsIn($filters['district'])))
            ->when($filters['from'] !== '', fn ($query) => $query->where('visited_at', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($query) => $query->where('visited_at', '<=', $filters['to']));
    }

    private function visitsIn(string $district): array
    {
        $this->visitDistricts ??= $this->dashboard->visitDistricts();

        return array_keys(array_filter($this->visitDistricts, fn ($name) => $name === $district)) ?: [0];
    }

    private function dataCheckCounts(array $filters): array
    {
        $checks = $this->dataChecks();
        $totals = [];
        $bindings = [];

        foreach ($checks as $key => $check) {
            $totals[] = "SUM(({$check['rule']})) AS {$key}";
            array_push($bindings, ...$check['bindings']);
        }

        // Only the counts, in place of the visit columns.
        $counts = $this->visits($filters)->toBase()->selectRaw(implode(', ', $totals), $bindings)->first();

        return collect($checks)
            ->map(fn ($check, $key) => ['label' => $check['label'], 'count' => (int) $counts->{$key}])
            ->all();
    }

    private function visitRecords(array $filters, array $table): Builder
    {
        $search = addcslashes($table['search'], '\\%_');
        $check = $table['check'] === '' ? null : $this->dataChecks()[$table['check']];

        return $this->visits($filters)
            ->when($table['search'] !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('city', 'like', "%{$search}%")
                        ->orWhere('district', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when($check !== null, fn ($query) => $query->whereRaw('('.$check['rule'].')', $check['bindings']))
            ->orderBy($table['sort'], $table['dir'])
            ->orderBy('id');
    }
}
