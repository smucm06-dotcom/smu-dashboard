<?php
namespace App\Http\Controllers;
use App\Models\Department;
use App\Models\Initiative;

class DepartmentController extends Controller
{
    public function index()
    {
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

    public function show(Department $department)
    {
        // Only active initiatives are listed. Every initiative starts inactive
        // (the migration's default) until it is switched on.
        $initiatives = $department->initiatives()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $totalInitiatives = $department->initiatives()->count();
        $activeInitiatives = $initiatives->count();

        $topbar = [
            'title' => $department->name,
            'description' => 'Explore initiatives registered under '.$department->name.' and monitor their implementation and reporting status.',
            'stats' => [
                ['label' => 'Total Initiatives', 'value' => $totalInitiatives],
                ['label' => 'Active Initiatives', 'value' => $activeInitiatives],
                ['label' => 'Inactive Initiatives', 'value' => $totalInitiatives - $activeInitiatives],
                [
                    'label' => 'Data Tables',
                    'value' => $department->initiatives()
                        ->whereNotNull('table_name')
                        ->where('table_name', '<>', '')
                        ->distinct()
                        ->count('table_name'),
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
