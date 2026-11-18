<?php

namespace App\Http\Controllers\Initiatives\HousingUrbanDevelopment;

use App\Dashboards\Initiatives\HousingUrbanDevelopment\PspaDashboard;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Initiatives\SchoolEducation\Concerns\ManagesPsrpVisits;
use App\Imports\Initiatives\HousingUrbanDevelopment\PspaVisitImport;
use App\Models\Initiative;
use App\Models\Initiatives\HousingUrbanDevelopment\PspaVisit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

// Punjab Saaf Pani Authority (PSPA), under Housing Urban Development & Public Health Engineering: the
// Water Filtration Plants design, with the four uploads and the records table below it, as on the other
// initiative pages.
class PunjabSaafPaniAuthorityController extends Controller
{
    // Upload and CSV download work the same as on the other initiative pages.
    use ManagesPsrpVisits;

    private const TABLE = 'punjab_saaf_pani_authority';

    // Every column of our visit file, in its order. The table uses shorter headings than the file's long
    // questions; each is one the visit import knows, so the CSV download can be uploaded again. The table
    // shows the visit's date; the CSV keeps its time too.
    private const VISIT_COLUMNS = [
        'source_sr' => ['label' => 'Sr', 'type' => 'number'],
        'visited_at' => ['label' => 'Visit date', 'type' => 'date'],
        'district' => ['label' => 'District', 'type' => 'text'],
        'tehsil' => ['label' => 'Tehsil', 'type' => 'text'],
        'site_name' => ['label' => 'Site Name', 'type' => 'text'],
        'functionality' => ['label' => 'Functionality', 'type' => 'text'],
        'total_taps' => ['label' => 'Total water taps', 'type' => 'number'],
        'functional_taps' => ['label' => 'Functional water taps', 'type' => 'number'],
        'plant_unclean' => ['label' => 'Plant unclean', 'type' => 'text'],
        'no_electricity' => ['label' => 'No electricity', 'type' => 'text'],
        'meter_disconnected' => ['label' => 'Meter disconnected', 'type' => 'text'],
        'pending_electricity_bill' => ['label' => 'Pending electricity bill', 'type' => 'text'],
        'motor_pump_non_functional' => ['label' => 'Motor/pump non-functional', 'type' => 'text'],
        'non_functional_long' => ['label' => 'Non-functional for long', 'type' => 'text'],
        'operator_present' => ['label' => 'Operator present', 'type' => 'text'],
        'private_company' => ['label' => 'Private company', 'type' => 'text'],
        'found_at_location' => ['label' => 'Found at location', 'type' => 'text'],
        'lacks_repair_maintenance' => ['label' => 'Lacks repair and maintenance', 'type' => 'text'],
        'tank_leakage' => ['label' => 'Tank leakage', 'type' => 'text'],
        'broken_floor_tap_area' => ['label' => 'Broken floor and tap area', 'type' => 'text'],
        'tank_cover' => ['label' => 'Cover over water tank', 'type' => 'text'],
        'contractor_left_incomplete' => ['label' => 'Contractor left work incomplete', 'type' => 'text'],
        'poor_water_quality' => ['label' => 'Poor quality of water', 'type' => 'text'],
        'smell_in_water' => ['label' => 'Smell in water', 'type' => 'text'],
        'non_functional_taps' => ['label' => 'Non-functional water taps', 'type' => 'text'],
        'broken_taps' => ['label' => 'Broken water taps', 'type' => 'text'],
        'filter_needs_replacement' => ['label' => 'Filter needs replacing', 'type' => 'text'],
        'filter_non_functional' => ['label' => 'Filter non-functional', 'type' => 'text'],
        'room_and_filling_area_closed' => ['label' => 'Room and filling area closed', 'type' => 'text'],
        'bill_paid_by_residents' => ['label' => 'Bill paid by residents', 'type' => 'text'],
        'operator_charging_citizens' => ['label' => 'Operator charging citizens', 'type' => 'text'],
        'taps_stolen' => ['label' => 'Taps stolen', 'type' => 'text'],
        'door_stolen' => ['label' => 'Door stolen', 'type' => 'text'],
        'shut_down_early' => ['label' => 'Shut down early', 'type' => 'text'],
        'operator_unpaid' => ['label' => 'Operator unpaid', 'type' => 'text'],
        'dilapidated_building' => ['label' => 'Dilapidated building', 'type' => 'text'],
        'illegally_occupied' => ['label' => 'Illegally occupied', 'type' => 'text'],
        'dogs_around_plant' => ['label' => 'Dogs around plant', 'type' => 'text'],
        'rats_inside_plant' => ['label' => 'Rats inside plant', 'type' => 'text'],
        'non_operational_15_days' => ['label' => 'Non-operational 15 days', 'type' => 'text'],
        'key_held_by_vendor' => ['label' => 'Key held by street vendor', 'type' => 'text'],
        'taps_on_motor_no_filtration' => ['label' => 'Taps on motor, no filtration', 'type' => 'text'],
        'pfa_suspended_supply' => ['label' => 'Supply suspended by Punjab Food Authority', 'type' => 'text'],
        'bore_not_working' => ['label' => 'Bore not working', 'type' => 'text'],
        'electricity_theft' => ['label' => 'Electricity theft', 'type' => 'text'],
        'direct_pole_connection' => ['label' => 'Direct connection from pole', 'type' => 'text'],
        'cooler_instead_of_taps' => ['label' => 'Water cooler instead of taps', 'type' => 'text'],
        'images' => ['label' => 'Image Path', 'type' => 'text'],
        // Shown in full; a number format would round them.
        'latitude' => ['label' => 'Latitude', 'type' => 'text'],
        'longitude' => ['label' => 'Longitude', 'type' => 'text'],
    ];

