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
    <style>
        .topbar-back{width:38px;height:38px;border-radius:10px;display:grid;place-items:center;flex:0 0 auto}
        .approval-bell{width:40px;height:40px;border-radius:10px;display:grid;place-items:center;position:relative}.approval-badge{position:absolute;top:-5px;right:-5px;min-width:20px;height:20px;padding:0 5px;border-radius:10px;background:#dc3545;color:#fff;border:2px solid #fff;font-size:.65rem;font-weight:700;display:grid;place-items:center}.approval-menu{width:350px;max-width:calc(100vw - 24px);padding:0;border:0;box-shadow:0 14px 40px rgba(15,31,56,.18);border-radius:12px;overflow:hidden}.approval-item{display:block;padding:12px 15px;border-top:1px solid #edf0f4;color:#172033;text-decoration:none}.approval-item:hover{background:#f5f8fc}.approval-item small{display:block;color:#6b7280;margin-top:2px}
        .sidebar .nav-link{margin-top:3px;margin-bottom:3px;transition:background-color .16s,color .16s,transform .16s}
        .sidebar .nav-link:hover{color:#fff;background:#1b3458;transform:translateX(2px)}
        .sidebar .nav-group-toggle{border:1px solid transparent;font-weight:600}
        .sidebar .nav-group-toggle:hover{background:#1b3458;border-color:#29486f;transform:none}
        .sidebar .nav-group-toggle.active{color:#dbeafe;background:#24436f;border-color:#3b6598;box-shadow:inset 4px 0 #60a5fa}
        .sidebar .nav-submenu{position:relative;padding-top:3px;padding-bottom:9px}
        .sidebar .nav-submenu:before{content:"";position:absolute;left:28px;top:4px;bottom:12px;width:1px;background:#29415f}
        .sidebar .nav-submenu .nav-link{position:relative;line-height:1.25;white-space:normal}
        .sidebar .nav-submenu .nav-link:hover{color:#bae6fd;background:#193354}
        .sidebar .nav-submenu .nav-link.active{color:#0f2f57;background:#dbeafe;box-shadow:inset 4px 0 #38bdf8,0 4px 12px rgba(56,189,248,.14);font-weight:700;transform:none}
        .sidebar .nav-submenu .nav-link.active i{color:#0369a1}
        .sidebar nav>.nav-link.active:not(.pos-nav){color:#fff;background:#2563eb;box-shadow:inset 4px 0 #93c5fd}
        .sidebar .pos-nav{margin:10px 12px 14px;padding:12px;color:#6ee7b7!important;background:rgba(5,150,105,.12)!important;border:1px solid rgba(52,211,153,.35);font-weight:750;box-shadow:none!important}
        .sidebar .pos-nav:hover{color:#fff!important;background:#047857!important;transform:translateY(-1px)}
        .sidebar .pos-nav.active{color:#fff!important;background:#059669!important;box-shadow:inset 4px 0 #a7f3d0,0 7px 18px rgba(5,150,105,.28)!important;transform:none}
    </style>
    @stack('styles')
</head>
<body>
<aside class="sidebar">
    <div class="brand d-flex align-items-center gap-3"><div class="brand-mark"><i class="bi bi-cpu"></i></div><div><div class="fw-bold">Supun Group</div><small class="text-white-50">ERP System</small></div></div>
    <nav class="pb-4" id="sidebarMenu">
        @php
            $masterOpen=request()->routeIs('products.*','categories.*','brands.*','units.*','customers.*','suppliers.*');
            $posOpen=request()->routeIs('sales.create','sales.cash.create','sales.credit.create');
            $salesOpen=(request()->routeIs('sales.*','backdated-invoices.*','quotations.*','sales-orders.*','delivery-notes.*','sale-returns.*','sales-exchanges.*')&&!$posOpen);
            $purchaseOpen=request()->routeIs('purchase-orders.*','grn.*','purchase-returns.*');
            $inventoryOpen=request()->routeIs('stock.*','inventory-operations.*','serial-numbers.*','imports.*');
            $financeOpen=request()->routeIs('cashier-sessions.*','receivables.*','payables.*','expenses.*','accounting.*');
            $reportsOpen=request()->routeIs('statements.*','reports.*');
            $adminOpen=request()->routeIs('admin.*','controls.*');
        @endphp
        <div class="nav-section">MAIN MENU</div>
        <a class="nav-link {{ request()->routeIs('dashboard')?'active':'' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a class="nav-link pos-nav {{ $posOpen?'active':'' }}" href="{{ route('sales.create') }}"><i class="bi bi-calculator-fill"></i> POS</a>
        @php
        $groups=[
          ['masterMenu','database','Master Data',$masterOpen,[['products.index','box-seam','Products'],['categories.index','diagram-3','Categories'],['brands.index','tags','Brands'],['units.index','rulers','Units'],['customers.index','people','Customers'],['suppliers.index','truck','Suppliers']]],
          ['salesMenu','cart3','Sales',$salesOpen,[['sales.index','receipt','All Sales'],['backdated-invoices.index','calendar-check','Backdated Invoices'],['quotations.index','file-earmark-text','Quotations'],['sales-orders.index','clipboard-check','Sales Orders'],['delivery-notes.index','truck','Delivery Notes'],['sale-returns.index','arrow-return-left','Sales Returns']]],
          ['purchaseMenu','bag','Purchases',$purchaseOpen,[['purchase-orders.index','bag','Purchase Orders'],['grn.index','box-arrow-in-down','Goods Received / GRN'],['purchase-returns.index','arrow-return-right','Purchase Returns']]],
          ['inventoryMenu','boxes','Inventory',$inventoryOpen,[['stock.index','boxes','Current Stock'],['imports.index','file-earmark-spreadsheet','Bulk Inventory Import'],['serial-numbers.index','upc-scan','Serials & Warranty'],['inventory-operations.index','arrow-left-right','Inventory Operations']]],
          ['financeMenu','bank','Finance',$financeOpen,[['cashier-sessions.index','cash-stack','Cashier Closing'],['receivables.index','person-lines-fill','Receivables — Customer Ledgers'],['payables.index','truck-flatbed','Payables — Supplier Ledgers'],['accounting.accounts','journal-text','General Account Ledgers'],['stock.index','boxes','Stock Ledgers'],['expenses.index','receipt','Expenses'],['accounting.journals','journal-bookmark','Accounting Journals']]],
          ['reportsMenu','bar-chart-line','Reports',$reportsOpen,[['reports.index','grid','Report Center'],['statements.index','file-earmark-bar-graph','Financial Statements'],['statements.profit-loss','graph-up-arrow','Profit & Loss'],['statements.balance-sheet','columns-gap','Balance Sheet'],['statements.cash-flow','cash-stack','Cash Flow'],['statements.reconciliation','check2-square','Reconciliation'],['reports.profitability','pie-chart','Profitability Report'],['reports.inventory','boxes','Inventory Report']]],
          ['adminMenu','gear','Administration',$adminOpen,[['admin.backdated-invoices.index','calendar-check','Backdated Invoice Approvals',[],'backdated_invoices.approve'],['admin.users.index','people','Staff Users'],['admin.roles.index','person-lock','Roles & Permissions'],['controls.index','shield-check','Control Center']]]
        ];
        @endphp
        @foreach($groups as [$id,$icon,$label,$open,$items])
          <button class="nav-link nav-group-toggle {{ $open?'active':'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $id }}" aria-expanded="{{ $open?'true':'false' }}"><i class="bi bi-{{ $icon }}"></i> {{ $label }}<i class="bi bi-chevron-down chevron"></i></button>
          <div class="collapse {{ $open?'show':'' }} nav-submenu" id="{{ $id }}" data-bs-parent="#sidebarMenu">
            @foreach($items as $item)
              @php
                [$route,$itemIcon,$itemLabel]=$item;
                $params=$item[3]??[];
                $requiredPermission=$item[4]??null;
                if($requiredPermission&&!auth()->user()->hasPermission($requiredPermission))continue;
                $isActive=request()->routeIs($route)&&collect($params)->every(fn($v,$k)=>request($k)===$v);
                $sectionPatterns=[
                    'products.index'=>['products.*'],
                    'categories.index'=>['categories.*'],
                    'brands.index'=>['brands.*'],
                    'units.index'=>['units.*'],
                    'customers.index'=>['customers.*'],
                    'suppliers.index'=>['suppliers.*'],
                    'imports.index'=>['imports.*'],
                    'purchase-orders.index'=>['purchase-orders.*'],
                    'grn.index'=>['grn.*'],
                    'purchase-returns.index'=>['purchase-returns.*'],
                    'stock.index'=>['stock.*'],
                    'serial-numbers.index'=>['serial-numbers.*'],
                    'inventory-operations.index'=>['inventory-operations.*'],
                    'backdated-invoices.index'=>['backdated-invoices.*'],
                    'quotations.index'=>['quotations.*'],
                    'sales-orders.index'=>['sales-orders.*'],
                    'delivery-notes.index'=>['delivery-notes.*'],
                    'sale-returns.index'=>['sale-returns.*'],
                    'cashier-sessions.index'=>['cashier-sessions.*'],
                    'receivables.index'=>['receivables.*'],
                    'payables.index'=>['payables.*'],
                    'expenses.index'=>['expenses.*'],
                    'accounting.accounts'=>['accounting.accounts','accounting.ledger','accounting.trial-balance'],
                    'accounting.journals'=>['accounting.journals','accounting.show'],
                    'reports.index'=>['reports.index'],
                    'reports.profitability'=>['reports.profitability'],
                    'reports.inventory'=>['reports.inventory'],
                    'statements.index'=>['statements.index'],
                    'statements.profit-loss'=>['statements.profit-loss'],
                    'statements.balance-sheet'=>['statements.balance-sheet'],
                    'statements.cash-flow'=>['statements.cash-flow'],
                    'statements.reconciliation'=>['statements.reconciliation'],
                    'admin.users.index'=>['admin.users.*'],
                    'admin.roles.index'=>['admin.roles.*'],
                    'admin.backdated-invoices.index'=>['admin.backdated-invoices.*'],
                    'controls.index'=>['controls.*'],
                ];
                if(isset($sectionPatterns[$route]))$isActive=request()->routeIs(...$sectionPatterns[$route]);
                if($route==='sales.index'){
                    $isSalesHistory=request()->routeIs('sales.index');
                    if($params)$isActive=$isSalesHistory&&collect($params)->every(fn($v,$k)=>request($k)===$v);
                    else $isActive=$isSalesHistory&&!request()->filled('payment_type')||request()->routeIs('sales.show','sales-exchanges.*');
                }
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
        $canApproveBackdated = auth()->user()->hasPermission('backdated_invoices.approve');
        $pendingBackdated = collect();
        $pendingWindowRequest = null;
        if ($canApproveBackdated) {
            $pendingBackdated = \App\Models\BackdatedInvoiceRequest::with('requester')->where('company_id',auth()->user()->company_id)->where('status','pending')->latest('submitted_at')->limit(5)->get();
            $pendingWindowRequest = \App\Models\BackdatedInvoiceSetting::with('requester')->where('company_id',auth()->user()->company_id)->whereNotNull('requested_days')->first();
        }
        $approvalNotificationCount = $pendingBackdated->count() + ($pendingWindowRequest ? 1 : 0);
    @endphp
    <header class="topbar">
        <div class="d-flex align-items-center gap-3">
            @if($showBackButton)
                <button type="button" id="global-back-button" class="btn btn-light topbar-back" title="Go back" aria-label="Go back"><i class="bi bi-arrow-left"></i></button>
            @endif
            <div><div class="fw-semibold">@yield('title', 'Dashboard')</div><small class="text-muted">{{ now()->format('l, d F Y') }}</small></div>
        </div>
        <div class="d-flex align-items-center gap-3">
            @if($canApproveBackdated)
            <div class="dropdown">
                <button class="btn btn-light approval-bell" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Approval notifications" aria-label="Approval notifications">
                    <i class="bi bi-bell{{ $approvalNotificationCount ? '-fill' : '' }}"></i>
                    @if($approvalNotificationCount)<span class="approval-badge">{{ $approvalNotificationCount > 99 ? '99+' : $approvalNotificationCount }}</span>@endif
                </button>
                <div class="dropdown-menu dropdown-menu-end approval-menu">
                    <div class="p-3 d-flex justify-content-between align-items-center"><strong>Approvals</strong>@if($approvalNotificationCount)<span class="badge text-bg-danger">{{ $approvalNotificationCount }} pending</span>@endif</div>
                    @if($pendingWindowRequest)
                        <a class="approval-item" href="{{ route('admin.backdated-invoices.index') }}"><div class="d-flex gap-2"><i class="bi bi-calendar-range text-warning"></i><div><strong>Date-range request</strong><small>{{ $pendingWindowRequest->requester?->name ?? 'Accountant' }} requested {{ $pendingWindowRequest->requested_days }} days.</small></div></div></a>
                    @endif
                    @foreach($pendingBackdated as $pendingApproval)
                        <a class="approval-item" href="{{ route('admin.backdated-invoices.show',$pendingApproval) }}"><div class="d-flex gap-2"><i class="bi bi-receipt text-primary"></i><div><strong>{{ $pendingApproval->request_number }}</strong><small>{{ $pendingApproval->requester?->name ?? 'Staff user' }} · Rs. {{ number_format($pendingApproval->total_amount,2) }}</small></div></div></a>
                    @endforeach
                    @if(!$approvalNotificationCount)<div class="p-4 text-center text-muted"><i class="bi bi-check-circle d-block fs-4 mb-1"></i>No approvals waiting.</div>@endif
                    <div class="p-2 border-top bg-light"><a class="btn btn-sm btn-primary w-100" href="{{ route('admin.backdated-invoices.index') }}">Open Approval Center</a></div>
                </div>
            </div>
            @endif
            <div class="text-end"><div class="fw-semibold small">{{ auth()->user()->name }}</div><a class="small text-muted" href="{{ route('password.edit') }}">Change password</a></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-light btn-sm" title="Sign out"><i class="bi bi-box-arrow-right"></i></button></form>
        </div>
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
    const sidebar=document.querySelector('.sidebar');
    if(sidebar){const saved=Number(sessionStorage.getItem('erp-sidebar-scroll')||0);sidebar.scrollTop=saved;sidebar.addEventListener('scroll',()=>sessionStorage.setItem('erp-sidebar-scroll',String(sidebar.scrollTop)),{passive:true});const active=sidebar.querySelector('.nav-submenu .nav-link.active, nav > .nav-link.active');if(active){requestAnimationFrame(()=>{const sidebarBox=sidebar.getBoundingClientRect(),activeBox=active.getBoundingClientRect(),safeTop=sidebarBox.top+100,safeBottom=sidebarBox.bottom-50;if(activeBox.top<safeTop||activeBox.bottom>safeBottom)active.scrollIntoView({block:'center'});});}}
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
