<?php

namespace App\Dashboards\Initiatives\LocalGovernment;

// How the Chief Minister Punjab Development Program page reads our visit file's checks and the response
// file's two texts, in the design's way:
// - each visit's failed checks;
// - each SMU report, city by city ("In Khushab, ..."), as the problems it names, one per sentence;
// - the department's answer to each problem: done, instructions given or under way, or not answered.
// These are rules on the words used, so a new report or answer is read the same way.
class PdpReportReader
{
    // The problems, in the design's order. The first 11 are the visit form's checks; "quality" and "drain"
    // come only from the SMU reports, and "other" holds a sentence no rule recognises.
    public const KEYS = [
        ['k' => 'manhole', 'l' => 'Manholes left without covers', 'g' => 'public', 'why' => 'Open manholes on the road or street', 'form' => 1],
        ['k' => 'board', 'l' => 'No safety signboards or barriers', 'g' => 'public', 'why' => 'Public not warned about the works', 'form' => 1],
        ['k' => 'excav', 'l' => 'Dug-up sites not fenced off', 'g' => 'public', 'why' => 'Open trenches without barriers', 'form' => 1],
        ['k' => 'traffic', 'l' => 'Traffic not managed', 'g' => 'public', 'why' => 'No safe way for traffic around the site', 'form' => 1],
        ['k' => 'ppe', 'l' => 'Workers without safety gear', 'g' => 'workers', 'why' => 'No helmets, gloves, boots or vests', 'form' => 1],
        ['k' => 'uniform', 'l' => 'Workers not in uniform', 'g' => 'workers', 'why' => 'Not in the uniform set for the project', 'form' => 1],
        ['k' => 'debris', 'l' => 'Debris not removed', 'g' => 'site', 'why' => 'Construction and demolition waste left lying', 'form' => 1],
        ['k' => 'dust', 'l' => 'No dust control', 'g' => 'site', 'why' => 'No water spraying at the site', 'form' => 1],
        ['k' => 'material', 'l' => 'Materials not stored properly', 'g' => 'site', 'why' => 'Sand, steel or pipes left loose', 'form' => 1],
        ['k' => 'trees', 'l' => 'Trees and green areas not protected', 'g' => 'site', 'why' => 'No care for trees near the works', 'form' => 1],
        ['k' => 'slow', 'l' => 'Work slow or stopped', 'g' => 'pace', 'why' => 'Few workers or work halted', 'form' => 1],
        ['k' => 'quality', 'l' => 'Poor quality work or materials', 'g' => 'quality', 'why' => 'Weak concrete, soil in sand, cracked pipes', 'form' => 0],
        ['k' => 'drain', 'l' => 'Drains blocked or water standing', 'g' => 'drain', 'why' => 'Silt, debris or rainwater in the works', 'form' => 0],
        ['k' => 'other', 'l' => 'Other problems', 'g' => 'other', 'why' => 'Named in an SMU report', 'form' => 0],
    ];

    // The visit form's checks: [visit file column => [problem, the answer that means the problem was seen]].
    // Some questions ask whether a problem was seen ("Manholes without cover"), the others whether
    // something was done right ("Measures taken to control dust"). Any other answer is not counted.
    public const FORM = [
        'manholes_without_cover' => ['manhole', 'yes'],
        'safety_boards_installed' => ['board', 'no'],
        'excavation_sites_secured' => ['excav', 'no'],
        'traffic_managed' => ['traffic', 'no'],
        'workers_using_safety_equipment' => ['ppe', 'no'],
        'workers_in_uniform' => ['uniform', 'no'],
        'construction_waste_removed' => ['debris', 'no'],
        'dust_control_measures' => ['dust', 'no'],
        'materials_organized' => ['material', 'no'],
        'trees_protected' => ['trees', 'no'],
        'slow_or_halted_work' => ['slow', 'yes'],
    ];

