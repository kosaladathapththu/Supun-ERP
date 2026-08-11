@extends('layouts.app')
@section('title','Sales / POS')
@push('styles')
<style>
.sale-choice-wrap{max-width:1050px;margin:35px auto}.sale-choice{border:2px solid transparent;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease;overflow:hidden}.sale-choice:hover{transform:translateY(-5px);box-shadow:0 18px 38px rgba(15,31,56,.13);border-color:#bad0ff}.sale-choice-icon{width:92px;height:92px;border-radius:24px;display:grid;place-items:center;font-size:2.6rem}.cash-icon{background:#e7f8ee;color:#198754}.credit-icon{background:#eaf1ff;color:#2563eb}.choice-list{list-style:none;padding:0;margin:24px 0}.choice-list li{padding:8px 0;border-bottom:1px solid #edf0f4}.choice-list i{width:25px;color:#198754}.credit-choice .choice-list i{color:#2563eb}.choice-button{padding:14px;font-size:1.08rem;font-weight:700}
</style>
@endpush
@section('content')
<div class="sale-choice-wrap">
    <div class="text-center mb-5"><h1 class="display-6 fw-bold mb-2">Start a Sale</h1><p class="text-muted fs-5 mb-0">Choose the correct transaction type before adding products.</p></div>
    <div class="row g-4">
        <div class="col-lg-6"><a href="{{ route('sales.cash.create') }}" class="text-decoration-none text-dark"><article class="card sale-choice h-100"><div class="card-body p-5"><div class="sale-choice-icon cash-icon mb-4"><i class="bi bi-cash-stack"></i></div><span class="badge text-bg-success mb-2">IMMEDIATE PAYMENT</span><h2 class="h2 fw-bold">Cash Sale / POS</h2><p class="text-muted">For walk-in customers and registered customers paying now.</p><ul class="choice-list"><li><i class="bi bi-check-circle"></i> Walk-in Customer selected automatically</li><li><i class="bi bi-check-circle"></i> Retail cash checkout</li><li><i class="bi bi-check-circle"></i> Cash, card, QR, cheque and other methods</li><li><i class="bi bi-check-circle"></i> Payment collected in full</li></ul><div class="btn btn-success w-100 choice-button"><i class="bi bi-cart-plus me-2"></i>Open Cash POS</div></div></article></a></div>
        <div class="col-lg-6"><a href="{{ route('sales.credit.create') }}" class="text-decoration-none text-dark"><article class="card sale-choice credit-choice h-100"><div class="card-body p-5"><div class="sale-choice-icon credit-icon mb-4"><i class="bi bi-credit-card-2-front"></i></div><span class="badge text-bg-primary mb-2">PAY LATER</span><h2 class="h2 fw-bold">Credit Sale</h2><p class="text-muted">For approved registered customers who will pay later.</p><ul class="choice-list"><li><i class="bi bi-check-circle"></i> Registered customers only</li><li><i class="bi bi-check-circle"></i> Credit-enabled customer required</li><li><i class="bi bi-check-circle"></i> Due date and receivable created</li><li><i class="bi bi-check-circle"></i> Payment recorded later in Receivables</li></ul><div class="btn btn-primary w-100 choice-button"><i class="bi bi-file-earmark-plus me-2"></i>Open Credit Sale</div></div></article></a></div>
    </div>
    <div class="text-center mt-4"><a href="{{ route('sales.index') }}" class="btn btn-light"><i class="bi bi-receipt me-1"></i>View Invoice History</a></div>
</div>
@endsection
