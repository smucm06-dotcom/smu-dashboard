<?php

namespace App\Dashboards\Initiatives\HousingUrbanDevelopment;

use App\Models\Initiatives\HousingUrbanDevelopment\PspaPlant;
use App\Models\Initiatives\HousingUrbanDevelopment\PspaResponse;
use App\Models\Initiatives\HousingUrbanDevelopment\PspaVisit;
use Illuminate\Support\Carbon;

// Everything the Water Filtration Plants design draws, worked out from the three uploaded files in the
// shape of the design's own data: PSPA's plants per district, each visited plant with its visits, the SMU
// reports read problem by problem with the department's answer to each, and the plants still to visit.
// The page's script does the rest, for whatever district and dates are chosen.
class PspaDashboard
{
    // Raised when the way the figures are worked out changes, so browsers fetch them again.
    private const DATA_VERSION = 2;

    // A visit in the visit file is the one an SMU report is about when it falls between three days
    // before the report's first date and the day after its last.
    private const BEFORE_REPORT = 3;

    private const AFTER_REPORT = 1;

    // A later date in one report-date cell keeps the year of the date before it when Excel's fill-down
    // has moved the year on ("13.10.2025 14.10.2025 15.10.2031"), if that keeps the dates in order
    // within a month.
    private const FILL_DAYS = 31;

    // Problem groups that a later visit doesn't show.
    private const NOT_RECHECKED = ['private', 'other'];

    // Coordinates outside this box around Punjab are not used, as on the other pages.
    private const PUNJAB = [27.7, 34.1, 69.3, 75.5];

    // Worked out once for each data(): the position of each district, and the visit file's visits by
    // tehsil and site name.
    private array $districtIndex = [];

    private array $visitsByName = [];

    public function __construct(private PspaIssueReader $issues, private PspaResponseReader $responses)
    {
    }

    // Changes whenever any of the three files' rows change.
    public function version(): string
    {
        $parts = [self::DATA_VERSION];

        foreach ([PspaPlant::class, PspaVisit::class, PspaResponse::class] as $model) {
            $table = $model::query()->toBase();
            $parts[] = (clone $table)->count();
            $parts[] = (clone $table)->max('updated_at');
        }

        return md5(implode('|', $parts));
    }

    public function data(): array
    {
        $list = PspaPlant::query()->toBase()->orderBy('id')->get();
        $districts = $this->districtList($list);
        $this->districtIndex = $districts['index'];
        $this->visitsByName = PspaVisit::query()->toBase()->get(['tehsil', 'site_name', 'visited_at', 'created_at'])
            ->groupBy(fn ($visit) => self::name($visit->tehsil).'|'.self::name($visit->site_name))
            ->all();

        // PSPA's plants, by code and by name.
        $byCode = [];
        $byName = [];
        $byTehsil = [];
        foreach ($list as $position => $plant) {
            $plant->position = $position;
            $plant->d = $this->district($plant->district, $districts);
            $byCode[trim((string) $plant->code)] ??= $plant;
            $byName[$plant->d.'|'.self::name($plant->site_name)][] = $plant;
            $byTehsil[$plant->d.'|'.self::name($plant->tehsil).'|'.self::name($plant->site_name)][] = $plant;
        }

        $rows = $this->responseRows($districts, $byCode, $byName);
        $visits = $this->visitRows($districts, $byName, $byTehsil, $rows);

        // Every plant with a visit, from the visit file or from an SMU report. Its pin is where the field
        // team recorded it at its latest visit with a location in Punjab; PSPA's list is used only when the
        // visit file has none for the plant ("pl").
        $plants = [];
        $locatedOn = [];
        foreach ($visits as $visit) {
            $code = $visit['plant']->code;
            $plants[$code] ??= $this->listedPlant($visit['plant']);
            $plants[$code]['v'][] = $visit['v'];
            if ($visit['at'][0] !== null && $visit['v'][0] >= ($locatedOn[$code] ?? '')) {
                [$plants[$code]['la'], $plants[$code]['lo']] = $visit['at'];
                $plants[$code]['pl'] = 0;
                $locatedOn[$code] = $visit['v'][0];
            }
        }

        [$reports, $texts] = $this->reports($rows, $plants, $byCode);

        foreach ($plants as &$plant) {
            usort($plant['v'], fn ($a, $b) => [$a[0], $a[1] === 'R'] <=> [$b[0], $b[1] === 'R']);
        }
        unset($plant);

        $recheck = [];
        foreach ($reports as &$report) {
            $plant = &$plants[$report['k']];
            $plant['r'][] = $report['i'];

            // Checked again: SMU's first visit after the later of the report and the response. Who runs a
            // plant, and an "other" note, can't be seen at a visit, so they aren't checked again.
            $after = max(array_filter([$report['w'][1], $report['rd'], $report['seen']]));
            $checked = array_values(array_diff(array_column($report['it'], 0), self::NOT_RECHECKED));
            if ($checked) {
                $recheck[] = ['rid' => $report['i'], 'k' => $report['k'], 'after' => $after, 'g' => $checked];
            }
            unset($report['seen'], $plant);
        }
        unset($report);

        // The plants in PSPA's list with no visit yet.
        $unvisited = [];
        foreach ($list as $plant) {
            if (! isset($plants[$plant->code])) {
                [$la, $lo] = $this->location($plant);
                $unvisited[] = [$plant->code, (string) $plant->site_name, (string) $plant->tehsil, $districts['index'][$plant->d], $la, $lo];
            }
        }

        $dates = array_column(array_column($visits, 'v'), 0);
        $first = array_column($reports, 'w');
        $responded = array_filter(array_column($reports, 'rd'));

        uasort($plants, fn ($a, $b) => [$a['d'], $a['ml'] ? 0 : 1, $a['o']] <=> [$b['d'], $b['ml'] ? 0 : 1, $b['o']]);

        return [
            'meta' => [
                'firstVisit' => $dates ? min($dates) : null,
                'lastVisit' => $dates ? max($dates) : null,
                'repFirst' => $first ? min(array_column($first, 0)) : null,
                'repLast' => $first ? max(array_column($first, 1)) : null,
                'respLast' => $responded ? max($responded) : null,
                'visits' => count($visits),
            ],
            'districts' => $districts['rows'],
            'keys' => PspaIssueReader::KEYS,
            'texts' => $texts,
            'plants' => array_values(array_map(function ($plant) {
                unset($plant['o']);

                return $plant;
            }, $plants)),
            'unvisited' => $unvisited,
            'reports' => $reports,
            'recheck' => $recheck,
        ];
    }

