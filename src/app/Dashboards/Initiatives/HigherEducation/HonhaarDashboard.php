<?php

namespace App\Dashboards\Initiatives\HigherEducation;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// CM Punjab Honhaar Scholarship: every figure of the "Honhaar Scholarship Call Center Feedback" design, worked
// out on the server from the three uploads: the department's list of students, our calls file and its
// Universities sheet. Only the totals go to the browser: for all of Punjab and for each district, so the
// district filter needs no new download. A date range is worked out again from the calls in it.
//
// The design's rules, which this follows:
// - Only calls the page counts are used: to a student in the list, and not a repeat of earlier rows (see
//   HonhaarLinker). A student is one person in the list.
// - A call gave feedback when it connected to the student and at least one answer was written. A student who
//   gave feedback more than once is counted from the latest such call; a bribe counts from any call.
// - Every student who reported a problem is unsatisfied, whatever they said to "Are you satisfied?".
// - Students outside the programme (left the university, did not apply, another scholarship, do not want it)
//   who reported no problem are not counted for satisfaction.
// - The latest call is the one with the latest Call Date; calls without one (the Master file's) come after the
//   dated calls, in the order they are in the files.
class HonhaarDashboard
{
    // Raise when the figures are worked out differently, so browsers fetch them again.
    private const DATA_VERSION = 1;

    // Districts with fewer students giving feedback are marked "few students" by the page.
    public const FEW = 30;

    private const CALLS = 'hed_honhaar_scholarship';
    private const STUDENTS = 'hed_honhaar_scholarship_students';
    private const INSTITUTIONS = 'hed_honhaar_scholarship_institutions';

    // What happened on each call, from its Call Status. The second is a connected call with nothing written.
    public const OUTCOMES = [
        'Connected', 'Connected, no answers written', 'Not answered', 'Phone switched off', 'Wrong number',
        'Student not available', 'Left university (told on call)', 'Hung up / no response', 'Status not written',
        'Other status',
    ];

    private const CONNECTED = 0;
    private const NO_ANSWERS = 1;
    private const WRONG = 4;
    private const NOT_WRITTEN = 8;
    private const OTHER_STATUS = 9;

    // Call Status as the call center writes it, in lower case.
    private const STATUSES = [
        'c' => 0, 'connected' => 0,
        'nc' => 2, 'not connected' => 2, 'not answered' => 2, 'no answer' => 2,
        'powered off' => 3, 'switched off' => 3, 'phone switched off' => 3,
        'w' => 4, 'wrong number' => 4,
        'concerned person not available' => 5, 'not available' => 5,
        'left university' => 6,
        'call hang up' => 7, 'hang up' => 7, 'no response from citizen' => 7, 'no response' => 7,
    ];

    public const YEARS = ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year', 'Degree Completed'];

    // The design's ten issues, in its order, with what each covers.
    public const ISSUES = [
        ['k' => 'notrecv', 'label' => 'Scholarship not received yet', 'desc' => "Said the scholarship has not reached them, or remark 'Scholarship (cheque) not received yet'"],
        ['k' => 'second', 'label' => '2nd cheque / instalment not received', 'desc' => "Got the first cheque only; remark '2nd cheque not received yet'"],
        ['k' => 'uni', 'label' => 'University or department did not cooperate', 'desc' => ''],
        ['k' => 'less', 'label' => 'Paid less than the full amount', 'desc' => 'Half or less of the amount received, or amount too small'],
        ['k' => 'bribe', 'label' => 'Paid a bribe', 'desc' => 'Said yes to the bribe question'],
        ['k' => 'name', 'label' => 'Name issue on cheque or record', 'desc' => ''],
        ['k' => 'inelig', 'label' => 'Declared not eligible by the department', 'desc' => ''],
        ['k' => 'bank', 'label' => 'Bank or cheque problem', 'desc' => 'Bank account issue, bank staff not cooperative, account not active, cheque not cashed, blank cheque, signature issue'],
        ['k' => 'fee', 'label' => 'University asked for fee or took money back', 'desc' => 'Includes fee not refunded after the scholarship'],
        ['k' => 'delay', 'label' => 'Delays, extra documents and other', 'desc' => 'Delay in process, unnecessary documents, Gmail changed, cancelled, no information'],
    ];

