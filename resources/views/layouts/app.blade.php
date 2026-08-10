<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · Supun Group ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root{--navy:#0f1f38;--navy2:#162b4b;--blue:#2563eb;--bg:#f4f7fb;--muted:#6b7280}
        body{background:var(--bg);font-family:Inter,Segoe UI,sans-serif;color:#172033}.sidebar{width:270px;background:var(--navy);height:100vh;position:fixed;inset:0 auto 0 0;color:#fff;overflow-y:scroll;overscroll-behavior:contain;scrollbar-width:thin;scrollbar-color:#405675 var(--navy)}.sidebar::-webkit-scrollbar{width:7px}.sidebar::-webkit-scrollbar-track{background:var(--navy)}.sidebar::-webkit-scrollbar-thumb{background:#405675;border-radius:8px}.sidebar::-webkit-scrollbar-thumb:hover{background:#587194}.brand{padding:24px;border-bottom:1px solid rgba(255,255,255,.1);position:sticky;top:0;background:var(--navy);z-index:2}.brand-mark{width:42px;height:42px;border-radius:12px;background:#2563eb;display:grid;place-items:center;font-size:21px}.nav-section{font-size:.69rem;letter-spacing:.12em;color:#8291a9;padding:20px 22px 7px}.sidebar .nav-link{color:#c8d2e1;border-radius:9px;margin:2px 12px;padding:10px 12px}.sidebar .nav-link:hover,.sidebar .nav-link.active{color:white;background:var(--navy2)}.sidebar .nav-link i{width:25px}.main{margin-left:270px;min-height:100vh}.topbar{height:72px;background:#fff;border-bottom:1px solid #e7ebf1;display:flex;align-items:center;justify-content:space-between;padding:0 28px;position:sticky;top:0;z-index:10}.content{padding:28px}.card{border:0;box-shadow:0 4px 18px rgba(15,31,56,.06);border-radius:14px}.page-title{font-weight:700}.metric-icon{width:46px;height:46px;border-radius:12px;display:grid;place-items:center;background:#eaf1ff;color:#2563eb;font-size:21px}.badge-soft{background:#edf7f1;color:#198754}.table thead th{font-size:.75rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;background:#f8fafc;border-bottom:1px solid #e8edf3}.btn-primary{background:#2563eb;border-color:#2563eb}.coming{opacity:.45;pointer-events:none}.alert{border:0;border-radius:12px}@media(max-width:991px){.sidebar{position:relative;width:100%;height:auto;max-height:none;overflow:visible}.main{margin-left:0}.topbar{position:relative}}
    </style>
    @stack('styles')
</head>
<body>
<aside class="sidebar">
    <div class="brand d-flex align-items-center gap-3"><div class="brand-mark"><i class="bi bi-cpu"></i></div><div><div class="fw-bold">Supun Group</div><small class="text-white-50">ERP System</small></div></div>
    <nav class="pb-4">
        <div class="nav-section">OVERVIEW</div>
        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <div class="nav-section">MASTER DATA</div>
        @foreach(['products.index'=>['box-seam','Products'],'categories.index'=>['diagram-3','Categories'],'brands.index'=>['tags','Brands'],'units.index'=>['rulers','Units'],'customers.index'=>['people','Customers'],'suppliers.index'=>['truck','Suppliers']] as $route => [$icon,$label])
            @if(Route::has($route))<a class="nav-link {{ request()->routeIs(str_replace('.index','.*',$route)) ? 'active' : '' }}" href="{{ route($route) }}"><i class="bi bi-{{ $icon }}"></i> {{ $label }}</a>@endif
        @endforeach
        <a class="nav-link {{ request()->routeIs('imports.*') ? 'active' : '' }}" href="{{ route('imports.index') }}"><i class="bi bi-file-earmark-spreadsheet"></i> Data Import</a>
        <div class="nav-section">TRANSACTIONS <span class="badge bg-secondary ms-1">Next phases</span></div>
        <a class="nav-link {{ request()->routeIs('sales.*')?'active':'' }}" href="{{ route('sales.create') }}"><i class="bi bi-cart3"></i> Sales / POS</a><a class="nav-link {{ request()->routeIs('sale-returns.*')?'active':'' }}" href="{{ route('sale-returns.index') }}"><i class="bi bi-arrow-return-left"></i> Sales Returns</a><a class="nav-link {{ request()->routeIs('purchase-orders.*')?'active':'' }}" href="{{ route('purchase-orders.index') }}"><i class="bi bi-bag"></i> Purchase Orders</a><a class="nav-link {{ request()->routeIs('grn.*')?'active':'' }}" href="{{ route('grn.index') }}"><i class="bi bi-box-arrow-in-down"></i> GRN</a><a class="nav-link {{ request()->routeIs('stock.*')?'active':'' }}" href="{{ route('stock.index') }}"><i class="bi bi-boxes"></i> Current Stock</a>
        <div class="nav-section">FINANCE</div>
        <a class="nav-link {{ request()->routeIs('receivables.*')?'active':'' }}" href="{{ route('receivables.index') }}"><i class="bi bi-cash-coin"></i> Receivables</a><a class="nav-link {{ request()->routeIs('payables.*')?'active':'' }}" href="{{ route('payables.index') }}"><i class="bi bi-wallet2"></i> Payables</a><a class="nav-link {{ request()->routeIs('expenses.*')?'active':'' }}" href="{{ route('expenses.index') }}"><i class="bi bi-receipt"></i> Expenses</a><a class="nav-link {{ request()->routeIs('accounting.*')?'active':'' }}" href="{{ route('accounting.journals') }}"><i class="bi bi-journal-bookmark"></i> Accounting</a><span class="nav-link coming"><i class="bi bi-bar-chart"></i> Reports</span>
    </nav>
</aside>
<main class="main">
    <header class="topbar"><div><div class="fw-semibold">@yield('title', 'Dashboard')</div><small class="text-muted">{{ now()->format('l, d F Y') }}</small></div><div class="d-flex align-items-center gap-3"><div class="text-end"><div class="fw-semibold small">{{ auth()->user()->name }}</div><small class="text-muted">Main Admin</small></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-light btn-sm" title="Sign out"><i class="bi bi-box-arrow-right"></i></button></form></div></header>
    <section class="content">
        @if(session('success'))<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><strong>Please correct the following:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body></html>
