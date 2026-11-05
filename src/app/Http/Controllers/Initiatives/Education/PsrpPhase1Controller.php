<?php

namespace App\Http\Controllers\Initiatives\Education;

use App\Http\Controllers\Controller;
use App\Models\Initiative;
use App\Models\PsrpPhase1;
use Illuminate\Http\Request;

class PsrpPhase1Controller extends Controller
{
    public function show(Request $request, Initiative $initiative)
    {
        abort_unless(
            $initiative->is_active
            && $initiative->table_name === 'public_school_reorganization_phase_1',
            404
        );

        // Count all recorded visits, including repeat visits.
        $totalVisits = PsrpPhase1::count();

        // Use each school's latest visit for current dashboard figures.
        // If timestamps match, use the record with the higher ID.
        $schools = PsrpPhase1::query()
            ->from('public_school_reorganization_phase_1 as records')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('public_school_reorganization_phase_1 as newer')
                    ->whereColumn('newer.emis_code', 'records.emis_code')
                    ->where(function ($query) {
                        $query
                            ->whereColumn(
                                'newer.visited_at',
                                '>',
                                'records.visited_at'
                            )
                            ->orWhere(function ($query) {
                                $query
                                    ->whereColumn(
                                        'newer.visited_at',
                                        'records.visited_at'
                                    )
                                    ->whereColumn(
                                        'newer.id',
                                        '>',
                                        'records.id'
                                    );
                            });
                    });
            });

        $totals = (clone $schools)
            ->selectRaw('
                COUNT(*) AS total_schools,
                COUNT(DISTINCT NULLIF(TRIM(district), "")) AS total_districts,
                SUM(students_enrolled) AS total_enrollment,
                SUM(total_teachers) AS total_teachers,
                SUM(total_classrooms) AS total_classrooms,
                SUM(total_washrooms) AS total_washrooms,
                SUM(dangerous_classrooms) AS dangerous_classrooms
            ')
            ->first();

        // Only use schools with both attendance values recorded.
        $studentAttendance = (clone $schools)
            ->whereNotNull('records.students_present')
            ->whereNotNull('records.students_enrolled')
            ->selectRaw('
                SUM(students_present) AS present,
                SUM(students_enrolled) AS enrolled
            ')
            ->first();

        $teacherAttendance = (clone $schools)
            ->whereNotNull('records.teachers_present')
            ->whereNotNull('records.total_teachers')
            ->selectRaw('
                SUM(teachers_present) AS present,
                SUM(total_teachers) AS total
            ')
            ->first();

        $percentage = static function ($numerator, $denominator) {
            return $denominator > 0
                ? round(($numerator / $denominator) * 100, 1)
                : null;
        };

        $salaryAnswered = (clone $schools)
            ->whereNotNull('records.staff_salary_status')
            ->count();

        $salaryPaid = (clone $schools)
            ->where('records.staff_salary_status', true)
            ->count();

        $councilAnswered = (clone $schools)
            ->whereNotNull('records.pts_council_established')
            ->count();

        $councilEstablished = (clone $schools)
            ->where('records.pts_council_established', true)
            ->count();

        $stats = [
            'total_visits' => $totalVisits,
            'total_schools' => (int) $totals->total_schools,
            'total_districts' => (int) $totals->total_districts,

            // Keep missing totals as null so the view can show "—".
            'total_enrollment' => $totals->total_enrollment,
            'total_teachers' => $totals->total_teachers,
            'total_classrooms' => $totals->total_classrooms,
            'total_washrooms' => $totals->total_washrooms,
            'dangerous_classrooms' => $totals->dangerous_classrooms,

            'student_attendance_percentage' => $percentage(
                $studentAttendance->present,
                $studentAttendance->enrolled
            ),

            'teacher_attendance_percentage' => $percentage(
                $teacherAttendance->present,
                $teacherAttendance->total
            ),

            'salary_paid_percentage' => $percentage(
                $salaryPaid,
                $salaryAnswered
            ),

            'council_established_percentage' => $percentage(
                $councilEstablished,
                $councilAnswered
            ),
        ];

        $issues = [
            'no_clean_water' => (clone $schools)
                ->where('records.clean_drinking_water_available', false)
                ->count(),

            'enrollment_fell' => (clone $schools)
                ->whereColumn(
                    'records.students_enrolled',
                    '<',
                    'records.students_at_handover'
                )
                ->count(),

            'salary_not_paid' => (clone $schools)
                ->where('records.staff_salary_status', false)
                ->count(),

            'dangerous_classrooms' => (clone $schools)
                ->where('records.dangerous_classrooms', '>', 0)
                ->count(),

            'no_students' => (clone $schools)
                ->where('records.students_enrolled', 0)
                ->count(),

            'not_handed_over' => (clone $schools)
                ->where('records.handover_status', false)
                ->count(),

            'charging_fees' => (clone $schools)
                ->where('records.students_charged_fees', true)
                ->count(),

            'no_teacher_present' => (clone $schools)
                ->where('records.teachers_present', 0)
                ->count(),
        ];

        return view('initiatives.education.psrp_phase_1', [
            'initiative' => $initiative,
            'stats' => $stats,
            'issues' => $issues,
        ]);
    }
}