    // The clean Remarks labels and the issue each stands for. "out" is a student outside the programme, and
    // "wrong" a call that reached the wrong person. Other remarks report no problem.
    public const REMARKS = [
        '2nd cheque not received yet' => 'second',
        'Scholarship (cheque) not received yet' => 'notrecv',
        'University / department did not cooperate' => 'uni',
        'Incomplete payment (half or less amount received)' => 'less',
        'Name issue' => 'name',
        'Not eligible by department' => 'inelig',
        'Not eligible by department, rude behaviour' => 'inelig',
        'Bank account issue' => 'bank',
        'Bank staff not cooperative' => 'bank',
        'Bank account not activated' => 'bank',
        'Cheque not cashed' => 'bank',
        'Blank cheque received' => 'bank',
        'Signature issue' => 'bank',
        'University asking for fee' => 'fee',
        'Delay in process / payment' => 'delay',
        'Unnecessary documents required' => 'delay',
        'Email (Gmail) changed' => 'delay',
        'Cancelled' => 'delay',
        'No information' => 'delay',
        'Left university' => 'out',
        'Did not apply' => 'out',
        'Already taking another scholarship' => 'out',
        'Does not want the scholarship' => 'out',
        'Wrong person' => 'wrong',
        'Cheque received' => null,
        // The problem is written in Any Other Issue; give the row one of the labels above to place it.
        'Other issue (see Any Other Issue)' => null,
    ];

    // The design's institution types, in its order. Others found in the Universities sheet follow them.
    private const TYPES = ['Public university', 'Private university', 'HED college', 'Medical college'];
    private const NOT_KNOWN = 'Not known';

    // When there is no Universities sheet, the type from the Active Students list's Institute Type.
    private const LIST_TYPES = [
        'public sector universities' => 'Public university',
        'private sector university' => 'Private university',
        'hed colleges' => 'HED college',
        'medical colleges' => 'Medical college',
    ];

    public const NO_DISTRICT = 'District not known';

    // How the page names each lot of answers that changed between calls (see changes()).
    private const CHG = 11;

    public function data(string $from = '', string $to = ''): array
    {
        return Cache::remember(
            'honhaar-dashboard:'.$this->version().':'.$from.':'.$to,
            now()->addDay(),
            fn () => $this->build($from, $to)
        );
    }

    // Changes whenever an upload or a deleted file changes what the page shows.
    public function version(): string
    {
        $calls = DB::table(self::CALLS)
            ->selectRaw('COUNT(*) AS n, MAX(id) AS last, COALESCE(SUM(student_id), 0) AS students, COUNT(copy_of_id) AS copies')
            ->first();
        $students = DB::table(self::STUDENTS)->selectRaw('COUNT(*) AS n, MAX(id) AS last')->first();
        $institutions = DB::table(self::INSTITUTIONS)->selectRaw('COUNT(*) AS n, MAX(id) AS last')->first();

        return md5(json_encode([self::DATA_VERSION, $calls, $students, $institutions]));
    }

    // The campaign's totals for the Call Center page: the calls the page counts, the students they reached
    // (each once) and the latest call date.
    public function summary(): array
    {
        $totals = DB::table(self::CALLS)
            ->whereNotNull('student_id')
            ->whereNull('copy_of_id')
            ->selectRaw('COUNT(*) AS calls, COUNT(DISTINCT student_id) AS people, MAX(call_date) AS last')
            ->first();

        return [
            'calls' => (int) $totals->calls,
            'people' => (int) $totals->people,
            'peopleLabel' => 'students',
            'lastCall' => $totals->last === null ? null : substr((string) $totals->last, 0, 10),
        ];
    }

