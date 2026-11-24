<?php

namespace App\Providers;

use App\Jobs\CallCenter\RelinkCallCenterCampaign;
use App\Models\InitiativeFile;
use Illuminate\Support\ServiceProvider;

// Call center campaigns (config/call_center.php).
class CallCenterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Deleting a calls file removes its calls, and deleting the list unlinks every call, so the calls are
        // linked again: a row that repeated the deleted ones may now be the only copy, for example.
        InitiativeFile::deleted(function (InitiativeFile $file) {
            if (! in_array($file->type, [InitiativeFile::TYPE_OUR_VISITS, InitiativeFile::TYPE_DEPARTMENT_LIST], true)) {
                return;
            }

            $table = $file->initiative()->value('table_name');

            if ($table !== null && config("call_center.campaigns.{$table}") !== null) {
                RelinkCallCenterCampaign::dispatch($file->initiative_id);
            }
        });
    }
}
