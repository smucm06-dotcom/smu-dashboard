<?php

namespace App\Dashboards\Initiatives\SchoolEducation;

use App\Models\Initiatives\SchoolEducation\SchoolMealResponse;
use App\Models\Initiatives\SchoolEducation\SchoolMealSchool;
use App\Models\Initiatives\SchoolEducation\SchoolMealVisit;
use Illuminate\Support\Carbon;

// Joins the department file, our visits and the department's response, one entry per school,
// in the shape the School Meal design reads.
class SchoolMealDashboard
{
    // Change this when the shape of the data changes, so browsers don't keep the old copy.
    private const DATA_VERSION = 3;

    // The visit form's "main issues", one Yes/No column each, under the design's codes. The form's
    // two wordings of the large-stock choice ("too much stock received" and "a large portion of the
    // stock was received") are one column in the visit file, the design's "too much milk stock".
    private const ISSUES = [
        'clean' => 'poor_cleanliness',
        'dump' => 'lack_of_disposal_facilities',
        'store' => 'inadequate_storage_facilities',
        'home' => 'milk_used_for_households',
        'over' => 'large_portion_of_stock_received',
    ];

    // A letter's own visit is the latest up to this many days before it.
    private const VISIT_BEFORE_LETTER = 30;

    // Changes whenever any of the sources change.
    public function version(): string
    {
        $parts = [self::DATA_VERSION];

        foreach ([SchoolMealSchool::class, SchoolMealVisit::class, SchoolMealResponse::class] as $model) {
            $table = $model::query()->toBase();
            $parts[] = (clone $table)->count();
            $parts[] = (clone $table)->max('updated_at');
        }

        return md5(implode('|', $parts));
    }

    public function data(): array
    {
        $map = SchoolMealDistricts::tehsilsAsDistricts();
        $schools = [];
        $byCode = [];

        foreach (SchoolMealSchool::query()->toBase()->orderBy('id')->get() as $row) {
            $byCode[$row->emis_code] = count($schools);
            $schools[] = [
                'k' => $row->emis_code,
                'n' => $row->school_name ?? 'Not recorded',
                'd' => SchoolMealDistricts::of($row->district, $row->tehsil, $map) ?? 'Not recorded',
                'th' => $row->tehsil ?? '',
                'la' => $row->latitude === null ? null : round((float) $row->latitude, 6),
                'lo' => $row->longitude === null ? null : round((float) $row->longitude, 6),
                'v' => [],
                'r' => [],
            ];
        }

        $keys = [];
        $maxVisits = 0;

        foreach (SchoolMealVisit::query()->toBase()->orderBy('visited_at')->orderBy('id')->get() as $row) {
            $index = $byCode[$row->emis_code] ?? null;

            // As in the design, a visit to a school that is not in the department file is left out
            // of the findings. The records table below still lists it.
            if ($index === null) {
                continue;
            }

            $visit = $this->visit($row);
            $schools[$index]['v'][] = $visit;
            $maxVisits = max($maxVisits, count($schools[$index]['v']));
            $keys[] = (int) str_replace('-', '', $visit['dt']);
        }

        $extra = [];
        $respMax = '';

        foreach ($this->reports() as $report) {
            if ($report['rd'] !== null && $report['rd'] > $respMax) {
                $respMax = $report['rd'];
            }

            $index = $report['e'] === null ? null : ($byCode[$report['e']] ?? null);

            // A response for a school that is not in the department file has no pin, but still counts.
            if ($index === null) {
                $extra[] = $report + [
                    'n' => $report['e'] === null ? 'School not named' : 'EMIS '.$report['e'],
                    'd' => $report['dist'] ?? 'Not recorded',
                ];

                continue;
            }

            $dates = array_column($schools[$index]['v'], 'dt');
            $schools[$index]['r'][] = $report + $this->visitsAround($dates, $report);
        }

        return [
            'schools' => $schools,
            'extra' => $extra,
            'meta' => [
                'minKey' => $keys === [] ? 0 : min($keys),
                'maxKey' => $keys === [] ? 0 : max($keys),
                'respMax' => $respMax,
                'maxVisits' => $maxVisits,
            ],
        ];
    }