    // Everything recorded for a visit, shown when its row is opened, under the visit file's own headings.
    private const VISIT_DETAILS = [
        'site_name' => 'Site Name',
        'district' => 'District',
        'tehsil' => 'Tehsil',
        'visited_at' => 'Date',
        'source_sr' => 'Sr',
        'functionality' => 'Functionality of filtration plant',
        'total_taps' => 'Total water taps',
        'functional_taps' => 'Functional water taps',
        'plant_unclean' => 'Uncleanliness of filtration plant',
        'no_electricity' => 'Unavailability of electricity',
        'meter_disconnected' => 'Electricity meter/connection disconnected',
        'pending_electricity_bill' => 'Pending electricity bill',
        'motor_pump_non_functional' => 'Non-functional motor/pump',
        'non_functional_long' => 'Filtration plant non-functional for long',
        'operator_present' => 'Operator/worker was present',
        'private_company' => 'Filtration plant is operating under a private company',
        'found_at_location' => 'Filtration plant found on the said location',
        'lacks_repair_maintenance' => 'Lack of repair and maintenance of filtration plant',
        'tank_leakage' => 'Leakage in the water supply tank',
        'broken_floor_tap_area' => 'Broken floor and water tap area',
        'tank_cover' => 'cover over water tank',
        'contractor_left_incomplete' => 'The contractor left without completing the filtration plant work',
        'poor_water_quality' => 'Poor quality of water',
        'smell_in_water' => 'Smell in water',
        'non_functional_taps' => 'Non-functional water taps',
        'broken_taps' => 'Broken water taps',
        'filter_needs_replacement' => 'The filter needs to be replaced',
        'filter_non_functional' => 'Non-functional filter (coarse/fine)',
        'room_and_filling_area_closed' => "Filtration plant's room and water filling area was closed",
        'bill_paid_by_residents' => 'Electricity bill payment by residents',
        'operator_charging_citizens' => 'Operator was charging amount from citizens for clean water',
        'taps_stolen' => 'The water taps have been taken away by thieves',
        'door_stolen' => 'The filtration plant door has been stolen',
        'shut_down_early' => 'The filtration plant is shut down early instead of operating according to its scheduled operating hours.',
        'operator_unpaid' => 'The plant operator has not received a salary for several months',
        'dilapidated_building' => 'dilapidated building of filtration plant',
        'illegally_occupied' => 'The filtration plant has been illegally occupied by an individual, and no proper care or maintenance of the plant is being carried out',
        'dogs_around_plant' => 'Someone has kept several dogs around the filtration plant, which is preventing citizens from getting water',
        'rats_inside_plant' => 'Rats have been found inside the filtration plant setup, resulting in significant uncleanliness and highly unhygienic conditions.',
        'non_operational_15_days' => 'The filtration plant has remained non-operational for the past 15 days. Multiple pipeline leakages and damage to one filtration vessel have rendered the system partially dysfunctional, with only one vessel currently in operation.',
        'key_held_by_vendor' => 'The filtration plant key is held by a street vendor (Chabri Shop), who uses the premises for storage and does not operate the plant regularly.',
        'taps_on_motor_no_filtration' => 'Water taps are directly connected to the motor, and no filtration system is installed.',
        'pfa_suspended_supply' => 'The Punjab Food Authority has suspended the water supply due to substandard water quality.',
        'bore_not_working' => 'The filtration plant bore is not functioning properly and is unable to extract water efficiently. A new bore needs to be installed, as the filter turns black within a few days.',
        'electricity_theft' => "Theft of Electricity from the Filtration Plant's Power Connection",
        'direct_pole_connection' => 'Electricity theft has been committed at the filtration plant by taking a direct connection from the electric pole.',
        'cooler_instead_of_taps' => 'Water cooler has been installed at this filtration plant instead of water taps',
        'images' => 'Image Path',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
    ];

