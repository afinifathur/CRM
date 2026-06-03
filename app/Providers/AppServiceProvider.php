<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            $pendingCount = \App\Models\ShipmentLine::whereIn('allocation_status', ['unallocated', 'suggested'])->count();
            $outstandingCount = \App\Models\SalesOrderLine::where('status', '!=', 'completed')->count();
            
            $negativeCount = \Illuminate\Support\Facades\DB::table('products as p')
                ->leftJoinSub(
                    \Illuminate\Support\Facades\DB::table('stock_snapshots as ss')
                        ->joinSub(
                            \Illuminate\Support\Facades\DB::table('stock_snapshots')->select('product_id', \Illuminate\Support\Facades\DB::raw('MAX(snapshot_date) as max_date'))->groupBy('product_id'),
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
                    \Illuminate\Support\Facades\DB::table('sales_order_lines')->where('status', '!=', 'completed')->select('product_id', \Illuminate\Support\Facades\DB::raw('SUM(outstanding_qty) as total_outstanding'))->groupBy('product_id'),
                    'orders',
                    'p.id',
                    '=',
                    'orders.product_id'
                )
                ->whereRaw('(COALESCE(stock.stock_pcs, 0) - COALESCE(orders.total_outstanding, 0)) < 0')
                ->count();

            $view->with([
                'pendingCount' => $pendingCount,
                'outstandingCount' => $outstandingCount,
                'negativeCount' => $negativeCount,
            ]);
        });
    }
}
