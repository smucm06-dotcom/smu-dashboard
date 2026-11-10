<?php

namespace App\Imports\Initiatives\SpecialEducation;

use App\Imports\Initiatives\SchoolEducation\PsrpPhase1Districts;

// The department's list and our visit file spell some districts differently. Both are saved
// the same way, so a district means one thing across the page.
class SpecialEducationDistricts
{
    private const SPELLINGS = [
        'FAISLABAD' => 'Faisalabad',
        'TOBA TAK SINGH' => 'Toba Tek Singh',
        'KOT ADU' => 'Kot Addu',
    ];

    public static function normalize(mixed $value): ?string
    {
        $name = is_scalar($value) ? trim((string) preg_replace('/\s+/', ' ', (string) $value)) : '';

        return self::SPELLINGS[strtoupper($name)] ?? PsrpPhase1Districts::normalize($name);
    }
}
