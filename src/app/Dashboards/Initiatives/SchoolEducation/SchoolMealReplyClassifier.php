<?php

namespace App\Dashboards\Initiatives\SchoolEducation;

// Reads the response sheet the way the School Meal design does. Each SMU observation is split into
// the issues it raises, and each issue gets the kind of response the department gave to it.
// The rules were checked against the design's own reading of the sheet.
class SchoolMealReplyClassifier
{
    // An observation that is a rating, not an issue ("Satisfactory", "NIL").
    private const NO_ISSUE = '/^(?:(?:average\s+)?satisfactory|good|nil|ok(?:ay)?|no\s+issues?(?:\s+found)?|all\s+(?:ok|fine|good))\.?$/i';

    // A response cell that only says no response has come yet.
    private const NO_RESPONSE = '/^no\s+(?:departmental\s+|department\s+)?(?:response|reply)\s+(?:has\s+been\s+)?(?:received|yet|on\s+file)\b/i';

    // Misuse of the milk wins wherever it is mentioned: "stock register", "kept at a teacher's home".
    private const MISUSE = '/misuse|personal\s+use|\bhomes?\b|transparen|not\s+distributed|no\s+order\s+for\s+distribution|distribution\s+was\s+recorded\s+as\s+zero|no\s+milk\s+distribution|inflated|delivered\s+to\s+\d+\s+students\s+while|carrying\s+milk|in\s+their\s+bags|stored\s+at|\bstorage\b|only\s+empty|stock\s+register|mismanage/i';

    // What else an issue can be about, each in its topic. The one named first in the text counts.
    private const ASPECTS = [
        'quality' => ['quality', '/quality|expir|harm|illness|\bsick|vomit|dissatisf/i'],
        'staff' => ['staff', '/\bteachers?\b|headmistress|headmaster|head\s*teacher|\bstaff\b|attendance|discipline|dismissed|misconduct|administrative|\bexam|early\s+leaves?|left\s+early|\bclosed\b/i'],
        'late' => ['stock', '/deliver|\bdel[ie]+v[e]?ry|on\s+time|\blate\b|\bdelay/i'],
        'avail' => ['stock', '/stock|\bsupply|shortage\s+of\s+(?:milk|stock)|run\s+out|not\s+available|availability|did\s+not\s+receive|not\s+receiv/i'],
        'furn' => ['furn', '/furniture|\bfloor\b|classroom\s+space|sitting\s+arrangement/i'],
        'building' => ['clean', '/building|deteriorat|plaster|maintenance|premises/i'],
        'waste' => ['clean', '/waste|dump|scattered|dispos|used\s+milk\s+packs?|empty\s+(?:milk\s+)?pack|wrapper/i'],
        'clean' => ['clean', '/clean|hygien|sanitation/i'],
    ];

    // A sentence starting like this goes on about the one before it ("Such conditions...").
    private const CONTINUES = '/^(?:such|this|these|that|it|they)\b/i';

    // A clause starting like this goes on about the one before it, so it is never split off.
    private const CLAUSE_CONTINUES = '/^(?:\p{Ll}+ing|while|but|which|whoch|whereas|as|so|whom|who)\b/u';

    // What a response says was done. Action on staff, a fix on the ground, or both, count as acted.
    private const STAFF_ACTIONS = [
        'susp' => '/suspend|suspension/i',
        'show' => '/show[\s-]*cause/i',
        'expl' => '/explanation/i',
        'warn' => '/\bwarn(?:ed|ing|ings)?\b/i',
    ];

    private const FIXES = [
        'stock' => '/\bnow\s+(?:sufficient\s+)?stock\b/i',
        'post' => '/\bposted\b/i',
        'clean' => '/\bwhite\s?washed\b|\b(?:room|school|premises)\s+(?:was|were|has\s+been|have\s+been)\s+cleaned\b|\bwere\s+collected\b|\bsweeper\b[^.]*\bensure\s+cleanliness/i',
        'sop' => '/incorporated\s+in\s+(?:the\s+)?SOPs|ensured\s+to\s+follow|timetable\s+has\s+been\s+issued/i',
        'room' => '/arranged\s+inside\s+the\s+room|served\s+indoors/i',
    ];

