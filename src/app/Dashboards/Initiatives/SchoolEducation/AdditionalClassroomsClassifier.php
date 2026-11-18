<?php

namespace App\Dashboards\Initiatives\SchoolEducation;

// Puts the visit file's free answers and the department's replies into the design's groups.
// The rules were checked against the design's own data: they give the same group for every visit.
class AdditionalClassroomsClassifier
{
    // What is holding work back, from the reason work stopped or the problem slowing it.
    // Checked in this order, so "Material Quality Concern" counts as poor materials.
    private const CAUSES = [
        'nocontr' => '/contractor\s+(?:not|yet\s+to\s+be)\s+appointed|no\s+contractor/i',
        'funds' => '/fund/i',
        'approval' => '/approval/i',
        'rain' => '/rain|flood|weather/i',
        'bricks' => '/brick|quality|sub-?standard/i',
        'materials' => '/material/i',
        'contr' => '/contractor|slow\s+progress/i',
        'land' => '/\bland\b|\bspace\b/i',
    ];

    // What a remark in our report is about. Checked in this order.
    private const TOPICS = [
        'quality' => '/brick|sub-?standard|masonry|mortar|quality/i',
        'safety' => '/fire|extinguisher|safety/i',
        'cost' => '/\bcost|estimate|financial|budget|\btax/i',
        'clean' => '/toilet|washroom|clean|sanitation|hygien/i',
        'wall' => '/\bwalls?\b/i',
        'work' => '/stopped|halted|contractor|commenced|resumed|slow\s+progress/i',
        'building' => '/dangerous|building/i',
    ];

    // A response that says the thing was done. Anything else gives a reason instead of a fix.
    private const FIXED = '/\b(?:has|have|had|is|are|was|were)\s+(?:now\s+)?(?:been\s+)?(?:now\s+)?(?:repaired|improved|removed|commenced|installed|resumed|restarted|replaced|rectified|cleaned|fixed|provided|placed|started)\b|\bnow\s+(?:available|functional|working)\b/i';

    // A sentence starting like this continues the one before it.
    private const CONTINUES = '/^(?:however|but|moreover|furthermore|further|also|additionally|therefore|hence|thus)\b/i';

    // Ongoing, Work Stopped, Not Started or Completed, as typed in the file.
    public static function status(?string $text): string
    {
        $text = (string) $text;

        return match (true) {
            preg_match('/complet/i', $text) === 1 => 'done',
            preg_match('/not\s*(?:yet\s*)?started|not\s+start/i', $text) === 1 => 'not',
            preg_match('/stop|halt|suspend|abandon/i', $text) === 1 => 'stop',
            default => 'on',
        };
    }

    public static function cause(?string $text): ?string
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        foreach (self::CAUSES as $id => $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return $id;
            }
        }

        return 'other';
    }

    // A site where work has not started is at no stage, whatever the stage column says.
    public static function stage(?string $text, string $status): string
    {
        $text = (string) $text;

        return match (true) {
            $status === 'not' => 'none',
            preg_match('/finish/i', $text) === 1 => 'finish',
            preg_match('/roof/i', $text) === 1 => 'roof',
            preg_match('/found|excavat/i', $text) === 1 => 'found',
            preg_match('/wall|struct/i', $text) === 1 => 'walls',
            default => 'none',
        };
    }

    // How often officials inspect the site. "nr" when the answer is missing or not one of these.
    public static function inspection(?string $text): string
    {
        $text = (string) $text;

        return match (true) {
            preg_match('/never|no\s+inspection|not\s+inspected/i', $text) === 1 => 'never',
            preg_match('/week/i', $text) === 1 => 'week',
            preg_match('/month/i', $text) === 1 => 'month',
            preg_match('/occasion|sometimes|rarely/i', $text) === 1 => 'occ',
            default => 'nr',
        };
    }

    public static function topic(?string $text): ?string
    {
        foreach (self::TOPICS as $id => $pattern) {
            if (preg_match($pattern, (string) $text) === 1) {
                return $id;
            }
        }

        return null;
    }

    // One report's rows (remark and response each) as the issues the page counts:
    // [{i: issue, t: response type, c: topic, x: response or null, cx: background remark}].
    //  - A remark that makes two different points, where the response answers only one of them,
    //    counts as two issues; the other point is "Not answered".
    //  - A remark with no response that raises no problem of its own only describes the one
    //    before it, so it is shown under that one ("SMU also noted") and not counted.
    public static function items(array $rows): array
    {
        $answered = array_filter($rows, fn ($row) => self::clean($row['response'] ?? null) !== null) !== [];
        $items = [];

        foreach ($rows as $row) {
            $remark = self::clean($row['remark'] ?? null);
            $response = self::clean($row['response'] ?? null);

            if ($remark === null && $response === null) {
                continue;
            }

            if ($response === null && $items !== [] && $remark !== null && self::topic($remark) === null) {
                $last = count($items) - 1;
                $items[$last]['cx'] = isset($items[$last]['cx']) ? $items[$last]['cx'].' '.$remark : $remark;

                continue;
            }

            foreach (self::points($remark, $response) as [$point, $reply]) {
                $items[] = [
                    'i' => $point,
                    't' => $reply !== null ? (preg_match(self::FIXED, $reply) === 1 ? 'fixed' : 'disputed') : ($answered ? 'notans' : 'none'),
                    'c' => self::topic($point) ?? 'other',
                    'x' => $reply,
                ];
            }
        }

        return $items;
    }

    // [[point, its response]]. A remark is split only when its points have different topics and
    // the response's topic matches exactly one of them.
    private static function points(?string $remark, ?string $response): array
    {
        $whole = [[$remark ?? '', $response]];
        $replyTopic = self::topic($response);

        if ($remark === null || $response === null || $replyTopic === null) {
            return $whole;
        }

        $groups = [];

        foreach (preg_split('/(?<=[.!?])\s+(?=[A-Z0-9])/', $remark) ?: [] as $sentence) {
            $topic = self::topic($sentence);
            $last = count($groups) - 1;

            // A sentence that carries on from the last one, or raises nothing new, stays with it,
            // and so does one following a lead-in that raised nothing yet.
            if ($last >= 0 && (preg_match(self::CONTINUES, $sentence) === 1 || $topic === null
                || $groups[$last]['topic'] === null || $topic === $groups[$last]['topic'])) {
                $groups[$last]['text'] .= ' '.$sentence;
                $groups[$last]['topic'] ??= $topic;

                continue;
            }

            $groups[] = ['text' => $sentence, 'topic' => $topic];
        }

        $matching = array_filter($groups, fn ($group) => $group['topic'] === $replyTopic);

        if (count($groups) < 2 || count($matching) !== 1) {
            return $whole;
        }

        return array_map(fn ($group) => [$group['text'], $group['topic'] === $replyTopic ? $response : null], $groups);
    }

    // Line breaks inside a cell are only wrapping, so the text reads as one paragraph. A sentence
    // typed straight after the last full stop ("school.Two") gets its space back, as in the design.
    private static function clean(?string $text): ?string
    {
        $text = trim((string) preg_replace(['/\s+/u', '/(?<=\p{Ll})\.(?=\p{Lu}\p{Ll})/u'], [' ', '. '], (string) $text));

        return $text === '' ? null : $text;
    }
}
