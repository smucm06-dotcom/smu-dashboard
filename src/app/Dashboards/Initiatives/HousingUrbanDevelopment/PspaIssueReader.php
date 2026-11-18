<?php

namespace App\Dashboards\Initiatives\HousingUrbanDevelopment;

// The problems the Water Filtration Plants design counts, and how they are read from our visit file's
// Yes / No questions and from the "Overall Issues" text of the department response file. The SMU reports
// list the problems in the visit form's own words, often with typing slips, so the text is read with its
// spaces and punctuation taken out.
class PspaIssueReader
{
    // Each problem with its label and the problem group it is counted under, in the design's order.
    public const KEYS = [
        ['k' => 'unclean', 'l' => 'Plant not clean', 'g' => 'unclean'],
        ['k' => 'noelec', 'l' => 'No electricity', 'g' => 'power'],
        ['k' => 'disc', 'l' => 'Electricity meter or connection cut', 'g' => 'power'],
        ['k' => 'bill', 'l' => 'Electricity bill pending', 'g' => 'power'],
        ['k' => 'motor', 'l' => 'Motor or pump not working', 'g' => 'motor'],
        ['k' => 'longoff', 'l' => 'Not working for a long time', 'g' => 'notwork'],
        ['k' => 'absent', 'l' => 'Operator not present', 'g' => 'absent'],
        ['k' => 'private', 'l' => 'Run by a private company', 'g' => 'private'],
        ['k' => 'notfound', 'l' => 'Not found at the listed location', 'g' => 'notfound'],
        ['k' => 'repair', 'l' => 'Repair and maintenance lacking', 'g' => 'repair'],
        ['k' => 'leak', 'l' => 'Leak in the water tank', 'g' => 'repair'],
        ['k' => 'floor', 'l' => 'Broken floor at the tap area', 'g' => 'repair'],
        ['k' => 'nocover', 'l' => 'No cover on the water tank', 'g' => 'repair'],
        ['k' => 'contractor', 'l' => 'Contractor left the work unfinished', 'g' => 'repair'],
        ['k' => 'quality', 'l' => 'Poor water quality', 'g' => 'water'],
        ['k' => 'smell', 'l' => 'Water smells', 'g' => 'water'],
        ['k' => 'tapsnf', 'l' => 'Water taps not working', 'g' => 'taps'],
        ['k' => 'tapsbroken', 'l' => 'Water taps broken', 'g' => 'taps'],
        ['k' => 'filter', 'l' => 'Filter needs replacing', 'g' => 'filter'],
        ['k' => 'filternf', 'l' => 'Filter (coarse or fine) not working', 'g' => 'filter'],
        ['k' => 'closed', 'l' => 'Plant room and water filling area locked', 'g' => 'closed'],
        ['k' => 'resbill', 'l' => 'Residents pay the electricity bill', 'g' => 'power'],
        ['k' => 'charge', 'l' => 'Operator charging citizens for water', 'g' => 'misuse'],
        ['k' => 'tapstolen', 'l' => 'Water taps stolen', 'g' => 'taps'],
        ['k' => 'doorstolen', 'l' => 'Plant door stolen', 'g' => 'misuse'],
        ['k' => 'early', 'l' => 'Plant shut before its hours end', 'g' => 'misuse'],
        ['k' => 'salary', 'l' => 'Operator unpaid for months', 'g' => 'salary'],
        ['k' => 'dilap', 'l' => 'Plant building dilapidated', 'g' => 'repair'],
        ['k' => 'occupied', 'l' => 'Plant illegally occupied, not looked after', 'g' => 'misuse'],
        ['k' => 'dogs', 'l' => 'Dogs kept at the plant keep citizens away', 'g' => 'misuse'],
        ['k' => 'rats', 'l' => 'Rats inside the plant', 'g' => 'unclean'],
        ['k' => 'vessel', 'l' => 'Pipeline leaks and a damaged filter vessel', 'g' => 'repair'],
        ['k' => 'vendor', 'l' => 'Plant key held by a shopkeeper who uses it for storage', 'g' => 'misuse'],
        ['k' => 'nofilter', 'l' => 'Taps connected straight to the motor, no filtration', 'g' => 'water'],
        ['k' => 'pfa', 'l' => 'Water supply suspended by the Punjab Food Authority', 'g' => 'water'],
        ['k' => 'bore', 'l' => 'Bore not working, a new bore is needed', 'g' => 'motor'],
        ['k' => 'theft', 'l' => "Electricity stolen from the plant's connection", 'g' => 'power'],
        ['k' => 'cooler', 'l' => 'Water cooler installed instead of taps', 'g' => 'misuse'],
        ['k' => 'partial', 'l' => 'Plant only partly working', 'g' => 'notwork'],
        ['k' => 'other', 'l' => 'Other problem noted in the report', 'g' => 'other'],
    ];

