<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\GoodsReceivedNoteController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\PayableController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\PurchaseReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');
    Route::middleware('permission:products.view')->group(function () {
        Route::resource('categories', CategoryController::class)->parameters(['categories' => 'record'])->except(['show', 'destroy']);
        Route::resource('brands', BrandController::class)->parameters(['brands' => 'record'])->except(['show', 'destroy']);
        Route::resource('units', UnitController::class)->parameters(['units' => 'record'])->except(['show', 'destroy']);
        Route::resource('products', ProductController::class)->except(['show', 'destroy']);
    });
    Route::resource('customers', CustomerController::class)->parameters(['customers' => 'record'])->except(['show', 'destroy'])->middleware('permission:customers.view');
    Route::resource('suppliers', SupplierController::class)->parameters(['suppliers' => 'record'])->except(['show', 'destroy'])->middleware('permission:suppliers.view');
    Route::middleware('permission:purchases.view')->group(function () {
        Route::resource('purchase-orders', PurchaseOrderController::class)->only(['index','create','store','show']);
        Route::post('purchase-orders/{purchase_order}/confirm',[PurchaseOrderController::class,'confirm'])->middleware('permission:purchases.approve')->name('purchase-orders.confirm');
        Route::get('purchase-orders/{purchase_order}/receive',[GoodsReceivedNoteController::class,'create'])->name('grn.create');
        Route::post('purchase-orders/{purchase_order}/receive',[GoodsReceivedNoteController::class,'store'])->name('grn.store');
        Route::get('grn',[GoodsReceivedNoteController::class,'index'])->name('grn.index');
        Route::get('grn/{grn}',[GoodsReceivedNoteController::class,'show'])->name('grn.show');
        Route::get('purchase-returns',[PurchaseReturnController::class,'index'])->name('purchase-returns.index');
        Route::get('grn/{grn}/return',[PurchaseReturnController::class,'create'])->name('purchase-returns.create');
        Route::post('grn/{grn}/return',[PurchaseReturnController::class,'store'])->name('purchase-returns.store');
        Route::get('purchase-returns/{purchase_return}',[PurchaseReturnController::class,'show'])->name('purchase-returns.show');
    });
    Route::middleware('permission:inventory.view')->group(function(){Route::get('stock',[StockController::class,'index'])->name('stock.index');Route::get('stock/{product}/ledger',[StockController::class,'ledger'])->name('stock.ledger');});
    Route::resource('sales', SaleController::class)->only(['index','create','store','show'])->middleware('permission:sales.view');
    Route::middleware('permission:sales.view')->group(function(){Route::get('sale-returns',[SaleReturnController::class,'index'])->name('sale-returns.index');Route::get('sales/{sale}/return',[SaleReturnController::class,'create'])->name('sale-returns.create');Route::post('sales/{sale}/return',[SaleReturnController::class,'store'])->name('sale-returns.store');Route::get('sale-returns/{sale_return}',[SaleReturnController::class,'show'])->name('sale-returns.show');});
    Route::middleware('permission:sales.view')->group(function(){
        Route::get('receivables/aging',[ReceivableController::class,'aging'])->name('receivables.aging');
        Route::get('receivables/customers/{customer}/ledger',[ReceivableController::class,'ledger'])->name('receivables.ledger');
        Route::resource('receivables',ReceivableController::class)->only(['index','create','store','show']);
    });
    Route::middleware('permission:accounting.view')->prefix('accounting')->name('accounting.')->group(function(){Route::get('journals',[AccountingController::class,'journals'])->name('journals');Route::get('journals/{journal}',[AccountingController::class,'show'])->name('show');Route::get('trial-balance',[AccountingController::class,'trialBalance'])->name('trial-balance');Route::get('ledger/{account}',[AccountingController::class,'ledger'])->name('ledger');});
    Route::middleware('permission:purchases.view')->group(function(){Route::get('payables',[PayableController::class,'index'])->name('payables.index');Route::get('payables/payment',[PayableController::class,'create'])->name('payables.create');Route::post('payables/payment',[PayableController::class,'store'])->name('payables.store');Route::get('payables/aging',[PayableController::class,'aging'])->name('payables.aging');Route::get('payables/suppliers/{supplier}/ledger',[PayableController::class,'ledger'])->name('payables.ledger');});
    Route::resource('expenses',ExpenseController::class)->only(['index','create','store'])->middleware('permission:accounting.view');
    Route::middleware('permission:imports.view')->prefix('imports')->name('imports.')->group(function () {
        Route::get('/', [ImportController::class, 'index'])->name('index');
        Route::get('/template', [ImportController::class, 'template'])->name('template');
        Route::post('/', [ImportController::class, 'store'])->name('store');
        Route::get('/{batch}', [ImportController::class, 'show'])->name('show');
        Route::post('/{batch}/confirm', [ImportController::class, 'confirm'])->middleware('permission:imports.create')->name('confirm');
    });
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
