<?php

namespace App\Dashboards\Initiatives\LocalGovernment;

// How a city or district named in the visit file or an SMU report is found in the department's list for the
// Chief Minister Punjab Development Program, when the two spell it differently. These are rules only, no
// names: spaces, case and doubled letters are ignored ("Alipur Chatha" = "Ali Pur Chatha", "Khuddian" =
// "Khudian"), a vowel may differ ("Joharabad" = "Jauharabad", "Ferozwala" = "Ferozewala"), a note in
// brackets is not part of the name ("Toba Tek Singh (Sew)"), a list name joining two towns with "&" stands
// for each ("Burewala & Gaggo"), and a short capital abbreviation stands for the words it starts
// ("TTS" = "Toba Tek Singh").
class PdpCityNames
{
    // A name of this many letters or fewer, in capitals, may be an abbreviation.
    private const ABBREVIATION = 4;

    // Spaces inside a name as typed are one space, and none at the ends.
    public static function spaced(mixed $value): string
    {
        return is_scalar($value) ? trim((string) preg_replace('/\s+/u', ' ', (string) $value)) : '';
    }

    // The letters of a name, in lower case: "Ali Pur Chatha" is "alipurchatha".
    public static function letters(mixed $value): string
    {
        return (string) preg_replace('/[^a-z]+/', '', strtolower(self::spaced($value)));
    }

    // Finds a name among [key => name], or returns null. Each rule is tried on every name before the next,
    // so an exact match always wins over a looser one. Without $loose, only the same letters count, for a
    // search across the whole list where a looser rule could pick another district's town.
    public static function find(mixed $value, array $names, bool $loose = true): ?string
    {
        $typed = self::spaced($value);
        $letters = self::letters($typed);

        if ($letters === '') {
            return null;
        }

        foreach ($names as $key => $name) {
            if (in_array($letters, self::forms($name), true)) {
                return (string) $key;
            }
        }

        if (! $loose) {
            return null;
        }

        $skeleton = self::skeleton($letters);

        foreach ($names as $key => $name) {
            if (in_array($skeleton, array_map(fn ($form) => self::skeleton($form), self::forms($name)), true)) {
                return (string) $key;
            }
        }

        if (self::isAbbreviation($typed)) {
            foreach ($names as $key => $name) {
                if (self::initials($name) === $letters) {
                    return (string) $key;
                }
            }
        }

        return null;
    }

    // The ways a list name can be written: as a whole, without a note in brackets, and each town of a
    // name joining two ("Burewala & Gaggo").
    private static function forms(mixed $name): array
    {
        $plain = self::withoutNote($name);
        $forms = [self::letters($name), self::letters($plain)];

        foreach (preg_split('/\s*(?:&|\+|\/|,|\band\b)\s*/i', $plain) ?: [] as $part) {
            $forms[] = self::letters($part);
        }

        return array_values(array_unique(array_filter($forms, fn ($form) => $form !== '')));
    }

    // A name's sound: doubled letters once, and no vowels after the first letter.
    private static function skeleton(string $letters): string
    {
        $single = (string) preg_replace('/(.)\1+/', '$1', $letters);

        return $single === '' ? '' : $single[0].preg_replace('/[aeiouy]/', '', substr($single, 1));
    }

    // The first letter of each word, without a note in brackets: "Toba Tek Singh (Sew)" is "tts".
    private static function initials(mixed $name): string
    {
        $words = preg_split('/[^a-z]+/', strtolower(self::withoutNote($name)), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return count($words) > 1 ? implode('', array_map(fn ($word) => $word[0], $words)) : '';
    }

    private static function isAbbreviation(string $typed): bool
    {
        return preg_match('/^[A-Z]{2,'.self::ABBREVIATION.'}$/', str_replace(['.', ' '], '', $typed)) === 1;
    }

    private static function withoutNote(mixed $name): string
    {
        return trim((string) preg_replace('/\([^)]*\)/', ' ', self::spaced($name)));
    }
}
