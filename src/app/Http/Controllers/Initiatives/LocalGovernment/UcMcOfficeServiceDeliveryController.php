<?php

namespace App\Http\Controllers\Initiatives\LocalGovernment;

use App\Dashboards\Initiatives\LocalGovernment\UcMcDashboard;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Initiatives\SchoolEducation\Concerns\ManagesPsrpVisits;
use App\Imports\Initiatives\LocalGovernment\UcMcVisitImport;
use App\Models\Initiative;
use App\Models\Initiatives\LocalGovernment\UcMcVisit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

// UC MC Office Service Delivery (Local Government & Community Development): the findings design, with
// the uploads and the records table at the end of the page, as on the other initiative pages.
class UcMcOfficeServiceDeliveryController extends Controller
{
    // Upload, filters and CSV download work the same as on the other initiative pages.
    use ManagesPsrpVisits;

    private const TABLE = 'lgcd_uc_mc_office_service_delivery';

    // Every column of our visit file, in its order. The headings are ones the visit import knows, so the
    // CSV download can be uploaded again. The table shows the visit's date; the CSV keeps its time too.
    private const VISIT_COLUMNS = [
        'visited_at' => ['label' => 'Visit date', 'type' => 'date'],
        'email' => ['label' => 'Email', 'type' => 'text'],
        'agent' => ['label' => 'Agent', 'type' => 'text'],
        'district' => ['label' => 'District', 'type' => 'text'],
        'tehsil' => ['label' => 'Tehsil', 'type' => 'text'],
        'name' => ['label' => 'Name', 'type' => 'text'],
        'uc_number' => ['label' => 'UC', 'type' => 'text'],
        'mc_name' => ['label' => 'MC', 'type' => 'text'],
        'office_open' => ['label' => 'Office open?', 'type' => 'text'],
        'incharge_present' => ['label' => 'Incharge present?', 'type' => 'text'],
        'service_delivery' => ['label' => 'Service delivery', 'type' => 'text'],
        'schedule_displayed' => ['label' => 'Schedule displayed?', 'type' => 'text'],
        'schedule_followed' => ['label' => 'Schedule followed?', 'type' => 'text'],
        'unauthorized_private_person' => ['label' => 'Unauthorized private person', 'type' => 'text'],
        'dilapidated_building' => ['label' => 'Dilapidated building', 'type' => 'text'],
        'office_cleanliness' => ['label' => 'Office cleanliness', 'type' => 'text'],
        'office_board_displayed' => ['label' => 'Office board displayed', 'type' => 'text'],
        // Shown in full; a number format would round them.
        'latitude' => ['label' => 'Latitude', 'type' => 'text'],
        'longitude' => ['label' => 'Longitude', 'type' => 'text'],
    ];

    // Everything recorded for a visit, shown when its row is opened, under the visit file's own headings.
    private const VISIT_DETAILS = [
        'name' => 'Name',
        'uc_number' => 'UC',
        'mc_name' => 'MC',
        'district' => 'District',
        'tehsil' => 'Tehsil',
        'visited_at' => 'Timestamp',
        'email' => 'Email',
        'agent' => 'Agent',
        'office_open' => 'Office open?',
        'incharge_present' => 'Incharge present?',
        'service_delivery' => 'Service delivery',
        'schedule_displayed' => 'Schedule displayed?',
        'schedule_followed' => 'Schedule followed?',
        'unauthorized_private_person' => 'Unauthorizedprivate person',
        'dilapidated_building' => 'Delipidated building',
        'office_cleanliness' => 'Office Cleanliness',
        'office_board_displayed' => 'Office Board displayed',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
    ];