    // What a sentence of an SMU report is about, tried in this order; the first that fits is the problem.
    // A problem marked "also" lets the next ones be tried too, so "workers in ordinary clothes and without
    // safety equipment" is two problems.
    private const PROBLEMS = [
        'drain' => '/\b(block(ed|age|ages)?|silt|rain ?water|rainfall|dewater\w*|inlets?|drainage capacity|water-carrying|accumulat\w*)\b/i',
        'slow' => '/\b(pace|slow|halted|delays?|delayed|no operator|non-release of funds|man ?power)\b|\bonly \w+ (labou?rers?|workers?)\b/i',
        'quality' => '/\b(poor quality|substandard|quality is not good|cracks?|cracked)\b|\b(mix|mixed|contained)( with)? soil\b/i',
        'traffic' => '/\btraffic\b/i',
        'excav' => '/\b(excavation|excavated|trench(es)?)\b/i',
        'board' => '/\b(sign ?boards?|barriers?)\b|\bno (adequate |proper )?safety arrangements?\b/i',
        'uniform' => '/\b(ordinary clothes|uniforms?)\b/i',
        'ppe' => '/\b(protective equipment|safety equipment|ppe|helmets?|safety sops|gloves|boots)\b/i',
        'debris' => '/\b(debris|garbage|waste)\b/i',
        'dust' => '/\bdust\b/i',
        'material' => '/\b(materials?|steel)\b.*\b(organi[sz]ed|stored|loose)\b|\bplaced directly on the ground\b/i',
        'trees' => '/\b(trees?|green (spaces?|areas?|infrastructure|belts?))\b/i',
        'manhole' => '/\bmanholes?\b/i',
    ];

    private const ALSO = ['uniform'];

    // What an answer must speak of to answer a problem. A problem is answered by the sentences of the
    // department's answer that share one of these with it: by its kind, and by what its own sentence
    // names ("cracked sewer pipes" are answered by "pipes were replaced", not by "drain cleaning").
    private const SUBJECTS_OF_PROBLEM = [
        'manhole' => ['manhole'],
        'board' => ['board', 'arrangements'],
        'excav' => ['board', 'arrangements', 'excav'],
        'traffic' => ['traffic'],
        'ppe' => ['ppe', 'arrangements'],
        'uniform' => ['uniform'],
        'debris' => ['debris'],
        'dust' => ['dust'],
        'material' => ['material'],
        'trees' => ['trees'],
        'slow' => ['slow'],
        'quality' => ['defect'],
        'drain' => [],
        'other' => [],
    ];

    private const SUBJECTS_IN_PROBLEM = [
        'debris' => '/\b(construction debris|garbage|excavated material)\b/i',
        'drain_clean' => '/\b(debris|waste|silt|garbage)\b.{0,60}\b(drains?|manholes?|sewers?|sewerage)\b|\bblock(ed|age|ages)?\b/i',
        'dewater' => '/\b(rain ?water|rainfall|dewater\w*|standing|accumulat\w*)\b/i',
        'inlets' => '/\binlets?\b/i',
        'pipes' => '/\bpipes?\b/i',
    ];

    private const SUBJECTS_IN_ANSWER = [
        'manhole' => '/\bmanhole covers?\b|\bcovered\b.{0,20}\bmanholes?\b|\bmanholes?\b.{0,20}\bcovered\b/i',
        'board' => '/\bsign ?boards?\b|\bbarric\w*|\bbarriers?\b/i',
        'arrangements' => '/\bsafety arrange?ments?\b/i',
        'excav' => '/\bexcavat\w*|\btrench\w*/i',
        'traffic' => '/\btraffic\b/i',
        'ppe' => '/\bppe\b|\bprotective\b|\bhelmets?\b|\boccupational health\b|\bsafety (gear|equipment|kits?)\b/i',
        'uniform' => '/\buniforms?\b/i',
        'debris' => '/\bdebris\b|\bgarbage\b|\bexcavated material\b/i',
        'dust' => '/\bdust\b|\bsprinkl\w*|\bwater spray\w*|\bbowsers?\b|\bwetting\b/i',
        'material' => '/\bmaterials?\b/i',
        'trees' => '/\btrees?\b|\bgreen (spaces?|areas?|infrastructure|belts?)\b/i',
        'slow' => '/\boperators?\b|\bman ?power\b|\bfunds?\b|\bpayments?\b|\bwork program\w*|\bpace\b|\bmachinery\b|\blabou?r\w*/i',
        'defect' => '/\bdefective\b|\brectif\w*|\bredone\b/i',
        'pipes' => '/\bpipes?\b/i',
        'drain_clean' => '/\bsilt\b|\b(clean\w*|clear\w*)\b.{0,40}\b(drains?|manholes?|sewer\w*)\b|\b(drains?|manholes?)\b.{0,40}\b(clean\w*|clear\w*)\b/i',
        'dewater' => '/\bdewater\w*|\brain ?water\b|\bpump\w* out\b/i',
        'inlets' => '/\binlets?\b/i',
    ];