    // The banner cards for all of Punjab and every visit: [value, line under it]. The page's script
    // works out the same from the same data, so nothing jumps when it loads.
    public function headline(array $data): array
    {
        $schools = $data['schools'];
        $visited = array_filter($schools, fn ($school) => $school['v'] !== []);
        $dates = [];
        $visits = 0;

        foreach ($visited as $school) {
            $visits += count($school['v']);
            foreach ($school['v'] as $visit) {
                $dates[$visit['dt']] = true;
            }
        }
        ksort($dates);

        $reports = $data['extra'];
        foreach ($schools as $school) {
            array_push($reports, ...$school['r']);
        }

        $replied = array_filter($reports, fn ($report) => $report['x'] !== null);
        $lag = $this->median(array_map(
            fn ($report) => $this->days($report['ld'], $report['rd']),
            array_filter($replied, fn ($report) => $report['ld'] && $report['rd'])
        ));
        $noReply = count($reports) - count($replied);
        $again = count(array_filter($schools, fn ($school) => count($school['v']) > 1));

        $repliedLine = implode('. ', array_filter([
            $lag === null ? '' : (count($replied) > 1 ? 'Usually within ' : 'Within ').$this->plural($lag, 'day', 'days').' of the SMU letter',
            $noReply === 0 ? '' : number_format($noReply).' with no response',
        ]));

        return [
            // As on the other pages, the user wants this card to show the count only.
            'total' => [count($schools), null],
            'visited' => [count($visited), $visited === [] ? 'None yet'
                : $this->percent(count($visited), count($schools)).'% of total schools, '.$this->plural($visits, 'visit', 'visits').' '.$this->dateText(array_keys($dates))],
            'replied' => [count($replied), $reports === [] ? '' : $repliedLine],
            'revisit' => [$again, $again === 0 ? 'No school checked again yet'
                : $this->plural($again, 'school', 'schools').' checked a second time'],
        ];
    }

    private function visit(object $row): array
    {
        $int = fn ($value) => $value === null ? null : (int) $value;
        $total = $int($row->total_staff);
        $absent = $int($row->staff_absent);
        // More staff absent than on roll can't be right, so it is shown as not clear.
        $staffUnclear = $absent !== null && $total !== null && $absent > $total;

        $visit = [
            'id' => (int) $row->id,
            'dt' => substr((string) $row->visited_at, 0, 10),
            'ag' => $row->district_agent === null ? null : (preg_match('/dfp|focal/i', $row->district_agent) === 1 ? 'dfp' : 'fa'),
            'op' => $int($row->school_opened),
            'dw' => $int($row->days_open_per_week),
            'en' => $int($row->students_enrolled),
            'bf' => $int($row->students_before_program),
            'sp' => $int($row->students_present),
            'me' => $int($row->students_receiving_milk),
            'st' => $total,
            'sx' => $staffUnclear ? null : $absent,
            'rc' => $int($row->milk_packs_received),
            'dg' => $int($row->milk_packs_distributed),
            'ex' => $int($row->milk_cartons_expired),
            'sk' => $int($row->milk_packs_in_stock),
            'xd' => $row->stock_expiry_date,
            'ers' => $int($row->expired_stock_received),
            'hm' => $int($row->milk_caused_harm),
            'ot' => $int($row->milk_delivered_on_time),
            'lag' => $int($row->delivery_gap_days),
            'ql' => $int($row->milk_quality),
            'iss' => $this->issues($row),
            'rt' => $this->rating($row->overall_rating),
            'ph' => $this->photos($row->photo_links),
        ];

        if ($staffUnclear) {
            $visit['sxbad'] = $absent;
        }

        // Only sent when the file left an issue blank, to keep the data small.
        $unanswered = $this->unanswered($row);

        if ($unanswered !== []) {
            $visit['nq'] = $unanswered;
        }

        return $visit;
    }

    // The visit form's "main issues" answered Yes at a visit, as the design's codes.
    private function issues(object $row): array
    {
        return array_keys(array_filter(self::ISSUES, fn ($column) => (bool) $row->{$column}));
    }

    // The issues left blank at a visit ("Null" in the file), which the page shows as not recorded.
    private function unanswered(object $row): array
    {
        return array_keys(array_filter(self::ISSUES, fn ($column) => $row->{$column} === null));
    }

    // Satisfactory, Average (Satisfactory) or Unsatisfactory, as typed in the form.
    private function rating(?string $text): ?string
    {
        return match (true) {
            $text === null || trim($text) === '' => null,
            preg_match('/unsat|not\s+sat/i', $text) === 1 => 'U',
            preg_match('/average/i', $text) === 1 => 'A',
            preg_match('/sat/i', $text) === 1 => 'S',
            default => null,
        };
    }

