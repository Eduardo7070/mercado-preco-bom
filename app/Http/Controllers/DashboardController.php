<?php

namespace App\Http\Controllers;

use App\Actions\Dashboard\BuildDashboard;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, BuildDashboard $action): View
    {
        return view('inicio', array_merge([
            'title' => 'Início', 'module' => 'inicio', 'asset' => 'inicio',
        ], $action->execute($request->user())));
    }
}
