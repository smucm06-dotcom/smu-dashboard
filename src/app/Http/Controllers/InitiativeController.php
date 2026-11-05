<?php

namespace App\Http\Controllers;

use App\Models\Initiative;
use Illuminate\Http\Request;

class InitiativeController extends Controller
{
    public function show(Request $request, Initiative $initiative)
    {
        $controller = config("initiatives.{$initiative->table_name}");

        abort_unless($initiative->is_active && $controller, 404);

        return app($controller)->show($request, $initiative);
    }
}
