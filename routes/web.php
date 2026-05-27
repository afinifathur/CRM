<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StockSnapshotImportController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/import/stock', [StockSnapshotImportController::class, 'index'])->name('import.stock.index');
Route::post('/import/stock', [StockSnapshotImportController::class, 'store'])->name('import.stock.store');

