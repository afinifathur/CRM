<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;

class FreeStockService
{
    /**
     * Compute free stock levels dynamically by joining the latest stock snapshots and outstanding orders.
     *
     * @param array $options Filters, search criteria, and sorting instructions
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getFreeStockStatus(array $options = [])
    {
        $search = $options['search'] ?? null;
        $sortBy = $options['sort_by'] ?? 'product_code';
        $sortDir = $options['sort_dir'] ?? 'asc';

        // 1. Get the maximum snapshot date per product
        $latestStockSub = DB::table('stock_snapshots')
            ->select('product_id', DB::raw('MAX(snapshot_date) as max_date'))
            ->groupBy('product_id');

        // 2. Fetch the stock quantities associated with that maximum snapshot date
        $latestStock = DB::table('stock_snapshots as ss')
            ->joinSub($latestStockSub, 'latest', function ($join) {
                $join->on('ss.product_id', '=', 'latest.product_id')
                     ->on('ss.snapshot_date', '=', 'latest.max_date');
            })
            ->select('ss.product_id', 'ss.snapshot_date', 'ss.stock_pcs', 'ss.stock_kg');

        // 3. Aggregate outstanding sales order line quantities for open/partially shipped lines
        $outstandingOrders = DB::table('sales_order_lines')
            ->where('status', '!=', 'completed')
            ->select('product_id', DB::raw('SUM(outstanding_qty) as total_outstanding'))
            ->groupBy('product_id');

        // 4. Combine all components into a fast LEFT JOIN query
        $query = DB::table('products as p')
            ->leftJoinSub($latestStock, 'stock', 'p.id', '=', 'stock.product_id')
            ->leftJoinSub($outstandingOrders, 'orders', 'p.id', '=', 'orders.product_id')
            ->select(
                'p.id',
                'p.product_code',
                'p.product_name',
                DB::raw('COALESCE(stock.stock_pcs, 0) as latest_stock_pcs'),
                DB::raw('COALESCE(stock.stock_kg, 0.0000) as latest_stock_kg'),
                DB::raw('COALESCE(orders.total_outstanding, 0.0000) as total_outstanding'),
                // Calculate Free Stock PCS dynamically
                DB::raw('(COALESCE(stock.stock_pcs, 0) - COALESCE(orders.total_outstanding, 0.0000)) as free_stock_pcs'),
                // Calculate Free Stock KG dynamically (proportional based on average weight from latest stock snapshot)
                DB::raw('COALESCE(stock.stock_kg, 0.0000) - (COALESCE(orders.total_outstanding, 0.0000) * (COALESCE(stock.stock_kg, 0.0000) / NULLIF(stock.stock_pcs, 0))) as free_stock_kg')
            );

        // Apply product search filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('p.product_code', 'like', "%{$search}%")
                  ->orWhere('p.product_name', 'like', "%{$search}%");
            });
        }

        // Validate sorting columns to defend against SQL injection
        $allowedSorts = [
            'product_code' => 'p.product_code',
            'product_name' => 'p.product_name',
            'latest_stock_pcs' => 'latest_stock_pcs',
            'total_outstanding' => 'total_outstanding',
            'free_stock_pcs' => 'free_stock_pcs',
        ];

        $sortColumn = $allowedSorts[$sortBy] ?? 'p.product_code';
        $sortDir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortColumn, $sortDir);

        // Paginate results (15 per page)
        return $query->paginate(15);
    }
}
