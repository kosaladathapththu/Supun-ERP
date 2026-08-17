<?php

namespace App\Http\Controllers;

use App\Models\CashierSession;
use App\Services\CashierSessionService;
use Illuminate\Http\Request;

class CashierSessionController extends Controller
{
    public function index(Request $request, CashierSessionService $service)
    {
        $query = CashierSession::with('user')->where('company_id', $request->user()->company_id);
        $current = (clone $query)->where('user_id', $request->user()->id)->where('status', 'open')->latest('opened_at')->first();

        return view('cashier-sessions.index', [
            'current' => $current,
            'totals' => $current ? $service->totals($current) : null,
            'sessions' => $query->latest('opened_at')->paginate(20),
        ]);
    }

    public function open(Request $request, CashierSessionService $service)
    {
        $data = $request->validate(['opening_cash' => ['required', 'numeric', 'min:0'], 'opening_notes' => ['nullable', 'string', 'max:1000']]);
        $service->open($request->user(), $data);

        return back()->with('success', 'Cashier session opened successfully.');
    }

    public function close(Request $request, CashierSession $session, CashierSessionService $service)
    {
        abort_unless((int) $session->company_id === (int) $request->user()->company_id && (int) $session->user_id === (int) $request->user()->id, 404);
        $data = $request->validate(['actual_cash' => ['required', 'numeric', 'min:0'], 'closing_notes' => ['nullable', 'string', 'max:1000']]);
        $service->close($session, $request->user(), $data);

        return back()->with('success', 'Cashier session closed and reconciled.');
    }
}