    // Promised for later: waiting for funds or a date.
    private const PLAN = '/will\s+be\s+(?:done|addressed|carried\s+out|repaired)|will\s+becarried|within\s+\d+\s+days|as\s+soon\s+as\s+funds|upcoming\s+\w+\s+quarter/i';

    // The problem denied, or the practice the department allows.
    private const DENIED = '/stock\s+(?:is|was)\s+(?:available|present)|\bp(?:er|re)mitted\b|considered\s+acceptable/i';

    // Orders sent to district offices or the supplier.
    private const DIRECTED = '/\bdirect(?:ed|ions?|ives?)\b|\binstruct(?:ed|ions?)\b|guidelines|letter\b[^.]*\b(?:written|issued)\b[^.]*\bto\b|request\s+has\s+been\s+made/i';

    // The visit form checks attendance, not how teachers behave.
    private const NOT_CHECKED = '/conduct/i';

    // One report's observation and the department's response to it (all of a merged block's
    // responses together), as the issues the page counts:
    // [{i: issue, c: topic, t: response type, sa?: [staff actions], fx?: fix, nck?: 1}].
    // A rating such as "Satisfactory" raises no issue: {c: 'ok', t: null}.
    public static function items(?string $observation, ?string $response): array
    {
        $observation = self::clean($observation);
        $response = self::response($response);

        if ($observation !== null && preg_match(self::NO_ISSUE, $observation) === 1) {
            return [['i' => $observation, 'c' => 'ok', 't' => null]];
        }

        $points = $observation === null ? [] : self::points($observation);

        // No observation was written: the response says what it was about.
        if ($points === []) {
            $aspect = $response === null ? null : self::aspect($response);

            if ($aspect === null) {
                return [['i' => $observation ?? 'No SMU observation in the sheet', 'c' => 'ok', 't' => null]];
            }

            $points = [['text' => 'No SMU observation in the sheet', 'aspect' => $aspect]];
        }

        // An observation with no topic of its own takes the topic of its response ("Statistics").
        foreach ($points as &$point) {
            $point['aspect'] ??= ($response === null ? null : self::aspect($response)) ?? 'other';
        }
        unset($point);

        $texts = self::answers($points, $response);
        $items = [];

        foreach ($points as $index => $point) {
            $item = ['i' => $point['text'], 'c' => self::topic($point['aspect'])];
            $item += self::reading($texts[$index], $response !== null);

            if ($item['c'] === 'staff' && preg_match(self::NOT_CHECKED, $point['text']) === 1) {
                $item['nck'] = 1;
            }

            $items[] = $item;
        }

        return $items;
    }

    // The issues one observation raises, in order: [{text, aspect}]. A list of short headings
    // ("Poor building conditions and waste management") is one issue per heading; a description
    // stays one issue for as long as it is about the same thing.
    private static function points(string $observation): array
    {
        $points = [];

        foreach (self::sentences($observation) as $sentence) {
            $fragments = self::fragments($sentence);

            // A sentence that only goes on about the last issue, or raises none of its own.
            $raises = array_filter($fragments, fn ($fragment) => $fragment['aspect'] !== null);
            if ($points !== [] && ($raises === [] || preg_match(self::CONTINUES, $sentence) === 1)) {
                $points[count($points) - 1]['text'] .= ' '.$sentence;

                continue;
            }

            foreach ($fragments as $at => $fragment) {
                $last = count($points) - 1;
                $fragment['joint'] = $at === 0 ? ' ' : $fragment['joint'];

                // Same subject, or nothing new, goes on with the last issue.
                if ($last >= 0 && ($fragment['aspect'] === null || $points[$last]['aspect'] === null
                    || $fragment['aspect'] === $points[$last]['aspect'])) {
                    $points[$last]['text'] .= $fragment['joint'].$fragment['text'];
                    $points[$last]['aspect'] ??= $fragment['aspect'];

                    continue;
                }

                $points[] = ['text' => $fragment['text'], 'aspect' => $fragment['aspect']];
            }
        }

        return array_map(fn ($point) => [
            'text' => self::tidy($point['text'], count($points) > 1),
            'aspect' => $point['aspect'],
        ], $points);
    }

