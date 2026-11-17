<?php

namespace App\Dashboards\Initiatives\LocalGovernment;

// Reads the department response file as the design does.
// - SMU's weekly reports: the "Issues Identified" text is split into problems, office by office
//   ("UCs 46 & 41 had no office board. ... No service delivery in UCs 46 & 47.").
// - The department's answer to each problem is read from its response by fixed rules: "office board
//   installed" is fixed, "holds additional charge" a reason given, "no evidence-based report" denied,
//   an order to all staff only instructions, and nothing said not answered.
// - Staff actions (PEEDA proceedings, explanations, warnings, suspensions) and the UCs and MCs a
//   response names.
// These are wording rules, not names: a new response with new wording falls to "not answered" or
// "reason given: other", and its full text is always shown on the page.
class UcMcReportReader
{
    public const SECRETARY_ISSUES = ['closed', 'absent', 'noserv'];

    // Problem words in SMU's issue text, in the order the design lists them.
    private const ISSUE_WORDS = [
        'closed' => '/\bclosed\b/iu',
        'absent' => '/\babsent\b|\bunavailable\b|not present|not available/iu',
        'noserv' => '/no service delivery/iu',
        'private' => '/private individual|private person/iu',
        'board' => '/office boards?\b/iu',
        'dilap' => '/dilapidated/iu',
        'unclean' => '/unclean/iu',
        'nofee' => '/fee schedules? (?:was|were) not displayed|fee schedule not displayed/iu',
        'over' => '/overcharg/iu',
    ];

    // An office named in SMU's issue text: UC numbers, or an MC ("MC Hazro", "CO MC Hazro", "UC Jauharabad").
    private const ISSUE_REF = '/\b(?:UCs?|Union Councils?)\s*(?:#|No\.?)?\s*-?\s*(\d+(?:\s*(?:,|&|and)\s*\d+)*)'
        .'|\b(?:COs?\s+)?(?:MCs?|UC)\s+(?:MC\s+)?((?!(?:was|were|had|offices?|and)\b)[A-Za-z]+(?:\s+(?!(?:Was|Were|Had|Offices?|And)\b)[A-Z][A-Za-z]+)*)/u';

    // What a sentence of the department's response is about.
    private const TOPIC = [
        'board' => '/\bboards?\b/iu',
        'nofee' => '/fee schedule|fee board|rate list/iu',
        'unclean' => '/clean/iu',
        'dilap' => '/building|rehabilitat|repair|renovat|construct|development funds/iu',
        'private' => '/private (?:individual|person)|unauthori[sz]ed/iu',
        'over' => '/overcharg|over-charg|excess (?:fee|amount)/iu',
    ];

    // Why a secretary was away, as the department explains, checked in this order.
    private const REASONS = [
        'charge' => '/additional charges?|look after charge|charge of (?:adjacent|another|other|two)/iu',
        'field' => '/culling|stray dog|field duty|in (?:the )?field|suthra|survey|inspection|campaign|polio|dengue/iu',
        'court' => '/\bcourt\b|arbitration|hearing|ombudsman|tribunal/iu',
        'leave' => '/\bleave\b/iu',
        'present' => '/present\b.{0,40}\b(?:UC|union council) offices?|actually present|present on duty|present at (?:their|his) (?:respective )?(?:UC )?offices?/iu',
        'office' => '/\bADLG\b|\bDDLG\b|\bAC office|\bDC office|office of (?:the )?(?:ADLG|DDLG|AC|DC|Administrator|Assistant|Deputy)|Administrator|meeting|official (?:assignment|task|work|record|documents?)|present in (?:the )?office/iu',
    ];

    private const STAFF_ACTION = 'proceedings|PEEDA|explanation|show[\s-]*cause|warning|suspend|stopped|deduct|withheld|action (?:has been |is being )?(?:taken|initiated|recommended)|disciplinary|departmental inquiry';

    private const FIXED = '/install|affix|placed|put up|displayed|display|fixed|erected|reinstall|pasted|hung|mounted|painted/iu';

    private const CLEANED = '/ensured|done|carried out|completed|has been|have been|removed|made|maintained/iu';

    private const LATER = '/funds?|would be started|will be started|as and when|budget|estimate|scheme/iu';

    private const DENIED = '/\bno (?:substantial )?(?:evidence|proof)|not found|no complaint|not received|baseless|no (?:such )?report|false|incorrect|not (?:been )?proved|\binquired\b/iu';

    private const GENERAL = '/\ball\b.{0,40}\b(?:UC|union council|secretar|staff|COs?)\b/iu';

    private const DIRECT = '/direct|instruct|advis|warned all|ensure|strictly/iu';

    // Staff actions named anywhere in a response.
    private const STAFF_ACTIONS = [
        'expl' => '/explanation/iu',
        'show' => '/show[\s-]*cause/iu',
        'proc' => '/PEEDA|proceeding|disciplinary/iu',
        'warn' => '/warning|warned/iu',
        'susp' => '/suspend/iu',
        'pay' => '/pay (?:has been |was )?(?:stopped|deducted|withheld)|salary (?:has been |was )?(?:stopped|withheld|deducted)|stoppage/iu',
        'rec' => '/action (?:has been |is )?recommended|recommended (?:for|against)/iu',
    ];

