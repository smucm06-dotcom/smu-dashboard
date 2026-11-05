<?php

namespace App\Dashboards\Initiatives\SchoolEducation;

use App\Imports\Initiatives\SchoolEducation\PsrpPhase1Districts;
use App\Models\Initiatives\SchoolEducation\PsrpPhase2;
use App\Models\Initiatives\SchoolEducation\PsrpPhase2Response;
use App\Models\Initiatives\SchoolEducation\PsrpPhase2School;
use App\Models\Initiatives\SchoolEducation\PsrpPhase2SchoolName;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

// Joins the department's list, the visits and the replies on EMIS code, one entry per school.
class PsrpPhase2Dashboard
{
    // Change this when the shape of the data changes, so browsers don't keep the old copy.
    private const DATA_VERSION = 1;

    // How much lower than declared a count must be to count as an issue.
    private const FAR_BELOW = 0.25;

    private const POOR = ['Dirty', 'Dilapidated'];

    // The department's list still has these under their old districts. The visits use the new ones.
    private const DISTRICT_OF_TEHSIL = [
        'Kot Adu' => 'Kot Addu',
        'Taunsa' => 'Taunsa',
        'Talagang' => 'Talagang',
        'Lawa' => 'Talagang',
        'Murree' => 'Murree',
        'Kotli Sattian' => 'Murree',
        'Chunian' => 'Kasur',
    ];

    private const CATEGORIES = [
        'INDIVIDUAL' => 'Individual',
        'YOUNG ENTERPRENUR' => 'Young Entrepreneur',
        'NGOS' => 'NGO',
        'ED TECH' => 'Ed-Tech',
    ];

    // Changes whenever any of the sources change.
    public function version(): string
    {
        $parts = [self::DATA_VERSION];

        foreach ([PsrpPhase2School::class, PsrpPhase2::class, PsrpPhase2Response::class, PsrpPhase2SchoolName::class] as $model) {
            $table = $model::query()->toBase();
            $parts[] = (clone $table)->count();
            $parts[] = (clone $table)->max('updated_at');
        }

        return md5(implode('|', $parts));
    }

    public function data(): array
    {
        $list = PsrpPhase2School::query()->toBase()->get()->keyBy('emis_code');
        $names = PsrpPhase2SchoolName::query()->toBase()->get()->keyBy('emis_code');
        // Each school has one visit in the file. If that changes, the latest one is used.
        $visits = PsrpPhase2::query()->toBase()->orderBy('visited_at')->orderBy('id')->get()->keyBy('emis_code');
        $replies = PsrpPhase2Response::query()->toBase()->orderBy('responded_at')->orderBy('id')->get()->groupBy('emis_code');

        $schools = [];

        foreach ($list as $emis => $school) {
            $schools[] = $this->school((string) $emis, $school, $names->get($emis), $visits->get($emis), $replies->get($emis));
        }

        // Visited schools missing from the department's list still count as visited.
        foreach ($visits as $emis => $visit) {
            if (! $list->has($emis)) {
                $schools[] = $this->school((string) $emis, null, $names->get($emis), $visit, $replies->get($emis));
            }
        }

        return ['schools' => $schools, 'meta' => $this->meta($visits, $replies)];
    }

    private function meta(Collection $visits, Collection $replies): array
    {
        $key = fn ($date) => $date === null ? 0 : (int) Carbon::parse($date)->format('Ymd');
        $label = fn ($date) => $date === null ? '' : Carbon::parse($date)->format('d M Y');

        $all = $replies->flatten(1);
        $lastVisit = $all->max('visit_date');
        $lastReply = $all->max('responded_at');

        return [
            'minKey' => $key($visits->min('visited_at')),
            'maxKey' => $key($visits->max('visited_at')),
            'cutKey' => $key($lastVisit),
            'lastReplyVisit' => $label($lastVisit),
            'lastReply' => $label($lastReply),
            'penalties' => $all->filter(fn ($reply) => preg_match('/penalt|warning letter/i', (string) $reply->response) === 1)->count(),
        ];
    }

    private function school(string $emis, ?object $listed, ?object $name, ?object $visit, ?Collection $replies): array
    {
        $school = [
            'e' => $emis,
            'n' => $visit?->school_name ?? $name?->school_name ?? $listed?->school_name ?? 'Not recorded',
            'd' => $this->district($listed, $visit),
            't' => $listed?->tehsil ?? '',
            'c' => $this->category($listed?->category),
            'o' => $listed?->operator ?? '',
        ];

        $location = $this->location($visit, $name, $listed);

        if ($location !== null) {
            [$school['la'], $school['lo']] = $location;
        }

        $reported = $listed !== null && ! str_contains((string) $listed->remarks, 'not shared');

        if ($reported) {
            $school['b'] = $this->declared($listed);
        } elseif ($listed !== null) {
            // The list says PMIU's data did not reach the department for this school.
            $school['pm'] = 1;
        }

        if ($visit !== null) {
            $school['v'] = $this->visit($visit, $school['b'] ?? null, $replies);
        }

        if ($replies !== null) {
            $school['r'] = $replies->map(fn ($reply) => $this->reply($reply))->values()->all();
        }

        return $school;
    }