    // The form's Yes / No questions, from "Uncleanliness of filtration plant" on.
    private const YES_NO_COLUMNS = [
        'plant_unclean', 'no_electricity', 'meter_disconnected', 'pending_electricity_bill', 'motor_pump_non_functional',
        'non_functional_long', 'operator_present', 'private_company', 'found_at_location', 'lacks_repair_maintenance',
        'tank_leakage', 'broken_floor_tap_area', 'tank_cover', 'contractor_left_incomplete', 'poor_water_quality',
        'smell_in_water', 'non_functional_taps', 'broken_taps', 'filter_needs_replacement', 'filter_non_functional',
        'room_and_filling_area_closed', 'bill_paid_by_residents', 'operator_charging_citizens', 'taps_stolen',
        'door_stolen', 'shut_down_early', 'operator_unpaid', 'dilapidated_building', 'illegally_occupied',
        'dogs_around_plant', 'rats_inside_plant', 'non_operational_15_days', 'key_held_by_vendor',
        'taps_on_motor_no_filtration', 'pfa_suspended_supply', 'bore_not_working', 'electricity_theft',
        'direct_pole_connection', 'cooler_instead_of_taps',
    ];

    // The map's Download: the plants on the map as rows of our visit file, under its own headings, in its
    // order and formats. The Yes / No questions, the photos and the location follow these (see fileColumns).
    private const FILE_COLUMNS = [
        'source_sr' => ['label' => 'Sr', 'type' => 'number'],
        'visited_at' => ['label' => 'Date', 'type' => 'date', 'excel_format' => 'mm-dd-yy'],
        'district' => ['label' => 'District', 'type' => 'text'],
        'tehsil' => ['label' => 'Tehsil', 'type' => 'text'],
        'site_name' => ['label' => 'Site Name', 'type' => 'text'],
        'functionality' => ['label' => 'Functionality of filtration plant', 'type' => 'text'],
        'total_taps' => ['label' => 'Total water taps', 'type' => 'number'],
        'functional_taps' => ['label' => 'Functional water taps', 'type' => 'number'],
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
            'totals' => ['visits' => PspaVisit::count()],
            'dataChecks' => $this->dataCheckCounts($filters),
            'records' => $this->visitRecords($filters, $table)
                ->paginate($table['per_page'])
                ->withQueryString()
                ->fragment('psrp-records'),
            'districtRows' => null,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'exportUrl' => route(
                'initiatives.punjab-saaf-pani-authority.export',
                ['initiative' => $initiative] + $request->except(['page', 'per_page'])
            ),
            'hasDistrictTab' => false,
            'searchHint' => 'Site name, district or tehsil',
            'detailFields' => self::VISIT_DETAILS,
            'detailDateFormat' => 'j M Y, g:i A',
            // The earliest rows have a date only, so no time is shown for them.
            'detailDateOnlyFormat' => 'j M Y',
            'nameColumn' => 'site_name',
            // There is no EMIS code: a visit with no site name is called by its district.
            'untitledVisit' => static fn ($visit) => self::untitled($visit),
            'visitLabel' => static fn ($row) => $row->site_name ?: self::untitled($row),
            'tableLabel' => 'Filtration plant visits table',
        ];