    // The Google Drive files of a visit's photos, by their id.
    private function photos(?string $links): array
    {
        preg_match_all('#(?:[?&]id=|/d/)([\w-]{10,})#', (string) $links, $found);

        return array_values(array_unique($found[1]));
    }

    // One entry per row of the response sheet. Rows whose observation is one merged cell share it,
    // and are read with all of their responses together.
    private function reports(): array
    {
        $rows = SchoolMealResponse::query()->toBase()->orderBy('id')->get();
        $blocks = [];

        foreach ($rows as $row) {
            // Rows saved before the sheet's merged observations were recorded have none.
            $row->observation_block ??= null;

            if ($row->observation_block !== null) {
                $blocks[$row->initiative_file_id.'-'.$row->observation_block][] = $row;
            }
        }

        $reports = [];

        foreach ($rows as $row) {
            $block = $row->observation_block === null ? [$row] : $blocks[$row->initiative_file_id.'-'.$row->observation_block];
            $responses = array_values(array_unique(array_filter(array_map(
                fn ($line) => SchoolMealReplyClassifier::response($line->response),
                $block
            ))));

            $report = [
                'id' => (int) $row->id,
                'sr' => $row->source_sr === null ? null : (int) $row->source_sr,
                'e' => $row->emis_code,
                'dist' => $row->district,
                'ld' => $row->letter_date === null ? null : substr((string) $row->letter_date, 0, 10),
                'rd' => $row->responded_at === null ? null : substr((string) $row->responded_at, 0, 10),
                'obs' => $row->smu_observation,
                'x' => SchoolMealReplyClassifier::response($row->response),
                'it' => SchoolMealReplyClassifier::items($row->smu_observation, $responses === [] ? null : implode(' ', $responses)),
            ];

            $others = array_values(array_filter(
                array_map(fn ($line) => $line->id === $row->id ? null : $line->source_sr, $block),
                fn ($sr) => $sr !== null
            ));

            if ($others !== []) {
                $report['sh'] = array_map('intval', $others);
            }

            $reports[] = $report;
        }

        return $reports;
    }

    // The visit a letter came from (the latest in the 30 days before it) and the first visit after
    // the department responded, from the school's visits in date order.
    private function visitsAround(array $dates, array $report): array
    {
        $found = [];
        $after = $report['rd'] ?? $report['ld'];

        foreach ($dates as $date) {
            if ($report['ld'] !== null && $date <= $report['ld'] && $this->days($date, $report['ld']) <= self::VISIT_BEFORE_LETTER) {
                $found['tv'] = $date;
            }

            if ($after !== null && $date > $after && ! isset($found['fu'])) {
                $found['fu'] = $date;
            }
        }

        return $found;
    }

    private function dateText(array $dates): string
    {
        if ($dates === []) {
            return '';
        }

        $months = array_map(fn ($date) => Carbon::parse($date)->format('M Y'), $dates);
        $last = end($months);

        if (count(array_unique($months)) === 1) {
            return count($dates) <= 3
                ? 'on '.$this->andList(array_map(fn ($date) => (string) (int) substr($date, 8, 2), $dates)).' '.$months[0]
                : 'in '.$months[0];
        }

        return 'from '.(substr($months[0], -4) === substr($last, -4) ? substr($months[0], 0, 3) : $months[0]).' to '.$last;
    }

    private function andList(array $items): string
    {
        return count($items) < 2 ? implode('', $items) : implode(', ', array_slice($items, 0, -1)).' and '.end($items);
    }

    private function days(string $from, string $to): int
    {
        return (int) round((strtotime($to) - strtotime($from)) / 86400);
    }

    // As the page's script works it out, so the two always agree.
    private function median(array $values): ?int
    {
        if ($values === []) {
            return null;
        }

        sort($values);
        $middle = intdiv(count($values), 2);

        return count($values) % 2 ? $values[$middle] : (int) round(($values[$middle - 1] + $values[$middle]) / 2);
    }

    private function percent(int $part, int $whole): string
    {
        if ($whole === 0) {
            return '0';
        }

        $percent = $part / $whole * 100;

        return match (true) {
            $percent > 0 && $percent < 0.5 => '<1',
            $percent > 99.5 && $percent < 100 => '>99',
            default => (string) round($percent),
        };
    }

    private function plural(int $count, string $one, string $many): string
    {
        return number_format($count).' '.($count === 1 ? $one : $many);
    }
}
