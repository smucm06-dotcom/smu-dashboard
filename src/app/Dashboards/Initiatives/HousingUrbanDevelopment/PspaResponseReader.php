<?php

namespace App\Dashboards\Initiatives\HousingUrbanDevelopment;

// Reads the department's response to an SMU report the way the Water Filtration Plants design does.
// - Whose plant it is: a response saying WASA, a council, an NGO or another body runs the plant, or that
//   the plant was abandoned or dropped, makes every problem "not PSPA's plant".
// - The answer to each problem group, read sentence by sentence by fixed wording rules: "cleaning has been
//   done" is fixed, "one operator for two or more plants" a reason given, "the WFP exists on the provided
//   location" denied, "to be rehabilitated" promised, and nothing said not answered.
// What a sentence says about one problem comes first; general sentences ("PSPA has made this WFP fully
// functional", "All mentioned issues have been addressed") count only for the problems they cover.
// These are wording rules, not names: new wording falls to "not answered", and the page always shows
// the full response.
class PspaResponseReader
{
    // The problem groups a report lists, in the design's order.
    public const GROUPS = ['notwork', 'absent', 'unclean', 'taps', 'repair', 'filter', 'water', 'power', 'motor',
        'salary', 'private', 'notfound', 'closed', 'misuse', 'other'];

    // Who runs the plant when it is not PSPA's.
    public const AGENCIES = ['wasa', 'ngo', 'lg', 'phed', 'cant', 'dept', 'aband', 'other'];

    // Words that tie a plant to whoever runs it: "installed and being operated by", "belongs to",
    // "falls under the purview of". A word or two may come before the name ("by a private NGO").
    private const OWNER = '(?:(?:installed|operated|maintained|commissioned|managed|owned|\brun)\b(?:\s+[^\s.]+){0,6}?\s*\bby'
        .'|\bbelongs?\s+to|\b(?:purview|jurisdiction|control)\s+of)\s+(?:the\s+|an?\s+)?(?:[^\s.]+\s+){0,2}?';

    // The names that follow those words, by who they are. The name said first counts.
    private const NAMES = [
        'wasa' => 'WASA\b|Water\s+(?:and|&)\s+Sanitation',
        'phed' => 'PHED\b|Public\s+Health\s+Engineering',
        'cant' => 'Cantonment|Cantt\b',
        'lg' => 'MCs?\b|TMAs?\b|Municipal|Metropolitan|Town\s+Committee|LG\s*&\s*CD|LGCDD?\b|Local\s+Government|District\s+Council',
        'dept' => '(?:School\s*)?Education\s*Department|Health\s*Department|(?:Government\s+)?(?:college|school|hospital|university)\b|[A-Z]\w+\s+Department\b',
        'ngo' => 'private\b|NGOs?\b|(?:local\s+)?community\b|trust\b|welfare\b|foundation\b|donors?\b',
    ];

    // An NGO or private owner named without those words: "(being private)", "the concerned NGO".
    private const NGO_WORDS = '/\bNGOs?\b|\bbeing\s+private\b|\bprivately\s+(?:owned|run|operated|installed)/iu';

    // Abandoned, removed or dropped: "declared abandoned", "the said site has been dropped".
    private const GONE = '/\bdeclared?\s+(?:it\s+|as\s+)?abandon|\babandoned\s+condition|\b(?:presently|now)\s+ab[ao]nd[oa]ned'
        .'|\b(?:site|plants?|wfps?)\s+(?:has|have|had)?\s*(?:been|was|is|were)\s+(?:declared\s+)?(?:dropped|removed|abandoned|dismantled|demolished)\b'
        .'|\bdropped\s+(?:this|the)\s+site|\b(?:is|was|has\s+been|have\s+been)\s+(?:removed|dismantled|demolished)\s+by\b/iu';

    // Not PSPA's, with no one named: "does not fall under the purview of PSPA", "yet to be taken over".
    private const NOT_OURS = '/(?:\bnot|n[\'’]t)\s+fall\s+(?:under|within)\s+(?:the\s+)?(?:[\w&]+\s+){0,4}?(?:purview|jurisdiction)'
        .'|(?:does\s*not|do\s*not|doesn[\'’]?t|don[\'’]?t)\s+relate\s+to\s+(?:the\s+)?PSPA|\bPSPA\s+is\s+not\s+responsible'
        .'|\bnot\s+(?:been\s+)?taken\s+over\s+by\s+(?:the\s+)?PSPA|\bstill\s+belongs?\s+to|\byet\s+to\s+be\s+taken\s+over/iu';

