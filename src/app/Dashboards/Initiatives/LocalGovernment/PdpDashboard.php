<?php

namespace App\Dashboards\Initiatives\LocalGovernment;

use App\Models\Initiatives\LocalGovernment\PdpCity;
use App\Models\Initiatives\LocalGovernment\PdpResponse;
use App\Models\Initiatives\LocalGovernment\PdpVisit;
use Illuminate\Support\Carbon;

// Everything the Punjab Development Program design draws, worked out from the three uploaded files in the
// shape of the design's own data: the department's cities per district with each one's scope of work, every
// visit to each city with the checks that failed and its photos and videos, and the SMU reports read problem
// by problem with the department's answer to each. The page's script does the rest, for whatever district
// and dates are chosen.
class PdpDashboard
{
    // Raised when the way the figures are worked out changes, so browsers fetch them again.
    private const DATA_VERSION = 1;

    // Coordinates outside this box around Punjab are not used, as on the other pages.
    private const PUNJAB = [27.7, 34.1, 69.3, 75.5];

    // The scope of work, in the department file's order: [column => its heading].
    private const SCOPE = [
        'population' => 'Population',
        'sewer_km' => 'Sewer (km)',
        'storm_drain_km' => 'Storm Drain (km)',
        'ugsts' => 'UGSTs',
        'ugst_capacity_mg' => 'UGST Cap (MG)',
        'disposal_stations_new' => 'Disposal Station - New',
        'disposal_stations_rehab' => 'Disposal Station - Rehab',
        'wwtp_mgd' => 'WWTP (MGD)',
        'pavement_km' => 'Pavement (km)',
        'machinery' => 'Machinery',
        'solar_kw' => 'Solar (KW)',
        'street_lights' => 'Street Lights',
    ];

    // Worked out once for each data().
    private array $cities = [];

    private array $listNames = [];

    private array $districtTowns = [];

    private array $visitDistrict = [];

    public function __construct(private PdpReportReader $reader)
    {
    }

    // The district each visit counts in on the page: its city's district in the department's list, so a
    // visit typed under "TTS", or under another district, is with its city. [visit id => district]
    public function visitDistricts(): array
    {
        $this->cities = [];
        $this->listNames = [];
        $this->districtTowns = [];
        $this->visitDistrict = [];

        $this->visits($this->listCities());

        return $this->visitDistrict;
    }

    // Changes whenever any of the three files' rows change.
    public function version(): string
    {
        $parts = [self::DATA_VERSION];

        foreach ([PdpCity::class, PdpVisit::class, PdpResponse::class] as $model) {
            $table = $model::query()->toBase();
            $parts[] = (clone $table)->count();
            $parts[] = (clone $table)->max('updated_at');
        }

        return md5(implode('|', $parts));
    }

    public function data(): array
    {
        $this->cities = [];
        $this->listNames = [];
        $this->districtTowns = [];
        $this->visitDistrict = [];

        $listDistricts = $this->listCities();
        $visitDates = $this->visits($listDistricts);
        [$letters, $items] = $this->letters();

        // Every district with a city in the department's list, and any other a visited town is in.
        $districts = $listDistricts;
        foreach ($this->cities as $city) {
            if ($city['d'] !== null && $city['d'] !== '') {
                $districts[$city['d']] ??= 0;
            }
        }
        ksort($districts, SORT_NATURAL | SORT_FLAG_CASE);
        $index = array_flip(array_keys($districts));

        $listed = array_filter($this->cities, fn ($city) => $city['ml']);
        $towns = array_filter($this->cities, fn ($city) => ! $city['ml']);
        uasort($listed, fn ($a, $b) => strnatcasecmp($a['n'], $b['n']));
        uasort($towns, fn ($a, $b) => strnatcasecmp($a['n'], $b['n']));

        // Positions of the cities in the list sent, for each problem's city.
        $cities = array_values(array_map(function ($city) use ($index) {
            $city['d'] = $city['d'] === null || $city['d'] === '' ? null : $index[$city['d']];

            return $city;
        }, [...$listed, ...$towns]));

        sort($visitDates);
        $rds = array_values(array_filter(array_column($letters, 'rd')));

        return [
            'meta' => [
                'firstVisit' => $visitDates[0] ?? null,
                'lastVisit' => $visitDates ? $visitDates[count($visitDates) - 1] : null,
                'reports' => array_values(array_unique($visitDates)),
                'respLast' => $rds ? max($rds) : null,
                'scope' => array_values(self::SCOPE),
            ],
            'districts' => array_map(fn ($name, $total) => ['n' => (string) $name, 'tot' => $total], array_keys($districts), $districts),
            'keys' => PdpReportReader::KEYS,
            'nform' => count(PdpReportReader::FORM),
            'cities' => $cities,
            'letters' => $letters,
            'items' => $items,
        ];
    }