    // A sentence's parts split at commas and, in a short heading, at "and" or "&". Each part
    // says what it is about, or null: [{text, aspect, joint}], joint being what came before it.
    private static function fragments(string $sentence): array
    {
        $parts = [];
        $clauses = preg_split('/(\s*,\s+)/', $sentence, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$sentence];

        // Clauses and the commas between them, so every part keeps the text exactly as typed.
        for ($at = 0; $at < count($clauses); $at += 2) {
            $clause = $clauses[$at];
            $joint = $at === 0 ? '' : $clauses[$at - 1];

            // "..., raising concerns over transparency and hygiene" goes on about what came before.
            if ($parts !== [] && preg_match(self::CLAUSE_CONTINUES, $clause) === 1) {
                $parts[count($parts) - 1]['text'] .= $joint.$clause;

                continue;
            }

            // In ", and empty packets ..." the "and" goes with the comma before it.
            if (preg_match('/^(?:and|&)\s+/i', $clause, $lead) === 1) {
                $joint .= $lead[0];
                $clause = substr($clause, strlen($lead[0]));
            }

            // Only a short heading is a list joined by "and": a longer clause is one description.
            $words = count(preg_split('/\s+/', trim($clause)) ?: []);
            $list = $words <= 8 ? '/(\s*&\s*|\s+and\s+(?!only\b|other\b|then\b|also\b))/i' : '/(\s*&\s*)/';
            $pieces = preg_split($list, $clause, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$clause];

            for ($piece = 0; $piece < count($pieces); $piece += 2) {
                $parts[] = ['text' => $pieces[$piece], 'joint' => $piece === 0 ? $joint : $pieces[$piece - 1]];
            }
        }

        // Each part's topic once its text is complete.
        foreach ($parts as &$part) {
            $part['aspect'] = self::aspect($part['text']);
        }
        unset($part);

        // A part that raises nothing, said before the first that does, belongs with that one.
        $out = [];
        $carry = '';

        foreach ($parts as $part) {
            if ($part['aspect'] === null && $out === []) {
                $carry .= ($carry === '' ? '' : $part['joint']).$part['text'];

                continue;
            }

            if ($carry !== '') {
                $part['text'] = $carry.$part['joint'].$part['text'];
                $part['joint'] = '';
                $carry = '';
            }

            $out[] = $part;
        }

        if ($carry !== '') {
            $out[] = ['text' => $carry, 'aspect' => null, 'joint' => ''];
        }

        return $out;
    }

    // The parts of the response about each issue: [index => {clauses, sentences}]. With one issue,
    // all of it. With more, each clause goes to the issue it names, or the one named before it; what
    // comes before any issue is named goes to the first. An issue nothing is said about gets none.
    private static function answers(array $points, ?string $response): array
    {
        $texts = array_fill(0, count($points), ['clauses' => [], 'sentences' => []]);

        if ($response === null) {
            return $texts;
        }

        if (count($points) === 1) {
            return [0 => ['clauses' => [$response], 'sentences' => [$response]]];
        }

        $last = null;
        $named = false;

        foreach (self::sentences($response) as $sentence) {
            $inSentence = [];
            $clauses = preg_split('/\s*,\s*|\s+and\s+|\s*;\s*/i', $sentence) ?: [];
            $mentions = array_map(fn ($clause) => self::mentioned($points, $clause), $clauses);

            // Words such as "However," at the start go with the issue the sentence goes on to name.
            $ahead = null;
            foreach ($mentions as $mentioned) {
                if ($mentioned !== []) {
                    $ahead = $mentioned;
                    break;
                }
            }

            foreach ($clauses as $at => $clause) {
                $mentioned = $mentions[$at];

                if ($mentioned === null) {
                    // Another subject altogether, such as stock in a reply about cleanliness.
                    $last = null;
                    $ahead = null;

                    continue;
                }

                if ($mentioned !== []) {
                    $named = true;
                    $last = $mentioned;
                    $ahead = null;
                }

                // Action on staff and fixes count for the clause that says them.
                foreach ($mentioned !== [] ? $mentioned : ($ahead ?? $last ?? ($named ? [] : [0])) as $index) {
                    $texts[$index]['clauses'][] = $clause;
                    $inSentence[$index] = true;
                }
            }

            // Orders and promises count for the whole sentence they are in.
            foreach (array_keys($inSentence) as $index) {
                $texts[$index]['sentences'][] = $sentence;
            }
        }

        return $texts;
    }

