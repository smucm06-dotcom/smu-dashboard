<?php

namespace App\Imports\Initiatives\HigherEducation;

use App\Imports\CallCenter\UploadProgress;
use App\Models\CallCenterUpload;
use Illuminate\Support\Facades\DB;

// CM Punjab Honhaar Scholarship: works out, for every call on file, the three things that decide whether the
// page counts it. It runs after every upload and after a calls file or the list is deleted, over all calls, so
// the answer never depends on the order things were uploaded in.
//
// 1. The student called: the department's list is searched by CNIC, else by phone number, else by the alt
//    phone number. A phone number that two or more students in the list share is not used.
// 2. Whether the row repeats earlier rows: a run of at least config('call_center.copy_run_rows') rows, one
//    after another, each the same as the row the same distance above it (a file pasted in twice). The rows are
//    kept but not counted. Real repeat calls to a student are separate rows and stay counted.
// 3. The district it counts in: the student's district in the list, else the District in the calls file.
class HonhaarLinker
{
    private const CALLS = 'hed_honhaar_scholarship';
    private const STUDENTS = 'hed_honhaar_scholarship_students';

    // Rows changed per update statement.
    private const UPDATE_CHUNK = 1000;

    public function link(?UploadProgress $progress = null): array
    {
        $total = DB::table(self::CALLS)->count();
        $progress?->phase(CallCenterUpload::PHASE_LINKING, $total);

        [$byCnic, $byPhone, $districts] = $this->students();
        $copies = $this->copies($progress, $total);

        $changes = [];
        $counts = ['counted' => 0, 'copies' => 0, 'notListed' => 0, 'cnic' => 0, 'phone' => 0, 'alt_phone' => 0];
        $done = 0;

        $calls = DB::table(self::CALLS)
            ->orderBy('id')
            ->select(['id', 'cnic', 'contact_no', 'alt_contact_no', 'district', 'student_id', 'matched_by', 'copy_of_id', 'home_district'])
            ->lazyById(5000);

        foreach ($calls as $call) {
            $cnic = self::digits($call->cnic);
            $phone = self::digits($call->contact_no);
            $alt = self::digits($call->alt_contact_no);

            [$student, $how] = match (true) {
                $cnic !== '' && isset($byCnic[$cnic]) => [$byCnic[$cnic], 'cnic'],
                $phone !== '' && isset($byPhone[$phone]) => [$byPhone[$phone], 'phone'],
                $alt !== '' && isset($byPhone[$alt]) => [$byPhone[$alt], 'alt_phone'],
                default => [null, null],
            };

            $copyOf = $copies[$call->id] ?? null;
            $district = $student !== null ? $districts[$student] : $call->district;

            $counts[match (true) {
                $copyOf !== null => 'copies',
                $student === null => 'notListed',
                default => 'counted',
            }]++;

            if ($how !== null) {
                $counts[$how]++;
            }

            if (! self::same($call->student_id, $student) || $call->matched_by !== $how || ! self::same($call->copy_of_id, $copyOf) || $call->home_district !== $district) {
                $changes[$call->id] = ['student_id' => $student, 'matched_by' => $how, 'copy_of_id' => $copyOf, 'home_district' => $district];
            }

            if (count($changes) === self::UPDATE_CHUNK) {
                $this->save($changes);
                $changes = [];
            }

            $progress?->advance(++$done);
        }

        $this->save($changes);
        $progress?->advance($done, true);

        return $counts;
    }

    // Each CNIC's student, each phone number's student where only one student has it, and each student's district.
    private function students(): array
    {
        $byCnic = [];
        $phones = [];
        $districts = [];

        foreach (DB::table(self::STUDENTS)->orderBy('id')->select(['id', 'cnic', 'contact', 'student_district'])->cursor() as $student) {
            $districts[$student->id] = $student->student_district;
            $cnic = self::digits($student->cnic);
            $phone = self::digits($student->contact);

            if ($cnic !== '') {
                $byCnic[$cnic] ??= $student->id;
            }

            if ($phone !== '') {
                $phones[$phone][] = $student->id;
            }
        }

        $byPhone = [];
        foreach ($phones as $phone => $ids) {
            if (count($ids) === 1) {
                $byPhone[$phone] = $ids[0];
            }
        }

        return [$byCnic, $byPhone, $districts];
    }

    // The rows that repeat earlier rows, each with the earlier row it repeats. A row is only a copy as part of
    // a long run, so a student called twice with the same answers is never taken for one.
    private function copies(?UploadProgress $progress, int $total): array
    {
        $minimum = max(2, (int) config('call_center.copy_run_rows', 100));

        $ids = [];
        $seen = [];
        $running = [];
        $runs = [];
        $position = 0;

        foreach (DB::table(self::CALLS)->orderBy('id')->select(['id', 'content_hash'])->cursor() as $call) {
            // For each gap back to an earlier row with the same values, how many rows in a row have matched.
            $next = [];
            foreach ($seen[$call->content_hash] ?? [] as $earlier) {
                $gap = $position - $earlier;
                $next[$gap] = ($running[$gap] ?? 0) + 1;
            }

            foreach ($running as $gap => $length) {
                if (! isset($next[$gap]) && $length >= $minimum) {
                    $runs[] = [$position - $length, $length, $gap];
                }
            }

            $running = $next;
            $seen[$call->content_hash][] = $position;
            $ids[$position] = $call->id;
            $position++;
        }

        foreach ($running as $gap => $length) {
            if ($length >= $minimum) {
                $runs[] = [$position - $length, $length, $gap];
            }
        }

        // Where runs overlap, the longest decides which earlier row a row repeats.
        usort($runs, fn ($a, $b) => $a[1] <=> $b[1]);

        $copies = [];
        foreach ($runs as [$start, $length, $gap]) {
            for ($p = $start; $p < $start + $length; $p++) {
                $copies[$ids[$p]] = $ids[$p - $gap];
            }
        }

        return $copies;
    }

    // Writes the changed rows in a few statements, not one per row.
    private function save(array $changes): void
    {
        if ($changes === []) {
            return;
        }

        foreach (array_chunk($changes, self::UPDATE_CHUNK, true) as $chunk) {
            $sets = [];
            $bindings = [];

            foreach (['student_id', 'matched_by', 'copy_of_id', 'home_district'] as $column) {
                $cases = '';
                foreach ($chunk as $id => $values) {
                    $cases .= ' WHEN ? THEN ?';
                    $bindings[] = $id;
                    $bindings[] = $values[$column];
                }
                $sets[] = "{$column} = CASE id{$cases} END";
            }

            $ids = array_keys($chunk);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            DB::update('UPDATE '.self::CALLS.' SET '.implode(', ', $sets)." WHERE id IN ({$placeholders})", array_merge($bindings, $ids));
        }
    }

    // Ids may come back from the database as numbers or as text.
    private static function same(mixed $a, mixed $b): bool
    {
        return $a === null || $b === null ? $a === $b : (string) $a === (string) $b;
    }

    private static function digits(?string $value): string
    {
        return $value === null ? '' : (string) preg_replace('/\D/', '', $value);
    }
}
