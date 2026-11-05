<?php

namespace App\Dashboards\Initiatives\SchoolEducation;

// The department's replies are free text, so outcomes come from reading their wording.
class PsrpPhase1ReplyClassifier
{
    // Which outcome represents a school that has several replies. The strongest action wins.
    public const PRIORITY = ['penalty', 'warning', 'fixed', 'inquiry', 'cleared', 'na'];

    public static function classify(string $text): array
    {
        $reply = strtolower($text);

        return [
            'outcome' => self::outcome($reply),
            'penalties' => self::penalties($reply),
            'sed' => preg_match('/responsibility (of )?sed|provision of boundary[^.]*\bsed\b/', $reply) === 1 ? 1 : 0,
        ];
    }

    // A reply can say "no penalty" about one issue and still give a percentage for another.
    private static function hasPenalty(string $reply): bool
    {
        return preg_match('/\d\s*%?\s*penalty/', $reply) === 1
            || (str_contains($reply, 'penalty') && preg_match('/\bno penalty\b/', $reply) !== 1);
    }

    private static function outcome(string $reply): string
    {
        return match (true) {
            preg_match('/not handed over|not related to psrp|reallocated|reacllocated/', $reply) === 1 => 'na',
            str_contains($reply, 'warning letter') => 'warning',
            preg_match('/being investigated|inquiry report/', $reply) === 1 => 'inquiry',
            self::hasPenalty($reply) => 'penalty',
            preg_match('/repaired|pictures were old|outside\/open areas/', $reply) === 1 => 'fixed',
            default => 'cleared',
        };
    }

    // The issues a penalty was imposed for.
    private static function penalties(string $reply): array
    {
        if (! self::hasPenalty($reply)) {
            return [];
        }

        // Only sentences giving a penalty count. Others may mention an issue that was not penalised.
        $sentences = array_filter(
            preg_split('/\.\s|\.$|\)\s/', $reply),
            fn ($sentence) => str_contains($sentence, 'penalty') && preg_match('/\bno penalty\b/', $sentence) !== 1
        );

        // Some replies are so broken up that the issue sits in another sentence.
        return self::issuesIn(implode(' ', $sentences)) ?: self::issuesIn($reply);
    }

    private static function issuesIn(string $text): array
    {
        $found = [];

        foreach ([
            'firstaid' => '/(first|firs|flest)[- ]?aid/',
            'water' => '/drinking water|\bwater\b/',
            'sch' => '/unclean|undean|undear|cleanliness(?! is satisfactory)/',
            'furniture' => '/furniture/',
            'books' => '/text ?books?|free books/',
            'salary' => '/salar/',
            'fees' => '/\bfee/',
            'wall' => '/boundary wall/',
            'danger' => '/dangerous/',
            'teach' => '/teacher/',
        ] as $issue => $pattern) {
            if (preg_match($pattern, $text) === 1) {
                $found[] = $issue;
            }
        }

        return $found;
    }
}