    // What a sentence of the answer says was done with it, tried in this order.
    private const NO_ACTION = '/\bnoted\b|\bpriorit\w*|\bamong the\b/i';

    private const INSTRUCTED = '/\binstruct\w*|\bdirect\w*|\bditect\w*|\burged\b|\bto ensure\b|\bmonitor\w*|\b(will|shall)\b|\bbe (cleared|removed|done|completed|installed|repaired|rectified|replaced|started)\b/i';

    private const DONE = '/\b(has|have|had) (already )?been\b|\b(were|was)\b|\bis ensured\b|\bare available\b|\b(is|are) being\b|\bregularly\b|\b(hired|released|installed|removed|rectified|replaced?|organi[sz]ed|stored|implemented|allocated)\b/i';

    private const UNDER_WAY = '/\bin progress\b|\bunder ?way\b|\bongoing\b/i';

    // A line that starts a city's part of a report or answer: "In Khushab, ..." or "In TTS,...". The name
    // starts with a capital and has a few words, so "In the month of ..." is not a city.
    private const CITY_LINE = '/^In\s+([A-Z][^,]{0,60}?),\s*(.*)$/u';

    private const CITY_WORDS = 5;

    private ?array $keyIndex = null;

    // The checks that failed at a visit, as positions in KEYS.
    public function findings(object $visit): array
    {
        $found = [];

        foreach (self::FORM as $column => [$key, $problem]) {
            if (strtolower(trim((string) ($visit->{$column} ?? ''))) === $problem) {
                $found[] = $this->index($key);
            }
        }

        sort($found);

        return $found;
    }

    // Reads one row of the response file. $cityOf turns a name in the texts into a city's key.
    // Returns the report's first line, the department's answer for all cities (when it gives one), and for
    // each city in the report its problems [[problem, sentence, response type]] and its own answer.
    public function letter(?string $report, ?string $answer, callable $cityOf): array
    {
        [$head, $sections] = $this->sections($report);
        [$general, $answers] = $this->sections($answer);
        $generalType = $this->generalType($general);

        $byCity = [];
        foreach ($answers as [$name, $lines]) {
            $key = $cityOf($name);
            $byCity[$key] = array_merge($byCity[$key] ?? [], $lines);
        }

        $cities = [];
        foreach ($sections as [$name, $lines]) {
            $key = $cityOf($name);
            $own = $byCity[$key] ?? null;
            $cities[$key] ??= ['answer' => $own === null ? null : implode("\n", $own), 'items' => []];

            foreach ($this->problems($lines) as [$problem, $text]) {
                $type = $answers === [] || $own === null
                    ? ($answers === [] ? $generalType : ($generalType === 'instr' ? 'instr' : 'notans'))
                    : $this->answered($problem, $text, $own, $generalType);
                $cities[$key]['items'][] = [$this->index($problem), $text, $type];
            }
        }

        return [
            'head' => $head === [] ? null : implode(' ', $head),
            // One answer for every city, when the answer names no city.
            'general' => $answers === [] && $general !== [] ? implode("\n", $general) : null,
            'cities' => $cities,
        ];
    }

    public function index(string $key): int
    {
        $this->keyIndex ??= array_flip(array_column(self::KEYS, 'k'));

        return $this->keyIndex[$key];
    }

    // The lines before the first city, and each city's lines: [[name, [lines]]].
    private function sections(?string $text): array
    {
        $head = [];
        $sections = [];

        foreach (preg_split('/\R/u', (string) $text) ?: [] as $line) {
            $line = trim((string) preg_replace('/\s+/u', ' ', $line));
            if ($line === '') {
                continue;
            }

            if (preg_match(self::CITY_LINE, $line, $m) === 1 && count(explode(' ', $m[1])) <= self::CITY_WORDS) {
                $sections[] = [trim($m[1]), $m[2] === '' ? [] : [$m[2]]];
            } elseif ($sections !== []) {
                $sections[count($sections) - 1][1][] = $line;
            } else {
                $head[] = $line;
            }
        }

        return [$head, $sections];
    }