    // The design's four cards, for all of Punjab and every date, with the line under each, worked out as
    // the page's script does, so nothing changes when the page has loaded.
    public function headline(array $data): array
    {
        $total = array_sum(array_column($data['districts'], 'tot'));
        $perDistrict = [];
        $population = 0;
        $listed = 0;
        $unlisted = 0;
        $visits = 0;
        $problems = 0;
        $reports = [];
        $first = null;
        $last = null;

        foreach ($data['cities'] as $city) {
            if ($city['ml'] && $city['d'] !== null) {
                $perDistrict[$city['d']] = ($perDistrict[$city['d']] ?? 0) + 1;
                $population += $city['sc'][0] ?? 0;
            }

            if (! $city['v']) {
                continue;
            }

            $city['ml'] ? $listed++ : $unlisted++;
            $visits += count($city['v']);

            foreach ($city['v'] as $visit) {
                $problems += count($visit[1]);
                $reports[$visit[0]] = true;
                $first = $first === null || $visit[0] < $first ? $visit[0] : $first;
                $last = $last === null || $visit[0] > $last ? $visit[0] : $last;
            }
        }

        $most = $perDistrict ? max($perDistrict) : 0;
        $biggest = array_map(fn ($d) => $data['districts'][$d]['n'], array_keys(array_filter($perDistrict, fn ($count) => $count === $most)));
        sort($biggest, SORT_NATURAL | SORT_FLAG_CASE);
        $people = $population >= 1e6 ? $this->decimal($population / 1e6, 2).' million people' : number_format($population).' people';

        $reportDates = array_keys($reports);
        sort($reportDates);
        $answered = array_values(array_filter($data['letters'], fn ($letter) => isset($reports[$letter['rep']])));

        return [
            'total' => [$total, $total
                ? 'Not districts: these are towns in '.number_format(count(array_filter(array_column($data['districts'], 'tot'))))." of Punjab's 41 districts. "
                    .$this->andList($biggest).(count($biggest) === 1 ? ' has ' : ' have ').$most.' each. For '.$people
                : 'None yet'],
            'visited' => [$listed, ($listed + $unlisted)
                ? $this->percent($listed, $total).'% of the cities, in '.$this->plural($visits, 'site visit', 'site visits').' from '.$this->range($first, $last)
                    .($unlisted ? '. Also '.$this->plural($unlisted, 'town', 'towns').' not in the PDP list' : '')
                : 'None yet'],
            'reports' => [count($reportDates), $reportDates
                ? 'With '.$this->plural($problems, 'problem', 'problems').' found at the sites, '.$this->range($reportDates[0], $reportDates[count($reportDates) - 1])
                : 'No SMU visit here yet'],
            'replied' => [count($answered), $reportDates
                ? 'Of '.$this->plural(count($reportDates), 'SMU report', 'SMU reports')
                    .($answered ? ', sent '.$this->andList(array_map(fn ($letter) => number_format((int) $letter['days']), $answered)).' days after the report' : '')
                : ''],
        ];
    }