    // The design's four banner cards for the whole of Punjab and every date: [value, line under it].
    // The page's script keeps them in step with the chosen district and dates, with the same rules.
    public function headline(array $data): array
    {
        $total = array_sum(array_column($data['districts'], 'tot'));
        $listed = 0;
        $unlisted = 0;
        $first = null;
        $last = null;
        foreach ($data['plants'] as $plant) {
            if (! $plant['v']) {
                continue;
            }
            $plant['ml'] ? $listed++ : $unlisted++;
            $dates = array_column($plant['v'], 0);
            $first = $first === null ? min($dates) : min($first, min($dates));
            $last = $last === null ? max($dates) : max($last, max($dates));
        }

        $reports = $data['reports'];
        $reported = count(array_unique(array_column($reports, 'k')));
        $problems = array_sum(array_map(fn ($report) => count($report['it']), $reports));
        $answered = count(array_filter($reports, fn ($report) => $report['x'] !== null || $report['na']));
        $gaps = [];
        foreach ($reports as $report) {
            if ($report['rd'] && empty($report['rdx'])) {
                $gap = $this->days($report['w'][1], $report['rd']);
                if ($gap >= 0) {
                    $gaps[] = $gap;
                }
            }
        }
        $usual = $this->median($gaps);
        $from = $reports ? min(array_column(array_column($reports, 'w'), 0)) : null;
        $to = $reports ? max(array_column(array_column($reports, 'w'), 1)) : null;

        return [
            'total' => [$total, "In PSPA's list, in ".number_format(count($data['districts'])).' districts'],
            'visited' => [$listed, ($listed + $unlisted)
                ? $this->percent($listed, $total).'% of total plants, visited from '.$this->range($first, $last)
                    .($unlisted ? '. Also '.$this->plural($unlisted, 'reported plant', 'reported plants').' not in the list' : '')
                : 'None yet'],
            'reports' => [$reported, $reports
                ? 'Plants, with '.$this->plural($problems, 'problem', 'problems').' in SMU reports from '.$this->range($from, $to)
                : 'None in this selection'],
            'replied' => [$answered, $reports
                ? 'Of '.$this->plural(count($reports), 'plant report', 'plant reports').($usual !== null ? ', usually '.$this->plural($usual, 'day', 'days').' after the report' : '')
                : ''],
        ];
    }

