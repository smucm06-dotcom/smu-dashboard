<?php

namespace App\Dashboards\Initiatives\SchoolEducation;

use App\Imports\Initiatives\SchoolEducation\PsrpPhase1Districts;
use App\Models\Initiatives\SchoolEducation\PsrpPhase1;
use App\Models\Initiatives\SchoolEducation\PsrpPhase1Response;
use App\Models\Initiatives\SchoolEducation\PsrpPhase1School;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

// Joins the department list, the visits and the replies on EMIS code, one entry per school.
class PsrpPhase1Dashboard
{
    // Change this when the shape of the data changes, so browsers don't keep the old copy.
    private const DATA_VERSION = 2;

    public const NO_DISTRICT = 'Not recorded';

    // Changes whenever any of the three sources change.
    public function version(): string
    {
        $parts = [self::DATA_VERSION];

        foreach ([PsrpPhase1School::class, PsrpPhase1::class, PsrpPhase1Response::class] as $model) {
            $table = $model::query()->toBase();
            $parts[] = (clone $table)->count();
            $parts[] = (clone $table)->max('updated_at');
        }

        return md5(implode('|', $parts));
    }

    public function data(): array
    {
        $list = PsrpPhase1School::query()->toBase()->get()->keyBy('emis_code');
        $visits = PsrpPhase1::query()->toBase()->orderBy('visited_at')->orderBy('id')->get()->groupBy('emis_code');
        // A blank reply is saved but says nothing, so the findings leave it out, as before.
        $replies = PsrpPhase1Response::query()->toBase()->whereRaw("TRIM(response) <> ''")->orderBy('id')->get()->groupBy('emis_code');

        $schools = [];

        foreach ($list as $emis => $school) {
            $schools[] = $this->school($emis, $school, $visits->get($emis), $replies->get($emis));
        }

        // Visited schools missing from the department's list still count as visited.
        foreach ($visits as $emis => $schoolVisits) {
            if (! $list->has($emis)) {
                $schools[] = $this->school($emis, null, $schoolVisits, $replies->get($emis));
            }
        }

        return [
            'schools' => $schools,
            'twice' => $visits->filter(fn ($schoolVisits) => $schoolVisits->count() > 1)->count(),
        ];
    }

    private function school(string $emis, ?object $listed, ?Collection $visits, ?Collection $replies): array
    {
        $latest = $visits?->last();

        $school = [
            'e' => $emis,
            'n' => $listed?->school_name ?? $latest?->school_name ?? 'Not recorded',
            'd' => $listed?->district ?? PsrpPhase1Districts::normalize($latest?->district) ?? self::NO_DISTRICT,
            't' => $listed?->tehsil ?? '',
            'c' => $listed?->category ?? '',
            'o' => $listed?->operator ?? '',
            'ls' => $listed?->handover_status ?? '',
            'll' => $listed?->remarks ?? '',
            'off' => $listed === null ? 1 : 0,
        ];

        $location = $this->location($listed, $latest);

        if ($location !== null) {
            [$school['la'], $school['lo']] = $location;
        }

        if ($visits !== null) {
            $count = $visits->count();
            $all = $visits->map(fn ($visit) => $this->visit($visit, $count))->values()->all();
            $school['v'] = array_pop($all);

            // Earlier visits are only needed when the date filter hides the latest one.
            if ($all !== []) {
                $school['vv'] = array_reverse($all);
            }
        }

        if ($replies !== null) {
            $school['r'] = $this->reply($replies);
        }

        return $school;
    }

    // The department's coordinates come first, then the visit's. Clearly wrong ones are ignored.
    private function location(?object $listed, ?object $visit): ?array
    {
        foreach ([$listed, $visit] as $source) {
            if ($source === null) {
                continue;
            }

            $latitude = PsrpPhase1Districts::coordinate($source->latitude, 27.7, 34.1);
            $longitude = PsrpPhase1Districts::coordinate($source->longitude, 69.3, 75.5);

            if ($latitude !== null && $longitude !== null) {
                return [$latitude, $longitude];
            }
        }

        return null;
    }

    private function visit(object $visit, int $count): array
    {
        $date = Carbon::parse($visit->visited_at);
        $int = fn ($value) => $value === null ? null : (int) $value;

        $fields = [
            'id' => (int) $visit->id,
            'iso' => $date->format('Y-m-d'),
            'dt' => $date->format('j M Y'),
            'nv' => $count,
            'ho' => $int($visit->handover_status),
            'sal' => $int($visit->staff_salary_status),
            'cou' => $int($visit->pts_council_established),
            'mt' => $int($visit->council_meetings_count),
            'fee' => $int($visit->students_charged_fees),
            'en' => $int($visit->students_enrolled),
            'ha' => $int($visit->students_at_handover),
            'pr' => $int($visit->students_present),
            'te' => $int($visit->total_teachers),
            'tp' => $int($visit->teachers_present),
            'cr' => $int($visit->total_classrooms),
            'wr' => $int($visit->total_washrooms),
            'dg' => $int($visit->dangerous_classrooms),
            'wall' => $int($visit->boundary_wall_available),
            'cc' => $visit->classroom_condition,
            'wc' => $visit->washroom_cleanliness,
            'sc' => $visit->school_cleanliness,
            'wat' => $int($visit->clean_drinking_water_available),
            'bk' => $int($visit->free_textbooks_available),
            'gr' => $int($visit->one_grade_per_classroom),
            'fu' => $int($visit->furniture_available),
            'fa' => $int($visit->first_aid_available),
            'tt' => $int($visit->teachers_enrolled_pef_training),
            'tn' => $int($visit->teachers_nominated_pef_cpdp),
        ];

        return $fields + ['i' => $this->issues($fields)];
    }

    // What counts as an issue. A missing answer is not counted as one.
    private function issues(array $v): array
    {
        $poor = ['Dirty', 'Dilapidated'];

        return array_keys(array_filter([
            'danger' => $v['dg'] !== null && $v['dg'] > 0,
            'wall' => $v['wall'] === 0,
            'water' => $v['wat'] === 0,
            'firstaid' => $v['fa'] === 0,
            'furniture' => $v['fu'] === 0,
            'books' => $v['bk'] === 0,
            'cls' => in_array($v['cc'], $poor, true),
            'wash' => in_array($v['wc'], $poor, true),
            'sch' => in_array($v['sc'], $poor, true),
            'salary' => $v['sal'] === 0,
            'fees' => $v['fee'] === 1,
            'teach' => $v['tp'] === 0 && ($v['en'] ?? 0) > 0,
        ]));
    }

    // A school with several replies is counted once, under its strongest outcome.
    private function reply(Collection $replies): array
    {
        $classified = $replies->map(fn ($reply) => ['row' => $reply] + PsrpPhase1ReplyClassifier::classify($reply->response));

        $main = $classified->sortBy(fn ($reply) => array_search($reply['outcome'], PsrpPhase1ReplyClassifier::PRIORITY, true))->first();

        return [
            'o' => $main['outcome'],
            'tx' => $main['row']->response,
            'rm' => $main['row']->sent_remarks ?? '',
            'dt' => $main['row']->responded_at === null ? '' : Carbon::parse($main['row']->responded_at)->format('j M Y'),
            'pen' => $main['penalties'],
            'sed' => $main['sed'],
        ];
    }
}
