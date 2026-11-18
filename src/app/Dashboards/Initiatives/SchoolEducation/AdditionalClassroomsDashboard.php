<?php

namespace App\Dashboards\Initiatives\SchoolEducation;

use App\Imports\Initiatives\SchoolEducation\PsrpPhase1Districts;
use App\Models\Initiatives\SchoolEducation\AdditionalClassroomResponse;
use App\Models\Initiatives\SchoolEducation\AdditionalClassroomSchool;
use App\Models\Initiatives\SchoolEducation\AdditionalClassroomVisit;
use Illuminate\Support\Carbon;

// Joins the department file, our visits and the department's response, one entry per school,
// in the shape the Additional Classrooms design reads.
class AdditionalClassroomsDashboard
{
    // Change this when the shape of the data changes, so browsers don't keep the old copy.
    private const DATA_VERSION = 1;

    // Changes whenever any of the sources change.
    public function version(): string
    {
        $parts = [self::DATA_VERSION];

        foreach ([AdditionalClassroomSchool::class, AdditionalClassroomVisit::class, AdditionalClassroomResponse::class] as $model) {
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
        $zoneOf = [];

        foreach (AdditionalClassroomSchool::query()->toBase()->orderBy('id')->get() as $row) {
            $byCode[$row->emis_code] = count($schools);
            $schools[] = [
                'k' => $row->emis_code,
                'e' => $row->emis_code,
                'n' => $row->school_name ?? 'Not recorded',
                'lv' => $this->level($row->level),
                'z' => $row->zone ?? '',
                'd' => $row->district ?? 'Not recorded',
                'dv' => $row->division ?? '',
                'th' => $row->tehsil ?? '',
                'ap' => (int) ($row->approved_classrooms ?? 0),
                'la' => $row->latitude === null ? null : round((float) $row->latitude, 6),
                'lo' => $row->longitude === null ? null : round((float) $row->longitude, 6),
                'v' => [],
                'r' => [],
            ];

            if ($row->district !== null && $row->zone !== null) {
                $zoneOf[$row->district] ??= $row->zone;
            }
        }

        $keys = [];

        foreach (AdditionalClassroomVisit::query()->toBase()->orderBy('visited_at')->orderBy('id')->get() as $row) {
            $index = $byCode[$row->emis_code] ?? null;

            // As in the design, a visit to a school that is not in the department file is left out
            // of the findings. The records table below still lists it.
            if ($index === null) {
                continue;
            }

            $visit = $this->visit($row);
            $schools[$index]['v'][] = $visit;
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
                    'd' => $report['d'] ?? 'Not recorded',
                    'z' => $zoneOf[$report['d']] ?? '',
                ];

                continue;
            }

            // The visit date in the response differs from the one in our visit file.
            $visited = $this->nearestDate(array_column($schools[$index]['v'], 'dt'), $report['vd']);
            if ($visited !== null && $visited !== $report['vd']) {
                $report['vm'] = $visited;
            }

            $schools[$index]['r'][] = $report;
        }

        foreach ($schools as &$school) {
            usort($school['r'], fn ($a, $b) => strcmp((string) $a['vd'], (string) $b['vd']));
        }
        unset($school);

