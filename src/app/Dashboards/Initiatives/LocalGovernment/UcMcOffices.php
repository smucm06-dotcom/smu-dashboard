<?php

namespace App\Dashboards\Initiatives\LocalGovernment;

// The UC and MC offices of the department's list, and which of them each visit row is about.
// The rules follow the design's reading of the files:
// - A UC is found by its district and UC number. When a district numbers its city and rural UCs
//   separately, the tehsil decides, and then the place name. A number the list doesn't have is an
//   office "not in the total list".
// - An MC is found by its name among the district's MCs, written in many ways ("D.D.Panaha" for
//   Daira Deen Panah), and failing that by its tehsil.
// - A list tehsil that the visit or response file names as a district (Kot Adu as Kot Addu) is
//   counted as that district, with its UCs and MCs.
// Nothing here names a place: every name comes from the files.
class UcMcOffices
{
    // Words that start a cell's MC name ("Municipal Committee, ...", "Muncipal Committee", "MC, ...").
    private const MC_PREFIX = '/^\s*(?:\d+\.\s*)?(?:(?:[Mm]un\w*|MC(?=[A-Z])|[Mm][Cc]\b)[\s,.]*)?(?:(?:[Cc]omm\w*|[Cc]orp\w*)\b)?[\s,.]*/u';

    /** @var array<int, array{i:int, d:string, th:string, no:string, n:string}> */
    public array $ucs = [];

    /** @var array<string, array{d:string, th:string, n:string}> keyed by normalised district | name */
    public array $mcs = [];

    /** @var array<string, array{u:int, m:int, dv:?string}> */
    public array $totals = [];

    private array $ucByDistrictNumber = [];

    private array $mcsByDistrict = [];

    /** @param iterable<object> $listRows  @param string[] $fileDistricts districts named in the visit and response files */
    public function __construct(iterable $listRows, array $fileDistricts)
    {
        $named = [];
        foreach ($fileDistricts as $district) {
            $key = self::norm($district);
            if ($key !== '' && ! isset($named[$key])) {
                $named[$key] = trim($district);
            }
        }

        $rows = [];
        $listDistricts = [];
        foreach ($listRows as $row) {
            $rows[] = $row;
            $listDistricts[self::norm($row->district)] = true;
        }

        foreach ($rows as $i => $row) {
            $tehsil = self::norm($row->tehsil);
            $district = isset($named[$tehsil]) && ! isset($listDistricts[$tehsil]) ? $named[$tehsil] : trim((string) $row->district);

            $uc = [
                'i' => $i,
                'd' => $district,
                'th' => trim((string) $row->tehsil),
                'no' => trim((string) $row->uc_no),
                'n' => trim((string) $row->standard),
            ];
            $this->ucs[$i] = $uc;
            $this->ucByDistrictNumber[self::norm($district).'|'.$uc['no']][] = $uc;

            $this->totals[$district] ??= ['u' => 0, 'm' => 0, 'dv' => $row->division];
            $this->totals[$district]['u']++;

            foreach (self::mcNames($row->municipal_committee) as $name) {
                $key = self::norm($district).'|'.self::norm($name);
                if (! isset($this->mcs[$key])) {
                    $this->mcs[$key] = ['d' => $district, 'th' => trim((string) $row->tehsil), 'n' => $name];
                    $this->mcsByDistrict[self::norm($district)][] = $key;
                    $this->totals[$district]['m']++;
                }
            }
        }

        ksort($this->totals);
    }

    // The office a visit is about: [key, type, the list's UC or MC, how it was found].
    public function officeOfVisit(object $visit): array
    {
        $district = trim((string) $visit->district);

        if (($visit->mc_name ?? '') !== '' && $visit->mc_name !== null) {
            $key = $this->matchMc($district, (string) $visit->tehsil, (string) $visit->mc_name);

            return $key === null
                ? ['m?-'.$district.'-'.trim($visit->mc_name), 'M', null]
                : ['m-'.$this->mcs[$key]['d'].'-'.$this->mcs[$key]['n'], 'M', $this->mcs[$key]];
        }

        $uc = $this->matchUc($district, (string) $visit->tehsil, (string) $visit->name, (string) $visit->uc_number);

        return $uc === null
            ? ['x-'.$district.'-'.preg_replace('/\D/', '', (string) $visit->uc_number), 'U', null]
            : ['u'.$uc['i'], 'U', $uc];
    }

    // The district's MC with this name, as the reports and letters name it.
    public function mcKey(string $district, string $name): ?string
    {
        $key = $this->matchMc($district, '', $name);

        return $key === null ? null : 'm-'.$this->mcs[$key]['d'].'-'.$this->mcs[$key]['n'];
    }

    public function matchMc(string $district, string $tehsil, string $name): ?string
    {
        $candidates = $this->mcsByDistrict[self::norm($district)] ?? [];
        $wanted = self::norm($name);
        $inTehsil = array_values(array_filter($candidates, fn ($key) => self::norm($this->mcs[$key]['th']) === self::norm($tehsil)));

        $tests = [
            fn ($key) => explode('|', $key, 2)[1] === $wanted,
            fn ($key) => self::skeleton(explode('|', $key, 2)[1]) === self::skeleton($wanted) || self::initialsMatch($this->mcs[$key]['n'], $name),
            fn ($key) => count($inTehsil) === 1 && $inTehsil[0] === $key,
        ];

        foreach ($tests as $test) {
            foreach ($candidates as $key) {
                if ($test($key)) {
                    return $key;
                }
            }
        }

        $best = null;
        $bestScore = -1.0;
        foreach ($candidates as $key) {
            $score = self::dice(explode('|', $key, 2)[1], $wanted);
            if ($score > $bestScore) {
                [$best, $bestScore] = [$key, $score];
            }
        }

        return $best !== null && $bestScore >= 0.4 ? $best : null;
    }