    // Every column of our visit file, under its own headings, in its order and with its formats, for
    // the map's download. Blanks are written as the file writes them: "Null".
    private const FILE_COLUMNS = [
        'visited_at' => ['label' => 'Timestamp', 'type' => 'date', 'excel_format' => '[$-809]dd mmmm yyyy;@'],
        'email' => ['label' => 'Email', 'type' => 'text'],
        'agent' => ['label' => 'Agent', 'type' => 'text', 'blank' => 'Null'],
        'district' => ['label' => 'District', 'type' => 'text'],
        'tehsil' => ['label' => 'Tehsil', 'type' => 'text'],
        'name' => ['label' => 'Name', 'type' => 'text', 'blank' => 'Null'],
        // A number stays a number; several UCs ("98,99") stay as written.
        'uc_number' => ['label' => 'UC', 'type' => 'number', 'blank' => 'Null'],
        'mc_name' => ['label' => 'MC', 'type' => 'text', 'blank' => 'Null'],
        'office_open' => ['label' => 'Office open?', 'type' => 'text', 'blank' => 'Null'],
        'incharge_present' => ['label' => 'Incharge present?', 'type' => 'text', 'blank' => 'Null'],
        'service_delivery' => ['label' => 'Service delivery', 'type' => 'text', 'blank' => 'Null'],
        'schedule_displayed' => ['label' => 'Schedule displayed?', 'type' => 'text', 'blank' => 'Null'],
        'schedule_followed' => ['label' => 'Schedule followed?', 'type' => 'text', 'blank' => 'Null'],
        'unauthorized_private_person' => ['label' => 'Unauthorizedprivate person', 'type' => 'text', 'blank' => 'Null'],
        'dilapidated_building' => ['label' => 'Delipidated building', 'type' => 'text', 'blank' => 'Null'],
        'office_cleanliness' => ['label' => 'Office Cleanliness', 'type' => 'text', 'blank' => 'Null'],
        'office_board_displayed' => ['label' => 'Office Board displayed', 'type' => 'text', 'blank' => 'Null'],
        'latitude' => ['label' => 'Latitude', 'type' => 'number'],
        'longitude' => ['label' => 'Longitude', 'type' => 'number'],
    ];

    // The answers a question on the form can have. Anything else is typed in by hand.
    private const FORM_ANSWERS = ['Yes', 'No', 'Not applicable', 'Office Closed'];

    private const ANSWER_COLUMNS = [
        'office_open', 'incharge_present', 'service_delivery', 'schedule_displayed', 'schedule_followed',
        'unauthorized_private_person', 'dilapidated_building', 'office_cleanliness', 'office_board_displayed',
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
            'totals' => ['visits' => UcMcVisit::count()],
            'dataChecks' => $this->dataCheckCounts($filters),
            'records' => $this->visitRecords($filters, $table)
                ->paginate($table['per_page'])
                ->withQueryString()
                ->fragment('psrp-records'),
            'districtRows' => null,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'exportUrl' => route(
                'initiatives.uc-mc-office-service-delivery.export',
                ['initiative' => $initiative] + $request->except(['page', 'per_page'])
            ),
            'hasDistrictTab' => false,
            'searchHint' => 'Name, UC, MC, district or tehsil',
            'detailFields' => self::VISIT_DETAILS,
            'detailDateFormat' => 'j M Y, g:i A',
            // Older rows have a date only, so no time is shown for them.
            'detailDateOnlyFormat' => 'j M Y',
            'nameColumn' => 'name',
            // There is no EMIS code: a visit with no name is called by its UC number or MC.
            'untitledVisit' => static fn ($visit) => self::officeOf($visit),
            'visitLabel' => static fn ($row) => $row->name ?: self::officeOf($row),
            'tableLabel' => 'UC and MC office visits table',
        ];

        if ($request->hasHeader(self::RECORDS_HEADER)) {
            return response()
                ->view('initiatives.school_education.psrp_shared.records', $records)
                ->header('Vary', self::RECORDS_HEADER);
        }

