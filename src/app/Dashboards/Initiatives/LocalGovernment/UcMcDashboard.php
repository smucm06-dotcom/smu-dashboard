<?php

namespace App\Dashboards\Initiatives\LocalGovernment;

use App\Models\Initiatives\LocalGovernment\UcMcResponse;
use App\Models\Initiatives\LocalGovernment\UcMcUnionCouncil;
use App\Models\Initiatives\LocalGovernment\UcMcVisit;
use Illuminate\Support\Carbon;

// Everything the UC MC Office Service Delivery design draws, worked out from the three uploaded files
// in the shape of the design's own data: totals per district, offices with their visits, the
// department's responses read problem by problem, and the offices checked again after a response.
// The page's script does the rest, for whatever district and dates are chosen.
class UcMcDashboard
{
    // Raised when the way the figures are worked out changes, so browsers fetch them again.
    private const DATA_VERSION = 1;

    // A letter covers visits in the 75 days before it, a weekly report those in the 21 days before.
    private const LETTER_DAYS = 75;

    private const REPORT_DAYS = 21;

    private const ISSUE_ORDER = ['closed', 'absent', 'noserv', 'over', 'nofee', 'private', 'unclean', 'board', 'dilap'];

    private const AGENTS = ['fieldagent' => 'F', 'dfp' => 'D', 'smuteam' => 'S'];

    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    public function __construct(private UcMcReportReader $reader)
    {
    }

    // Changes whenever any of the three files' rows change.
    public function version(): string
    {
        $parts = [self::DATA_VERSION];

        foreach ([UcMcUnionCouncil::class, UcMcVisit::class, UcMcResponse::class] as $model) {
            $table = $model::query()->toBase();
            $parts[] = (clone $table)->count();
            $parts[] = (clone $table)->max('updated_at');
        }

        return md5(implode('|', $parts));
    }

    public function data(): array
    {
        $visits = UcMcVisit::query()->toBase()->orderBy('visited_at')->orderBy('id')->get();
        $responses = UcMcResponse::query()->toBase()->orderBy('id')->get();

        $fileDistricts = array_merge($visits->pluck('district')->all(), $responses->pluck('district')->all());
        $list = new UcMcOffices(UcMcUnionCouncil::query()->toBase()->orderBy('id')->get(), array_filter($fileDistricts));

        $offices = $this->offices($visits, $list);
        [$reports, $recheck] = $this->reports($responses, $offices, $list);

        foreach ($reports as $report) {
            foreach ($report['o'] as $key) {
                $offices[$key]['r'][] = $report['i'];
            }
        }

        // Every district the files name, with its offices in the list (none for a district the
        // department's list doesn't have).
        $totals = $list->totals;
        foreach ([...array_column($offices, 'd'), ...array_column($reports, 'd')] as $district) {
            $totals[$district] ??= ['u' => 0, 'm' => 0, 'dv' => null];
        }
        ksort($totals);

        $dates = $visits->map(fn ($row) => substr((string) $row->visited_at, 0, 10));

        uasort($offices, fn ($a, $b) => [$a['d'], $a['k']] <=> [$b['d'], $b['k']]);

        return [
            'meta' => [
                'firstVisit' => $dates->min(),
                'lastVisit' => $dates->max(),
                'visits' => $visits->count(),
                'ucTotal' => array_sum(array_column($totals, 'u')),
                'mcTotal' => array_sum(array_column($totals, 'm')),
                'districts' => count($totals),
            ],
            'totals' => $totals,
            'offices' => array_values($offices),
            'reports' => $reports,
            'recheck' => $recheck,
        ];
    }