    // Every district in PSPA's list with its plants, and any other district the files name.
    private function districtList($list): array
    {
        $rows = [];
        foreach ($list as $plant) {
            $name = self::spaced($plant->district);
            if ($name === '') {
                continue;
            }
            $rows[$name] ??= ['n' => $name, 'tot' => 0, 'div' => self::spaced($plant->division) ?: null, 'reg' => self::spaced($plant->region) ?: null];
            $rows[$name]['tot']++;
        }

        $tehsils = [];
        foreach ($list as $plant) {
            $tehsils[self::spelling($plant->tehsil)] ??= self::spaced($plant->district);
        }

        $districts = ['rows' => $rows, 'tehsils' => $tehsils];
        foreach ([...PspaVisit::query()->toBase()->distinct()->pluck('district'), ...PspaResponse::query()->toBase()->distinct()->pluck('district')] as $name) {
            $name = $this->district($name, $districts);
            if ($name !== '' && ! isset($districts['rows'][$name])) {
                $districts['rows'][$name] = ['n' => $name, 'tot' => 0, 'div' => null, 'reg' => null];
            }
        }

        ksort($districts['rows']);
        $districts['names'] = array_keys($districts['rows']);
        $districts['index'] = array_flip($districts['names']);
        $districts['rows'] = array_values($districts['rows']);

        return $districts;
    }

    // A district as PSPA's list spells it. Spaces, case, punctuation and doubled letters don't matter
    // ("Toba Tek\nSingh", "Muree"); a tehsil in the list stands for its district ("Samundari"); and a
    // shortened name whose words are all in one district is that district ("Dera Khan"). Anything else
    // is kept as typed.
    private function district(mixed $value, array $districts): string
    {
        $name = self::spaced($value);
        if ($name === '') {
            return '';
        }

        $names = $districts['names'] ?? array_keys($districts['rows']);
        $spelling = self::spelling($name);
        foreach ($names as $known) {
            if (self::spelling($known) === $spelling) {
                return $known;
            }
        }

        if (isset($districts['tehsils'][$spelling])) {
            return $districts['tehsils'][$spelling];
        }

        $words = self::words($name);
        $within = array_values(array_filter($names, fn ($known) => ! array_diff($words, self::words($known))));

        return count($within) === 1 ? $within[0] : $name;
    }

    // Each response row with its district, report dates and plant.
    private function responseRows(array $districts, array $byCode, array $byName): array
    {
        $rows = [];
        $resolved = [];

        foreach (PspaResponse::query()->toBase()->orderBy('id')->get() as $position => $row) {
            $district = $this->district($row->district, $districts);
            $window = $this->window($row->smu_report_date);
            $code = trim((string) $row->plant_code);
            $plant = $byCode[$code] ?? null;

            // The plant's code, unless it belongs to another district; then the site name in the
            // district. A name shared by several plants goes to the one SMU visited in the report's dates,
            // or the one an earlier row of that name went to, or else the first in PSPA's list.
            if ($plant && $plant->d === $district) {
                $key = $plant->code;
            } else {
                $name = $district.'|'.self::name($row->site_name);
                $same = $byName[$name] ?? [];
                $key = match (true) {
                    ! $same => 'X:'.$district.':'.self::name($row->site_name),
                    count($same) === 1 => $same[0]->code,
                    default => null,
                };
                if ($key === null) {
                    $key = $resolved[$name] ?? null;
                    if ($key === null && $window) {
                        $seen = array_values(array_filter($same, fn ($candidate) => $this->visitedIn($candidate, $window)));
                        if ($seen) {
                            $key = $resolved[$name] = $seen[0]->code;
                        }
                    }
                    $key ??= $same[0]->code;
                }
            }

            $rows[] = [
                'position' => $position,
                'row' => $row,
                'k' => $key,
                'd' => $district,
                'w' => $window,
                'code' => $plant ? $plant->code : null,
            ];
        }

        return $rows;
    }

    // Whether the visit file has a visit to this plant in the dates of a report, by its site name.
    private function visitedIn(object $plant, array $window): bool
    {
        foreach ($this->visitsByName[self::name($plant->tehsil).'|'.self::name($plant->site_name)] ?? [] as $visit) {
            if ($this->near($this->visitDate($visit), $window)) {
                return true;
            }
        }

        return false;
    }

