<?php

namespace App\Support;

use App\Models\Initiative;

// Which initiatives are call center campaigns (config/call_center.php).
final class CallCenterCampaigns
{
    // Their table names.
    public static function tables(): array
    {
        return array_keys(config('call_center.campaigns', []));
    }

    public static function includes(?Initiative $initiative): bool
    {
        return $initiative !== null && in_array($initiative->table_name, self::tables(), true);
    }

    // The upload buttons read in the background for this campaign: InitiativeFile type => import class.
    public static function imports(Initiative $initiative): array
    {
        return config("call_center.campaigns.{$initiative->table_name}.imports", []);
    }
}
