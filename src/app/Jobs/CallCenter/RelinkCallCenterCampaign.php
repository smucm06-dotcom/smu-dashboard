<?php

namespace App\Jobs\CallCenter;

use App\Models\Initiative;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

// Links a campaign's calls to its department list again, after a calls file or the list was deleted. It runs
// on the uploads' queue, so it never overlaps an upload.
class RelinkCallCenterCampaign implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public function __construct(public int $initiativeId)
    {
        $this->onQueue(config('call_center.queue'));
    }

    public function handle(): void
    {
        $initiative = Initiative::find($this->initiativeId);
        $linker = config('call_center.campaigns.'.$initiative?->table_name.'.linker');

        if ($linker !== null) {
            DB::transaction(fn () => app($linker)->link());
        }
    }
}