    // A sentence about work still to come, or an order to someone. "Done" wording in it never counts as a
    // fix ("Contractor has been directed to replace the taps"), unless it says the work has been done
    // ("directed to do the necessary cleaning and the same has been done").
    private const PLANNED = '/\b(?:will|shall|would)\b|\bto\s*be\b|\b(?:directed|asked|requested|approached|communicated|instructed|expected|advised)\b'
        .'|\b(?:once|upon|until|unless)\b|\bafter\s+which\b/iu';

    private const DONE = '/\b(?:has|have)\s+(?:already\s+)?been\s+(?:already\s+)?(?:done|completed|carried\s+out)\b|\bsame\s+(?:has|have)\s+been\b/iu';

    // "has not been made functional", "the connection is not intact": no fix and no denial.
    private const NEGATED = '/\b(?:not|never|no\s+longer|yet\s+to)\b|n[\'’]t\b/iu';

    // General sentences. The plant made to work again: "PSPA has made this WFP fully functional during
    // rehabilitation", "Plant is made functional", "The Plants have now been rehabilitated". The plant (or
    // PSPA) must be the one made to work: "the electricity supply has been restored" is not.
    private const MADE = '/\b(?:plants?|wfps?|PSPA|it)\w*(?:\s+[^\s.]+){0,8}?\s+made\b(?:\s+[^\s.]+){0,10}?\s+(?:fully\s+)?(?:functional|operational)\b'
        .'|\b(?:plants?|wfps?|it)\s+(?:has|have)\s+(?:now\s+)?been\s+(?:rehabilitated|revamped|restored)\b|\bfully\s+functional\s+during\s+(?:the\s+)?rehabilita/iu';

    // The plant working: "Plant is operational", "The instant plants are functional".
    private const WORKING = '/\b(?:plants?|wfps?|it)\s*(?:is|are)\s+(?:now\s+|still\s+)?(?:fully\s+)?(?:functional|operational)\b/iu';

    // Every point answered: "All mentioned issues have been addressed", "resolving all mentioned observations".
    private const ALL_FIXED = '/\b(?:issues|observations|problems|deficiencies)\s+(?:have|has|are|were)\s+(?:now\s+)?(?:been\s+)?(?:addressed|resolved|rectified|removed|fixed)\b'
        .'|\bresolving\s+all\b/iu';

    // The plant on the rehabilitation list: "among the dysfunctional WFPs to be rehabilitated / revamped",
    // "will be fully functional by 30.06.2025". Not "handed over to the contractor for rehabilitation".
    private const REHAB = '/\bto\s*be\s+(?:rehabilitated|revamped)\b|\bwill\s+be\s+(?:fully\s+)?(?:functional|operational)\s+by\b'
        .'|\b(?:will|shall)\s+be\s+done\s+during\b|\brehabilitation\s+drive\b[^.]*\b(?:underway|under\s+way|in\s+progress)/iu';

