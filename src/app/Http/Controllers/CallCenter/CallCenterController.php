<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\Initiative;
use App\Models\InitiativeFile;
use Illuminate\Http\Request;

// The Call Center menu: a card for each call center campaign the user can open (config/call_center.php).
class CallCenterController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $campaigns = Initiative::callCenter()
            ->where('is_active', true)
            ->visibleTo($user)
            ->with('department:id,name')
            ->orderBy('name')
            ->get();

        // Someone given no campaign has nothing here; someone given one goes straight to it.
        if (! $user->isAdmin()) {
            abort_if($campaigns->isEmpty(), 403);

            if ($campaigns->count() === 1) {
                return redirect()->route('initiatives.show', $campaigns->first());
            }
        }

        $cards = $campaigns->map(function (Initiative $campaign) {
            $summary = config("call_center.campaigns.{$campaign->table_name}.summary");

            return [
                'initiative' => $campaign,
                'summary' => $summary === null ? null : app($summary)->summary(),
            ];
        });

        $topbar = [
            'title' => 'Call Center',
            'description' => 'The call center phones the people that government programmes serve and asks how the programme worked for them. Each campaign below has its own page.',
            // Numbered 1 to 4 by this page's styles, like the Departments page.
            'stats' => [
                ['label' => 'Campaigns', 'value' => $campaigns->count()],
                ['label' => 'Calls made', 'value' => $cards->sum(fn ($card) => $card['summary']['calls'] ?? 0), 'info' => 'across all campaigns'],
                ['label' => 'People called', 'value' => $cards->sum(fn ($card) => $card['summary']['people'] ?? 0), 'info' => 'each counted once'],
                [
                    'label' => 'Call files uploaded',
                    'value' => InitiativeFile::where('type', InitiativeFile::TYPE_OUR_VISITS)
                        ->whereIn('initiative_id', $campaigns->pluck('id'))
                        ->count(),
                ],
            ],
        ];

        return view('call_center.index', compact('cards', 'topbar'));
    }
}