    // The design's four banner cards for the whole of Punjab and every date: [value, line under it].
    // The page's script keeps them in step with the chosen district and dates, with the same rules.
    public function headline(array $data): array
    {
        $totals = $data['totals'];
        $uc = array_sum(array_column($totals, 'u'));
        $mc = array_sum(array_column($totals, 'm'));
        $total = $uc + $mc;

        // Every visit row, and the UCs and MCs visited, and those visited more than once.
        $visits = 0;
        $seen = ['U' => 0, 'M' => 0];
        $again = ['U' => 0, 'M' => 0];
        foreach ($data['offices'] as $office) {
            if (! $office['v']) {
                continue;
            }
            $visits += count($office['v']);
            $seen[$office['t']]++;
            if (count($office['v']) > 1) {
                $again[$office['t']]++;
            }
        }
        $visitedAgain = [];
        foreach (['U' => 'UCs', 'M' => 'MCs'] as $type => $name) {
            if ($seen[$type]) {
                $visitedAgain[] = $this->percent($again[$type], $seen[$type]).'% of '.$name.' ('.number_format($again[$type]).' of '.number_format($seen[$type]).')';
            }
        }

        $letters = array_values(array_filter($data['reports'], fn ($r) => $r['f'] === 'L'));
        $weekly = array_values(array_filter($data['reports'], fn ($r) => $r['f'] === 'W'));
        $cases = array_sum(array_map(fn ($r) => $r['c'][0], $data['reports']));
        $letterDates = array_values(array_filter(array_column($letters, 'ld')));
        sort($letterDates);
        $weeks = count(array_unique(array_filter(array_column($weekly, 'sd'))));
        $replied = count($letters) + count($weekly);

        return [
            'total' => [$total, number_format($uc).' union councils and '.number_format($mc).' municipal committees, in '.number_format(count($totals)).' districts'],
            'visited' => [$visits, $visitedAgain ? 'Visited more than once: '.implode(' and ', $visitedAgain) : 'None yet'],
            'reports' => [$cases, $cases
                ? 'Cases in '.$this->plural(count($letters), 'SMU letter', 'SMU letters')
                    .($letters ? ' ('.$this->range($letterDates[0] ?? null, end($letterDates) ?: null).')' : '')
                    .($weeks ? ' and '.$this->plural($weeks, 'weekly report', 'weekly reports') : '')
                : 'None in this selection'],
            'replied' => [$replied, $replied ? 'From district officers, on every letter and weekly report on file' : ''],
        ];
    }

    // Each office SMU visited, with its visits in date order.
    private function offices($visits, UcMcOffices $list): array
    {
        $offices = [];

        foreach ($visits as $row) {
            [$key, $type, $match] = $list->officeOfVisit($row);

            if (! isset($offices[$key])) {
                $offices[$key] = [
                    'k' => $key,
                    't' => $type,
                    'd' => $match['d'] ?? trim((string) $row->district),
                    // A UC's tehsil is the list's; an MC's, or an office not in the list, is the visit's.
                    'th' => $type === 'U' && $match ? $match['th'] : trim((string) $row->tehsil),
                    'no' => $type === 'U' ? $this->ucNumber($match ? $match['no'] : preg_replace('/\D/', '', (string) $row->uc_number)) : null,
                    'n' => $match['n'] ?? '',
                    'ml' => $match ? 1 : 0,
                    'la' => null,
                    'lo' => null,
                    'v' => [],
                    'r' => [],
                ];
            }

            // The office's place: its first visit with a location.
            if ($offices[$key]['la'] === null && $row->latitude !== null && $row->longitude !== null) {
                $offices[$key]['la'] = round((float) $row->latitude, 5);
                $offices[$key]['lo'] = round((float) $row->longitude, 5);
            }

            $offices[$key]['v'][] = $this->visit($row);
        }

        return $offices;
    }

    // [date, agent, open, in-charge present, serving, fee schedule shown, fee schedule followed,
    // private person, building dilapidated, clean, office board, visit id]. 1 is Yes, 0 No, null not
    // asked ("Null" or "Not applicable" in the file).
    private function visit(object $row): array
    {
        $answer = function (?string $value): ?int {
            $value = mb_strtolower(trim((string) $value));

            return $value === 'yes' ? 1 : ($value === 'no' ? 0 : null);
        };

        return [
            substr((string) $row->visited_at, 0, 10),
            self::AGENTS[UcMcOffices::norm($row->agent)] ?? '',
            $answer($row->office_open),
            $answer($row->incharge_present),
            $answer($row->service_delivery),
            $answer($row->schedule_displayed),
            $answer($row->schedule_followed),
            $answer($row->unauthorized_private_person),
            $answer($row->dilapidated_building),
            $answer($row->office_cleanliness),
            $answer($row->office_board_displayed),
            (int) $row->id,
        ];
    }