    // Each group's own wording, checked in this order: the first rule that a sentence meets gives the answer.
    // Rules about the group itself come before the general sentences above.
    private const RULES = [
        'notwork' => [
            ['fixed', self::MADE],
            ['fixed', self::WORKING],
            // "shall be made operational upon installation of the meter", "will become operational".
            ['later', '/\b(?:will|shall)\b[^.]*\b(?:functional|operational)\b|\bre-?commission/iu'],
            ['fixed', self::ALL_FIXED],
            ['later', self::REHAB],
        ],
        'absent' => [
            // "rehabilitated & made functional by PSPA with on duty operators".
            ['fixed', '/\bwith\s+on[\s-]*duty\s+operators?\b|\boperators?\s+(?:has|have)\s+(?:now\s+)?been\s+(?:deputed|posted|appointed|engaged|hired)\b/iu'],
            // An order about the operators, before the reason below: "directed to ensure ... on duty operator",
            // "directed to recover the salary shortfall ... of the operators".
            ['later', '/\boperators?\b[^.]*\b(?:directed|asked|instructed)\b|\b(?:directed|asked|instructed)\b[^.]*\boperators?\b/iu'],
            // "one operator ... for at least two or more than two water filtration plants".
            ['explained', '/\bone\s+operat\w*\b[^.]*\b(?:two|2|more|several|multiple)\b|\bunavailab\w*\s+may\s+be\s+due|\bpresence\s+of\s+(?:the\s+)?operator\s+(?:on|at)\s+(?:the\s+)?other\b|\bon\s+leave\b/iu'],
            ['denied', '/\boperators?\s+(?:was|were|is|remains?)\s+(?:present|available|on\s+duty)\b/iu'],
            ['fixed', self::ALL_FIXED],
            ['later', self::REHAB],
        ],
        'unclean' => [
            // "directed to do the necessary cleaning and the same has been done", "has been cleaned".
            ['fixed', '/\bclean\w*\b[^.]*\b(?:has|have)\s+(?:already\s+)?been\s+(?:already\s+)?(?:done|ensured|carried\s+out|completed)\b|\bsame\s+(?:has|have)\s+been\b[^.]*\bclean|\bclean\w*\b[^.]*\bsame\s+(?:has|have)\s+been|\bcleaned\b/iu'],
            // "Contractor has been directed to ensure the cleanliness".
            ['later', '/\b(?:directed|asked|requested|instructed|assigned|communicated)\b[^.]*\bclean|\bensure\s+(?:the\s+)?(?:necessary\s+)?clean/iu'],
            // The operator is also the cleaner and looks after two plants; or rain is blamed.
            ['explained', '/\b(?:watch\s*guard|operator)\b[^.]*\bclean|\bunclean\w*\s+(?:is|was)\s+due\s+to|\bdue\s+to\s+(?:the\s+)?(?:recent\s+)?(?:heavy\s+)?rain/iu'],
            ['fixed', self::ALL_FIXED],
            ['later', self::REHAB],
        ],
        'taps' => [
            // "Issue regarding water taps has been resolved", "the water tabs made functional".
            ['fixed', '/\bta[pb]s?\b[^.]*\b(?:resolved|replaced|repaired|fixed|installed|restored|made\s+functional)\b|\b(?:replaced|repaired|installed|fixed)\b[^.]*\bta[pb]s?\b/iu'],
            ['later', '/\b(?:directed|asked|requested|instructed)\b[^.]*\bta[pb]s?\b|\bta[pb]s?\b[^.]*\b(?:will|shall)\s+be\b/iu'],
            ['fixed', self::ALL_FIXED],
            ['fixed', self::MADE],
            ['later', self::REHAB],
        ],
        'repair' => [
            // Promised work comes first, even beside "made fully functional": "condition of structure was miserable
            // and necessitate rectification", "tile work will be commenced", "directed to rectify any damages",
            // "PC-I for civil work ... will be carried out upon funds".
            ['later', '/\brectification\b|\b(?:structure|tile\s*works?|civil\s+works?|floor|building)\b[^.]*\b(?:will|shall|would)\b'
                .'|\b(?:directed|asked|instructed)\b[^.]*\b(?:rectify|repair|damages?)\b|\bcivil\s+works?\b[^.]*\b(?:approval|funds?)\b/iu'],
            // "Issues regarding water taps and floor have been resolved", "broken tiles have been replaced",
            // "water tank leakage has been resolved", "The leakage of the tank been rectified".
            ['fixed', '/\b(?:floors?|tiles?|leak\w*|tanks?|structure|building|pipe\w*|vessels?|cover|damages?|repairs?)\b[^.]*\b(?:resolved|replaced|rectified|repaired|fixed|renovated)\b'
                .'|\b(?:repaired|renovated)\b/iu'],
            ['later', self::REHAB],
            ['fixed', self::ALL_FIXED],
            ['fixed', self::MADE],
        ],
        'filter' => [
            // "The filters of instant plant have been replaced", "a minor mechanical defect ... has been resolved".
            ['fixed', '/\bfilters?\b[^.]*\b(?:replaced|resolved|repaired|fixed|changed|cleaned)\b|\b(?:mechanical|technical)\s+(?:defects?|faults?|issues?|problems?)\b[^.]*\bresolved\b/iu'],
            ['later', '/\b(?:directed|asked|requested|instructed)\b[^.]*\bfilters?\b|\bfilters?\b[^.]*\b(?:will|shall)\s+be\b/iu'],
            ['fixed', self::ALL_FIXED],
            ['fixed', self::MADE],
            ['later', self::REHAB],
        ],
        'water' => [
            // "the filter have been replaced to improve the water quality".
            ['fixed', '/\b(?:quality|smell|odou?r|taste|TDS)\b[^.]*\b(?:improved|resolved|rectified|fixed)\b|\bto\s+improve\s+the\s+(?:water\s+)?quality\b/iu'],
            // "providing safe drinking water as per water quality standards", "the quality of water is fit".
            // Not "providing safe drinking water to the public", which every response says.
            ['denied', '/\b(?:as\s+per|according\s+to|within|meets?)\s+(?:the\s+)?(?:\w+\s+)?(?:water\s+)?quality\s+standards?\b|\bquality\s+of\s+(?:the\s+)?water\s+is\s+(?:fit|safe|fine|good)\b'
                .'|\b(?:fit|safe)\s+for\s+(?:human\s+)?(?:drinking|consumption)\b|\bwater\s+(?:samples?|tests?)\b[^.]*\b(?:fit|safe|satisfactory|within\s+limits?)\b/iu'],
            // "the technology is being changed from UF to RO", "directed to ensure the water quality".
            ['later', '/\b(?:technology|reverse\s+osmosis)\b[^.]*\b(?:will\s+be|shall\s+be|being)\s+changed\b|\b(?:directed|asked)\b[^.]*\bquality\b/iu'],
            ['fixed', self::ALL_FIXED],
            ['later', self::REHAB],
        ],
        'power' => [
            // "The electric connection is intact", even beside "made fully functional".
            ['denied', '/\b(?:electric\w*|power|connection|meter)\b[^.]*\bintact\b/iu'],
            // "The issue regarding electricity has been resolved", "Electricity bills are being paid by PSPA".
            ['fixed', '/\b(?:electric\w*|power|meter|transformer|connection)\b[^.]*\b(?:resolved|restored|repaired|replaced|reconnected)\b'
                .'|\bbills?\b[^.]*\b(?:paid|being\s+paid)\s+by\s+(?:the\s+)?PSPA\b|\bPSPA\s+is\s+(?:\w+\s+)?paying\b/iu'],
            ['later', '/\b(?:electric\w*|power|meter|transformer|connection|bills?)\b[^.]*\b(?:will|shall)\b|\b(?:requested|directed|asked)\b[^.]*\b(?:electric\w*|power|meter|transformer)\b/iu'],
            ['fixed', self::ALL_FIXED],
            ['fixed', self::MADE],
            ['later', self::REHAB],
        ],
        'motor' => [
            // "The motor has been replaced"; otherwise the plant made functional again.
            ['fixed', '/\b(?:motors?|pumps?|bore|machinery)\b[^.]*\b(?:replaced|resolved|repaired|fixed|installed|made\s+functional)\b/iu'],
            ['later', '/\b(?:directed|asked|requested|instructed)\b[^.]*\b(?:motors?|pumps?|bore)\b|\b(?:motors?|pumps?|bore)\b[^.]*\b(?:will|shall)\s+be\b/iu'],
            ['fixed', self::ALL_FIXED],
            ['fixed', self::MADE],
            ['later', self::REHAB],
        ],
        'salary' => [
            ['fixed', '/\b(?:salar\w*|wages?|pay)\b[^.]*\b(?:has|have)\s+been\s+(?:paid|released|disbursed|cleared)\b/iu'],
            // "the concerned contractor has accordingly been directed to recover the salary shortfall".
            ['later', '/\b(?:salar\w*|payroll|wages?)\b[^.]*\b(?:directed|asked|instructed|will|shall|under\s+process)\b|\b(?:directed|asked|instructed)\b[^.]*\b(?:salar\w*|payroll|wages?)\b/iu'],
            ['denied', '/\b(?:salar\w*|wages?)\b[^.]*\b(?:paid\s+regularly|regularly\s+paid|being\s+paid|no\s+(?:pending|pendency|outstanding))\b/iu'],
            ['fixed', self::ALL_FIXED],
            ['later', self::REHAB],
        ],
        'private' => [
            // "Plant falls under the purview of PSPA instead of any private company".
            ['denied', '/\b(?:under|within)\s+(?:the\s+)?(?:administrative\s+)?(?:purview|control|jurisdiction)\s+of\s+(?:the\s+)?(?:PSPA|Punjab\s+Saaf\s+Pani)\b'
                .'|\binstead\s+of\s+(?:a|any|the)\s+private\b/iu'],
            ['fixed', self::ALL_FIXED],
            ['later', self::REHAB],
        ],
        'notfound' => [
            // "present inside the hospital & among the dysfunctional WFPs to be rehabilitated" is promised.
            ['later', self::REHAB],
            // "The instant WFP exists on the provided location", "The plant is located adjacent to ...".
            ['denied', '/\bexists?\b[^.]*\b(?:location|site|place)\b|\b(?:is|are)\s+(?:present|located|situated|available)\s+(?:at|on|in|inside|near|adjacent|within)\b'
                .'|\blocated\s+(?:at|adjacent|near|inside|in|on)\b|\bpresent\s+at\s+(?:the\s+)?site\b/iu'],
            ['fixed', self::ALL_FIXED],
        ],
        'closed' => [
            ['fixed', self::MADE],
            ['fixed', self::WORKING],
            ['fixed', '/\bopen(?:ed)?\s+(?:for|to)\s+(?:the\s+)?public\b|\b(?:unlocked|reopened)\b/iu'],
            ['fixed', self::ALL_FIXED],
            ['later', self::REHAB],
        ],
        'misuse' => [
            // "the concerned contractor has also been directed to ensure strict compliance with these operational
            // timings", "field teams are hereby directed to exercise enhanced vigilance".
            ['later', '/\b(?:operational\s+(?:hours|timings?)|sale\s+of\s+water|selling|charg\w+|theft|stolen|misuse)\b[^.]*\b(?:directed|asked|instructed|will|shall)\b'
                .'|\b(?:directed|asked|instructed)\b[^.]*\b(?:operational\s+(?:hours|timings?)|vigilance|no\s+such\s+practice|strict\s+compliance)\b/iu'],
            // "PSPA ensures the provision of safe drinking water during declared operational hours".
            ['explained', '/\bduring\s+(?:the\s+)?declared\s+operational\s+hours\b|\boperational\s+(?:hours|timings?)\s+(?:are\s+)?(?:displayed|mentioned)\b/iu'],
            // "no evidence substantiating the said allegation has been found".
            ['denied', '/\bno\s+evidence\b|\ballegations?\b[^.]*\b(?:baseless|false|incorrect|not\s+(?:proved|substantiated))\b/iu'],
            ['fixed', self::ALL_FIXED],
            ['later', self::REHAB],
        ],
        'other' => [
            ['fixed', self::ALL_FIXED],
            ['later', self::REHAB],
        ],
    ];

