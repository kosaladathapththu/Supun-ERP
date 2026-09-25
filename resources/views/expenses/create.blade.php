@extends('layouts.app')
@section('title',$salaryMode?'New Head Office Salary':'Add Other Head Office Expense')
@section('content')
<div class="mb-4">
    <h1 class="h3 page-title mb-1">{{ $salaryMode?'New Head Office Salary Expense':($previous?'Continue Head Office Expense':'Add Other Head Office Expense') }}</h1>
    <p class="text-muted mb-0">{{ $salaryMode?'Record the total Head Office salary expense for a month. Employee names are not required. Any unpaid amount remains payable until it is paid.':'Record rent, electricity, transport, repairs, marketing, bank charges, or another Head Office expense. Any unpaid amount remains payable.' }}</p>
</div>
<form method="POST" action="{{ route('expenses.store') }}" id="expense-form">
@csrf
<input type="hidden" name="expense_type" value="{{ $salaryMode?'salary':'general' }}">
<input type="hidden" name="business_unit" value="Head Office">
@if($salaryMode)
<input type="hidden" name="payee" value="Head Office Salaries">
<input type="hidden" name="account_id" value="{{ $accounts->first()->id }}">
@endif
<div class="card"><div class="card-body p-4"><div class="row g-3">
    @unless($salaryMode)
    <div class="col-12"><label class="form-label">Continue from previous bill</label><select id="previous-bill" name="previous_expense_id" class="form-select"><option value="">Start a new bill series</option>@foreach($recentBills as $bill)<option value="{{ $bill->id }}" data-account="{{ $bill->account_id }}" data-payee="{{ $bill->payee }}" data-description="{{ $bill->description }}" data-balance="{{ $bill->balance_amount }}" @selected(old('previous_expense_id',$previous?->id)==$bill->id)>{{ $bill->document_number }} — {{ $bill->payee }}{{ $bill->billing_period?' ('.$bill->billing_period.')':'' }} · Outstanding Rs. {{ number_format($bill->balance_amount,2) }}</option>@endforeach</select><div class="form-text">Selecting a previous bill keeps its history connected without adding its old balance again.</div></div>
    <div id="previous-context" class="col-12 {{ $previous?'':'d-none' }}"><div class="alert alert-info mb-0">Previous bill outstanding: <strong>Rs. <span id="previous-balance">{{ number_format($previous?->balance_amount ?? 0,2) }}</span></strong>.</div></div>
    @endunless
    @if($salaryMode)
    <div class="col-12"><div class="alert alert-primary mb-0"><strong>How this works:</strong> enter one total salary amount for the month. Posting records a salary expense and an outstanding payable. Use “Pay Expense” afterward to settle it partially or fully.</div></div>
    @endif
    <div class="col-md-3"><label class="form-label">{{ $salaryMode?'Salary posting date':'Bill date' }}</label><input type="date" name="expense_date" value="{{ old('expense_date',now()->toDateString()) }}" class="form-control" required></div>
    <div class="col-md-3"><label class="form-label">Due date</label><input type="date" name="due_date" value="{{ old('due_date') }}" class="form-control"></div>
    @if($salaryMode)
    <div class="col-md-3"><label class="form-label">Salary month</label><input name="billing_period" value="{{ old('billing_period',now()->format('F Y')) }}" class="form-control" placeholder="e.g. September 2026" required></div>
    <div class="col-md-3"><label class="form-label">Expense account</label><input class="form-control" value="6100 — Salaries" readonly></div>
    @else
    <div class="col-md-3"><label class="form-label">Expense account</label><select id="expense-account" name="account_id" class="form-select" required>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected(old('account_id',$previous?->account_id)==$account->id)>{{ $account->code }} — {{ $account->name }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Billing period</label><input name="billing_period" value="{{ old('billing_period') }}" class="form-control" placeholder="e.g. September 2026"></div>
    @endif
    <div class="col-md-3"><label class="form-label">{{ $salaryMode?'Total salary expense':'Bill amount' }}</label><input id="bill-amount" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="form-control" required></div>
    @unless($salaryMode)<div class="col-md-3"><label class="form-label">Payee / service provider</label><input id="payee" name="payee" value="{{ old('payee',$previous?->payee) }}" class="form-control" placeholder="e.g. Electricity Board" required></div>@endunless
    <div class="col-md-3"><label class="form-label">Amount paid now</label><input id="paid-amount" type="number" step="0.01" min="0" name="paid_amount" value="{{ old('paid_amount',0) }}" class="form-control" required></div>
    <div class="col-md-3"><label class="form-label">Payment method</label><select id="payment-method" name="payment_method" class="form-select"><option value="">Not paid yet</option>@foreach(['cash'=>'Cash','bank_transfer'=>'Bank transfer','cheque'=>'Cheque','online_payment'=>'Online payment'] as $value=>$label)<option value="{{ $value }}" @selected(old('payment_method')===$value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Reference</label><input name="reference" value="{{ old('reference') }}" class="form-control" placeholder="Optional"></div>
    <div class="col-12"><div class="alert alert-info mb-0 d-flex justify-content-between"><span>Outstanding payable after posting</span><strong id="balance-preview">Rs. 0.00</strong></div></div>
    <div class="col-12"><label class="form-label">{{ $salaryMode?'Note':'Description' }}</label><textarea id="description" name="description" class="form-control" required>{{ old('description',$salaryMode?'Head Office salaries for '.now()->format('F Y'):$previous?->description) }}</textarea></div>
    <div class="col-12 text-end"><button class="btn btn-primary px-4">{{ $salaryMode?'Post Salary Expense & Payable':'Post Expense & Payable' }}</button></div>
</div></div></div>
</form>
@endsection
@push('scripts')
<script>
(()=>{const bill=document.getElementById('bill-amount'),paid=document.getElementById('paid-amount'),method=document.getElementById('payment-method'),preview=document.getElementById('balance-preview');const update=()=>{const total=Number(bill.value||0),settled=Number(paid.value||0);paid.max=total||'';method.required=settled>0;method.disabled=settled<=0;if(settled<=0)method.value='';preview.textContent='Rs. '+Math.max(0,total-settled).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})};bill.addEventListener('input',update);paid.addEventListener('input',update);update();const previous=document.getElementById('previous-bill');if(previous){const account=document.getElementById('expense-account'),payee=document.getElementById('payee'),description=document.getElementById('description'),context=document.getElementById('previous-context'),previousBalance=document.getElementById('previous-balance');previous.addEventListener('change',()=>{const option=previous.selectedOptions[0],linked=Boolean(option.value);context.classList.toggle('d-none',!linked);if(!linked)return;account.value=option.dataset.account;payee.value=option.dataset.payee;description.value=option.dataset.description||'';previousBalance.textContent=Number(option.dataset.balance||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})});}})();
</script>
@endpush