    // Each Yes / No question of the visit form and the problem it shows. The answer that is the problem
    // is "Yes", except for the three questions that ask whether something is right.
    public const QUESTIONS = [
        'plant_unclean' => 'unclean',
        'no_electricity' => 'noelec',
        'meter_disconnected' => 'disc',
        'pending_electricity_bill' => 'bill',
        'motor_pump_non_functional' => 'motor',
        'non_functional_long' => 'longoff',
        'operator_present' => ['absent', 'No'],
        'private_company' => 'private',
        'found_at_location' => ['notfound', 'No'],
        'lacks_repair_maintenance' => 'repair',
        'tank_leakage' => 'leak',
        'broken_floor_tap_area' => 'floor',
        'tank_cover' => ['nocover', 'No'],
        'contractor_left_incomplete' => 'contractor',
        'poor_water_quality' => 'quality',
        'smell_in_water' => 'smell',
        'non_functional_taps' => 'tapsnf',
        'broken_taps' => 'tapsbroken',
        'filter_needs_replacement' => 'filter',
        'filter_non_functional' => 'filternf',
        'room_and_filling_area_closed' => 'closed',
        'bill_paid_by_residents' => 'resbill',
        'operator_charging_citizens' => 'charge',
        'taps_stolen' => 'tapstolen',
        'door_stolen' => 'doorstolen',
        'shut_down_early' => 'early',
        'operator_unpaid' => 'salary',
        'dilapidated_building' => 'dilap',
        'illegally_occupied' => 'occupied',
        'dogs_around_plant' => 'dogs',
        'rats_inside_plant' => 'rats',
        'non_operational_15_days' => 'vessel',
        'key_held_by_vendor' => 'vendor',
        'taps_on_motor_no_filtration' => 'nofilter',
        'pfa_suspended_supply' => 'pfa',
        'bore_not_working' => 'bore',
        'electricity_theft' => 'theft',
        'direct_pole_connection' => 'theft',
        'cooler_instead_of_taps' => 'cooler',
    ];

    // The problems as the SMU reports word them, matched on the text with only its letters kept, so
    // "Non- functional", "Nonfunctional" and a word broken over two lines all read the same. Small slips
    // such as "Unleanliness", "Brokon" or "fof long" are allowed for.
    private const PHRASES = [
        'unclean' => '/un?c?l?ea?nl?i?n?ess|unclean/',
        'noelec' => '/unavailab\w{0,5}of(the)?electric|noelectric|nothaveanyelectric/',
        'disc' => '/meter\w{0,3}connection\w{0,3}disconn/',
        'bill' => '/pendingelectricitybill/',
        'motor' => '/non\w{0,2}functionalmotor|motor\w{0,4}pump\w{0,4}(not|non)/',
        'longoff' => '/non\w{0,2}funct\w{0,5}(for|fof)\w{0,2}long/',
        'absent' => '/operator\w{0,3}worke?\w?r?(was)?no/',
        'private' => '/under(a)?private/',
        'notfound' => '/notfounds?/',
        'repair' => '/lackofre\w{0,4}/',
        'leak' => '/leakage/',
        'floor' => '/brokenfloor/',
        'nocover' => '/nocover/',
        'contractor' => '/contractorleft/',
        'quality' => '/poorqualit/',
        'smell' => '/smellinwater/',
        'tapsnf' => '/non\w{0,2}functionalwatertap/',
        'tapsbroken' => '/br\w{0,4}watertap/',
        'filter' => '/filterne+ds?to(be)?repl/',
        'filternf' => '/non\w{0,2}functionalfilter/',
        'closed' => '/roomand\w{0,6}filling\w{0,8}closed/',
        'resbill' => '/billpaymentby/',
        'charge' => '/chargingamount/',
        'tapstolen' => '/tapshavebeentakenaway/',
        'doorstolen' => '/doorhasbeenstolen/',
        'early' => '/shutdownearly/',
        'salary' => '/notreceivedasalary/',
        'dilap' => '/dilapidated/',
        'occupied' => '/illegallyoccupied/',
        'dogs' => '/keptseveraldogs/',
        'rats' => '/ratshavebeenfound/',
        'vessel' => '/nonoperationalforthepast/',
        'vendor' => '/keyisheldby/',
        'nofilter' => '/directlyconnectedtothemotor/',
        'pfa' => '/foodauthority/',
        'bore' => '/boreisnotfunction/',
        'theft' => '/theftofelectricity|electricitytheft/',
        'cooler' => '/watercoolerhasbeeninstalled/',
        'partial' => '/partially/',
    ];