    // Who runs the plant when the response says it is not PSPA's (see AGENCIES), or null. A text holding
    // more than one response (repeated report rows, one per paragraph) counts as PSPA's when any of them
    // answers as PSPA.
    public function agency(string $text): ?string
    {
        $found = null;

        foreach ($this->parts($text) as $part) {
            $agency = $this->partAgency($part);
            if ($agency === null) {
                return null;
            }
            $found = $agency;
        }

        return $found;
    }

    // The department's answer to one problem group: fixed, later, explained, denied, notpspa or notans.
    public function type(string $text, string $group): string
    {
        return $this->read($text, [$group])[$group];
    }

    // [group => answer] for each group given.
    public function read(string $text, array $groups): array
    {
        $out = [];

        if ($this->agency($text) !== null) {
            foreach ($groups as $group) {
                $out[$group] = 'notpspa';
            }

            return $out;
        }

        $ours = array_filter($this->parts($text), fn ($part) => $this->partAgency($part) === null);
        $clauses = $this->clauses(implode(' ', $ours));
        $planned = array_map(fn ($clause) => preg_match(self::PLANNED, $clause) === 1 && preg_match(self::DONE, $clause) !== 1, $clauses);
        $negated = array_map(fn ($clause) => preg_match(self::NEGATED, $clause) === 1, $clauses);

        foreach ($groups as $group) {
            $out[$group] = $this->answer($clauses, $planned, $negated, self::RULES[$group] ?? self::RULES['other']);
        }

        return $out;
    }

