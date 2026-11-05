<?php

namespace App\Dashboards\Initiatives\SchoolEducation;

// The department's replies are free text, so outcomes come from reading their wording.
class PsrpPhase2ReplyClassifier
{
    // What a reply has to mention for each issue to count as addressed.
    private const THEMES = [
        'danger' => '/danger|repair|safety|roof|building|crack/',
        'water' => '/water|cooler|filter|pump|motor|boring/',
        'gDesk' => '/desk|furnit|bench/',
        'gChair' => '/chair|furnit/',
        'gWash' => '/wash|toilet/',
        'gCls' => '/room|classr|building/',
        'cls' => '/room|clean|repair|white ?wash|paint|building/',
        'wash' => '/wash|toilet|clean/',
        'sch' => '/clean|repair|tidy|white ?wash|paint|better condition|premises/',
        'wall' => '/wall|boundary|boundry/',
        'nofurn' => '/furnit|desk|chair/',
    ];

    public static function classify(?string $text): array
    {
        $reply = mb_strtolower(trim((string) $text));

        return [
            'outcome' => self::outcome($reply),
            'self' => preg_match('/licen|focal person|statement/', $reply) === 1 ? 1 : 0,
        ];
    }

    // The issues found at a visit that the school's replies mention.
    public static function addressed(array $issues, array $replies): array
    {
        $text = mb_strtolower(implode(' ', $replies));

        return array_values(array_filter(
            $issues,
            fn ($issue) => isset(self::THEMES[$issue]) && preg_match(self::THEMES[$issue], $text) === 1
        ));
    }

    private static function outcome(string $reply): string
    {
        return match (true) {
            $reply === '' => 'other',
            preg_match('/\bno action (required|needed)\b|^all okay/', $reply) === 1 => 'noaction',
            preg_match('/directe?e?d|direction|instruct|advis|(been|further) asked/', $reply) === 1 => 'directed',
            preg_match('/inaccura|discrepan|no reduction|not any decrease|at the time of visit|smu team/', $reply) === 1 => 'dispute',
            preg_match('/\bfunds?|nofunds|payment/', $reply) === 1 => 'funds',
            preg_match('/under process|in process|in progress|under consideration|\bwill\b|soon|ordered|order (has|for)|\bplaced\b|request|procur(?!ed as needed)|shortly|tomorrow|campaign|working on|being arranged|taken up/', $reply) === 1 => 'pending',
            preg_match('/\bnow\b|repair|clean|avai|arranged|functional|install|maintain|fixed|tidy|completed|resolved|provided|white ?wash|paint|renovat|enough|sufficient|inventory|useable|present/', $reply) === 1 => 'fixed',
            default => 'other',
        };
    }
}