    // Each row of the visit file, on its plant in PSPA's list. The visit file has no plant code, so a
    // visit is matched on its district, tehsil and site name. When the name is shared by two or more
    // plants in the tehsil, the visit goes to the plant an SMU report about it names by code, or else
    // to the next one of that name in the list. A site the list doesn't have is a plant of its own,
    // "not in PSPA's list", at the location the visit gives.
    private function visitRows(array $districts, array $byName, array $byTehsil, array $responses): array
    {
        $reported = [];
        foreach ($responses as $response) {
            if ($response['code'] !== null && $response['w']) {
                $reported[$response['code']][] = $response['w'];
            }
        }

        $rows = PspaVisit::query()->toBase()->orderBy('source_sr')->orderBy('id')->get();
        $found = [];
        $taken = [];

        foreach ($rows as $i => $row) {
            $district = $this->district($row->district, $districts);
            $name = self::name($row->site_name);
            if ($district === '' || $name === '') {
                continue;
            }
            $found[$i] = $byTehsil[$district.'|'.self::name($row->tehsil).'|'.$name] ?? $byName[$district.'|'.$name] ?? [];
        }

        $placed = [];
        foreach ($found as $i => $same) {
            if (count($same) === 1) {
                $placed[$i] = $same[0];
            } elseif ($same === []) {
                $district = $this->district($rows[$i]->district, $districts);
                $placed[$i] = (object) [
                    'code' => 'X:'.$district.':'.self::name($rows[$i]->site_name),
                    'site_name' => self::spaced($rows[$i]->site_name),
                    'tehsil' => self::spaced($rows[$i]->tehsil),
                    'd' => $district,
                    'latitude' => null,
                    'longitude' => null,
                    'position' => PHP_INT_MAX,
                    'unlisted' => true,
                ];
            }
        }
        foreach ($found as $i => $same) {
            if (count($same) < 2) {
                continue;
            }
            $date = $this->visitDate($rows[$i]);
            foreach ($same as $plant) {
                if (! isset($taken[$plant->code]) && array_filter($reported[$plant->code] ?? [], fn ($window) => $this->near($date, $window))) {
                    $placed[$i] = $plant;
                    $taken[$plant->code] = true;
                    break;
                }
            }
        }
        foreach ($found as $i => $same) {
            if (count($same) < 2 || isset($placed[$i])) {
                continue;
            }
            $free = array_values(array_filter($same, fn ($plant) => ! isset($taken[$plant->code])));
            $placed[$i] = $free[0] ?? $same[0];
            $taken[$placed[$i]->code] = true;
        }

        $visits = [];
        foreach ($rows as $i => $row) {
            if (! isset($placed[$i])) {
                continue;
            }
            $keys = $this->issues->visitKeys($row);
            $total = $row->total_taps === null ? null : (int) $row->total_taps;
            $working = $row->functional_taps === null ? null : (int) $row->functional_taps;
            if ($total !== null && $working !== null) {
                $working = min($working, $total);
            }
            $photos = $this->photos($row->images);

            $visits[] = [
                'plant' => $placed[$i],
                'at' => $this->location($row),
                'v' => [
                    $this->visitDate($row),
                    'V',
                    $this->issues->status($row->functionality, $keys),
                    $total,
                    $working,
                    array_map(fn ($key) => $this->issues->index($key), $keys),
                    $photos,
                    null,
                    (int) $row->id,
                ],
            ];
        }

        return $visits;
    }

    // The photos in a visit's "Images" cell, which lists links separated by commas: the file id of a
    // Google Drive link, so the page can show the photo small and open it, or else the link as written.
    private function photos(?string $cell): array
    {
        $photos = [];
        foreach (preg_split('/[\s,;]+/', (string) $cell, -1, PREG_SPLIT_NO_EMPTY) as $link) {
            if (preg_match('~^https?://(?:drive|docs)\.google\.com/.*?(?:[?&]id=|/d/)([\w-]+)~i', $link, $m)) {
                $photos[] = $m[1];
            } elseif (preg_match('~^https?://~i', $link)) {
                $photos[] = $link;
            }
        }

        return $photos;
    }

