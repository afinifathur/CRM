<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StockSnapshotImportController;
use App\Http\Controllers\OutstandingPoImportController;
use App\Http\Controllers\ShipmentImportController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/import/stock', [StockSnapshotImportController::class, 'index'])->name('import.stock.index');
Route::post('/import/stock', [StockSnapshotImportController::class, 'store'])->name('import.stock.store');

Route::get('/import/po', [OutstandingPoImportController::class, 'index'])->name('import.po.index');
Route::post('/import/po', [OutstandingPoImportController::class, 'store'])->name('import.po.store');

Route::get('/import/shipment', [ShipmentImportController::class, 'index'])->name('import.shipment.index');
Route::post('/import/shipment', [ShipmentImportController::class, 'store'])->name('import.shipment.store');