    // The problems SMU's issue text names: [[['U', '46'] or ['M', 'Hazro'], 'board'], ...] in text order.
    public function problems(?string $text): array
    {
        $items = [];

        foreach (preg_split('/(?<=\.)\s+(?=[A-Z])|\n+/u', (string) $text) ?: [] as $sentence) {
            $last = [];

            foreach ($this->clauses($sentence) as $clause) {
                $refs = $this->issueRefs($clause);
                if ($refs !== []) {
                    $last = $refs;
                }

                $issues = [];
                foreach (self::ISSUE_WORDS as $issue => $pattern) {
                    if (preg_match($pattern, $clause) === 1) {
                        $issues[] = $issue;
                    }
                }

                // "office board" counts only when the board was missing.
                if (in_array('board', $issues, true) && preg_match('/\bno office boards?\b|office boards? (?:was|were) not/iu', $clause) !== 1) {
                    $issues = array_values(array_diff($issues, ['board']));
                }

                if ($issues === [] || $last === []) {
                    continue;
                }

                foreach ($issues as $issue) {
                    foreach ($last as $ref) {
                        $items[implode('|', $ref).'|'.$issue] = [$ref, $issue];
                    }
                }
            }
        }

        return array_values($items);
    }

    // The department's answer to one problem at one office: [type, reason or null].
    public function answer(?string $text, array $ref, string $issue): array
    {
        $units = $this->units((string) $text);
        $own = array_values(array_filter($units, fn ($unit) => $unit['refs'] && $this->mentions($unit['text'], $ref)));
        $general = array_values(array_filter($units, fn ($unit) => ! $unit['refs']));

        if (in_array($issue, self::SECRETARY_ISSUES, true)) {
            return $this->secretaryAnswer(array_column($own, 'text'), array_column($general, 'text'), $ref);
        }

        $topic = self::TOPIC[$issue];
        $about = [];
        foreach ($own as $unit) {
            if (in_array($issue, $unit['topics'], true)
                || ($issue === 'over' && in_array('nofee', $unit['topics'], true) && preg_match('/overcharg/iu', $unit['text']) === 1)) {
                $about[] = $unit['text'];
            }
        }

        foreach ($about as $sentence) {
            $answer = match ($issue) {
                'dilap' => preg_match(self::LATER, $sentence) === 1 ? 'later'
                    : (preg_match('/repaired|renovated|rehabilitated|completed/iu', $sentence) === 1 ? 'acted' : null),
                'private', 'over' => preg_match(self::DENIED, $sentence) === 1 ? 'denied'
                    : (preg_match('/'.self::STAFF_ACTION.'|refund|returned/iu', $sentence) === 1 ? 'acted' : null),
                'unclean' => preg_match(self::CLEANED, $sentence) === 1 ? 'acted'
                    : (preg_match(self::DENIED, $sentence) === 1 ? 'denied' : null),
                default => preg_match(self::FIXED, $sentence) === 1 ? 'acted' : null,
            };

            if ($answer !== null) {
                return [$answer, null];
            }
        }

        // A sentence on the topic that names no office, and is not an order to all staff, counts for
        // every office reported for it ("No evidence-based report of overcharging has been received").
        $generalTexts = array_column($general, 'text');
        foreach ($generalTexts as $sentence) {
            if (preg_match($topic, $sentence) !== 1 || preg_match(self::GENERAL, $sentence) === 1) {
                continue;
            }
            if (in_array($issue, ['private', 'over'], true) && preg_match(self::DENIED, $sentence) === 1) {
                return ['denied', null];
            }
            if (in_array($issue, ['board', 'nofee'], true) && preg_match(self::FIXED, $sentence) === 1 && preg_match(self::DIRECT, $sentence) !== 1) {
                return ['acted', null];
            }
            if ($issue === 'unclean' && preg_match(self::CLEANED, $sentence) === 1 && preg_match(self::DIRECT, $sentence) !== 1) {
                return ['acted', null];
            }
        }

        // An order about the fee schedule also answers overcharging.
        $orderTopic = $issue === 'over' ? '/overcharg|over-charg|excess (?:fee|amount)|fee schedule/iu' : $topic;
        foreach ($generalTexts as $sentence) {
            if ($ref[0] === 'U' && preg_match($orderTopic, $sentence) === 1 && preg_match(self::DIRECT, $sentence) === 1) {
                return ['directed', null];
            }
        }

        return ['notans', null];
    }

    // The staff actions a response names, in a fixed order.
    public function staffActions(?string $text): array
    {
        $found = [];
        foreach (self::STAFF_ACTIONS as $key => $pattern) {
            if (preg_match($pattern, (string) $text) === 1) {
                $found[] = $key;
            }
        }

        return $found;
    }

