<?php
namespace App\Http\Controllers;
use App\Models\Department;
use App\Models\Initiative;
use App\Models\InitiativeFile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // A department user sees only their own departments, and goes straight to it when they have one.
        if (! $user->isAdmin()) {
            $mine = $user->departments();

            abort_if($mine->isEmpty(), 403);

            if ($mine->count() === 1) {
                return redirect()->route('departments.show', $mine->first());
            }
        }

        $departments = $user->isAdmin() ? Department::orderBy('name')->get() : $user->departments();

        // The initiatives switched on, which are the ones with a page, that this user can open. The visits
        // and reports count these only.
        // Call center campaigns open from the Call Center menu and are not counted here.
        $initiatives = Initiative::where('is_active', true)
            ->visibleTo($user)
            ->withoutCallCenter()
            ->whereIn('department_id', $departments->pluck('id'))
            ->get(['id', 'department_id', 'table_name']);

        $topbar = [
            'title' => 'Special Monitoring Unit',
            'description' => 'Monitor government initiatives, track departmental performance, and review field observations to strengthen public service delivery across Punjab.',
            // Numbered 1 to 4 by this page's styles, like the initiative pages' cards.
            'stats' => [
                ['label' => 'Departments', 'value' => $departments->count()],
                ['label' => 'Departments with Initiatives', 'value' => Initiative::visibleTo($user)->withoutCallCenter()->whereIn('department_id', $departments->pluck('id'))->distinct()->count('department_id')],
                [
                    'label' => 'Field visits by SMU',
                    'value' => $this->fieldVisits($initiatives),
                    'info' => 'across all initiatives',
                ],
                [
                    // Our report PDFs, uploaded with "Upload report" on the initiative pages.
                    'label' => 'Reports sent to departments',
                    'value' => InitiativeFile::where('type', InitiativeFile::TYPE_OUR_REPORT)
                        ->whereIn('initiative_id', $initiatives->pluck('id'))
                        ->count(),
                ],
            ],
        ];

        return view('departments.index', compact('departments', 'topbar'));
    }

    public function show(Request $request, Department $department)
    {
        // Only active initiatives are listed. Every initiative starts inactive
        // (the migration's default) until it is switched on. A department user sees only the
        // initiatives given to them, and every card below counts those only.
        $initiatives = $department->initiatives()
            ->visibleTo($request->user())
            ->withoutCallCenter()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $totalInitiatives = $department->initiatives()->visibleTo($request->user())->withoutCallCenter()->count();

        // Visits, reports and recent visits count this department's own initiatives, the ones listed here.
        $topbar = [
            'title' => $department->name,
            'description' => 'Explore initiatives registered under '.$department->name.' and monitor their implementation and reporting status.',
            // Numbered 1 to 4 by this page's styles, like the initiative pages' cards.
            'stats' => [
                ['label' => 'Total Initiatives', 'value' => $totalInitiatives],
                [
                    'label' => 'Field visits by SMU',
                    'value' => $this->fieldVisits($initiatives),
                    'info' => 'across all initiatives',
                ],
                [
                    // Our report PDFs, uploaded with "Upload report" on this department's initiative pages.
                    'label' => 'Reports sent to department',
                    'value' => InitiativeFile::where('type', InitiativeFile::TYPE_OUR_REPORT)
                        ->whereIn('initiative_id', $initiatives->pluck('id'))
                        ->count(),
                ],
                [
                    'label' => 'Visits in the last 30 days',
                    'value' => $this->fieldVisits($initiatives, now()->subDays(30)->startOfDay()),
                ],
            ],
        ];

        return view('departments.show', compact(
            'department',
            'initiatives',
            'topbar'
        ));
    }

    // Every row of every initiative's visit file, or only the visits dated from $since on. An initiative's
    // table_name is its visit table; one without a page or table yet counts nothing.
    private function fieldVisits(Collection $initiatives, ?Carbon $since = null): int
    {
        return $initiatives
            ->filter(fn (Initiative $initiative) => config("initiatives.{$initiative->table_name}") !== null
                && Schema::hasTable($initiative->table_name)
                && ($since === null || Schema::hasColumn($initiative->table_name, 'visited_at')))
            ->sum(fn (Initiative $initiative) => DB::table($initiative->table_name)
                ->when($since !== null, fn ($query) => $query->where('visited_at', '>=', $since))
                ->count());
    }
}