    public function matchUc(string $district, string $tehsil, string $name, string $raw): ?array
    {
        preg_match_all('/\d+/', $raw, $numbers);
        $number = $numbers[0][0] ?? trim($raw);
        $candidates = $this->ucByDistrictNumber[self::norm($district).'|'.$number] ?? [];

        if (count($candidates) <= 1) {
            return $candidates[0] ?? null;
        }

        $t = self::norm($tehsil);
        $same = array_values(array_filter($candidates, fn ($uc) => self::norm($uc['th']) === $t));
        if ($same === []) {
            $same = array_values(array_filter($candidates, fn ($uc) => $t !== '' && self::norm($uc['th']) !== ''
                && (str_contains($t, self::norm($uc['th'])) || str_contains(self::norm($uc['th']), $t))));
        }

        if (count($same) === 1) {
            return $same[0];
        }

        return self::closestByName($same !== [] ? $same : $candidates, $name);
    }

    // The UCs a district has with this number, for the reports, which name UCs by number.
    public function ucsByNumber(string $district, string $number): array
    {
        return $this->ucByDistrictNumber[self::norm($district).'|'.$number] ?? [];
    }

    // The candidate whose list name is closest to the visit's place name; on a tie, the later one.
    private static function closestByName(array $candidates, string $name): array
    {
        $wanted = self::norm($name);
        $best = $candidates[0];
        $bestScore = -1.0;

        foreach ($candidates as $uc) {
            $score = self::dice(self::norm($uc['n']), $wanted);
            if ($score >= $bestScore) {
                [$best, $bestScore] = [$uc, $score];
            }
        }

        return $best;
    }

    // The MCs one cell of the list names: "Municipal Committee Lalian Municipal Committee Chenab Nagar",
    // "1. Municipal Committee Sheikhupura 2. ...", "... P.D. Khan and Municipal Committee Khewra".
    // "District Council" is not an MC.
    public static function mcNames(?string $cell): array
    {
        $cell = trim((string) $cell);
        if ($cell === '' || in_array(mb_strtolower($cell), ['null', '-', '--', 'n/a', 'na', 'nil'], true)) {
            return [];
        }

        $parts = preg_split('/\R|\s+(?:and\s+)?(?=(?:\d+\.\s*)?(?:mun\w*|mc)\b)/iu', $cell) ?: [$cell];
        $names = [];

        foreach ($parts as $part) {
            $part = trim((string) preg_replace('/^\s*\d+\.\s*/u', '', $part));
            if ($part === '' || preg_match('/district\s+council/iu', $part) === 1) {
                continue;
            }

            $name = trim((string) preg_replace(self::MC_PREFIX, '', $part), " ,.\t");
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    // Lower case letters and digits only, with doubled letters written once ("Kot Adu" = "Kot Addu").
    public static function norm(?string $text): string
    {
        $text = (string) preg_replace('/[^a-z0-9]/', '', mb_strtolower((string) $text));

        return (string) preg_replace('/(.)\1+/', '$1', $text);
    }

    private static function skeleton(string $text): string
    {
        return (string) preg_replace('/[aeiouhy]/', '', $text);
    }

    // How alike two names are, from the letter pairs they share (0 to 1).
    public static function dice(string $a, string $b): float
    {
        $pairs = fn (string $s) => array_map(fn ($i) => substr($s, $i, 2), range(0, max(-1, strlen($s) - 2)));
        $left = strlen($a) > 1 ? $pairs($a) : [];
        $right = strlen($b) > 1 ? $pairs($b) : [];

        if ($left === [] || $right === []) {
            return 0.0;
        }

        $rest = array_count_values($right);
        $hits = 0;
        foreach ($left as $pair) {
            if (($rest[$pair] ?? 0) > 0) {
                $rest[$pair]--;
                $hits++;
            }
        }

        return 2 * $hits / (count($left) + count($right));
    }

    // "D.D.Panaha" = "Daira Deen Panah", "HS Muqeem" = "Hujra Shah Muqeem".
    private static function initialsMatch(string $listName, string $visitName): bool
    {
        $words = function (string $text): array {
            $out = [];
            foreach (preg_split('/[\s.]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) as $word) {
                if (preg_match('/^[A-Z]{2,3}$/', $word) === 1) {
                    array_push($out, ...str_split($word));
                } else {
                    $out[] = $word;
                }
            }

            return $out;
        };

        $a = $words($listName);
        $b = $words($visitName);

        if (count($a) !== count($b) || count($a) < 2) {
            return false;
        }

        foreach ($a as $i => $x) {
            $y = $b[$i];
            if (mb_strlen($x) === 1 || mb_strlen($y) === 1) {
                if (mb_strtolower(mb_substr($x, 0, 1)) !== mb_strtolower(mb_substr($y, 0, 1))) {
                    return false;
                }
            } elseif (substr(self::skeleton(self::norm($x)), 0, 3) !== substr(self::skeleton(self::norm($y)), 0, 3)) {
                return false;
            }
        }

        return true;
    }
}