    // The response file's rows as the design reads them, and the offices checked again after each.
    private function reports($responses, array $offices, UcMcOffices $list): array
    {
        $byNumber = [];
        foreach ($offices as $key => $office) {
            if ($office['no'] !== null) {
                $byNumber[UcMcOffices::norm($office['d']).'|'.$office['no']][] = $key;
            }
        }

        $reports = [];
        $recheck = [];
        $index = 0;

        foreach ($responses as $row) {
            $index++;
            $weekly = $row->smu_report_date !== null || ($row->letter_date === null && $row->issues_identified !== null);
            $district = trim((string) $row->district);
            $text = (string) ($row->action_taken ?? '');

            $report = [
                'i' => $index,
                'f' => $weekly ? 'W' : 'L',
                'd' => $district,
                'c' => [
                    (int) $row->cases_reported,
                    (int) $row->cases_admitted,
                    (int) $row->cases_denied,
                    (int) $row->cases_justified,
                    (int) $row->cases_unaddressed,
                ],
                'x' => $text,
                'sa' => $this->reader->staffActions($text),
                'o' => [],
            ];

            if ($weekly) {
                $date = $this->dateText($row->smu_report_date ?? $row->letter_date);
                $report += ['sd' => $date, 'is' => (string) ($row->issues_identified ?? ''), 'it' => []];

                foreach ($this->reader->problems($row->issues_identified) as [$ref, $issue]) {
                    $key = $date === null ? null : $this->officeNamed($ref, $district, $date, self::REPORT_DAYS, $offices, $byNumber, $list);
                    $own = $key === null ? $ref : ($offices[$key]['t'] === 'U' ? ['U', (string) $offices[$key]['no']] : ['M', $offices[$key]['n']]);
                    [$type, $reason] = $this->reader->answer($text, $own, $issue);

                    $report['it'][] = [$key, $key === null ? ($ref[0] === 'U' ? 'UC ' : 'MC ').$ref[1] : null, $issue, $type, $reason];
                    if ($key !== null && ! in_array($key, $report['o'], true)) {
                        $report['o'][] = $key;
                    }
                }
            } else {
                $date = $this->dateText($row->letter_date);
                $report['ld'] = $date;

                if ($date !== null) {
                    $refs = array_map(fn ($n) => ['U', (string) (int) $n], array_unique($this->reader->numbers($text)));
                    foreach ($this->reader->mcNames($text) as $name) {
                        $refs[] = ['M', $name];
                    }
                    foreach ($refs as $ref) {
                        $key = $this->officeNamed($ref, $district, $date, self::LETTER_DAYS, $offices, $byNumber, $list);
                        if ($key !== null && ! in_array($key, $report['o'], true)) {
                            $report['o'][] = $key;
                        }
                    }
                }
            }

            $reports[] = $report;

            // The visit behind the report, and what it found, so the page can tell whether the problems
            // were still there at the next visit.
            foreach ($report['o'] as $key) {
                $behind = $this->visitBefore($offices[$key]['v'], $date, $weekly ? self::REPORT_DAYS : self::LETTER_DAYS);
                if ($behind === null) {
                    continue;
                }

                $issues = $weekly
                    ? array_values(array_filter(self::ISSUE_ORDER, fn ($k) => in_array($k, array_map(
                        fn ($item) => $item[2],
                        array_filter($report['it'], fn ($item) => $item[0] === $key)
                    ), true)))
                    : array_values(array_filter(self::ISSUE_ORDER, fn ($k) => $this->bad($offices[$key]['v'][$behind], $k) === true));

                $recheck[] = ['rid' => $index, 'f' => $report['f'], 'k' => $key, 'rv' => $behind, 'after' => $date, 'iss' => $issues];
            }
        }

        return [$reports, $recheck];
    }