        // The controller is called with the request and initiative only, so the dashboard is made here.
        return response()
            ->view('initiatives.local_government.uc_mc_office_service_delivery.show', $records + [
                'topbar' => $this->topbar($initiative, app(UcMcDashboard::class)),
                'files' => $initiative->filesByType(),
            ])
            ->header('Vary', self::RECORDS_HEADER);
    }

    // Everything the page's figures, map and tables are worked out from.
    public function data(Request $request, Initiative $initiative, UcMcDashboard $dashboard)
    {
        $this->ensureThisInitiative($initiative);

        return $this->dashboardData($request, $dashboard);
    }

    // The offices on the map for the chosen finding or response, as rows of our visit file.
    public function mapDownload(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        // The visit file has no Sr column; its rows are in timestamp order.
        return $this->downloadMapSchools($request, UcMcVisit::class, self::FILE_COLUMNS, 'uc-mc', 'visited_at');
    }

    public function export(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        $table = $this->tableOptions($request);

        return $this->downloadCsv(
            $this->visitRecords($this->filters($request), $table)->cursor(),
            $table['columns'],
            'uc-mc-office-visits-'.now()->format('Y-m-d').'.csv'
        );
    }

    public function import(Request $request, Initiative $initiative, UcMcVisitImport $import)
    {
        $this->ensureThisInitiative($initiative);

        return $this->uploadVisits($request, $initiative, $import);
    }

    // The design's four cards, numbered 1 to 4 by the page's styles, with the line under each. They show
    // all of Punjab here, and the page's script keeps them in step with its filters. "Reported to
    // department" is the design's own count: the cases in the department response file.
    private function topbar(Initiative $initiative, UcMcDashboard $dashboard): array
    {
        $cards = $dashboard->headline($dashboard->data());

        $labels = [
            'total' => 'Total offices',
            'visited' => 'Visited by SMU',
            'reports' => 'Reported to department',
            'replied' => 'Department responses',
        ];

        $stats = [];
        foreach ($labels as $key => $label) {
            [$value, $info] = $cards[$key];
            $stats[] = ['label' => $label, 'value' => $value, 'info' => $info, 'id' => 'c-'.$key];
        }

        return [
            'title' => $initiative->name,
            // The design's opening paragraph, in two lines.
            'description' => 'SMU field teams make unannounced visits to Union Council and Municipal Committee offices, where citizens register births, deaths, marriages and divorces, '
                .'and check the office is open, the in-charge present and serving, the fee schedule displayed and followed; the department responds with action taken.',
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
        $placeholders = implode(', ', array_fill(0, count(self::FORM_ANSWERS), '?'));
        $rule = implode(' OR ', array_map(
            fn ($column) => "({$column} IS NOT NULL AND {$column} NOT IN ({$placeholders}))",
            self::ANSWER_COLUMNS
        ));

        return [
            'other_answer' => [
                'label' => 'Answer not on the form',
                'rule' => $rule,
                'bindings' => array_merge(...array_fill(0, count(self::ANSWER_COLUMNS), self::FORM_ANSWERS)),
            ],
            'no_location' => [
                'label' => 'No location',
                'rule' => 'latitude IS NULL OR longitude IS NULL',
                'bindings' => [],
            ],
            // The box around Punjab used for the department lists' coordinates on the other pages.
            'outside_punjab' => [
                'label' => 'Location outside Punjab',
                'rule' => 'latitude NOT BETWEEN ? AND ? OR longitude NOT BETWEEN ? AND ?',
                'bindings' => [27.7, 34.1, 69.3, 75.5],
            ],
        ];
    }

    // A visit's office when it has no name: its UC number or its MC.
    private static function officeOf(object $visit): string
    {
        return match (true) {
            ($visit->uc_number ?? '') !== '' => 'UC '.$visit->uc_number,
            ($visit->mc_name ?? '') !== '' => 'MC '.$visit->mc_name,
            default => 'Visit',
        };
    }

    private function visits(array $filters): Builder
    {
        return UcMcVisit::query()
            ->when($filters['district'] !== '', fn ($query) => $query->where('district', $filters['district']))
            ->when($filters['from'] !== '', fn ($query) => $query->where('visited_at', '>=', $filters['from'].' 00:00:00'))
            ->when($filters['to'] !== '', fn ($query) => $query->where('visited_at', '<=', $filters['to'].' 23:59:59'));
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
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('uc_number', 'like', "%{$search}%")
                        ->orWhere('mc_name', 'like', "%{$search}%")
                        ->orWhere('district', 'like', "%{$search}%")
                        ->orWhere('tehsil', 'like', "%{$search}%");
                });
            })
            ->when($check !== null, fn ($query) => $query->whereRaw('('.$check['rule'].')', $check['bindings']))
            ->orderBy($table['sort'], $table['dir'])
            ->orderBy('id');
    }
}