    // The design's four cards for all of Punjab, as the page's script shows them.
    public function headline(array $data): array
    {
        $g = $data['agg']['ALL'];
        $lists = $data['meta']['lists'];

        $listLine = implode(' + ', array_map(
            fn ($name, $counts) => preg_replace(['/^CM /', '/^Honhaar /', '/ \(20K\)$| \d+$/'], '', $name).' '.number_format($counts[0]),
            array_keys($lists),
            $lists
        ));

        return [
            'dep' => [$g['dep'], $listLine],
            'stu' => [$g['students'], self::percent($g['students'], $g['dep']).' of the list. Each student counted once'],
            'fb' => [$g['fb'], self::percent($g['fb'], $g['students']).' of students called. Most of the rest did not pick up or had the phone switched off'],
            'sat' => [$g['fb'] ? round(($g['fb'] - $g['iss']) / $g['fb'] * 100, 1) : null, 'Students who reported no issue. Everyone with an issue counts as unsatisfied'],
        ];
    }

    private function build(string $from, string $to): array
    {
        [$bySpelling, $types] = $this->institutions();
        [$students, $dists, $lists, $institutions] = $this->students($bySpelling, $types);

        $agg = ['ALL' => $this->group(count($types))];
        foreach ($dists as $district) {
            $agg[$district] = $this->group(count($types));
        }

        // The list's students, whether or not they were called.
        foreach ($students as $student) {
            foreach (['ALL', $student['d']] as $key) {
                $g = &$agg[$key];
                $g['dep']++;
                $g['types'][$student['t']][0]++;
                $g['inst'][$student['i']][1] = ($g['inst'][$student['i']][1] ?? 0) + 1;
                unset($g);
            }
        }

        $listCounts = array_fill_keys($lists, [0, 0, 0]);
        foreach ($students as $student) {
            $listCounts[$student['l']][0]++;
        }

        $outcomesUsed = array_fill(0, count(self::OUTCOMES), 0);

        foreach ($this->callsByStudent($from, $to) as $studentId => $calls) {
            $student = $students[$studentId] ?? null;

            if ($student === null) {
                continue;
            }

            $summary = $this->student($calls);
            $listCounts[$student['l']][1]++;

            if ($summary['fb']) {
                $listCounts[$student['l']][2]++;
            }

            foreach ($summary['outcomes'] as $outcome) {
                $outcomesUsed[$outcome]++;
            }

            foreach (['ALL', $student['d']] as $key) {
                $this->add($agg[$key], $student, $summary);
            }
        }

        // "Other status" only shows when a Call Status is not one the page knows.
        $outcomes = self::OUTCOMES;
        if ($outcomesUsed[self::OTHER_STATUS] === 0) {
            array_pop($outcomes);
            foreach ($agg as &$g) {
                array_pop($g['out']);
            }
            unset($g);
        }

        foreach ($agg as &$g) {
            $g['inst'] = $this->institutionRows($g);
            unset($g['instIssues']);
        }
        unset($g);

        return [
            'meta' => [
                'outs' => $outcomes,
                'years' => self::YEARS,
                'itypes' => $types,
                'insts' => $institutions,
                'issues' => self::ISSUES,
                'lists' => $listCounts,
                'few' => self::FEW,
                'from' => $from,
                'to' => $to,
                'dated' => $this->datedRange(),
            ],
            'dists' => $dists,
            'agg' => $agg,
        ];
    }

    // The first and last call dates on file, for the date filter.
    private function datedRange(): array
    {
        $range = DB::table(self::CALLS)->whereNotNull('student_id')->whereNull('copy_of_id')
            ->selectRaw('MIN(call_date) AS first, MAX(call_date) AS last, SUM(CASE WHEN call_date IS NULL THEN 1 ELSE 0 END) AS undated')
            ->first();

        return [
            'first' => $range->first === null ? null : substr((string) $range->first, 0, 10),
            'last' => $range->last === null ? null : substr((string) $range->last, 0, 10),
            'undated' => (int) $range->undated,
        ];
    }

    // The single name and type of each spelling, the names in order, and the types.
    private function institutions(): array
    {
        $bySpelling = [];
        $found = [];

        foreach (DB::table(self::INSTITUTIONS)->select(['spelling', 'name', 'type'])->cursor() as $row) {
            $bySpelling[$row->spelling] = [$row->name, $row->type ?: self::NOT_KNOWN];
            $found[$row->type ?: self::NOT_KNOWN] = true;
        }

        $types = self::TYPES;
        foreach (array_keys($found) as $type) {
            if (! in_array($type, $types, true) && $type !== self::NOT_KNOWN) {
                $types[] = $type;
            }
        }
        $types[] = self::NOT_KNOWN;

        return [$bySpelling, $types];
    }