    // Which issues a clause names: [] for none, null when it names only something else.
    private static function mentioned(array $points, string $clause): ?array
    {
        $aspects = self::aspectsIn($clause);

        // "An explanation letter was issued to the head teacher" names staff only as who was acted on.
        if ($aspects === ['staff'] && self::actsOnStaff($clause)
            && ! in_array('staff', array_column($points, 'aspect'), true)) {
            return [];
        }

        if ($aspects === []) {
            return [];
        }

        $found = [];
        foreach ($points as $index => $point) {
            if (in_array($point['aspect'], $aspects, true)) {
                $found[] = $index;
            }
        }

        return $found === [] ? null : $found;
    }

    // The response type, staff actions and fix for what the response says about one issue.
    private static function reading(array $text, bool $answered): array
    {
        if (! $answered) {
            return ['t' => 'none'];
        }

        if ($text['clauses'] === []) {
            return ['t' => 'notans'];
        }

        $clauses = implode(' ', $text['clauses']);
        $sentences = implode(' ', $text['sentences']);

        $actions = [];
        foreach (self::STAFF_ACTIONS as $id => $pattern) {
            if (preg_match($pattern, $clauses) === 1) {
                $actions[] = $id;
            }
        }

        $fix = null;
        foreach (self::FIXES as $id => $pattern) {
            if (preg_match($pattern, $clauses) === 1) {
                $fix = $id;
                break;
            }
        }

        if ($actions !== [] || $fix !== null) {
            return ['t' => 'fixed'] + ($actions === [] ? [] : ['sa' => $actions]) + ($fix === null ? [] : ['fx' => $fix]);
        }

        return ['t' => match (true) {
            preg_match(self::PLAN, $sentences) === 1 => 'plan',
            preg_match(self::DENIED, $clauses) === 1 => 'disputed',
            preg_match(self::DIRECTED, $sentences) === 1 => 'directed',
            default => 'disputed',
        }];
    }

    private static function actsOnStaff(string $text): bool
    {
        foreach (self::STAFF_ACTIONS as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }

    // The aspect named first in the text, misuse before anything else.
    private static function aspect(string $text): ?string
    {
        if (preg_match(self::MISUSE, $text) === 1) {
            return 'misuse';
        }

        $first = null;
        $at = PHP_INT_MAX;

        foreach (self::ASPECTS as $id => [, $pattern]) {
            if (preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE) === 1 && $match[0][1] < $at) {
                $first = $id;
                $at = $match[0][1];
            }
        }

        return $first;
    }

    // Every aspect a clause names.
    private static function aspectsIn(string $text): array
    {
        $aspects = preg_match(self::MISUSE, $text) === 1 ? ['misuse'] : [];

        foreach (self::ASPECTS as $id => [, $pattern]) {
            if (preg_match($pattern, $text) === 1) {
                $aspects[] = $id;
            }
        }

        return $aspects;
    }

    private static function topic(string $aspect): string
    {
        return $aspect === 'misuse' ? 'misuse' : (self::ASPECTS[$aspect][0] ?? 'other');
    }

    // Sentences end at a full stop after a word, before a capital letter. Numbered points
    // ("directions: 1. ... 2. ...") stay in the sentence that lists them.
    private static function sentences(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/(?<=[\p{L})\]"\'][.!?])\s+(?=\p{Lu})/u', $text) ?: [])));
    }

    // An issue as typed, without the comma or "and" it was split at. A part split off an
    // observation starts with a capital, as the observation does.
    private static function tidy(string $text, bool $split): string
    {
        $text = trim((string) preg_replace('/^(?:and|&)\s+|[\s,;:&]+$/iu', '', trim($text)));

        return $split ? mb_strtoupper(mb_substr($text, 0, 1)).mb_substr($text, 1) : $text;
    }

    // The department's response, or null when the cell only says none has come yet
    // ("No departmental response received till date").
    public static function response(?string $text): ?string
    {
        $text = self::clean($text);

        return $text !== null && preg_match(self::NO_RESPONSE, $text) === 1 ? null : $text;
    }

    // Line breaks inside a cell are only wrapping, so the text reads as one paragraph.
    private static function clean(?string $text): ?string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', (string) $text));

        return $text === '' ? null : $text;
    }
}
