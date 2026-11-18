<?php

namespace App\Models\Initiatives\SpecialEducation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

// The department's reply to one issue in our report on an institute.
class SpecialEducationResponse extends Model
{
    protected $table = 'special_education_institutes_responses';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'source_sr' => 'integer',
            'reported_at' => 'date',
            'responded_at' => 'date',
        ];
    }

    // The response file has no EMIS codes, so each reply is linked by its institute's name.
    // Run after a list or visit upload, so replies uploaded earlier are linked too.
    public static function linkToInstitutes(): int
    {
        $codes = self::codesByName();

        if ($codes === []) {
            return 0;
        }

        $linked = 0;

        DB::transaction(function () use ($codes, &$linked) {
            self::query()
                ->whereNull('emis_code')
                ->whereNotNull('institution_name')
                ->get(['id', 'district', 'institution_name'])
                ->each(function (self $reply) use ($codes, &$linked) {
                    $emis = self::codeFor($codes, $reply->institution_name, $reply->district);

                    if ($emis !== null) {
                        self::query()->whereKey($reply->id)->update(['emis_code' => $emis]);
                        $linked++;
                    }
                });
        });

        return $linked;
    }

    // [simplified name => [EMIS code => simplified district]] from the list, then from visits.
    public static function codesByName(): array
    {
        $codes = [];

        $add = function (iterable $rows) use (&$codes) {
            foreach ($rows as $row) {
                $name = self::simplified($row->institution_name);

                if ($name !== '' && ! isset($codes[$name][$row->emis_code])) {
                    $codes[$name][$row->emis_code] = self::simplified($row->district);
                }
            }
        };

        $add(SpecialEducationInstitute::query()->toBase()->get(['emis_code', 'institution_name', 'district']));
        $add(SpecialEducationVisit::query()->toBase()->distinct()->get(['emis_code', 'institution_name', 'district']));

        return $codes;
    }

    // Only a clear match: one institute with that name, or one with that name in that district.
    public static function codeFor(array $codes, ?string $name, ?string $district): ?string
    {
        $matches = $codes[self::simplified($name)] ?? [];

        if (count($matches) === 1) {
            return (string) array_key_first($matches);
        }

        $inDistrict = array_keys($matches, self::simplified($district), true);

        return count($inDistrict) === 1 ? (string) $inDistrict[0] : null;
    }

    // Ignores case, spaces and punctuation, so "Govt. School" and "Govt School" match, and so do
    // "EducationCenter" and "Education Center".
    private static function simplified(?string $text): string
    {
        $words = trim((string) preg_replace('/[^a-z0-9]+/', ' ', mb_strtolower((string) $text)));

        return str_replace(' ', '', $words);
    }
}
