<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Models\SalesOrderLine;
use App\Models\ShipmentLine;
use App\Models\StockSnapshot;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Show the operational reconciliation homepage.
     */
    public function index()
    {
        // 1. Fetch latest ingestion batches
        $latestBatches = ImportBatch::orderBy('id', 'desc')
            ->take(5)
            ->get();

        // 2. Determine latest snapshot date
        $latestSnapshotDate = StockSnapshot::max('snapshot_date');

        // 3. Count negative free stock products
        $negativeCount = DB::table('products as p')
            ->leftJoinSub(
                DB::table('stock_snapshots as ss')
                    ->joinSub(
                        DB::table('stock_snapshots')->select('product_id', DB::raw('MAX(snapshot_date) as max_date'))->groupBy('product_id'),
                        'latest',
                        function ($join) {
                            $join->on('ss.product_id', '=', 'latest.product_id')->on('ss.snapshot_date', '=', 'latest.max_date');
                        }
                    )->select('ss.product_id', 'ss.stock_pcs'),
                'stock',
                'p.id',
                '=',
                'stock.product_id'
            )
            ->leftJoinSub(
                DB::table('sales_order_lines')->where('status', '!=', 'completed')->select('product_id', DB::raw('SUM(outstanding_qty) as total_outstanding'))->groupBy('product_id'),
                'orders',
                'p.id',
                '=',
                'orders.product_id'
            )
            ->whereRaw('(COALESCE(stock.stock_pcs, 0) - COALESCE(orders.total_outstanding, 0)) < 0')
            ->count();

        // 4. Retrieve total count parameters
        $pendingCount = ShipmentLine::whereIn('allocation_status', ['unallocated', 'suggested'])->count();
        $outstandingCount = SalesOrderLine::where('status', '!=', 'completed')->count();

        return view('dashboard.home', compact(
            'latestBatches',
            'latestSnapshotDate',
            'negativeCount',
            'pendingCount',
            'outstandingCount'
        ));
    }
}