    // A city's problems, [[problem, text]]: a sentence each. Sentences in a row on one line about the same
    // problem are one problem, and a sentence that names no problem belongs to the next one on its line
    // (or the one before, at the end of the line).
    private function problems(array $lines): array
    {
        $out = [];

        foreach ($lines as $line) {
            $pending = [];
            $last = null;

            foreach ($this->sentences($line) as $sentence) {
                $keys = $this->problemsIn($sentence);

                if ($keys === []) {
                    $pending[] = $sentence;

                    continue;
                }

                $text = implode(' ', [...$pending, $sentence]);
                $pending = [];

                if ($last !== null && $keys === [$out[$last][0]]) {
                    $out[$last][1] .= ' '.$text;

                    continue;
                }

                foreach ($keys as $key) {
                    $out[] = [$key, $text];
                }
                $last = count($out) - 1;
            }

            if ($pending !== []) {
                if ($last !== null) {
                    $out[$last][1] .= ' '.implode(' ', $pending);
                } else {
                    $out[] = ['other', implode(' ', $pending)];
                }
            }
        }

        return $out;
    }

    private function problemsIn(string $sentence): array
    {
        $keys = [];

        foreach (self::PROBLEMS as $key => $pattern) {
            if (preg_match($pattern, $sentence) === 1) {
                $keys[] = $key;

                if (! in_array($key, self::ALSO, true)) {
                    break;
                }
            }
        }

        return $keys;
    }

    private function sentences(string $line): array
    {
        return preg_split('/(?<=[.!?])\s+(?=\S)/u', trim($line), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    // The type of the city's answer to one problem. The sentences that speak of it decide, the best one
    // counting; a problem none speaks of is given instructions when the answer instructs in general, and
    // is not answered otherwise.
    private function answered(string $problem, string $text, array $lines, string $generalType): string
    {
        $subjects = self::SUBJECTS_OF_PROBLEM[$problem] ?? [];
        foreach (self::SUBJECTS_IN_PROBLEM as $subject => $pattern) {
            if (preg_match($pattern, $text) === 1) {
                $subjects[] = $subject;
            }
        }

        $best = null;
        $instructs = $generalType === 'instr';

        foreach ($lines as $line) {
            foreach ($this->sentences($line) as $sentence) {
                $type = $this->actionIn($sentence);
                $about = $this->subjectsIn($sentence);

                if ($about === [] && $type === 'instr') {
                    $instructs = true;
                }

                if (array_intersect($subjects, $about) !== [] && ($best === null || $this->rank($type) > $this->rank($best))) {
                    $best = $type;
                }
            }
        }

        return match (true) {
            $best === 'done', $best === 'instr' => $best,
            $instructs => 'instr',
            default => 'notans',
        };
    }

    private function subjectsIn(string $sentence): array
    {
        $found = [];

        foreach (self::SUBJECTS_IN_ANSWER as $subject => $pattern) {
            if (preg_match($pattern, $sentence) === 1) {
                $found[] = $subject;
            }
        }

        return $found;
    }

    private function actionIn(string $sentence): string
    {
        return match (true) {
            preg_match(self::NO_ACTION, $sentence) === 1 => 'none',
            preg_match(self::INSTRUCTED, $sentence) === 1 => 'instr',
            preg_match(self::DONE, $sentence) === 1 => 'done',
            preg_match(self::UNDER_WAY, $sentence) === 1 => 'instr',
            default => 'none',
        };
    }

    private function rank(string $type): int
    {
        return ['none' => 0, 'instr' => 1, 'done' => 2][$type];
    }

    // An answer that names no city covers every problem: as instructions given when it says anything was
    // done or asked for (the design reads "instructions have been issued" so), and not answered otherwise.
    private function generalType(array $lines): string
    {
        foreach ($lines as $line) {
            if (in_array($this->actionIn($line), ['instr', 'done'], true)) {
                return 'instr';
            }
        }

        return 'notans';
    }
}