    // Lines that say there is no problem.
    private const NOTHING = '/^(none|na|nil|plants?(is|are)functional)$/';

    private array $index;

    public function __construct()
    {
        $this->index = array_flip(array_column(self::KEYS, 'k'));
    }

    // The position of each problem in KEYS, which is how the page refers to them.
    public function index(string $key): int
    {
        return $this->index[$key];
    }

    // The problems a visit found, from its Yes / No answers, in KEYS order.
    public function visitKeys(object $visit): array
    {
        $found = [];
        foreach (self::QUESTIONS as $column => $rule) {
            [$key, $problem] = is_array($rule) ? $rule : [$rule, 'Yes'];
            if (strcasecmp(trim((string) ($visit->{$column} ?? '')), $problem) === 0) {
                $found[$key] = true;
            }
        }

        return $this->inOrder($found);
    }

    // The problems an SMU report lists, in the order it lists them. A line of its own that matches none of
    // the form's problems, and is more than a few words, is "Other problem noted in the report", last.
    public function reportKeys(?string $text): array
    {
        $text = (string) $text;
        $letters = self::letters($text);
        $at = [];

        foreach (self::PHRASES as $key => $pattern) {
            if (preg_match($pattern, $letters, $match, PREG_OFFSET_CAPTURE)) {
                $at[$key] = $match[0][1];
            }
        }
        asort($at);
        $found = array_fill_keys(array_keys($at), true);

        $lines = array_values(array_filter(array_map('trim', preg_split('/[,\n]|\.(?:\s|$)/u', $text) ?: []), fn ($line) => $line !== ''));
        foreach ($lines as $i => $line) {
            $letters = self::letters($line);
            if ($letters === '' || preg_match(self::NOTHING, $letters) || $this->readable($letters)) {
                continue;
            }
            // A problem broken over two lines is read with the lines around it.
            if ($this->readable(self::letters(($lines[$i - 1] ?? '').$line.($lines[$i + 1] ?? '')))) {
                continue;
            }
            if (count(preg_split('/\s+/u', $line)) >= 4) {
                $found['other'] = true;
            }
        }

        return array_keys($found);
    }

    // Whether a plant was giving water on the visit day: F working, N not working, X not found at the
    // listed location, I locked and could not be checked. The visit form says it in "Functionality of
    // filtration plant"; when that is blank, or for an SMU report, the problems found decide.
    public function status(?string $functionality, array $keys): string
    {
        $said = mb_strtolower(trim((string) $functionality));

        return match (true) {
            str_contains($said, 'not found') => 'X',
            str_contains($said, 'inaccessible'), str_contains($said, 'locked') => 'I',
            (bool) preg_match('/\bnon[\s-]*functional|not\s+functional/', $said) => 'N',
            str_contains($said, 'functional') => 'F',
            in_array('notfound', $keys, true) => 'X',
            in_array('closed', $keys, true) => 'I',
            in_array('longoff', $keys, true), in_array('partial', $keys, true) => 'N',
            default => 'F',
        };
    }

    private function readable(string $letters): bool
    {
        foreach (self::PHRASES as $pattern) {
            if (preg_match($pattern, $letters)) {
                return true;
            }
        }

        return false;
    }

    private function inOrder(array $found): array
    {
        return array_values(array_filter(array_column(self::KEYS, 'k'), fn ($key) => isset($found[$key])));
    }

    private static function letters(string $text): string
    {
        return (string) preg_replace('/[^a-z]/', '', mb_strtolower($text));
    }
}