    // Each student's district, list, institution and type; the districts; the lists, in the file's order; and
    // the institutions in name order, each with its type.
    private function students(array $bySpelling, array $types): array
    {
        $rows = [];
        $names = [];
        $lists = [];
        $districts = [];

        foreach (DB::table(self::STUDENTS)->orderBy('source_sr')->orderBy('id')->select(['id', 'source_file', 'student_district', 'institute', 'institute_type'])->cursor() as $student) {
            $spelling = (string) $student->institute;
            [$name, $type] = $bySpelling[$spelling] ?? [
                $spelling !== '' ? $spelling : 'Institution not given',
                self::LIST_TYPES[strtolower((string) $student->institute_type)] ?? self::NOT_KNOWN,
            ];

            $district = $student->student_district ?: self::NO_DISTRICT;
            $list = $student->source_file ?: 'List not given';

            $names[$name] = $type;
            $lists[$list] = true;
            $districts[$district] = true;
            $rows[$student->id] = ['d' => $district, 'l' => $list, 'i' => $name, 't' => $type];
        }

        // Names in order, each with its type; each student points to its name's place.
        ksort($names, SORT_NATURAL | SORT_FLAG_CASE);
        $insts = [];
        $index = [];
        foreach ($names as $name => $type) {
            $index[$name] = count($insts);
            $insts[] = [$name, array_search($type, $types, true)];
        }

        $typeIndex = array_flip($types);
        foreach ($rows as &$row) {
            $row['i'] = $index[$row['i']];
            $row['t'] = $typeIndex[$row['t']];
        }
        unset($row);

        $districts = array_keys($districts);
        sort($districts, SORT_NATURAL | SORT_FLAG_CASE);

        return [$rows, $districts, array_keys($lists), $insts];
    }

