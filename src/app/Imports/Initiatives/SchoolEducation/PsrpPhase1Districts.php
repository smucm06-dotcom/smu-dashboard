<?php

namespace App\Imports\Initiatives\SchoolEducation;

class PsrpPhase1Districts
{
    // The department's list spells these differently from the visit sheet.
    private const SPELLINGS = [
        'D.G. KHAN' => 'Dera Ghazi Khan',
        'D.G.KHAN' => 'Dera Ghazi Khan',
        'DG KHAN' => 'Dera Ghazi Khan',
        'MANDI BAHA UD DIN' => 'Mandi Bahauddin',
        'M.B.DIN' => 'Mandi Bahauddin',
        'RAHIMYAR KHAN' => 'Rahim Yar Khan',
        'SHEIKUPURA' => 'Sheikhupura',
        'T.T.SINGH' => 'Toba Tek Singh',
        'T.T. SINGH' => 'Toba Tek Singh',
        'TT SINGH' => 'Toba Tek Singh',
    ];

    public static function normalize(mixed $value): ?string
    {
        $name = is_scalar($value) ? trim(preg_replace('/\s+/', ' ', (string) $value) ?? '') : '';

        if ($name === '' || $name === '-') {
            return null;
        }

        return self::SPELLINGS[strtoupper($name)] ?? self::title($name);
    }

    // Like a normal title case, but also capitalises after a dot, as in D.G.Khan.
    public static function title(string $name): string
    {
        return (string) preg_replace_callback('/(^|[\s.(-])(\p{Ll})/u', fn ($m) => $m[1].mb_strtoupper($m[2]), mb_strtolower($name));
    }

    // Returns a coordinate rounded to 6 places, or null when it is missing or clearly wrong.
    public static function coordinate(mixed $value, float $min, float $max): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        return $number >= $min && $number <= $max ? round($number, 6) : null;
    }
}
