<?php

namespace App\Dashboards\Initiatives\SchoolEducation;

use App\Models\Initiatives\SchoolEducation\SchoolMealResponse;
use App\Models\Initiatives\SchoolEducation\SchoolMealSchool;
use App\Models\Initiatives\SchoolEducation\SchoolMealVisit;

// The districts the School Meal page shows. The department file gives each school its district and
// tehsil. Where our visit file or the response sheet names a tehsil as a district of its own (newer
// districts, such as one made from a tehsil), the schools of that tehsil are shown under that
// district. Nothing is named here: it follows the uploaded files.
class SchoolMealDistricts
{
    // [tehsil as the department file writes it => district as the visit or response file writes it].
    public static function tehsilsAsDistricts(): array
    {
        $listDistricts = [];
        foreach (SchoolMealSchool::query()->toBase()->whereNotNull('district')->distinct()->pluck('district') as $district) {
            $listDistricts[self::key($district)] = true;
        }

        $tehsils = SchoolMealSchool::query()->toBase()->whereNotNull('tehsil')->distinct()->pluck('tehsil')->all();

        // The names the visit and response files give, the most used spelling first.
        $named = [];
        foreach ([SchoolMealVisit::class, SchoolMealResponse::class] as $model) {
            foreach ($model::query()->toBase()->whereNotNull('district')->groupBy('district')
                ->selectRaw('district, COUNT(*) AS uses')->get() as $row) {
                $named[$row->district] = ($named[$row->district] ?? 0) + (int) $row->uses;
            }
        }
        arsort($named);

        $map = [];
        foreach (array_keys($named) as $name) {
            if (isset($listDistricts[self::key($name)])) {
                continue;
            }

            foreach ($tehsils as $tehsil) {
                if (! isset($map[$tehsil]) && self::key($tehsil) === self::key($name)) {
                    $map[$tehsil] = $name;
                }
            }
        }

        return $map;
    }

    // The page's district of a school in the department file.
    public static function of(?string $district, ?string $tehsil, array $map): ?string
    {
        return ($tehsil !== null ? ($map[$tehsil] ?? null) : null) ?? $district;
    }

    // A name for matching spellings: letters and digits only, in lower case, with a doubled letter
    // written once, so "Kot Adu" and "Kot Addu" match.
    public static function key(string $name): string
    {
        $plain = preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower($name)) ?? '';

        return (string) preg_replace('/(\p{L})\1+/u', '$1', $plain);
    }
}