    // The department's cities, in its list's order, and how many each district has.
    private function listCities(): array
    {
        $districts = [];

        foreach (PdpCity::query()->orderBy('id')->get() as $row) {
            $name = PdpCityNames::spaced($row->city);
            $district = PdpCityNames::spaced($row->district);
            $key = $this->newKey(PdpCityNames::letters($name) ?: 'city'.$row->id);

            $districts[$district] = ($districts[$district] ?? 0) + 1;
            $this->listNames[$key] = $name;
            $this->districtTowns[$district][$key] = $name;
            $this->cities[$key] = [
                'k' => $key,
                'n' => $name !== '' ? $name : 'City not named in the list',
                'd' => $district,
                'div' => PdpCityNames::spaced($row->division) ?: null,
                'sc' => array_map(fn ($column) => $row->{$column} === null ? 0 : $row->{$column} + 0, array_keys(self::SCOPE)),
                'la' => null,
                'lo' => null,
                'ml' => 1,
                'v' => [],
                'r' => [],
            ];
        }

        return $districts;
    }

    // Each visit on its city, oldest first, with the checks that failed, its photos and videos. A city's pin
    // is where the field team recorded its latest visit with a location inside Punjab. Returns every visit date.
    private function visits(array $listDistricts): array
    {
        $districtNames = array_combine(array_map('strval', array_keys($listDistricts)), array_map('strval', array_keys($listDistricts)));
        $dates = [];

        foreach (PdpVisit::query()->orderBy('visited_at')->orderBy('id')->get() as $visit) {
            $date = $visit->visited_at->format('Y-m-d');
            $district = PdpCityNames::find($visit->district, $districtNames) ?? PdpCityNames::spaced($visit->district);
            $key = $this->cityOfVisit($visit, $district);

            $this->cities[$key]['v'][] = [
                $date,
                $this->reader->findings($visit),
                $this->links($visit->images),
                $this->links($visit->videos),
                $visit->id,
            ];

            $this->visitDistrict[$visit->id] = (string) $this->cities[$key]['d'];

            [$la, $lo] = $this->location($visit);
            if ($la !== null) {
                $this->cities[$key]['la'] = $la;
                $this->cities[$key]['lo'] = $lo;
            }

            $dates[] = $date;
        }

        return $dates;
    }

    // The department's city a visit is to: by name in its district, then by the same name anywhere in the
    // list (a visit filed under another district), and otherwise a town of its own, not in the list.
    private function cityOfVisit(PdpVisit $visit, string $district): string
    {
        $key = isset($this->districtTowns[$district]) ? PdpCityNames::find($visit->city, $this->districtTowns[$district]) : null;
        $key ??= PdpCityNames::find($visit->city, $this->listNames, false);

        if ($key !== null) {
            return $key;
        }

        $towns = array_map(fn ($city) => $city['n'], array_filter($this->cities, fn ($city) => ! $city['ml'] && $city['d'] === $district));
        $key = PdpCityNames::find($visit->city, $towns);

        return $key ?? $this->newTown(PdpCityNames::spaced($visit->city) ?: 'Site not named', $district);
    }

    // Each row of the response file as one of the design's letters, and its problems one by one:
    // [letter, city, problem, sentence, response type].
    private function letters(): array
    {
        $letters = [];
        $items = [];

        foreach (PdpResponse::query()->orderBy('smu_report_date')->orderBy('id')->get() as $row) {
            $rep = $row->smu_report_date?->format('Y-m-d');
            $rd = $row->response_letter_date?->format('Y-m-d');
            $read = $this->reader->letter($row->smu_report, $row->dept_response, fn (string $name) => $this->cityInLetter($name, $rep));
            $number = count($letters);

            foreach ($read['cities'] as $key => $city) {
                foreach ($city['items'] as [$problem, $text, $type]) {
                    $this->cities[$key]['r'][] = count($items);
                    $items[] = [$number, (string) $key, $problem, $text, $type];
                }
            }

            $letters[] = [
                'rep' => $rep,
                'rd' => $rd,
                'days' => $rep && $rd ? $this->days($rep, $rd) : null,
                'head' => $read['head'],
                'general' => $read['general'],
                'cities' => (object) array_map(fn ($city) => $city['answer'], $read['cities']),
            ];
        }

        return [$letters, $items];
    }

