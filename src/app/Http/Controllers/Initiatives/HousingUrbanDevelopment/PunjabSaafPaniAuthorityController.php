<?php

namespace App\Http\Controllers\Initiatives\HousingUrbanDevelopment;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Initiatives\SchoolEducation\Concerns\ManagesPsrpVisits;
use App\Imports\Initiatives\HousingUrbanDevelopment\PspaVisitImport;
use App\Models\Initiative;
use App\Models\InitiativeFile;
use App\Models\Initiatives\HousingUrbanDevelopment\PspaPlant;
use App\Models\Initiatives\HousingUrbanDevelopment\PspaResponse;
use App\Models\Initiatives\HousingUrbanDevelopment\PspaVisit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

// Punjab Saaf Pani Authority (PSPA), under Housing Urban Development & Public Health Engineering. Until its
// design arrives, the page has the banner, the four uploads and the records table, as on the other
// initiative pages before theirs.
class PunjabSaafPaniAuthorityController extends Controller
{
    // Upload and CSV download work the same as on the other initiative pages.
    use ManagesPsrpVisits;

    private const TABLE = 'punjab_saaf_pani_authority';

    // Every column of our visit file, in its order. The table uses shorter headings than the file's long
    // questions; each is one the visit import knows, so the CSV download can be uploaded again. The table
    // shows the visit's date; the CSV keeps its time too.
    private const VISIT_COLUMNS = [
        'visited_at' => ['label' => 'Visit date', 'type' => 'date'],
        'district' => ['label' => 'District', 'type' => 'text'],
        'tehsil' => ['label' => 'Tehsil', 'type' => 'text'],
        'site_name' => ['label' => 'Site Name', 'type' => 'text'],
        'functionality' => ['label' => 'Functionality', 'type' => 'text'],
        'total_taps' => ['label' => 'Total water taps', 'type' => 'number'],
        'functional_taps' => ['label' => 'Functional water taps', 'type' => 'number'],
        'water_quality' => ['label' => 'Quality of water', 'type' => 'text'],
        'worker_present' => ['label' => 'Worker present', 'type' => 'text'],
        'plant_cleaned' => ['label' => 'Plant cleaned', 'type' => 'text'],
        'lacks_repair_maintenance' => ['label' => 'Lacks repair and maintenance', 'type' => 'text'],
        'non_functional_long_time' => ['label' => 'Non-functional for a long time', 'type' => 'text'],
        'non_functional_taps' => ['label' => 'Non-functional water taps', 'type' => 'text'],
        'broken_taps' => ['label' => 'Broken water taps', 'type' => 'text'],
        'broken_tiles' => ['label' => 'Broken tiles', 'type' => 'text'],
        'filter_needs_replacement' => ['label' => 'Filter needs replacement', 'type' => 'text'],
        'poor_filtered_water' => ['label' => 'Poor quality of filtered water', 'type' => 'text'],
        'electricity_available' => ['label' => 'Electricity available', 'type' => 'text'],
        'motor_pump_working' => ['label' => 'Motor pump working', 'type' => 'text'],
        'filter_working' => ['label' => 'Filter working', 'type' => 'text'],
        'odor_in_water' => ['label' => 'Odor in water', 'type' => 'text'],
        'found_at_stated_location' => ['label' => 'Found at stated location', 'type' => 'text'],
        'fenced_and_locked' => ['label' => 'Fenced and locked', 'type' => 'text'],
        'sewerage_non_functional' => ['label' => 'Sewerage non-functional', 'type' => 'text'],
        'issues_found' => ['label' => 'Issues found', 'type' => 'text'],
        'run_by_private_company' => ['label' => 'Run by a private company', 'type' => 'text'],
        'operator_unpaid' => ['label' => 'Operator unpaid', 'type' => 'text'],
        'dilapidated_building' => ['label' => 'Dilapidated building', 'type' => 'text'],
        'meter_cut_by_wapda' => ['label' => 'Meter cut off by WAPDA', 'type' => 'text'],
        'contractor_left_incomplete' => ['label' => 'Contractor left work incomplete', 'type' => 'text'],
        'tank_leak' => ['label' => 'Leak in supply tank', 'type' => 'text'],
        'shut_down_early' => ['label' => 'Shut down early', 'type' => 'text'],
        'pending_electricity_bill' => ['label' => 'Pending electricity bill', 'type' => 'text'],
        'enclosure_door_stolen' => ['label' => 'Tap enclosure door stolen', 'type' => 'text'],
        'building_dilapidated_condition' => ['label' => 'Building in dilapidated condition', 'type' => 'text'],
        'taps_stolen' => ['label' => 'Taps stolen', 'type' => 'text'],
        'bill_paid_by_residents' => ['label' => 'Bill paid by residents', 'type' => 'text'],
        'tank_has_lid' => ['label' => 'Water tank has lid', 'type' => 'text'],
        'purifying_chemical_available' => ['label' => 'Purifying chemical available', 'type' => 'text'],
        'development_work_in_progress' => ['label' => 'Development work in progress', 'type' => 'text'],
        'operator_charging_citizens' => ['label' => 'Operator charging citizens', 'type' => 'text'],
        'illegally_occupied' => ['label' => 'Illegally occupied', 'type' => 'text'],
        'taps_on_motor_no_filtration' => ['label' => 'Taps on motor, no filtration', 'type' => 'text'],
        'electricity_theft' => ['label' => 'Electricity theft', 'type' => 'text'],
        'connection_without_meter' => ['label' => 'Connection without meter', 'type' => 'text'],
        'rats_inside_plant' => ['label' => 'Rats inside plant', 'type' => 'text'],
        'key_held_by_vendor' => ['label' => 'Key held by street vendor', 'type' => 'text'],
        'cooler_instead_of_taps' => ['label' => 'Water cooler instead of taps', 'type' => 'text'],
        'dogs_obstructing_citizens' => ['label' => 'Dogs obstructing citizens', 'type' => 'text'],
        'pfa_suspended_supply' => ['label' => 'Supply suspended by Punjab Food Authority', 'type' => 'text'],
        'bore_not_working' => ['label' => 'Bore not working', 'type' => 'text'],
        'pipeline_leaking' => ['label' => 'Pipeline leaking, non-functional 15 days', 'type' => 'text'],
        'fire_damage' => ['label' => 'Fire damage', 'type' => 'text'],
        'remarks' => ['label' => 'Remarks', 'type' => 'text'],
        'images' => ['label' => 'Images', 'type' => 'text'],
        // Shown in full; a number format would round them.
        'latitude' => ['label' => 'Lat', 'type' => 'text'],
        'longitude' => ['label' => 'Long', 'type' => 'text'],
    ];