        return [
            'schools' => $schools,
            'extra' => $extra,
            'meta' => [
                'minKey' => $keys === [] ? 0 : min($keys),
                'maxKey' => $keys === [] ? 0 : max($keys),
                'respMax' => $respMax,
            ],
        ];
    }

    // The design's banner cards for all of Punjab and every visit: [value, line under it].
    // The page's script works out the same from the same data, so nothing jumps when it loads.
    public function headline(array $data): array
    {
        $schools = $data['schools'];
        $visited = array_filter($schools, fn ($school) => $school['v'] !== []);
        $dates = [];

        foreach ($visited as $school) {
            foreach ($school['v'] as $visit) {
                $dates[$visit['dt']] = true;
            }
        }
        ksort($dates);

        $reports = $data['extra'];
        foreach ($schools as $school) {
            array_push($reports, ...$school['r']);
        }

        $replied = array_filter($reports, fn ($report) => $this->answered($report));
        $lag = $this->median(array_map(
            fn ($report) => $this->days($report['vd'], $report['rd']),
            array_filter($replied, fn ($report) => $report['vd'] && $report['rd'])
        ));
        $noReply = count($reports) - count($replied);
        $revisited = count(array_filter($schools, fn ($school) => count($school['v']) > 1));

        $repliedLine = implode('. ', array_filter([
            $lag === null ? '' : (count($replied) > 1 ? 'Usually within ' : 'Within ').$this->plural($lag, 'day', 'days').' of the visit',
            $noReply === 0 ? '' : number_format($noReply).' with no response',
        ]));

        return [
            // As on Special Education, the user wants this card to show the count only.
            'total' => [count($schools), null],
            'visited' => [count($visited), $visited === [] ? 'None yet'
                : $this->percent(count($visited), count($schools)).'% of total schools, '.$this->dateText(array_keys($dates))],
            'replied' => [count($replied), $reports === [] ? '' : $repliedLine],
            'revisit' => [$revisited, $revisited === 0 ? 'No school visited twice yet'
                : $this->plural($revisited, 'school', 'schools').' checked a second time'],
        ];
    }

    private function visit(object $row): array
    {
        $int = fn ($value) => $value === null ? null : (int) $value;
        $status = AdditionalClassroomsClassifier::status($row->construction_status);
        $working = $status === 'on';
        $obstruction = $int($row->obstruction);

        // A visit with no student count ("Null" in the file) did not count students or staff, and
        // there an existing-room count of 0 means the rooms were not checked.
        $vacation = $row->students_enrolled === null ? 1 : 0;
        $rooms = fn ($value) => $vacation && (int) $value === 0 ? null : $int($value);

        return [
            'id' => (int) $row->id,
            'dt' => substr((string) $row->visited_at, 0, 10),
            'sr' => $int($row->source_sr),
            'st' => $status,
            'vac' => $vacation,
            // The reason applies where work is not going on, the problem slowing it where it is.
            'why' => $working ? null : AdditionalClassroomsClassifier::cause($row->not_started_reason),
            'wraw' => $row->not_started_reason,
            'ob' => $working ? $obstruction : null,
            'oc' => $working && $obstruction === 1 ? AdditionalClassroomsClassifier::cause($row->obstruction_type) : null,
            'oraw' => $row->obstruction_type,
            'sg' => AdditionalClassroomsClassifier::stage($row->construction_stage, $status),
            'sraw' => $row->construction_stage,
            'lab' => $int($row->labour_present),
            'ins' => AdditionalClassroomsClassifier::inspection($row->inspection_frequency),
            'fac' => $row->new_classroom_facilities,
            'cc' => $this->condition($row->classroom_condition),
            'wc' => $this->condition($row->washroom_condition),
            'oa' => $this->condition($row->overall_cleanliness),
            'wa' => $int($row->clean_drinking_water),
            'bw' => $int($row->boundary_wall_available),
            'q' => $int($row->construction_quality_satisfactory),
            'ap' => $int($row->approved_classrooms),
            'fc' => $int($row->constructed_classrooms),
            'uc' => $int($row->under_construction_classrooms),
            'cr' => $rooms($row->total_classrooms),
            'wr' => $rooms($row->total_washrooms),
            'en' => $int($row->students_enrolled),
            'sp' => $int($row->students_present),
            'tt' => $int($row->total_teachers),
            'tp' => $int($row->teachers_present),
            'nt' => $int($row->non_teaching_staff),
            'np' => $int($row->non_teaching_staff_present),
            'ty' => $row->school_type === null ? null : PsrpPhase1Districts::title($row->school_type),
        ];
    }

    // One entry per report: its rows share a file, Sr, EMIS code and visit date.
    private function reports(): array
    {
        $groups = [];

        foreach (AdditionalClassroomResponse::query()->toBase()->orderBy('id')->get() as $row) {
            $key = implode('|', [$row->initiative_file_id, $row->source_sr, $row->emis_code, $row->visit_date]);
            $groups[$key][] = $row;
        }

        $reports = [];

        foreach ($groups as $rows) {
            $first = $rows[0];
            $responded = collect($rows)->pluck('responded_at')->filter()->first();

            $reports[] = [
                'id' => (int) $first->id,
                'sr' => $first->source_sr === null ? null : (int) $first->source_sr,
                'e' => $first->emis_code,
                'd' => $first->district,
                'vd' => $first->visit_date === null ? null : substr((string) $first->visit_date, 0, 10),
                'rd' => $responded === null ? null : substr((string) $responded, 0, 10),
                'it' => AdditionalClassroomsClassifier::items(array_map(
                    fn ($row) => ['remark' => $row->sent_remarks, 'response' => $row->response],
                    $rows
                )),
            ];
        }

        return $reports;
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

    // The visit date in our file closest to the one given, or null when there are no visits.
    private function nearestDate(array $dates, ?string $date): ?string
    {
        if ($dates === [] || $date === null) {
            return $dates === [] ? null : end($dates);
        }

        usort($dates, fn ($a, $b) => abs($this->days($a, $date)) <=> abs($this->days($b, $date)));

        return $dates[0];
    }

    // The file writes higher secondary schools as "H.Sec." and as "Higher Secondary".
    private function level(?string $level): string
    {
        $level = trim((string) $level);

        return preg_match('/^h\.?\s*sec\.?$/i', $level) === 1 ? 'Higher Secondary' : $level;
    }

    private function condition(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : ucfirst(strtolower($value));
    }

    private function dateText(array $dates): string
    {
        if ($dates === []) {
            return '';
        }

        $months = array_map(fn ($date) => Carbon::parse($date)->format('M Y'), $dates);
        $last = end($months);

        if (count(array_unique($months)) === 1 && count($dates) <= 3) {
            return 'on '.$this->andList(array_map(fn ($date) => (string) (int) substr($date, 8, 2), $dates)).' '.$months[0];
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