    // The first rule any clause meets. A fix said in a plan or an order is not a fix, and a fix or a
    // denial said with "not" counts as neither.
    private function answer(array $clauses, array $planned, array $negated, array $rules): string
    {
        foreach ($rules as [$kind, $pattern]) {
            foreach ($clauses as $at => $clause) {
                if (($kind === 'fixed' && $planned[$at]) || (in_array($kind, ['fixed', 'denied'], true) && $negated[$at])) {
                    continue;
                }
                if (preg_match($pattern, $clause) === 1) {
                    return $kind;
                }
            }
        }

        return 'notans';
    }

    private function partAgency(string $part): ?string
    {
        $text = $this->clean($part);
        $best = null;
        $at = PHP_INT_MAX;

        foreach (self::NAMES as $agency => $names) {
            if (preg_match('/'.self::OWNER.'('.$names.')/iu', $text, $match, PREG_OFFSET_CAPTURE) === 1 && $match[1][1] < $at) {
                [$best, $at] = [$agency, $match[1][1]];
            }
        }

        return $best
            ?? (preg_match(self::NGO_WORDS, $text) === 1 ? 'ngo' : null)
            ?? (preg_match(self::GONE, $text) === 1 ? 'aband' : null)
            ?? (preg_match(self::NOT_OURS, $text) === 1 ? 'other' : null);
    }