    // Everything recorded for a visit, shown when its row is opened, under the visit file's own headings.
    private const VISIT_DETAILS = [
        'site_name' => 'Site Name',
        'district' => 'District',
        'tehsil' => 'Tehsil',
        'visited_at' => 'Date',
        'functionality' => 'Functionality of filtration plant',
        'total_taps' => 'Total water taps',
        'functional_taps' => 'Functional water taps',
        'water_quality' => 'Quality of water',
        'worker_present' => 'Filtration plant worker present',
        'plant_cleaned' => 'Filtration plant cleaned',
        'lacks_repair_maintenance' => 'Water filtration plant lacks repair and maintenance',
        'non_functional_long_time' => 'Water filtration plant non-functional for a long time',
        'non_functional_taps' => 'Non-functional water taps',
        'broken_taps' => 'Broken water taps',
        'broken_tiles' => 'Broken tiles',
        'filter_needs_replacement' => 'Filter needs replacement',
        'poor_filtered_water' => 'Poor quality of filtered water',
        'electricity_available' => 'electricity available',
        'motor_pump_working' => 'Motor pump working',
        'filter_working' => 'Filter working',
        'odor_in_water' => 'Odor in water',
        'found_at_stated_location' => 'Filtration plant found at the stated location',
        'fenced_and_locked' => 'Site fenced and locked, so functionality of the plant could not be assessed',
        'sewerage_non_functional' => 'Sewerage system non-functional',
        'issues_found' => 'Issues found',
        'run_by_private_company' => 'Filtration plant run by a private company',
        'operator_unpaid' => 'Plant operator unpaid for several months',
        'dilapidated_building' => 'Dilapidated building',
        'meter_cut_by_wapda' => 'Electricity meter connection cut off by WAPDA',
        'contractor_left_incomplete' => 'Contractor left without completing the filtration plant work',
        'tank_leak' => 'Leak in water supply tank',
        'shut_down_early' => 'Plant shut down early instead of running during scheduled hours',
        'pending_electricity_bill' => 'Pending electricity bill',
        'enclosure_door_stolen' => 'Door of the tap enclosure has been stolen',
        'building_dilapidated_condition' => 'Filtration plant building in dilapidated condition',
        'taps_stolen' => 'Taps stolen by thieves',
        'bill_paid_by_residents' => 'Electricity bill paid by residents',
        'tank_has_lid' => 'Water tank has lid',
        'purifying_chemical_available' => 'chemical for purifying the water is available',
        'development_work_in_progress' => 'Development work in progress at the filtration plant',
        'operator_charging_citizens' => 'Operator was charging citizens money for clean water',
        'illegally_occupied' => 'Plant illegally occupied by an individual and not properly maintained or repaired',
        'taps_on_motor_no_filtration' => 'Water taps connected directly to the motor and no filtration system installed',
        'electricity_theft' => 'Electricity theft from the filtration plant connection',
        'connection_without_meter' => 'Direct electricity connection taken from a pole without a meter',
        'rats_inside_plant' => 'Rats found inside the plant, causing filth and highly unhygienic conditions',
        'key_held_by_vendor' => 'Plant key held by a street vendor who uses the space for storage and does not run the plant regularly',
        'cooler_instead_of_taps' => 'Water cooler installed instead of water taps',
        'dogs_obstructing_citizens' => 'Dogs kept around the plant by an individual, obstructing citizens from getting water',
        'pfa_suspended_supply' => 'Punjab Food Authority suspended water supply due to substandard water quality',
        'bore_not_working' => 'Plant bore not working properly and not drawing water, new bore needed as filter turns black within days',
        'pipeline_leaking' => 'Employee reported plant non-functional for fifteen days pipeline leaking at multiple points, with major cracks and leakage under water pressure',
        'fire_damage' => 'Fire damaged the building, equipment and other material',
        'remarks' => 'Remarks',
        'images' => 'Images',
        'latitude' => 'Lat',
        'longitude' => 'Long',
    ];