    // A city named in an SMU report or the answer to it: the same name in the department's list, else one
    // spelled like it among the cities visited for that report, then in the whole list, then a visited
    // town not in the list. A name found nowhere becomes a town of its own.
    private function cityInLetter(string $name, ?string $report): string
    {
        $key = PdpCityNames::find($name, $this->listNames, false);

        if ($key === null && $report !== null) {
            $visited = array_map(fn ($city) => $city['n'], array_filter($this->cities, fn ($city) => in_array($report, array_column($city['v'], 0), true)));
            $key = PdpCityNames::find($name, $visited);
        }

        $key ??= PdpCityNames::find($name, $this->listNames);
        $key ??= PdpCityNames::find($name, array_map(fn ($city) => $city['n'], array_filter($this->cities, fn ($city) => ! $city['ml'])));

        return $key ?? $this->newTown(PdpCityNames::spaced($name), null);
    }

    private function newTown(string $name, ?string $district): string
    {
        $key = $this->newKey('x-'.PdpCityNames::letters($district ?? '').'-'.PdpCityNames::letters($name));

        $this->cities[$key] = ['k' => $key, 'n' => $name, 'd' => $district, 'div' => null, 'sc' => null, 'la' => null, 'lo' => null, 'ml' => 0, 'v' => [], 'r' => []];

        return $key;
    }

    private function newKey(string $key): string
    {
        $unique = $key;
        for ($n = 2; isset($this->cities[$unique]); $n++) {
            $unique = $key.'-'.$n;
        }

        return $unique;
    }

    // The links in a photo or video cell: a Google Drive file's id, or any other link as written.
    private function links(?string $cell): array
    {
        $links = [];
        foreach (preg_split('/[\s,;]+/', (string) $cell, -1, PREG_SPLIT_NO_EMPTY) as $link) {
            if (preg_match('~^https?://(?:drive|docs)\.google\.com/.*?(?:[?&]id=|/d/)([\w-]+)~i', $link, $m)) {
                $links[] = $m[1];
            } elseif (preg_match('~^https?://~i', $link)) {
                $links[] = $link;
            }
        }

        return $links;
    }

    private function location(PdpVisit $visit): array
    {
        $la = is_numeric($visit->latitude) ? (float) $visit->latitude : null;
        $lo = is_numeric($visit->longitude) ? (float) $visit->longitude : null;
        [$south, $north, $west, $east] = self::PUNJAB;

        if ($la === null || $lo === null || $la < $south || $la > $north || $lo < $west || $lo > $east) {
            return [null, null];
        }

        return [round($la, 6), round($lo, 6)];
    }

    private function days(string $from, string $to): int
    {
        return (int) round((strtotime($to) - strtotime($from)) / 86400);
    }

    private function percent(int $part, int $whole): string|int
    {
        if (! $whole) {
            return 0;
        }
        $share = $part / $whole * 100;

        return match (true) {
            $share > 0 && $share < 0.5 => '<1',
            $share > 99.5 && $share < 100 => '>99',
            default => (int) round($share),
        };
    }

    // A number with up to this many decimal places, as the design writes it (6.99, 7, 6.9).
    private function decimal(float $value, int $places): string
    {
        $text = number_format($value, $places);

        return str_contains($text, '.') ? rtrim(rtrim($text, '0'), '.') : $text;
    }

    private function plural(int $count, string $one, string $many): string
    {
        return number_format($count).' '.($count === 1 ? $one : $many);
    }

    private function andList(array $items): string
    {
        return count($items) < 2 ? implode('', $items) : implode(', ', array_slice($items, 0, -1)).' and '.$items[count($items) - 1];
    }

    private function range(?string $from, ?string $to): string
    {
        if (! $from || ! $to) {
            return '';
        }
        $label = fn (string $date) => Carbon::parse($date)->format('M Y');

        if ($label($from) === $label($to)) {
            return $label($from);
        }

        return (substr($from, 0, 4) === substr($to, 0, 4) ? Carbon::parse($from)->format('M') : $label($from)).' to '.$label($to);
    }
}
