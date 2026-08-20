<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackdatedInvoiceController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CashierSessionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ControlCenterController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GoodsReceivedNoteController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InventoryOperationController;
use App\Http\Controllers\PayableController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\SalesExchangeController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SerialNumberController;
use App\Http\Controllers\StatementController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/change-password', [\App\Http\Controllers\PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/change-password', [\App\Http\Controllers\PasswordController::class, 'update'])->name('password.update');
    Route::get('/', DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');
    Route::middleware('permission:products.view')->group(function () {
        Route::resource('categories', CategoryController::class)->parameters(['categories' => 'record'])->except(['show', 'destroy']);
        Route::resource('brands', BrandController::class)->parameters(['brands' => 'record'])->except(['show', 'destroy']);
        Route::resource('units', UnitController::class)->parameters(['units' => 'record'])->except(['show', 'destroy']);
        Route::resource('products', ProductController::class)->except(['show', 'destroy']);
    });
    Route::get('customers/{record}/print', [CustomerController::class, 'print'])->middleware('permission:customers.view')->name('customers.print');
    Route::resource('customers', CustomerController::class)->parameters(['customers' => 'record'])->except(['destroy'])->middleware('permission:customers.view');
    Route::resource('suppliers', SupplierController::class)->parameters(['suppliers' => 'record'])->except(['show', 'destroy'])->middleware('permission:suppliers.view');
    Route::middleware('permission:purchases.view')->group(function () {
        Route::get('purchases/bill-to-invoice', [GoodsReceivedNoteController::class, 'directCreate'])->name('purchases.direct.create');
        Route::post('purchases/bill-to-invoice', [GoodsReceivedNoteController::class, 'directStore'])->name('purchases.direct.store');
        Route::resource('purchase-orders', PurchaseOrderController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('purchase-orders/{purchase_order}/confirm', [PurchaseOrderController::class, 'confirm'])->middleware('permission:purchases.approve')->name('purchase-orders.confirm');
        Route::get('purchase-orders/{purchase_order}/receive', [GoodsReceivedNoteController::class, 'create'])->name('grn.create');
        Route::post('purchase-orders/{purchase_order}/receive', [GoodsReceivedNoteController::class, 'store'])->name('grn.store');
        Route::get('grn', [GoodsReceivedNoteController::class, 'index'])->name('grn.index');
        Route::get('grn/{grn}', [GoodsReceivedNoteController::class, 'show'])->name('grn.show');
        Route::get('purchase-returns', [PurchaseReturnController::class, 'index'])->name('purchase-returns.index');
        Route::get('grn/{grn}/return', [PurchaseReturnController::class, 'create'])->name('purchase-returns.create');
        Route::post('grn/{grn}/return', [PurchaseReturnController::class, 'store'])->name('purchase-returns.store');
        Route::get('purchase-returns/{purchase_return}', [PurchaseReturnController::class, 'show'])->name('purchase-returns.show');
    });
    Route::middleware('permission:inventory.view')->group(function () {
    Route::get('stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('stock/{product}/ledger', [StockController::class, 'ledger'])->name('stock.ledger');
    });
    Route::middleware('permission:inventory.view')->group(function () {
    Route::get('serial-numbers', [SerialNumberController::class, 'index'])->name('serial-numbers.index');
    Route::post('serial-numbers', [SerialNumberController::class, 'store'])->middleware('permission:inventory.update')->name('serial-numbers.store');
    Route::get('serial-numbers/{serialNumber}', [SerialNumberController::class, 'show'])->name('serial-numbers.show');
    Route::put('serial-numbers/{serialNumber}', [SerialNumberController::class, 'update'])->middleware('permission:inventory.update')->name('serial-numbers.update');
    });
    Route::middleware('permission:inventory.update')->prefix('inventory-operations')->name('inventory-operations.')->group(function () {
    Route::get('/', [InventoryOperationController::class, 'index'])->name('index');
    Route::get('transfer', [InventoryOperationController::class, 'transferForm'])->name('transfer');
    Route::post('transfer', [InventoryOperationController::class, 'transfer'])->name('transfer.store');
    Route::get('adjustment', [InventoryOperationController::class, 'adjustmentForm'])->name('adjustment');
    Route::post('adjustment', [InventoryOperationController::class, 'adjustment'])->name('adjustment.store');
    Route::get('count', [InventoryOperationController::class, 'countForm'])->name('count.start');
    Route::post('count', [InventoryOperationController::class, 'startCount'])->name('count.store');
    Route::get('count/{count}', [InventoryOperationController::class, 'editCount'])->name('count.edit');
    Route::post('count/{count}/post', [InventoryOperationController::class, 'postCount'])->name('count.post');
    });
    Route::get('sales/cash/create', [SaleController::class, 'cashCreate'])->middleware('permission:sales.view')->name('sales.cash.create');
    Route::get('sales/credit/create', [SaleController::class, 'creditCreate'])->middleware('permission:sales.view')->name('sales.credit.create');
    Route::get('backdated-invoices', [BackdatedInvoiceController::class, 'index'])->middleware('permission:backdated_invoices.view')->name('backdated-invoices.index');
    Route::get('admin/backdated-invoice-approvals', [BackdatedInvoiceController::class, 'index'])->middleware('permission:backdated_invoices.approve')->name('admin.backdated-invoices.index');
    Route::get('admin/backdated-invoice-approvals/{backdatedInvoice}', [BackdatedInvoiceController::class, 'show'])->middleware('permission:backdated_invoices.approve')->name('admin.backdated-invoices.show');
    Route::get('backdated-invoices/create', [BackdatedInvoiceController::class, 'create'])->middleware('permission:backdated_invoices.create')->name('backdated-invoices.create');
    Route::get('backdated-invoices/{backdatedInvoice}', [BackdatedInvoiceController::class, 'show'])->middleware('permission:backdated_invoices.view')->name('backdated-invoices.show');
    Route::post('backdated-invoices', [BackdatedInvoiceController::class, 'store'])->middleware('permission:backdated_invoices.create')->name('backdated-invoices.store');
    Route::post('backdated-invoices/window/request', [BackdatedInvoiceController::class, 'requestWindow'])->middleware('permission:backdated_invoices.create')->name('backdated-invoices.window.request');
    Route::post('backdated-invoices/window', [BackdatedInvoiceController::class, 'updateWindow'])->middleware('permission:backdated_invoices.update')->name('backdated-invoices.window');
    Route::post('backdated-invoices/window/reject', [BackdatedInvoiceController::class, 'rejectWindow'])->middleware('permission:backdated_invoices.update')->name('backdated-invoices.window.reject');
    Route::post('backdated-invoices/{backdatedInvoice}/approve', [BackdatedInvoiceController::class, 'approve'])->middleware('permission:backdated_invoices.approve')->name('backdated-invoices.approve');
    Route::post('backdated-invoices/{backdatedInvoice}/reject', [BackdatedInvoiceController::class, 'reject'])->middleware('permission:backdated_invoices.approve')->name('backdated-invoices.reject');
    Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show'])->middleware('permission:sales.view');
    Route::middleware('permission:sales.view')->group(function () {
    Route::resource('quotations', QuotationController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('quotations/{quotation}/convert', [QuotationController::class, 'convert'])->name('quotations.convert');
    Route::get('sales-orders', [SalesOrderController::class, 'index'])->name('sales-orders.index');
    Route::get('sales-orders/{sales_order}', [SalesOrderController::class, 'show'])->name('sales-orders.show');
    Route::get('sales-orders/{sales_order}/deliver', [SalesOrderController::class, 'deliver'])->name('sales-orders.deliver');
    Route::post('sales-orders/{sales_order}/deliver', [SalesOrderController::class, 'storeDelivery'])->name('sales-orders.delivery.store');
    Route::get('delivery-notes', [SalesOrderController::class, 'deliveries'])->name('delivery-notes.index');
    Route::get('delivery-notes/{delivery_note}', [SalesOrderController::class, 'showDelivery'])->name('delivery-notes.show');
    });
    Route::middleware('permission:sales.view')->group(function () {
    Route::get('sale-returns', [SaleReturnController::class, 'index'])->name('sale-returns.index');
    Route::get('sales/{sale}/return', [SaleReturnController::class, 'create'])->name('sale-returns.create');
    Route::post('sales/{sale}/return', [SaleReturnController::class, 'store'])->name('sale-returns.store');
    Route::get('sale-returns/{sale_return}', [SaleReturnController::class, 'show'])->name('sale-returns.show');
    });
    Route::middleware('permission:sales.create')->group(function () {
    Route::get('sales/{sale}/exchange', [SalesExchangeController::class, 'create'])->name('sales-exchanges.create');
    Route::post('sales/{sale}/exchange', [SalesExchangeController::class, 'store'])->name('sales-exchanges.store');
    Route::get('sales-exchanges/{exchange}', [SalesExchangeController::class, 'show'])->name('sales-exchanges.show');
    Route::post('sales/{sale}/void', [SalesExchangeController::class, 'void'])->name('sales.void');
    });
    Route::middleware('permission:sales.view')->group(function () {
        Route::get('receivables/aging', [ReceivableController::class, 'aging'])->name('receivables.aging');
        Route::get('receivables/history', [ReceivableController::class, 'history'])->name('receivables.history');
        Route::get('receivables/export/excel', [ReceivableController::class, 'exportExcel'])->name('receivables.export.excel');
        Route::get('receivables/export/pdf', [ReceivableController::class, 'exportPdf'])->name('receivables.export.pdf');
        Route::get('receivables/customers/{customer}/ledger', [ReceivableController::class, 'ledger'])->name('receivables.ledger');
        Route::resource('receivables', ReceivableController::class)
            ->parameters(['receivables' => 'receipt'])
            ->only(['index', 'create', 'store', 'show']);
    });
    Route::middleware('permission:accounting.view')->prefix('accounting')->name('accounting.')->group(function () {
    Route::get('journals', [AccountingController::class, 'journals'])->name('journals');
    Route::get('journals/{journal}', [AccountingController::class, 'show'])->name('show');
    Route::get('trial-balance', [AccountingController::class, 'trialBalance'])->name('trial-balance');
    Route::get('ledger/{account}', [AccountingController::class, 'ledger'])->name('ledger');
    });
    Route::middleware('permission:accounting.view')->prefix('statements')->name('statements.')->group(function () {
    Route::get('/', [StatementController::class, 'index'])->name('index');
    Route::get('profit-loss', [StatementController::class, 'profitLoss'])->name('profit-loss');
    Route::get('balance-sheet', [StatementController::class, 'balanceSheet'])->name('balance-sheet');
    Route::get('cash-flow', [StatementController::class, 'cashFlow'])->name('cash-flow');
    Route::get('reconciliation', [StatementController::class, 'reconciliation'])->name('reconciliation');
    });
    Route::middleware('permission:accounting.view')->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('profitability', [ReportController::class, 'profitability'])->name('profitability');
    Route::get('profitability/export', [ReportController::class, 'exportProfitability'])->name('profitability.export');
    Route::get('inventory', [ReportController::class, 'inventory'])->name('inventory');
    Route::get('inventory/export', [ReportController::class, 'exportInventory'])->name('inventory.export');
    });
    Route::prefix('controls')->name('controls.')->group(function () {
    Route::get('/', [ControlCenterController::class, 'index'])->middleware('permission:settings.view')->name('index');
    Route::get('periods', [ControlCenterController::class, 'periods'])->middleware('permission:periods.view')->name('periods');
    Route::post('periods/{period}/request', [ControlCenterController::class, 'requestPeriod'])->middleware('permission:periods.update')->name('periods.request');
    Route::get('approvals', [ControlCenterController::class, 'approvals'])->middleware('permission:periods.approve')->name('approvals');
    Route::post('approvals/{approval}/review', [ControlCenterController::class, 'review'])->middleware('permission:periods.approve')->name('approvals.review');
    Route::get('audit', [ControlCenterController::class, 'audit'])->middleware('permission:audit.view')->name('audit');
    Route::post('backup', [ControlCenterController::class, 'backup'])->middleware('permission:backups.create')->name('backup');
    Route::get('backups/{backup}/download', [ControlCenterController::class, 'downloadBackup'])->middleware('permission:backups.create')->name('backups.download');
    });
    Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('users', [UserManagementController::class, 'index'])->middleware('permission:users.view')->name('users.index');
    Route::get('users/create', [UserManagementController::class, 'create'])->middleware('permission:users.create')->name('users.create');
    Route::post('users', [UserManagementController::class, 'store'])->middleware('permission:users.create')->name('users.store');
    Route::get('users/{user}/edit', [UserManagementController::class, 'edit'])->middleware('permission:users.update')->name('users.edit');
    Route::put('users/{user}', [UserManagementController::class, 'update'])->middleware('permission:users.update')->name('users.update');
    Route::get('roles', [RoleManagementController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
    Route::get('roles/{role}/edit', [RoleManagementController::class, 'edit'])->middleware('permission:roles.update')->name('roles.edit');
    Route::put('roles/{role}', [RoleManagementController::class, 'update'])->middleware('permission:roles.update')->name('roles.update');
    });
    Route::middleware('permission:purchases.view')->group(function () {
    Route::get('payables', [PayableController::class, 'index'])->name('payables.index');
    Route::get('payables/history', [PayableController::class, 'history'])->name('payables.history');
    Route::get('payables/export/excel', [PayableController::class, 'exportExcel'])->name('payables.export.excel');
    Route::get('payables/print', [PayableController::class, 'print'])->name('payables.print');
    Route::get('payables/payment', [PayableController::class, 'create'])->name('payables.create');
    Route::post('payables/payment', [PayableController::class, 'store'])->name('payables.store');
    Route::get('payables/aging', [PayableController::class, 'aging'])->name('payables.aging');
    Route::get('payables/suppliers/{supplier}/ledger', [PayableController::class, 'ledger'])->name('payables.ledger');
    });
    Route::get('accounting/accounts', [AccountingController::class, 'accounts'])->middleware('permission:accounting.view')->name('accounting.accounts');
    Route::resource('expenses', ExpenseController::class)->only(['index', 'create', 'store'])->middleware('permission:accounting.view');
    Route::middleware('permission:cashiers.view')->prefix('cashier-sessions')->name('cashier-sessions.')->group(function () {
    Route::get('/', [CashierSessionController::class, 'index'])->name('index');
    Route::post('open', [CashierSessionController::class, 'open'])->middleware('permission:cashiers.create')->name('open');
    Route::post('{session}/close', [CashierSessionController::class, 'close'])->middleware('permission:cashiers.post')->name('close');
    });
    Route::middleware('permission:imports.view')->prefix('imports')->name('imports.')->group(function () {
        Route::get('/', [ImportController::class, 'index'])->name('index');
        Route::get('/template', [ImportController::class, 'template'])->name('template');
        Route::post('/', [ImportController::class, 'store'])->name('store');
        Route::get('/{batch}', [ImportController::class, 'show'])->name('show');
        Route::get('/{batch}/rows/{row}', [ImportController::class, 'viewRow'])->name('rows.show');
        Route::get('/{batch}/rows/{row}/edit', [ImportController::class, 'editRow'])->name('rows.edit');
        Route::put('/{batch}/rows/{row}', [ImportController::class, 'updateRow'])->middleware('permission:imports.create')->name('rows.update');
        Route::delete('/{batch}/rows/{row}', [ImportController::class, 'deleteRow'])->middleware('permission:imports.create')->name('rows.destroy');
        Route::post('/{batch}/cancel', [ImportController::class, 'cancel'])->middleware('permission:imports.create')->name('cancel');
        Route::post('/{batch}/confirm', [ImportController::class, 'confirm'])->middleware('permission:imports.create')->name('confirm');
    });
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
