<?php

namespace App\Http\Controllers;

use App\Services\Inventory\FreeStockService;
use Illuminate\Http\Request;

class FreeStockDashboardController extends Controller
{
    protected FreeStockService $freeStockService;

    public function __construct(FreeStockService $freeStockService)
    {
        $this->freeStockService = $freeStockService;
    }

    /**
     * Display the operational Free Stock levels page for PPIC.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'product_code');
        $sortDir = $request->input('sort_dir', 'asc');

        // Query dynamic calculated free stock levels
        $items = $this->freeStockService->getFreeStockStatus([
            'search' => $search,
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
        ]);

        return view('dashboard.freestock', compact('items', 'sortBy', 'sortDir'));
    }
}
