<?php

namespace App\Dashboards\Initiatives\SpecialEducation;

use App\Models\Initiatives\SpecialEducation\SpecialEducationInstitute;
use App\Models\Initiatives\SpecialEducation\SpecialEducationResponse;
use App\Models\Initiatives\SpecialEducation\SpecialEducationVisit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

// Joins the department's list, our visits and the department's replies, one entry per institute,
// in the shape the findings design reads.
class SpecialEducationDashboard
{
    // Change this when the shape of the data changes, so browsers don't keep the old copy.
    private const DATA_VERSION = 2;

    // Changes whenever any of the sources change.
    public function version(): string
    {
        $parts = [self::DATA_VERSION];

        foreach ([SpecialEducationInstitute::class, SpecialEducationVisit::class, SpecialEducationResponse::class] as $model) {
            $table = $model::query()->toBase();
            $parts[] = (clone $table)->count();
            $parts[] = (clone $table)->max('updated_at');
        }

        return md5(implode('|', $parts));
    }

    public function data(): array
    {
        $schools = [];
        $byCode = [];

        foreach (SpecialEducationInstitute::query()->toBase()->orderBy('id')->get() as $listed) {
            $this->addSchool($schools, $byCode, $listed);
        }

        $visits = SpecialEducationVisit::query()->toBase()->orderBy('visited_at')->orderBy('id')->get();

        foreach ($visits as $visit) {
            // Visited institutes missing from the department's list still count as visited.
            $index = $this->schoolIndex($schools, $byCode, (string) $visit->emis_code, $visit->district)
                ?? $this->addSchool($schools, $byCode, $visit);

            $schools[$index]['v'][] = $this->visit($visit);
        }

        foreach ($schools as &$school) {
            if ($school['la'] === null || $school['lo'] === null) {
                // The list has no location for this one, so the latest visit's is used.
                $located = $visits->last(fn ($visit) => (string) $visit->emis_code === $school['e']
                    && $this->coordinate($visit->latitude, 27.7, 34.1) !== null
                    && $this->coordinate($visit->longitude, 69.3, 75.5) !== null);

                [$school['la'], $school['lo']] = $located === null ? [null, null]
                    : [$this->coordinate($located->latitude, 27.7, 34.1), $this->coordinate($located->longitude, 69.3, 75.5)];
            }
        }
        unset($school);

        $texts = [];
        $extra = $this->attachReports($schools, $byCode, $this->reports($texts));

        return [
            'schools' => $schools,
            'extra' => $extra,
            'rtext' => array_keys($texts),
            'cats' => array_map(
                fn ($id, $category) => ['id' => $id, 'label' => $category['label']],
                array_keys(SpecialEducationReplyClassifier::CATEGORIES),
                SpecialEducationReplyClassifier::CATEGORIES
            ),
            'meta' => $this->meta($visits),
        ];
    }