    // The form's Yes / No questions, from "Filtration plant worker present" on.
    private const YES_NO_COLUMNS = [
        'worker_present', 'plant_cleaned', 'lacks_repair_maintenance', 'non_functional_long_time', 'non_functional_taps',
        'broken_taps', 'broken_tiles', 'filter_needs_replacement', 'poor_filtered_water', 'electricity_available',
        'motor_pump_working', 'filter_working', 'odor_in_water', 'found_at_stated_location', 'fenced_and_locked',
        'sewerage_non_functional', 'issues_found', 'run_by_private_company', 'operator_unpaid', 'dilapidated_building',
        'meter_cut_by_wapda', 'contractor_left_incomplete', 'tank_leak', 'shut_down_early', 'pending_electricity_bill',
        'enclosure_door_stolen', 'building_dilapidated_condition', 'taps_stolen', 'bill_paid_by_residents', 'tank_has_lid',
        'purifying_chemical_available', 'development_work_in_progress', 'operator_charging_citizens', 'illegally_occupied',
        'taps_on_motor_no_filtration', 'electricity_theft', 'connection_without_meter', 'rats_inside_plant',
        'key_held_by_vendor', 'cooler_instead_of_taps', 'dogs_obstructing_citizens', 'pfa_suspended_supply',
        'bore_not_working', 'pipeline_leaking', 'fire_damage',
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

        return response()
            ->view('initiatives.housing_urban_development.punjab_saaf_pani_authority.show', $records + [
                'topbar' => $this->topbar($initiative),
                'files' => $initiative->filesByType(),
            ])
            ->header('Vary', self::RECORDS_HEADER);
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

    // The four cards, numbered 1 to 4 by the shared styles, counted from the uploaded files:
    // the plants on the department list, the rows of our visit file, our report PDFs and the rows of
    // the department's latest response file.
    private function topbar(Initiative $initiative): array
    {
        return [
            'title' => $initiative->name,
            'description' => 'Monitor field inspection data and implementation progress for this initiative.',
            'stats' => [
                ['label' => 'Total filtration plants', 'value' => PspaPlant::count(), 'id' => 'c-total'],
                ['label' => 'Visited by SMU', 'value' => PspaVisit::count(), 'id' => 'c-visited'],
                ['label' => 'Reports sent to department', 'value' => $initiative->files()->where('type', InitiativeFile::TYPE_OUR_REPORT)->count(), 'id' => 'c-reports'],
                ['label' => 'Department responses', 'value' => PspaResponse::count(), 'id' => 'c-replied'],
            ],
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