    // The SMU reports: repeated rows (the same plant and report dates) are one report. A report is
    // the visit SMU made in its dates when the visit file has one; otherwise it counts as a visit of its
    // own, with the problems it lists.
    private function reports(array $rows, array &$plants, array $byCode): array
    {
        $groups = [];
        foreach ($rows as $row) {
            if (! $row['w']) {
                continue;
            }
            $groups[$row['k'].'|'.implode('|', $row['w'])][] = $row;
        }

        // In date order, by the last report date.
        uasort($groups, fn ($a, $b) => [$a[0]['w'][1], $a[0]['position']] <=> [$b[0]['w'][1], $b[0]['position']]);

        $texts = [];
        $reports = [];
        foreach ($groups as $group) {
            $first = $group[0];
            $key = $first['k'];
            $window = $first['w'];

            $keys = [];
            $said = [];
            $date = null;
            $na = false;
            foreach ($group as $row) {
                foreach ($this->issues->reportKeys($row['row']->overall_issues) as $issue) {
                    $keys[$issue] = true;
                }
                $text = $this->responseText($row, $rows);
                $dated = $row['row']->response_date ? substr((string) $row['row']->response_date, 0, 10) : null;
                // "N/A", or a response date with an empty cell, is an answer that skips every point.
                // "None" is no response yet.
                if (self::notApplicable($text) || ($dated !== null && trim((string) $text) === '')) {
                    $na = true;
                } elseif (! self::blankText($text)) {
                    $said[trim((string) $text)] = true;
                }
                $date ??= $dated;
            }
            $keys = array_keys($keys);

            // Repeated rows can each carry the department's words; all of them are kept.
            $text = $said ? implode("\n\n", array_keys($said)) : null;
            if ($text !== null) {
                $texts[$text] ??= count($texts);
            }
            $na = $na && $text === null;

            // The report's problem groups, in the order of their first problem, with the department's answer.
            $agency = $text === null ? null : $this->responses->agency($text);
            $items = [];
            foreach ($keys as $issue) {
                $kind = PspaIssueReader::KEYS[$this->issues->index($issue)]['g'];
                if (! isset($items[$kind])) {
                    $items[$kind] = match (true) {
                        $text === null => $na ? 'notans' : 'none',
                        $agency !== null => 'notpspa',
                        default => $this->responses->type($text, $kind),
                    };
                }
            }

            $plants[$key] ??= isset($byCode[$key]) ? $this->listedPlant($byCode[$key]) : $this->unlistedPlant($key, $first);
            $seen = $this->reportVisit($plants[$key]['v'], $window);
            if ($seen === null) {
                $plants[$key]['v'][] = [$window[0], 'R', $this->issues->status(null, $keys), null, null, array_map(fn ($issue) => $this->issues->index($issue), $keys), [], $window[1], null];
            }

            $report = [
                'i' => count($reports),
                'k' => $key,
                'w' => $window,
                'rd' => $date,
                'x' => $text === null ? null : $texts[$text],
                'na' => $na ? 1 : 0,
                'keys' => array_map(fn ($issue) => $this->issues->index($issue), $keys),
                'it' => array_map(fn ($group, $type) => [$group, $type], array_keys($items), $items),
                'sr' => array_map(fn ($row) => $row['row']->source_sr, $group),
                'seen' => $seen,
            ];
            if ($seen !== null) {
                $report['same'] = 1;
            }
            if ($agency !== null) {
                $report['ag'] = $agency;
            }
            if ($date !== null && $date < $window[0]) {
                $report['rdx'] = 1;
            }
            $reports[] = $report;
        }

        return [$reports, array_keys($texts)];
    }

    // The visit file's visit a report is about, by its date, or null.
    private function reportVisit(array $visits, array $window): ?string
    {
        $match = null;
        foreach ($visits as $visit) {
            if ($visit[1] === 'V' && $this->near($visit[0], $window)) {
                $match = $match === null ? $visit[0] : max($match, $visit[0]);
            }
        }

        return $match;
    }

    // A response that only says "Same as serial No. 2" is the response of that row, in the same report.
    private function responseText(array $row, array $rows): ?string
    {
        $text = $row['row']->action_taken;
        if (! preg_match('/^\s*same\s+as\s+(?:serial|sr)\.?\s*(?:no\.?|number)?\s*(\d+)\s*\.?\s*$/i', (string) $text, $m)) {
            return $text;
        }

        foreach ($rows as $other) {
            if ($other !== $row && $other['w'] === $row['w'] && (int) $other['row']->source_sr === (int) $m[1]) {
                return $other['row']->action_taken;
            }
        }

        return $text;
    }

    private function listedPlant(object $plant): array
    {
        [$la, $lo] = $this->location($plant);

        return [
            'k' => $plant->code,
            'n' => (string) $plant->site_name,
            't' => (string) $plant->tehsil,
            'd' => $this->districtIndex($plant->d),
            'la' => $la,
            'lo' => $lo,
            'pl' => $la === null ? 0 : 1,
            'ml' => empty($plant->unlisted) ? 1 : 0,
            'v' => [],
            'r' => [],
            'o' => $plant->position,
        ];
    }

