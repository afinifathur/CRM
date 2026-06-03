<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StockSnapshotImportController;
use App\Http\Controllers\OutstandingPoImportController;
use App\Http\Controllers\ShipmentImportController;
use App\Http\Controllers\AllocationController;
use App\Http\Controllers\OutstandingPoDashboardController;
use App\Http\Controllers\FreeStockDashboardController;
use App\Http\Controllers\HomeController;

use App\Http\Controllers\ImportPreviewController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/import/stock', [StockSnapshotImportController::class, 'index'])->name('import.stock.index');
Route::post('/import/stock', [StockSnapshotImportController::class, 'store'])->name('import.stock.store');

Route::get('/import/po', [OutstandingPoImportController::class, 'index'])->name('import.po.index');
Route::post('/import/po', [OutstandingPoImportController::class, 'store'])->name('import.po.store');

Route::get('/import/shipment', [ShipmentImportController::class, 'index'])->name('import.shipment.index');
Route::post('/import/shipment', [ShipmentImportController::class, 'store'])->name('import.shipment.store');

// Preview Layer Routes
Route::get('/imports/preview/{id}', [ImportPreviewController::class, 'show'])->name('imports.preview');
Route::post('/imports/preview/{id}/confirm', [ImportPreviewController::class, 'confirm'])->name('imports.confirm');
Route::post('/imports/preview/{id}/cancel', [ImportPreviewController::class, 'cancel'])->name('imports.cancel');
Route::get('/imports/preview/{id}/download-csv', [ImportPreviewController::class, 'downloadCsv'])->name('imports.download_csv');
Route::get('/imports/history', [ImportPreviewController::class, 'history'])->name('imports.history');
Route::get('/imports/summary/{batch_id}', [ImportPreviewController::class, 'summary'])->name('imports.summary');

Route::get('/allocations', [AllocationController::class, 'index'])->name('allocations.index');
Route::post('/allocations/{id}/approve', [AllocationController::class, 'approve'])->name('allocations.approve');
Route::post('/allocations/{id}/reset', [AllocationController::class, 'reset'])->name('allocations.reset');

Route::get('/dashboard/outstanding', [OutstandingPoDashboardController::class, 'index'])->name('dashboard.outstanding');
Route::get('/dashboard/freestock', [FreeStockDashboardController::class, 'index'])->name('dashboard.freestock');