        if ($request->hasHeader(self::RECORDS_HEADER)) {
            return response()
                ->view('initiatives.school_education.psrp_shared.records', $records)
                ->header('Vary', self::RECORDS_HEADER);
        }

        // The controller is called with the request and initiative only, so the dashboard is made here.
        return response()
            ->view('initiatives.housing_urban_development.punjab_saaf_pani_authority.show', $records + [
                'topbar' => $this->topbar($initiative, app(PspaDashboard::class)),
                'files' => $initiative->filesByType(),
            ])
            ->header('Vary', self::RECORDS_HEADER);
    }

    // Everything the page's figures, map and tables are worked out from.
    public function data(Request $request, Initiative $initiative, PspaDashboard $dashboard)
    {
        $this->ensureThisInitiative($initiative);

        return $this->dashboardData($request, $dashboard);
    }

    // The plants on the map for the chosen finding or response, as rows of our visit file.
    public function mapDownload(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        return $this->downloadMapSchools($request, PspaVisit::class, $this->fileColumns(), 'water-filtration-plants');
    }

    // Every column of the visit file: the first ones, the Yes / No questions under the file's own
    // wording, then the photos and the location.
    private function fileColumns(): array
    {
        $columns = self::FILE_COLUMNS;
        foreach (self::YES_NO_COLUMNS as $column) {
            $columns[$column] = ['label' => self::VISIT_DETAILS[$column], 'type' => 'text'];
        }

        // The location as numbers, as in the file.
        return $columns + [
            'images' => ['label' => 'Image Path', 'type' => 'text'],
            'latitude' => ['label' => 'Latitude', 'type' => 'number'],
            'longitude' => ['label' => 'Longitude', 'type' => 'number'],
        ];
    }

    public function export(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        $table = $this->tableOptions($request);

        return $this->downloadCsv(
            $this->visitRecords($this->filters($request), $table)->cursor(),
            $table['columns'],
            'pspa-filtration-plant-visits-'.now()->format('Y-m-d').'.csv'
        );
    }

    public function import(Request $request, Initiative $initiative, PspaVisitImport $import)
    {
        $this->ensureThisInitiative($initiative);

        return $this->uploadVisits($request, $initiative, $import);
    }

    // The design's four cards, numbered 1 to 4 by the page's styles, with the line under each. They show
    // all of Punjab here, and the page's script keeps them in step with its filters. "Reported to the
    // department" is the design's own count: the plants in the SMU reports of the response file.
    private function topbar(Initiative $initiative, PspaDashboard $dashboard): array
    {
        $cards = $dashboard->headline($dashboard->data());

        $labels = [
            'total' => 'Total plants',
            'visited' => 'Visited by SMU',
            'reports' => 'Reported to the department',
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
            'description' => "Water filtration plants give citizens free, clean drinking water from public taps. SMU field teams make unannounced visits to the plants in PSPA's list, "
                .'check whether each plant is working, its operator present, its taps and filters working and the plant clean, and report the problems to PSPA, which responds with the action taken.',
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
            'more_working_taps' => [
                'label' => 'More working taps than taps',
                'rule' => 'functional_taps > total_taps',
                'bindings' => [],
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

    // A visit's name when the file gives no site name.
    private static function untitled(object $visit): string
    {
        return ($visit->district ?? '') !== '' ? 'Plant in '.$visit->district : 'Visit';
    }

    private function visits(array $filters): Builder
    {
        return PspaVisit::query()
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
                    $query->where('site_name', 'like', "%{$search}%")
                        ->orWhere('district', 'like', "%{$search}%")
                        ->orWhere('tehsil', 'like', "%{$search}%");
                });
            })
            ->when($check !== null, fn ($query) => $query->whereRaw('('.$check['rule'].')', $check['bindings']))
            ->orderBy($table['sort'], $table['dir'])
            ->orderBy('id');
    }
}