    // The five cards in the banner, for all of Punjab. The page's script keeps them in step
    // with its filters, with the same wording.
    public function headline(array $data): array
    {
        $schools = $data['schools'];
        $visited = array_filter($schools, fn ($school) => $school['v'] !== []);

        $dates = [];
        $reports = [];
        foreach ($schools as $school) {
            array_push($dates, ...array_column($school['v'], 'dt'));

            foreach ($school['r'] as $report) {
                $reports[$report['id']] = $report;
            }
        }
        $visitCount = count($dates);
        foreach ($data['extra'] as $report) {
            $reports[$report['id']] = $report;
        }

        $sent = array_filter(array_column($reports, 'sd'));
        $replied = array_filter($reports, fn ($report) => $this->answered($report));
        $lags = array_map(
            fn ($report) => $this->days($report['sd'], $report['rd']),
            array_filter($replied, fn ($report) => $report['sd'] && $report['rd'])
        );

        $gaps = [];
        foreach ($schools as $school) {
            $open = array_values(array_filter($school['v'], fn ($visit) => empty($visit['cl'])));
            if (count($open) > 1) {
                $gaps[] = max(1, (int) round($this->days($open[0]['dt'], end($open)['dt']) / 30.44));
            }
        }

        $total = count($schools);
        $reportCount = count($reports);
        $lag = $this->median($lags);

        return [
            // The user asked for this card to show the count only, with no line under it.
            'total' => [$total, null],
            'visited' => [count($visited), $visitCount === 0 ? 'None yet' : $this->percent(count($visited), $total).'% of total schools, '
                .$this->plural($visitCount, 'visit', 'visits').' from '.$this->month(min($dates)).' to '.$this->month(max($dates))],
            'reports' => [$reportCount, $reportCount === 0 ? 'None in this selection' : 'Covering '
                .$this->plural(count(array_filter($schools, fn ($school) => $school['r'] !== [])), 'school', 'schools')
                .($sent === [] ? '' : ', '.$this->month(min($sent)).' to '.$this->month(max($sent)))],
            'replied' => [count($replied), $reportCount === 0 ? '' : ($lag === null ? '' : 'Usually within '.$this->plural($lag, 'day', 'days').'. ')
                .number_format($reportCount - count($replied)).' with no response'],
            'revisit' => [count($gaps), $gaps === [] ? 'None in this selection' : 'Checked again '
                .(min($gaps) === max($gaps) ? min($gaps) : min($gaps).' to '.max($gaps)).' months later'],
        ];
    }

    // One institute from the department's list, or from a visit when it is not on the list.
    private function addSchool(array &$schools, array &$byCode, object $row): int
    {
        $emis = (string) $row->emis_code;
        $district = (string) ($row->district ?? '');
        $index = count($schools);

        // The list gives one code to two institutes in different districts. The second one
        // gets its district added to tell them apart on the page.
        $key = $emis;
        if (isset($byCode[$emis])) {
            $key = $emis.'-'.strtoupper(substr((string) preg_replace('/[^A-Za-z]/', '', $district), 0, 3) ?: (string) $index);
        }

        $byCode[$emis][] = $index;
        $schools[] = [
            'k' => $key,
            'e' => $emis,
            'n' => trim((string) preg_replace('/\s+/u', ' ', (string) ($row->institution_name ?? ''))) ?: 'Name not recorded',
            'd' => $district,
            'z' => (string) ($row->zone ?? ''),
            'la' => $this->coordinate($row->latitude ?? null, 27.7, 34.1),
            'lo' => $this->coordinate($row->longitude ?? null, 69.3, 75.5),
            'v' => [],
            'r' => [],
        ];

        return $index;
    }

    // The institute with this code, and in this district when the code is shared.
    private function schoolIndex(array $schools, array $byCode, string $emis, ?string $district): ?int
    {
        $found = $byCode[$emis] ?? [];

        if (count($found) <= 1) {
            return $found[0] ?? null;
        }

        foreach ($found as $index) {
            if (strcasecmp($schools[$index]['d'], (string) $district) === 0) {
                return $index;
            }
        }

        return $found[0];
    }

