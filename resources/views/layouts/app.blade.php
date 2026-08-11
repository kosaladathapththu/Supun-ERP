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
        body{background:var(--bg);font-family:Inter,Segoe UI,sans-serif;color:#172033}.sidebar{width:270px;background:var(--navy);height:100vh;position:fixed;inset:0 auto 0 0;color:#fff;overflow-y:scroll;overscroll-behavior:contain;scrollbar-width:thin;scrollbar-color:#405675 var(--navy)}.sidebar::-webkit-scrollbar{width:7px}.sidebar::-webkit-scrollbar-track{background:var(--navy)}.sidebar::-webkit-scrollbar-thumb{background:#405675;border-radius:8px}.sidebar::-webkit-scrollbar-thumb:hover{background:#587194}.brand{padding:24px;border-bottom:1px solid rgba(255,255,255,.1);position:sticky;top:0;background:var(--navy);z-index:2}.brand-mark{width:42px;height:42px;border-radius:12px;background:#2563eb;display:grid;place-items:center;font-size:21px}.nav-section{font-size:.69rem;letter-spacing:.12em;color:#8291a9;padding:20px 22px 7px}.sidebar .nav-link{color:#c8d2e1;border-radius:9px;margin:2px 12px;padding:10px 12px}.sidebar .nav-link:hover,.sidebar .nav-link.active{color:white;background:var(--navy2)}.sidebar .nav-link i{width:25px}.sidebar .pos-nav{margin:10px 12px 12px;padding:12px;background:#2563eb;color:#fff;font-weight:700;box-shadow:0 7px 18px rgba(37,99,235,.28)}.sidebar .pos-nav:hover,.sidebar .pos-nav.active{background:#1d4ed8;color:#fff}.nav-group-toggle{width:calc(100% - 24px);border:0;text-align:left;display:flex;align-items:center}.nav-group-toggle .chevron{margin-left:auto;width:auto!important;transition:transform .2s}.nav-group-toggle:not(.collapsed) .chevron{transform:rotate(180deg)}.nav-submenu{padding:2px 0 7px}.nav-submenu .nav-link{font-size:.91rem;padding:8px 12px 8px 39px;margin-top:0;margin-bottom:0}.nav-submenu .nav-link i{margin-left:-25px}.main{margin-left:270px;min-height:100vh}.topbar{height:72px;background:#fff;border-bottom:1px solid #e7ebf1;display:flex;align-items:center;justify-content:space-between;padding:0 28px;position:sticky;top:0;z-index:10}.content{padding:28px}.card{border:0;box-shadow:0 4px 18px rgba(15,31,56,.06);border-radius:14px}.page-title{font-weight:700}.metric-icon{width:46px;height:46px;border-radius:12px;display:grid;place-items:center;background:#eaf1ff;color:#2563eb;font-size:21px}.badge-soft{background:#edf7f1;color:#198754}.table thead th{font-size:.75rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;background:#f8fafc;border-bottom:1px solid #e8edf3}.btn-primary{background:#2563eb;border-color:#2563eb}.coming{opacity:.45;pointer-events:none}.alert{border:0;border-radius:12px}@media(max-width:991px){.sidebar{position:relative;width:100%;height:auto;max-height:none;overflow:visible}.main{margin-left:0}.topbar{position:relative}}
    </style>
    <style>.topbar-back{width:38px;height:38px;border-radius:10px;display:grid;place-items:center;flex:0 0 auto}</style>
    @stack('styles')
</head>
<body>
<aside class="sidebar">
    <div class="brand d-flex align-items-center gap-3"><div class="brand-mark"><i class="bi bi-cpu"></i></div><div><div class="fw-bold">Supun Group</div><small class="text-white-50">ERP System</small></div></div>
    <nav class="pb-4" id="sidebarMenu">
        @php
            $masterOpen=request()->routeIs('products.*','categories.*','brands.*','units.*','customers.*','suppliers.*','imports.*');
            $posOpen=request()->routeIs('sales.create','sales.cash.create','sales.credit.create');
            $salesOpen=(request()->routeIs('sales.*','quotations.*','sales-orders.*','delivery-notes.*','sale-returns.*','sales-exchanges.*')&&!$posOpen);
            $purchaseOpen=request()->routeIs('purchase-orders.*','grn.*','purchase-returns.*');
            $inventoryOpen=request()->routeIs('stock.*','inventory-operations.*','serial-numbers.*');
            $financeOpen=request()->routeIs('cashier-sessions.*','receivables.*','payables.*','expenses.*','accounting.*');
            $reportsOpen=request()->routeIs('statements.*','reports.*');
            $adminOpen=request()->routeIs('admin.*','controls.*');
        @endphp
        <div class="nav-section">MAIN MENU</div>
        <a class="nav-link {{ request()->routeIs('dashboard')?'active':'' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a class="nav-link pos-nav {{ $posOpen?'active':'' }}" href="{{ route('sales.create') }}"><i class="bi bi-calculator-fill"></i> POS</a>
        <a class="nav-link {{ request()->routeIs('receivables.*')?'active':'' }}" href="{{ route('receivables.index') }}"><i class="bi bi-cash-coin"></i> Receivables</a>
        <a class="nav-link {{ request()->routeIs('payables.*')?'active':'' }}" href="{{ route('payables.index') }}"><i class="bi bi-wallet2"></i> Payables</a>
        @php
        $groups=[
          ['masterMenu','database','Master Data',$masterOpen,[['products.index','box-seam','Products'],['categories.index','diagram-3','Categories'],['brands.index','tags','Brands'],['units.index','rulers','Units'],['customers.index','people','Customers'],['suppliers.index','truck','Suppliers'],['imports.index','file-earmark-spreadsheet','Data Import']]],
          ['salesMenu','cart3','Sales',$salesOpen,[['sales.index','receipt','All Sales'],['sales.index','cash','Cash Sale History',['payment_type'=>'cash']],['sales.index','credit-card','Credit Sale History',['payment_type'=>'credit']],['quotations.index','file-earmark-text','Quotations'],['sales-orders.index','clipboard-check','Sales Orders'],['delivery-notes.index','truck','Delivery Notes'],['sale-returns.index','arrow-return-left','Sales Returns']]],
          ['purchaseMenu','bag','Purchases',$purchaseOpen,[['purchase-orders.index','bag','Purchase Orders'],['grn.index','box-arrow-in-down','Goods Received / GRN'],['purchase-returns.index','arrow-return-right','Purchase Returns']]],
          ['inventoryMenu','boxes','Inventory',$inventoryOpen,[['stock.index','boxes','Current Stock'],['serial-numbers.index','upc-scan','Serials & Warranty'],['inventory-operations.index','arrow-left-right','Inventory Operations']]],
          ['financeMenu','bank','Finance',$financeOpen,[['cashier-sessions.index','cash-stack','Cashier Closing'],['receivables.index','person-lines-fill','Customer Ledgers & Receipts'],['payables.index','truck-flatbed','Supplier Ledgers & Payments'],['accounting.accounts','journal-text','General Account Ledgers'],['stock.index','boxes','Stock Ledgers'],['expenses.index','receipt','Expenses'],['accounting.journals','journal-bookmark','Accounting Journals']]],
          ['reportsMenu','bar-chart-line','Reports',$reportsOpen,[['reports.index','grid','Report Center'],['statements.index','file-earmark-bar-graph','Financial Statements'],['statements.profit-loss','graph-up-arrow','Profit & Loss'],['statements.balance-sheet','columns-gap','Balance Sheet'],['statements.cash-flow','cash-stack','Cash Flow'],['statements.reconciliation','check2-square','Reconciliation'],['reports.profitability','pie-chart','Profitability Report'],['reports.inventory','boxes','Inventory Report']]],
          ['adminMenu','gear','Administration',$adminOpen,[['admin.users.index','people','Staff Users'],['admin.roles.index','person-lock','Roles & Permissions'],['controls.index','shield-check','Control Center']]]
        ];
        @endphp
        @foreach($groups as [$id,$icon,$label,$open,$items])
          <button class="nav-link nav-group-toggle {{ $open?'':'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $id }}" aria-expanded="{{ $open?'true':'false' }}"><i class="bi bi-{{ $icon }}"></i> {{ $label }}<i class="bi bi-chevron-down chevron"></i></button>
          <div class="collapse {{ $open?'show':'' }} nav-submenu" id="{{ $id }}" data-bs-parent="#sidebarMenu">
            @foreach($items as $item)
              @php
                [$route,$itemIcon,$itemLabel]=$item;
                $params=$item[3]??[];
                $isActive=request()->routeIs($route)&&collect($params)->every(fn($v,$k)=>request($k)===$v);
                if($route==='sales.index'&&!$params){$isActive=$isActive&&!request()->filled('payment_type');}
              @endphp
              <a class="nav-link {{ $isActive?'active':'' }}" href="{{ route($route,$params) }}"><i class="bi bi-{{ $itemIcon }}"></i> {{ $itemLabel }}</a>
            @endforeach
          </div>
        @endforeach
    </nav>
</aside>
<main class="main">
    @php
        $showBackButton = !request()->routeIs(
            'dashboard', '*.index', 'accounting.accounts', 'accounting.journals', 'statements.index',
            'reports.index', 'controls.index', 'password.*'
        );
    @endphp
    <header class="topbar">
        <div class="d-flex align-items-center gap-3">
            @if($showBackButton)
                <button type="button" id="global-back-button" class="btn btn-light topbar-back" title="Go back" aria-label="Go back"><i class="bi bi-arrow-left"></i></button>
            @endif
            <div><div class="fw-semibold">@yield('title', 'Dashboard')</div><small class="text-muted">{{ now()->format('l, d F Y') }}</small></div>
        </div>
        <div class="d-flex align-items-center gap-3"><div class="text-end"><div class="fw-semibold small">{{ auth()->user()->name }}</div><a class="small text-muted" href="{{ route('password.edit') }}">Change password</a></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-light btn-sm" title="Sign out"><i class="bi bi-box-arrow-right"></i></button></form></div>
    </header>
    <section class="content">
        @if(!auth()->user()->password_changed_at && !request()->routeIs('password.*'))<div class="alert alert-warning"><strong>Security action required:</strong> replace the initial administrator password. <a href="{{ route('password.edit') }}">Change it now</a>.</div>@endif
        @if(session('success'))<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><strong>Please correct the following:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const normalizeQuantityInputs = root => root.querySelectorAll?.('input[type="number"]').forEach(input => {
        if (input.classList.contains('qty') || /(^|\[)(quantity|counted_quantity)(\]|$)/.test(input.name || '')) {
            input.step = 'any';
            if (Number(input.min) > 0 && Number(input.min) < 0.01) input.min = '0.01';
            if (input.value !== '' && Number.isFinite(Number(input.value))) input.value = String(Number(input.value));
        }
    });
    normalizeQuantityInputs(document);
    new MutationObserver(records => records.forEach(record => record.addedNodes.forEach(node => {
        if (node.nodeType === 1) normalizeQuantityInputs(node);
    }))).observe(document.body, {childList:true, subtree:true});
    const backButton = document.getElementById('global-back-button');
    if (!backButton) return;
    const existingBackLink = [...document.querySelectorAll('.content a')].find(link => link.querySelector('.bi-arrow-left'));
    if (existingBackLink) backButton.style.display = 'none';
    backButton.addEventListener('click', () => {
        if (window.history.length > 1) window.history.back();
        else window.location.href = @json(route('dashboard'));
    });
});
</script>
@stack('scripts')
</body></html>