    private function district(?object $listed, ?object $visit): string
    {
        if ($visit?->district) {
            return $visit->district;
        }

        return self::DISTRICT_OF_TEHSIL[$listed?->tehsil] ?? self::DISTRICT_OF_TEHSIL[$listed?->district] ?? $listed?->district ?? PsrpPhase1Dashboard::NO_DISTRICT;
    }

    private function category(?string $category): string
    {
        return self::CATEGORIES[mb_strtoupper(trim((string) $category))] ?? (string) $category;
    }

    // The visit's own coordinates come first. Clearly wrong ones are ignored.
    private function location(?object ...$sources): ?array
    {
        foreach ($sources as $source) {
            if ($source === null) {
                continue;
            }

            $latitude = PsrpPhase1Districts::coordinate($source->latitude, 27.7, 34.1);
            $longitude = PsrpPhase1Districts::coordinate($source->longitude, 69.3, 75.5);

            if ($latitude !== null && $longitude !== null) {
                return [round($latitude, 5), round($longitude, 5)];
            }
        }

        return null;
    }

    private function int(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    private function declared(object $listed): array
    {
        return array_filter([
            'en' => $this->int($listed->students_enrolled),
            'cr' => $this->int($listed->classrooms),
            'wr' => $this->int($listed->washrooms),
            'de' => $this->int($listed->desks_per_classroom),
            'ch' => $this->int($listed->chairs_per_classroom),
            'wa' => $this->int($listed->clean_water_available),
        ], fn ($value) => $value !== null);
    }

    private function visit(object $visit, ?array $declared, ?Collection $replies): array
    {
        $date = Carbon::parse($visit->visited_at);

        $fields = [
            'id' => (int) $visit->id,
            'dt' => $date->format('d M Y'),
            'dk' => (int) $date->format('Ymd'),
            'ho' => $this->int($visit->handover_status),
            'fu' => $this->int($visit->furniture_available),
            'wr' => $this->int($visit->total_washrooms),
            'cr' => $this->int($visit->total_classrooms),
            'de' => $this->int($visit->total_desks),
            'ch' => $this->int($visit->total_room_chairs),
            'fa' => $this->int($visit->total_air_fans),
            'cc' => $visit->classroom_condition,
            'wc' => $visit->washroom_cleanliness,
            'sc' => $visit->school_cleanliness,
            'wat' => $this->int($visit->clean_drinking_water_available),
            'wall' => $this->int($visit->boundary_wall_available),
            'dg' => $this->int($visit->dangerous_classrooms),
        ];

        if ($visit->students_at_handover !== null) {
            $fields['eb'] = (int) $visit->students_at_handover;
        }

        if ($visit->students_enrolled !== null) {
            $fields['ea'] = (int) $visit->students_enrolled;
        }

        $fields['i'] = $this->issues($fields, $declared ?? []);

        if ($replies !== null) {
            $fields['ad'] = PsrpPhase2ReplyClassifier::addressed($fields['i'], $replies->pluck('response')->all());
        }

        return $fields;
    }

    // What counts as an issue. A missing answer is not counted as one.
    private function issues(array $visit, array $declared): array
    {
        $farBelow = function (string $item) use ($visit, $declared) {
            $was = $declared[$item] ?? null;
            $now = $visit[$item];

            return $was !== null && $was > 0 && $now !== null && ($was - $now) / $was >= self::FAR_BELOW;
        };

        return array_keys(array_filter([
            'danger' => $visit['dg'] !== null && $visit['dg'] > 0,
            'water' => $visit['wat'] === 0,
            'wall' => $visit['wall'] === 0,
            'nohand' => $visit['ho'] === 0,
            'nofurn' => $visit['fu'] === 0,
            'cls' => in_array($visit['cc'], self::POOR, true),
            'wash' => in_array($visit['wc'], self::POOR, true),
            'sch' => in_array($visit['sc'], self::POOR, true),
            'gCls' => $farBelow('cr'),
            'gWash' => $farBelow('wr'),
            'gDesk' => $farBelow('de'),
            'gChair' => $farBelow('ch'),
        ]));
    }

    private function reply(object $reply): array
    {
        $found = PsrpPhase2ReplyClassifier::classify($reply->response);
        $answered = $reply->responded_at === null ? null : Carbon::parse($reply->responded_at);

        return [
            'vd' => $reply->visit_date === null ? '' : Carbon::parse($reply->visit_date)->format('d M Y'),
            'dt' => $answered?->format('d M Y') ?? '',
            'dk' => $answered === null ? 0 : (int) $answered->format('Ymd'),
            'rm' => $reply->sent_remarks ?? '',
            'tx' => $reply->response,
            'o' => $found['outcome'],
            's' => $found['self'],
        ];
    }
}