    // A plant named in an SMU report that isn't in PSPA's list. It has no location.
    private function unlistedPlant(string $key, array $row): array
    {
        return [
            'k' => $key,
            'n' => self::spaced($row['row']->site_name),
            't' => '',
            'd' => $this->districtIndex($row['d']),
            'la' => null,
            'lo' => null,
            'pl' => 0,
            'ml' => 0,
            'v' => [],
            'r' => [],
            'o' => $row['position'],
        ];
    }

    private function districtIndex(string $district): int
    {
        return $this->districtIndex[$district];
    }

    // A location from PSPA's list or the visit file, when it is inside Punjab.
    private function location(object $plant): array
    {
        $la = is_numeric($plant->latitude) ? (float) $plant->latitude : null;
        $lo = is_numeric($plant->longitude) ? (float) $plant->longitude : null;
        [$south, $north, $west, $east] = self::PUNJAB;

        if ($la === null || $lo === null || $la < $south || $la > $north || $lo < $west || $lo > $east) {
            return [null, null];
        }

        return [round($la, 6), round($lo, 6)];
    }

    // The dates of an SMU report: every date in the cell, from the first to the last.
    private function window(?string $text): ?array
    {
        preg_match_all('/(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})/', (string) $text, $m, PREG_SET_ORDER);

        $dates = [];
        foreach ($m as [, $day, $month, $year]) {
            if (! checkdate((int) $month, (int) $day, (int) $year)) {
                continue;
            }
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $previous = end($dates);
            if ($previous !== false && substr($date, 0, 4) !== substr($previous, 0, 4)) {
                $sameYear = substr($previous, 0, 4).substr($date, 4);
                if (checkdate((int) $month, (int) $day, (int) substr($previous, 0, 4)) && $sameYear >= $previous && $this->days($previous, $sameYear) <= self::FILL_DAYS) {
                    $date = $sameYear;
                }
            }
            $dates[] = $date;
        }

        return $dates ? [min($dates), max($dates)] : null;
    }

    // A visit's date. A date after the day the row was first saved can't be right: it is the day and
    // month typed the other way round ("9/12/2026" for 12 September), when that gives a date before then.
    private function visitDate(object $visit): string
    {
        $date = substr((string) $visit->visited_at, 0, 10);
        $saved = substr((string) ($visit->created_at ?? ''), 0, 10);

        if ($saved !== '' && $date > $saved) {
            [$year, $month, $day] = array_map('intval', explode('-', $date));
            if ($day <= 12 && checkdate($day, $month, $year)) {
                $swapped = sprintf('%04d-%02d-%02d', $year, $day, $month);
                if ($swapped <= $saved) {
                    return $swapped;
                }
            }
        }

        return $date;
    }

    private function near(string $date, array $window): bool
    {
        return $this->days($window[0], $date) >= -self::BEFORE_REPORT && $this->days($window[1], $date) <= self::AFTER_REPORT;
    }

    private function days(string $from, string $to): int
    {
        return (int) Carbon::parse($from)->diffInDays(Carbon::parse($to), false);
    }

    private function median(array $values): ?int
    {
        if (! $values) {
            return null;
        }
        sort($values);
        $middle = intdiv(count($values), 2);

        return count($values) % 2 ? $values[$middle] : (int) round(($values[$middle - 1] + $values[$middle]) / 2);
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

    private function plural(int $count, string $one, string $many): string
    {
        return number_format($count).' '.($count === 1 ? $one : $many);
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

    private static function blankText(?string $text): bool
    {
        return (bool) preg_match('/^\s*(none|nil|-+|\.)?\s*$/i', (string) $text);
    }

    private static function notApplicable(?string $text): bool
    {
        return (bool) preg_match('/^\s*(n\s*\/?\s*a\.?|0)\s*$/i', (string) $text);
    }

    // A site name with only its letters and digits, for matching.
    private static function name(mixed $value): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', mb_strtolower((string) $value));
    }

    // A district or tehsil for matching: its letters and digits, with doubled letters written once.
    private static function spelling(mixed $value): string
    {
        return (string) preg_replace('/(.)\1+/', '$1', self::name($value));
    }

    private static function words(string $value): array
    {
        preg_match_all('/[a-z]+/', mb_strtolower($value), $m);

        return array_unique($m[0]);
    }

    private static function spaced(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $value));
    }
}
