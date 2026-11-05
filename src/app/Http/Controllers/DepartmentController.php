<?php
namespace App\Http\Controllers;
use App\Models\Department;
use App\Models\Initiative;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        // A department user has only one department, so they go straight to it.
        if (! $request->user()->isAdmin()) {
            $department = $request->user()->department();

            abort_unless($department, 403);

            return redirect()->route('departments.show', $department);
        }

        $departments = Department::orderBy('name')->get();

        $topbar = [
            'title' => 'Special Monitoring Unit',
            'description' => 'Monitor government initiatives, track departmental performance, and review field observations to strengthen public service delivery across Punjab.',
            'stats' => [
                ['label' => 'Departments', 'value' => $departments->count()],
                ['label' => 'Departments with Initiatives', 'value' => Initiative::distinct()->count('department_id')],
                ['label' => 'Total Initiatives', 'value' => Initiative::count()],
                ['label' => 'Active Initiatives', 'value' => Initiative::where('is_active', true)->count()],
            ],
        ];

        return view('departments.index', compact('departments', 'topbar'));
    }

    public function show(Request $request, Department $department)
    {
        // Inactive initiatives are listed too. The page marks them as inactive.
        $initiatives = $department->initiatives()
            ->visibleTo($request->user())
            ->orderBy('name')
            ->get();

        $totalInitiatives = $initiatives->count();
        $activeInitiatives = $initiatives->where('is_active', true)->count();

        $topbar = [
            'title' => $department->name,
            'description' => 'Explore initiatives registered under '.$department->name.' and monitor their implementation and reporting status.',
            'stats' => [
                ['label' => 'Total Initiatives', 'value' => $totalInitiatives],
                ['label' => 'Active Initiatives', 'value' => $activeInitiatives],
                ['label' => 'Inactive Initiatives', 'value' => $totalInitiatives - $activeInitiatives],
                [
                    'label' => 'Data Tables',
                    'value' => $initiatives->pluck('table_name')->filter()->unique()->count(),
                ],
            ],
        ];

        return view('departments.show', compact(
            'department',
            'initiatives',
            'topbar'
        ));
    }
}
