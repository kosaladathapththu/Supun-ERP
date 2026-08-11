@extends('layouts.app')
@section('title','Cashier Closing')
@section('content')
<div class="d-flex justify-content-between align-items-start mb-4"><div><h1 class="page-title h2 mb-1">Cashier opening & closing</h1><p class="text-muted mb-0">Reconcile physical cash against transactions recorded by the system.</p></div>@if($current)<span class="badge text-bg-success fs-6">Session open</span>@endif</div>

@if(!$current)
<div class="card mb-4"><div class="card-body p-4"><h2 class="h5">Open your cashier session</h2><form method="POST" action="{{ route('cashier-sessions.open') }}" class="row g-3">@csrf
<div class="col-md-4"><label class="form-label">Opening cash float</label><div class="input-group"><span class="input-group-text">Rs.</span><input class="form-control" type="number" min="0" step="0.01" name="opening_cash" value="{{ old('opening_cash',0) }}" required></div></div>
<div class="col-md-6"><label class="form-label">Opening notes (optional)</label><input class="form-control" name="opening_notes" value="{{ old('opening_notes') }}"></div>
<div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100"><i class="bi bi-unlock me-1"></i> Open session</button></div></form></div></div>
@else
<div class="row g-3 mb-4">
@foreach([['Opening float',$current->opening_cash,'primary'],['Cash sales',$totals['cash_sales'],'success'],['Customer receipts',$totals['customer_receipts'],'success'],['Cash expenses',$totals['cash_expenses'],'danger'],['Supplier payments',$totals['supplier_payments'],'danger'],['Cash refunds',$totals['cash_refunds'],'danger']] as [$label,$amount,$colour])
<div class="col-md-4 col-xl-2"><div class="card h-100"><div class="card-body"><small class="text-muted">{{ $label }}</small><div class="fw-bold text-{{ $colour }} mt-2">Rs. {{ number_format($amount,2) }}</div></div></div></div>
@endforeach
</div>
<div class="card mb-4"><div class="card-body p-4"><div class="row align-items-end g-3"><div class="col-lg-4"><div class="text-muted">Expected drawer cash</div><div class="display-6 fw-bold">Rs. {{ number_format($totals['expected_cash'],2) }}</div><small class="text-muted">Opened {{ $current->opened_at->format('d M Y, h:i A') }}</small></div><div class="col-lg-8"><form method="POST" action="{{ route('cashier-sessions.close',$current) }}" class="row g-3">@csrf
<div class="col-md-4"><label class="form-label">Actual counted cash</label><input class="form-control" type="number" min="0" step="0.01" name="actual_cash" required></div><div class="col-md-5"><label class="form-label">Closing notes</label><input class="form-control" name="closing_notes"></div><div class="col-md-3 d-flex align-items-end"><button class="btn btn-danger w-100" onclick="return confirm('Close and lock this cashier session?')"><i class="bi bi-lock me-1"></i> Close session</button></div></form></div></div></div></div>
@endif

<div class="card"><div class="card-header bg-white p-4"><h2 class="h5 mb-0">Session history</h2></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Date / Cashier</th><th>Opened</th><th>Closed</th><th>Expected</th><th>Counted</th><th>Variance</th><th>Status</th></tr></thead><tbody>
@forelse($sessions as $session)<tr><td><strong>{{ $session->business_date->format('d M Y') }}</strong><div class="text-muted small">{{ $session->user->name }}</div></td><td>Rs. {{ number_format($session->opening_cash,2) }}</td><td>{{ $session->closed_at?->format('h:i A') ?? '—' }}</td><td>Rs. {{ number_format($session->expected_cash,2) }}</td><td>{{ $session->actual_cash === null ? '—' : 'Rs. '.number_format($session->actual_cash,2) }}</td><td class="fw-semibold {{ (float)$session->variance===0.0?'text-success':'text-danger' }}">{{ $session->variance === null ? '—' : 'Rs. '.number_format($session->variance,2) }}</td><td><span class="badge {{ $session->status==='open'?'text-bg-success':'text-bg-secondary' }}">{{ ucfirst($session->status) }}</span></td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-5">No cashier sessions yet.</td></tr>@endforelse
</tbody></table></div>@if($sessions->hasPages())<div class="card-footer bg-white">{{ $sessions->links() }}</div>@endif</div>
@endsection
