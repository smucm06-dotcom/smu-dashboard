<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission as SpatiePermission;

// One permission per initiative, named like sed.psrp_phase_1: the department's code, then the
// initiative's short name. Having it means the user can open that initiative.
class Permission extends SpatiePermission
{
    public function initiative(): BelongsTo
    {
        return $this->belongsTo(Initiative::class);
    }
}
