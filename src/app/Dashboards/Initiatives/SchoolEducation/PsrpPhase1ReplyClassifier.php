<?php

namespace App\Dashboards\Initiatives\SchoolEducation;

// The department's replies are free text, so outcomes come from reading their wording.
//
// The first rules are the ones the earlier response file was read with, unchanged, so its replies
// keep their outcomes. The rules after them cover the newer file's wording ("Warning has been issued
// to licensee", "Licensee has been directed to…") and the ways a reply is likely to describe other
// actions, such as a corruption case (FIR, recovery, licence cancelled). A reply that describes no
// action the rules know is counted as "No action needed".
class PsrpPhase1ReplyClassifier
{
    // Which outcome represents a school that has several replies. The strongest action wins.
    public const PRIORITY = ['penalty', 'warning', 'fixed', 'directed', 'inquiry', 'cleared', 'na'];

    // A sanction on the operator: a fine, deduction or recovery, or its licence cancelled.
    private const NEWER_PENALTY = '/\bfined\b|\bfine (?:of|has been|was|is|amounting|imposed)|(?:imposed|impose) (?:a |the )?fine|\bdeduct(?:ed|ion)\b|\brecover(?:y|ed)\b|licen[cs]e[^.]{0,40}\b(?:terminated|cancelled|canceled|revoked)\b|(?:termination|cancellation) of (?:the |his |her |its )?licen[cs]e|\bcancellation\b|black-?list|debarred|forfeit/';

    // The school is outside the programme or was never handed over.
    private const NEWER_NA = '/not be handed over|re-?allocation|merged into|not included in the psrp|not part of (?:the )?psrp|de-?notified/';

    // A warning, an explanation or show-cause notice, or disciplinary action.
    private const NEWER_WARNING = '/warning (?:has been |was |have been )?issued|(?:warning|warned)\b.{0,40}\blicen[cs]ee|explanation (?:has been |was )?called|show[- ]?cause|disciplinary|strict action|action (?:has been |was |is being )?(?:taken|initiated) against|\bsuspended\b/';

    // Under investigation, or taken up with another office: an inquiry, a committee, the police.
    private const NEWER_INQUIRY = '/taken up\b|referred to|\binquir|\benquir|investigat|\bprobe\b|fact[- ]finding|committee (?:has been |was )?(?:constituted|formed|notified)|\bf\.?i\.?r\b(?! ?aid)|anti[- ]corruption|\bpolice\b/';

    // The operator says it is fixed, or being fixed.
    private const NEWER_FIXED = '/ha(?:s|ve) been (?:provided|paid|resolved|appointed|hired|made functional|installed|done|arranged|fixed|completed|released|increased|replaced|constructed|repaired)|resolved the issue|issue resolved|has arranged|made available|made functional|\bprovided\.$|is arranging/';

    // The operator was told to do something, with nothing yet said to be done.
    private const NEWER_DIRECTED = '/\b(?:directed|instructed|advised|sensiti[sz]ed|asked|ordered)\b|has been told|called for a meeting|bound to/';

    public static function classify(string $text): array
    {
        $reply = strtolower($text);

        return [
            'outcome' => self::outcome($reply),
            'penalties' => self::penalties($reply),
            // Said to be the School Education Department's or the Government of Punjab's job, not the operator's.
            'sed' => preg_match('/responsibility (of )?sed|provision of boundary[^.]*\bsed\b|responsibility of (?:the )?(?:government|govt\.?) of (?:the )?punjab|rests? with the (?:government|govt\.?) of (?:the )?punjab/', $reply) === 1 ? 1 : 0,
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
            // The earlier file's rules, in their order
            preg_match('/not handed over|not related to psrp|reallocated|reacllocated/', $reply) === 1 => 'na',
            str_contains($reply, 'warning letter') => 'warning',
            preg_match('/being investigated|inquiry report/', $reply) === 1 => 'inquiry',
            self::hasPenalty($reply) => 'penalty',
            preg_match('/repaired|pictures were old|outside\/open areas/', $reply) === 1 => 'fixed',
            // The newer wording, strongest action first
            preg_match(self::NEWER_PENALTY, $reply) === 1 => 'penalty',
            preg_match(self::NEWER_NA, $reply) === 1 => 'na',
            preg_match(self::NEWER_WARNING, $reply) === 1 => 'warning',
            preg_match(self::NEWER_INQUIRY, $reply) === 1 => 'inquiry',
            preg_match(self::NEWER_FIXED, $reply) === 1 => 'fixed',
            preg_match(self::NEWER_DIRECTED, $reply) === 1 => 'directed',
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