    // Responses merged into one text (repeated report rows) are kept apart by a blank line after a full stop.
    // A blank line inside a sentence is only wrapping.
    private function parts(string $text): array
    {
        return array_values(array_filter(
            preg_split('/(?<=[.!?)\]])[\h\x{3000}]*\R[\h\x{3000}]*\R/u', $text) ?: [],
            fn ($part) => $this->clean($part) !== ''
        ));
    }

    // Sentences, split at a full stop before a letter, with or without a space after it ("by PSPA.The
    // Plant has now been ..."), but not after an abbreviation ("govt. entity", "w.e.f."). A bracketed note
    // of attached evidence ends a sentence. "and" starts a new clause before "the issue" or "plant"
    // ("... will be commenced and Issue regarding water taps have been resolved"), and "Same has been
    // already done" stays with the sentence it answers.
    private function clauses(string $text): array
    {
        $text = (string) preg_replace('/(\([^()]*\b(?:attached|evidences?|pictures?)\b[^()]*\))\s*(?=\p{L})/iu', '$1. ', $this->clean($text));
        $pieces = preg_split('/(?<=[.!?])\s*(?=\p{L})|\s+and\s+(?=(?:the\s+)?(?:issues?|plants?|wfps?)\b)/iu', $text) ?: [];

        $out = [];
        $join = false;
        foreach ($pieces as $piece) {
            $piece = trim($piece);
            if ($piece === '') {
                continue;
            }
            if ($out !== [] && ($join || preg_match('/^(?:the\s+)?same\s+(?:has|have|had|is|was|will|shall|would)\b/iu', $piece) === 1)) {
                $out[count($out) - 1] = $join ? $out[count($out) - 1].' '.$piece : rtrim($out[count($out) - 1], '.!? ').', '.$piece;
            } else {
                $out[] = $piece;
            }
            $join = preg_match('/(?:^|[\s(\/])(?:govt|dept|no|nos|etc|approx|viz|vs|mr|mrs|dr|st|sr|ref|[a-z])\.$/iu', $piece) === 1;
        }

        return $out;
    }

    // Line breaks, double spaces and wide spaces inside a cell are only wrapping.
    private function clean(string $text): string
    {
        return trim((string) preg_replace('/[\s\x{00A0}\x{2000}-\x{200B}\x{3000}]+/u', ' ', $text));
    }
}