    private function visit(object $visit): array
    {
        $flag = fn ($value) => $value === null ? null : (int) (bool) $value;
        $count = fn ($value) => $value === null ? null : (int) $value;
        $transport = mb_strtolower(trim((string) $visit->free_transport));

        $row = [
            // What the map's download sends back for the schools it shows.
            'id' => (int) $visit->id,
            'dt' => Carbon::parse($visit->visited_at)->toDateString(),
            'sr' => $count($visit->source_sr),
            'pr' => $flag($visit->principal_present),
            'tt' => $count($visit->total_teachers),
            'tp' => $count($visit->present_teachers),
            'pt' => $count($visit->total_psychologists),
            'pp' => $count($visit->present_psychologists),
            'at' => $count($visit->total_admin_staff),
            'ap' => $count($visit->present_admin_staff),
            'dv' => $flag($visit->bus_driver_post_vacant),
            'en' => $count($visit->students_enrolled),
            'sp' => $count($visit->students_present),
            'fee' => $flag($visit->fee_charged),
            'sti' => $flag($visit->monthly_stipend),
            'cr' => $count($visit->total_classrooms),
            'wr' => $count($visit->total_washrooms),
            'sep' => $flag($visit->separate_classroom_per_grade),
            'wc' => $visit->washroom_cleanliness ?: null,
            'cc' => $visit->classroom_cleanliness ?: null,
            'bk' => $flag($visit->free_books),
            'un' => $flag($visit->free_uniform),
            'tr' => $transport === '' ? null : ($transport === 'yes' ? 1 : 0),
            'fu' => $flag($visit->furniture_available),
            'fa' => $flag($visit->first_aid_available),
            'wa' => $flag($visit->clean_drinking_water_available),
            'bw' => $flag($visit->boundary_wall_available),
        ];

        // "Transport service not available": the institute has no transport to give free.
        if (str_contains($transport, 'not available')) {
            $row['trn'] = 1;
        }

        // No students at all on a school day: the institute was closed on the visit day.
        // The visit stays on record but none of its figures are counted.
        if ($visit->students_enrolled > 0 && $visit->students_present !== null && (int) $visit->students_present === 0) {
            $row['cl'] = 1;
        }

        return $row;
    }

    // Each institute's block of rows in a response file is one report. Each row with an issue
    // starts an item; a row with only a reply adds to the item above it.
    private function reports(array &$texts): array
    {
        $reports = [];
        $current = null;
        $groupKey = null;

        foreach (SpecialEducationResponse::query()->toBase()->orderBy('id')->get() as $row) {
            $key = implode('|', [$row->initiative_file_id, $row->source_sr, $row->district, $row->institution_name]);

            if ($key !== $groupKey) {
                if ($current !== null) {
                    $reports[] = $current;
                }

                $groupKey = $key;
                $current = [
                    'sr' => $row->source_sr === null ? null : (int) $row->source_sr,
                    'district' => $row->district,
                    'named' => $row->institution_name !== null,
                    'emis' => null,
                    'sd' => null,
                    'rd' => null,
                    'items' => [],
                ];
            }

            $current['emis'] ??= $row->emis_code;
            $current['sd'] ??= $row->reported_at === null ? null : Carbon::parse($row->reported_at)->toDateString();
            $current['rd'] ??= $row->responded_at === null ? null : Carbon::parse($row->responded_at)->toDateString();

            // Issues often start or end with the comma that separated them in the report.
            $issue = (string) preg_replace('/^[\s,]+|[\s,]+$/u', '', (string) $row->issue);
            $reply = trim((string) $row->response);

            if ($issue !== '' || $current['items'] === []) {
                $current['items'][] = ['i' => $issue, 'replies' => []];
            }

            if ($reply !== '') {
                $current['items'][array_key_last($current['items'])]['replies'][] = $reply;
            }
        }

        if ($current !== null) {
            $reports[] = $current;
        }

        foreach ($reports as $id => &$report) {
            $report['id'] = $id + 1;
            $report['it'] = array_map(function ($item) use (&$texts) {
                $indexes = [];
                foreach ($item['replies'] as $reply) {
                    $texts[$reply] ??= count($texts);
                    $indexes[] = $texts[$reply];
                }

                return [
                    'i' => $item['i'],
                    'c' => SpecialEducationReplyClassifier::categories($item['i']),
                    't' => SpecialEducationReplyClassifier::type($item['replies'], $item['i']),
                    'x' => $indexes,
                ];
            }, $report['items']);
            unset($report['items']);
        }
        unset($report);

        return $reports;
    }