    // The UC numbers a response names, as written ("UCs # 46 & 41", "Union Council No. 97", "UCs of 71, 78").
    public function numbers(?string $text): array
    {
        preg_match_all('/\b(?:UCs?|Union Councils?)\s*(?:of\s+)?(?:#|No\.?|Nos?\.?)?\s*-?\s*(\d+(?:\s*,?\s*(?:,|&|and)?\s*(?:#\s*)?\d+)*)/iu', (string) $text, $matches);
        $numbers = [];
        foreach ($matches[1] as $list) {
            preg_match_all('/\d+/', $list, $digits);
            array_push($numbers, ...$digits[0]);
        }

        return $numbers;
    }

    // The MCs a letter names ("MC Hafizabad", "MC Sharaqpur").
    public function mcNames(?string $text): array
    {
        preg_match_all('/\bMCs?\s+([A-Z][A-Za-z]+(?:\s+[A-Z][A-Za-z]+)*)/u', (string) $text, $matches);

        return array_values(array_unique($matches[1]));
    }

    private function secretaryAnswer(array $own, array $general, array $ref): array
    {
        foreach ($own as $sentence) {
            if (preg_match('/'.self::STAFF_ACTION.'/iu', $sentence) === 1) {
                return ['acted', null];
            }
        }
        foreach ($own as $sentence) {
            if (preg_match('/\bdirections?\b|\binstructions?\b/iu', $sentence) === 1) {
                return ['directed', null];
            }
        }
        foreach (self::REASONS as $reason => $pattern) {
            foreach ($own as $sentence) {
                if (preg_match($pattern, $sentence) === 1) {
                    return ['explained', $reason];
                }
            }
        }
        foreach ($own as $sentence) {
            if (preg_match(self::DENIED, $sentence) === 1 && preg_match('/inquired/iu', $sentence) !== 1) {
                return ['denied', null];
            }
        }
        foreach ($own as $sentence) {
            if (preg_match('/secretar|\bCO\b/iu', $sentence) === 1) {
                return ['explained', 'other'];
            }
        }
        foreach ($general as $sentence) {
            if ($ref[0] === 'U' && preg_match('/movement register|duty schedule|remain (?:available|present|vigilant)|office hours|attendance/iu', $sentence) === 1) {
                return ['directed', null];
            }
        }

        return ['notans', null];
    }

    // Commas and "and"/"&" that start a new statement. A piece that names offices but no problem is the
    // start of a list ("UCs 31, 46, MC Sukheki had no office board"), so it joins the next piece.
    private function clauses(string $sentence): array
    {
        $parts = array_values(array_filter(
            preg_split('/,\s*(?=[A-Za-z])|\s+and\s+(?=[a-z])|\s+&\s+(?=[a-z])/u', $sentence) ?: [],
            fn ($part) => trim($part) !== ''
        ));

        $out = [];
        $carry = '';
        foreach ($parts as $part) {
            $part = $carry.$part;
            if ($this->issueRefs($part) !== [] && ! $this->hasIssue($part)) {
                $carry = $part.', ';

                continue;
            }
            $carry = '';
            $out[] = $part;
        }
        if ($carry !== '') {
            $out[] = $carry;
        }

        return $out;
    }

    private function hasIssue(string $text): bool
    {
        foreach (self::ISSUE_WORDS as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }

    private function issueRefs(string $clause): array
    {
        preg_match_all(self::ISSUE_REF, $clause, $matches, PREG_SET_ORDER);
        $refs = [];
        foreach ($matches as $match) {
            if (($match[1] ?? '') !== '') {
                preg_match_all('/\d+/', $match[1], $digits);
                foreach ($digits[0] as $number) {
                    $refs[] = ['U', $number];
                }
            } elseif (($match[2] ?? '') !== '') {
                $refs[] = ['M', $match[2]];
            }
        }

        return $refs;
    }

    // Sentences, each with whether it names an office and what it is about. A sentence naming offices
    // but no topic takes the topic of the sentence before it, when that one named no office.
    private function units(string $text): array
    {
        $units = [];
        $previousTopics = [];
        $previousRefs = true;

        foreach (preg_split('/(?<=[.;])\s+(?=[A-Z])|\n+/u', $text) ?: [] as $sentence) {
            if (trim($sentence) === '') {
                continue;
            }

            $topics = [];
            foreach (self::TOPIC as $key => $pattern) {
                if (preg_match($pattern, $sentence) === 1) {
                    $topics[] = $key;
                }
            }
            $refs = $this->numbers($sentence) !== [] || preg_match('/\bMCs?\b/u', $sentence) === 1;
            if ($refs && $topics === [] && ! $previousRefs) {
                $topics = $previousTopics;
            }

            $units[] = ['text' => $sentence, 'topics' => $topics, 'refs' => $refs];
            [$previousTopics, $previousRefs] = [$topics, $refs];
        }

        return $units;
    }

    private function mentions(string $sentence, array $ref): bool
    {
        if ($ref[0] === 'U') {
            return in_array($ref[1], $this->numbers($sentence), true);
        }

        $name = (string) preg_replace('/[^a-z]/', '', mb_strtolower($ref[1]));

        return $name !== '' && str_contains((string) preg_replace('/[^a-z]/', '', mb_strtolower($sentence)), substr($name, 0, 5));
    }
}
