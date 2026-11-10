<?php

namespace App\Dashboards\Initiatives\SpecialEducation;

// The response file is free text, so topics and response types come from its wording.
// Checked against the findings design: all 771 issues in the 2025 file get the same topics
// and the same response type as there.
class SpecialEducationReplyClassifier
{
    // Topics an issue can be about. One issue often lists several.
    public const CATEGORIES = [
        'staff' => ['label' => 'Shortage of teachers and staff', 'rule' => '/teacher|support staff|admin staff|professional staff|shortage of support|psychologist|physiologist|hostel management|hostal management/'],
        'principal' => ['label' => 'Principal on additional charge', 'rule' => '/additional charge/'],
        'absence' => ['label' => 'Staff or principal absent', 'rule' => '/absen/'],
        'transport' => ['label' => 'Buses, fuel and drivers', 'rule' => '/\bbus|transport|fuel|driver|\bvan\b|conductor/'],
        'building' => ['label' => 'Classrooms, buildings and hostels', 'rule' => '/separate classroom|shortage of classroom|building|hostel(?! management)|hall instead|rented|maintenance/'],
        'clean' => ['label' => 'Dirty or broken washrooms and classrooms', 'rule' => '/uncleaned|dilapidated|cleanliness|cleaning/'],
        'devices' => ['label' => 'Hearing aids and wheelchairs', 'rule' => '/hearing aid|wheel ?chair/'],
        'equip' => ['label' => 'Computers, sports and vocational kit', 'rule' => '/\bsports?\b|computer|vocational|play ?ground/'],
        'furniture' => ['label' => 'Furniture', 'rule' => '/furniture/'],
        'utility' => ['label' => 'Solar, water coolers and sewage', 'rule' => '/solar|water cooler|wtaer cooler|sewage|drinking water|electricity/'],
        'cctv' => ['label' => 'CCTV cameras not working', 'rule' => '/cctv/'],
        'stipend' => ['label' => 'Stipend delayed', 'rule' => '/stipend/'],
        'books' => ['label' => 'Books and uniforms', 'rule' => '/book|uniform/'],
        'funds' => ['label' => 'Shortage of funds', 'rule' => '/shortage of funds/'],
        'firstaid' => ['label' => 'First aid', 'rule' => '/first aid/'],
        'fee' => ['label' => 'Fee charged from students', 'rule' => '/\bfee\b/'],
        'beating' => ['label' => 'Students beaten', 'rule' => '/beat/'],
    ];

    // Checked in this order; a response that matches none cites a programme or plan.
    private const TYPES = [
        'notans' => '/not (been )?(properly )?(answered|entertain)|unsatisfactory response|answer not provided/',
        'disputed' => '/no (such )?incident|no budget head|not required at this level|no space for|no any problem|plenty of/',
        'fixed' => '/got resolved|suspended/',
        'directed' => '/\bdirected\b|instruction|circulat|re-align|meeting|corrective/',
    ];

    // The topics one issue raises, in the order above.
    public static function categories(?string $issue): array
    {
        $text = self::lower($issue);

        if ($text === '') {
            return [];
        }

        return array_keys(array_filter(
            self::CATEGORIES,
            fn ($category) => preg_match($category['rule'], $text) === 1
        ));
    }

    // fixed, directed, plan, disputed, notans or none, from all the replies to one issue.
    public static function type(array $replies, ?string $issue): string
    {
        $text = self::lower(implode(' ', $replies));

        if ($text === '') {
            return 'none';
        }

        // A reply that only repeats the issue does not answer it.
        $plain = self::letters($text);

        if ($plain !== '' && str_contains(self::letters(self::lower($issue)), $plain)) {
            return 'notans';
        }

        foreach (self::TYPES as $type => $rule) {
            if (preg_match($rule, $text) === 1) {
                return $type;
            }
        }

        return 'plan';
    }

    private static function lower(?string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', mb_strtolower((string) $text)));
    }

    private static function letters(string $text): string
    {
        return (string) preg_replace('/[^a-z0-9]+/', '', $text);
    }
}