    // Puts each report on its institute. Returns the reports that name none, which count in
    // Punjab's totals only.
    private function attachReports(array &$schools, array $byCode, array $reports): array
    {
        $extra = [];
        $joint = [];

        foreach ($reports as $report) {
            $index = $report['emis'] === null ? null : $this->schoolIndex($schools, $byCode, (string) $report['emis'], $report['district']);

            if ($index !== null) {
                $schools[$index]['r'][] = $this->report($report, $schools[$index]);
            } elseif (! $report['named'] && $report['district'] !== null) {
                $joint[] = $report;
            } else {
                $extra[] = $this->report($report, null);
            }
        }

        // A report with a district but no institute named answers for that district's
        // institutes visited since their own last report. It is shown at each of them and
        // counted once.
        foreach ($joint as $report) {
            $covered = [];

            foreach ($schools as $index => $school) {
                if (strcasecmp($school['d'], (string) $report['district']) !== 0) {
                    continue;
                }

                $visitDate = $this->visitBefore($school, $report['sd']);
                $answered = array_filter($school['r'], fn ($own) => empty($own['j']) && $own['sd'] !== null && $own['sd'] >= $visitDate);

                if ($visitDate !== null && $answered === []) {
                    $covered[] = $index;
                }
            }

            if ($covered === []) {
                $extra[] = $this->report($report, null);

                continue;
            }

            foreach ($covered as $index) {
                $schools[$index]['r'][] = $this->report($report, $schools[$index]) + ['j' => count($covered)];
            }
        }

        foreach ($schools as &$school) {
            usort($school['r'], fn ($a, $b) => [$a['sd'] ?? '', $a['sr'] ?? 0] <=> [$b['sd'] ?? '', $b['sr'] ?? 0]);

            // A later report on the same institute follows up an earlier one.
            foreach ($school['r'] as $i => &$report) {
                if ($i > 0 && empty($report['j'])) {
                    $report['fu'] = 1;
                }
            }
            unset($report);
        }
        unset($school);

        return $extra;
    }

    private function report(array $report, ?array $school): array
    {
        return [
            'id' => $report['id'],
            'sr' => $report['sr'],
            'sd' => $report['sd'],
            'rd' => $report['rd'],
            // The visit the report was written from: the latest one on or before its date.
            'vd' => $school === null ? null : $this->visitBefore($school, $report['sd']),
            'it' => $report['it'],
        ];
    }

    private function visitBefore(array $school, ?string $date): ?string
    {
        $before = array_filter(array_column($school['v'], 'dt'), fn ($visit) => $date !== null && $visit <= $date);

        return $before === [] ? null : max($before);
    }

    private function meta(Collection $visits): array
    {
        $key = fn ($date) => $date === null ? 0 : (int) Carbon::parse($date)->format('Ymd');
        $lastReply = SpecialEducationResponse::query()->max('responded_at');

        return [
            'minKey' => $key($visits->min('visited_at')),
            'maxKey' => $key($visits->max('visited_at')),
            'replyMax' => $lastReply === null ? '' : Carbon::parse($lastReply)->toDateString(),
        ];
    }

    private function answered(array $report): bool
    {
        foreach ($report['it'] as $item) {
            if ($item['t'] !== 'none') {
                return true;
            }
        }

        return false;
    }

    // Only a point inside Punjab is a location; 0 or a typo would put the pin elsewhere.
    private function coordinate(mixed $value, float $min, float $max): ?float
    {
        if (! is_numeric($value) || (float) $value < $min || (float) $value > $max) {
            return null;
        }

        return round((float) $value, 5);
    }

    private function days(string $from, string $to): int
    {
        return (int) round((strtotime($to) - strtotime($from)) / 86400);
    }

    private function median(array $values): ?int
    {
        if ($values === []) {
            return null;
        }

        sort($values);
        $middle = intdiv(count($values), 2);

        return count($values) % 2 ? $values[$middle] : (int) round(($values[$middle - 1] + $values[$middle]) / 2);
    }

    // As the design writes shares: under half a percent is "<1", just under all is ">99".
    private function percent(int $part, int $whole): string
    {
        if ($whole === 0) {
            return '0';
        }

        $share = $part / $whole * 100;

        return match (true) {
            $share > 0 && $share < 0.5 => '<1',
            $share > 99.5 && $share < 100 => '>99',
            default => (string) round($share),
        };
    }

    private function plural(int $count, string $one, string $many): string
    {
        return number_format($count).' '.($count === 1 ? $one : $many);
    }

    private function month(string $date): string
    {
        return Carbon::parse($date)->format('M Y');
    }
}
