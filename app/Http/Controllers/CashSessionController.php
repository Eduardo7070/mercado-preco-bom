<?php

namespace App\Http\Controllers;

use App\Actions\Cash\ManageSession;
use App\Http\Requests\CashSessionRequest;
use App\Models\CashEntry;
use App\Models\CashRegister;
use App\Models\CashSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashSessionController extends Controller
{
    public function index(Request $request, ManageSession $action): View
    {
        $session = $action->current($request->user());
        $history = CashSession::with('user', 'register')->when(! in_array($request->user()->profile?->slug, ['admin', 'gerente']), fn ($q) => $q->where('user_id', $request->user()->id))->latest()->paginate(20);

        return view('caixa.sessoes', ['title' => 'Abertura e fechamento de caixa', 'module' => 'caixa', 'asset' => 'caixa/sessoes', 'session' => $session, 'expected' => $session ? $action->expected($session) : 0, 'registers' => CashRegister::all(), 'history' => $history, 'entries' => $session ? CashEntry::where('cash_session_id', $session->id)->latest()->get() : collect()]);
    }

    public function store(CashSessionRequest $request, ManageSession $action): RedirectResponse
    {
        $action->execute($request->validated(), $request->user());

        return back()->with('status', 'Operação do caixa registrada com sucesso.');
    }
}