    // The counted calls in the dates, by student, oldest first.
    private function callsByStudent(string $from, string $to): \Generator
    {
        $query = DB::table(self::CALLS)
            ->whereNotNull('student_id')
            ->whereNull('copy_of_id')
            ->when($from !== '', fn ($q) => $q->where('call_date', '>=', $from))
            ->when($to !== '', fn ($q) => $q->where('call_date', '<=', $to))
            ->orderBy('student_id')
            ->orderByRaw('CASE WHEN call_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('call_date')
            ->orderBy('id')
            ->select(['student_id', 'call_status', 'first_cheque_received', 'receiving_scholarship', 'bribe_paid', 'year_of_degree', 'satisfied', 'remarks']);

        $current = null;
        $calls = [];

        foreach ($query->cursor() as $call) {
            if ($current !== null && $call->student_id !== $current) {
                yield $current => $calls;
                $calls = [];
            }

            $current = $call->student_id;
            $calls[] = $call;
        }

        if ($current !== null) {
            yield $current => $calls;
        }
    }

    // One student's calls, worked out once and added to their district and to all of Punjab.
    private function student(array $calls): array
    {
        $outcomes = [];
        $feedback = [];

        foreach ($calls as $call) {
            $outcome = $this->outcome($call);
            $outcomes[] = $outcome;

            if ($outcome === self::CONNECTED) {
                $feedback[] = $this->answers($call);
            }
        }

        $summary = ['outcomes' => $outcomes, 'n' => count($calls), 'fb' => false];

        if ($feedback === []) {
            return $summary;
        }

        $latest = end($feedback);
        $bribed = array_filter($feedback, fn ($a) => $a['bribe'] === 'Yes') !== [];

        // Outside the programme, with no problem and no bribe: not counted for satisfaction.
        if ($latest['out'] && ! $bribed) {
            return $summary;
        }

        $issues = $latest['issues'];
        if ($bribed && ! in_array('bribe', $issues, true)) {
            $issues[] = 'bribe';
        }

        $asked = array_filter($feedback, fn ($a) => $a['bribe'] !== null) !== [];

        return array_merge($summary, [
            'fb' => true,
            'issues' => $issues,
            'recv' => $latest['recv'],
            'bribe' => $bribed ? 0 : ($asked ? 1 : null),
            'year' => $this->latestYear($feedback),
            'changes' => count($feedback) > 1 ? $this->changes($feedback) : null,
        ]);
    }

    private function outcome(object $call): int
    {
        $status = strtolower(trim((string) $call->call_status));

        if ($status === '') {
            return self::NOT_WRITTEN;
        }

        $outcome = self::STATUSES[$status] ?? self::OTHER_STATUS;

        if ($outcome !== self::CONNECTED) {
            return $outcome;
        }

        // Connected to someone else.
        if (array_key_exists((string) $call->remarks, self::REMARKS) && self::REMARKS[$call->remarks] === 'wrong') {
            return self::WRONG;
        }

        $written = array_filter([$call->first_cheque_received, $call->receiving_scholarship, $call->bribe_paid, $call->year_of_degree, $call->satisfied, $call->remarks], fn ($v) => $v !== null && $v !== '');

        return $written === [] ? self::NO_ANSWERS : self::CONNECTED;
    }

    // A feedback call's answers. The scholarship question was asked two ways over time.
    private function answers(object $call): array
    {
        $received = $call->first_cheque_received ?? $call->receiving_scholarship;
        $remark = $call->remarks;
        $group = $remark !== null && array_key_exists($remark, self::REMARKS) ? self::REMARKS[$remark] : null;

        $issues = [];
        if ($group !== null && $group !== 'out' && $group !== 'wrong') {
            $issues[] = $group;
        }

        // A "no" to the scholarship question with no other remark is "not received yet".
        if (($call->first_cheque_received === 'No' || $call->receiving_scholarship === 'No') && $issues === [] && $group !== 'out') {
            $issues[] = 'notrecv';
        }

        if ($call->bribe_paid === 'Yes') {
            $issues[] = 'bribe';
        }

        return [
            'issues' => $issues,
            'out' => $group === 'out' && $issues === [],
            'recv' => $received === 'Yes' ? 0 : ($received === 'No' ? 1 : null),
            'bribe' => $call->bribe_paid,
            'sat' => $call->satisfied,
            'year' => $call->year_of_degree,
            'remark' => $remark,
            'problem' => array_values(array_diff($issues, ['bribe'])),
        ];
    }

    private function latestYear(array $feedback): ?int
    {
        for ($i = count($feedback) - 1; $i >= 0; $i--) {
            $year = array_search($feedback[$i]['year'], self::YEARS, true);

            if ($year !== false) {
                return $year;
            }
        }

        return null;
    }

    // How a student's answers moved between their feedback calls, as the design's eleven counts. A question not
    // asked on a call is left out of the comparison, and a student's first answer is compared with their last:
    // 0 gave feedback twice or more; 1 same answers every time; 2 some answer changed;
    // 3 scholarship "no" first, "yes" last; 4 "yes" first, "no" last; 5 satisfied "no" first, "yes" last;
    // 6 "yes" first, "no" last; 7 a problem on an earlier call gone by the latest; 8 no problem earlier but one
    // on the latest; 9 a problem on an earlier call (7 is out of these); 10 "not received" first and asked
    // again (3 is out of these).
    private function changes(array $feedback): array
    {
        $c = array_fill(0, self::CHG, 0);
        $c[0] = 1;

        // A remark written on one call and not on another is a change; the other answers are compared where
        // they were given. The year of the degree is not compared.
        $same = true;
        foreach (['sat', 'recv', 'problem', 'remark', 'bribe'] as $key) {
            $values = array_column($feedback, $key);

            if ($key !== 'remark') {
                $values = array_filter($values, fn ($v) => $v !== null);
            }

            $same = $same && count(array_unique(array_map('serialize', $values))) <= 1;
        }
        $c[$same ? 1 : 2] = 1;

        $recv = $this->answered($feedback, 'recv');
        $sat = $this->answered($feedback, 'sat');
        $c[3] = (int) ($recv !== null && $recv === [1, 0]);
        $c[4] = (int) ($recv !== null && $recv === [0, 1]);
        $c[5] = (int) ($sat !== null && $sat === ['No', 'Yes']);
        $c[6] = (int) ($sat !== null && $sat === ['Yes', 'No']);

        $earlier = array_filter(array_slice($feedback, 0, -1), fn ($a) => $a['problem'] !== []) !== [];
        $latest = end($feedback)['problem'] !== [];
        $c[7] = (int) ($earlier && ! $latest);
        $c[8] = (int) (! $earlier && $latest);
        $c[9] = (int) $earlier;
        $c[10] = (int) ($recv !== null && $recv[0] === 1);

        return $c;
    }

    // The first and last answer to a question, when it was answered on two calls or more.
    private function answered(array $feedback, string $key): ?array
    {
        $values = array_values(array_filter(array_column($feedback, $key), fn ($v) => $v !== null));

        return count($values) < 2 ? null : [$values[0], end($values)];
    }

    private function group(int $types): array
    {
        return [
            'calls' => 0, 'students' => 0, 'fb' => 0, 'iss' => 0, 'dep' => 0,
            'out' => array_fill(0, count(self::OUTCOMES), 0),
            'cat' => array_fill(0, count(self::ISSUES), 0),
            'recv' => [0, 0],
            'bribe' => [0, 0],
            'year' => array_fill(0, count(self::YEARS), 0),
            'ncalls' => [0, 0, 0, 0, 0],
            'chg' => array_fill(0, self::CHG, 0),
            'types' => array_fill(0, $types, [0, 0, 0]),
            'inst' => [],
            'instIssues' => [],
        ];
    }

    private function add(array &$g, array $student, array $summary): void
    {
        $keys = array_column(self::ISSUES, 'k');

        $g['calls'] += $summary['n'];
        $g['students']++;
        $g['ncalls'][min($summary['n'], 5) - 1]++;

        foreach ($summary['outcomes'] as $outcome) {
            $g['out'][$outcome]++;
        }

        $i = $student['i'];
        $g['inst'][$i][1] ??= 0;
        $g['inst'][$i][2] = ($g['inst'][$i][2] ?? 0) + 1;
        $g['inst'][$i][3] ??= 0;
        $g['inst'][$i][4] ??= 0;

        if (! $summary['fb']) {
            return;
        }

        $g['fb']++;
        $g['types'][$student['t']][1]++;
        $g['inst'][$i][3]++;

        if ($summary['issues'] !== []) {
            $g['iss']++;
            $g['types'][$student['t']][2]++;
            $g['inst'][$i][4]++;

            foreach ($summary['issues'] as $issue) {
                $index = array_search($issue, $keys, true);
                $g['cat'][$index]++;
                $g['instIssues'][$i][$index] = ($g['instIssues'][$i][$index] ?? 0) + 1;
            }
        }

        if ($summary['recv'] !== null) {
            $g['recv'][$summary['recv']]++;
        }

        if ($summary['bribe'] !== null) {
            $g['bribe'][$summary['bribe']]++;
        }

        if ($summary['year'] !== null) {
            $g['year'][$summary['year']]++;
        }

        if ($summary['changes'] !== null) {
            foreach ($summary['changes'] as $k => $v) {
                $g['chg'][$k] += $v;
            }
        }
    }

    // The design's rows for the universities table: [institution, in the list, called, gave feedback,
    // reported an issue, main issue (-1: none), students with the main issue].
    private function institutionRows(array $g): array
    {
        $rows = [];

        foreach ($g['inst'] as $i => $counts) {
            $issues = $g['instIssues'][$i] ?? [];
            $main = -1;
            $mainCount = 0;

            foreach ($issues as $index => $count) {
                if ($count > $mainCount || ($count === $mainCount && $index < $main)) {
                    $main = $index;
                    $mainCount = $count;
                }
            }

            $rows[] = [$i, $counts[1] ?? 0, $counts[2] ?? 0, $counts[3] ?? 0, $counts[4] ?? 0, $main, $mainCount];
        }

        usort($rows, fn ($a, $b) => $a[0] <=> $b[0]);

        return $rows;
    }

    private static function percent(int $part, int $whole): string
    {
        return $whole ? round($part / $whole * 100).'%' : '–';
    }
}