    // The office a report names, among the district's offices with that UC number or MC name, with a
    // visit in the days before the report (the latest such visit, if more than one office has one).
    private function officeNamed(array $ref, string $district, string $date, int $days, array $offices, array $byNumber, UcMcOffices $list): ?string
    {
        if ($ref[0] === 'U') {
            $candidates = $byNumber[UcMcOffices::norm($district).'|'.$this->ucNumber($ref[1])] ?? [];
        } else {
            $key = $list->mcKey($district, $ref[1]);
            $candidates = $key !== null && isset($offices[$key]) ? [$key] : [];
        }

        $best = null;
        $bestDate = null;
        foreach ($candidates as $key) {
            $index = $this->visitBefore($offices[$key]['v'], $date, $days);
            if ($index !== null && ($bestDate === null || $offices[$key]['v'][$index][0] > $bestDate)) {
                [$best, $bestDate] = [$key, $offices[$key]['v'][$index][0]];
            }
        }

        return $best;
    }

    // The latest visit in the days up to the date, by its place in the office's visits.
    private function visitBefore(array $visits, ?string $date, int $days): ?int
    {
        if ($date === null) {
            return null;
        }

        $from = Carbon::parse($date)->subDays($days)->format('Y-m-d');
        $found = null;
        foreach ($visits as $index => $visit) {
            if ($visit[0] >= $from && $visit[0] <= $date) {
                $found = $index;
            }
        }

        return $found;
    }

    // Whether a visit found the problem: true, false, or null when it wasn't checked.
    private function bad(array $v, string $issue): ?bool
    {
        [, , $open, $present, $serving, $feeShown, $feeFollowed, $private, $dilapidated, $clean, $board] = $v;

        return match ($issue) {
            'closed' => $open === 0,
            'absent' => $open === 0 ? true : ($present === null ? null : $present === 0),
            'noserv' => $open === 0 ? true : ($serving === null ? null : $serving === 0),
            'nofee' => $feeShown === null ? null : $feeShown === 0,
            'over' => $feeFollowed === null ? null : $feeFollowed === 0,
            'private' => $private === null ? null : $private === 1,
            'dilap' => $dilapidated === null ? null : $dilapidated === 1,
            'unclean' => $clean === null ? null : $clean === 0,
            'board' => $board === null ? null : $board === 0,
            default => null,
        };
    }

    // A UC number as the list keeps it: digits without leading zeros, or the text as written.
    private function ucNumber(?string $value): int|string|null
    {
        $value = trim((string) $value);

        return $value === '' ? null : (ctype_digit($value) ? (int) $value : $value);
    }

    private function dateText(mixed $value): ?string
    {
        return $value === null ? null : substr((string) $value, 0, 10);
    }

    // The same wording as the page's script, so the banner doesn't change when the script starts.
    private function percent(int $part, int $whole): string|int
    {
        if ($whole === 0) {
            return 0;
        }

        $p = $part / $whole * 100;

        return match (true) {
            $p > 0 && $p < 0.5 => '<1',
            $p > 99.5 && $p < 100 => '>99',
            default => (int) round($p),
        };
    }

    private function plural(int $count, string $one, string $many): string
    {
        return number_format($count).' '.($count === 1 ? $one : $many);
    }

    private function monthYear(string $date): string
    {
        return self::MONTHS[(int) substr($date, 5, 2) - 1].' '.substr($date, 0, 4);
    }

    private function range(?string $from, ?string $to): string
    {
        if ($from === null || $to === null) {
            return '';
        }

        if ($this->monthYear($from) === $this->monthYear($to)) {
            return $this->monthYear($from);
        }

        $start = substr($from, 0, 4) === substr($to, 0, 4) ? self::MONTHS[(int) substr($from, 5, 2) - 1] : $this->monthYear($from);

        return $start.' to '.$this->monthYear($to);
    }
}
