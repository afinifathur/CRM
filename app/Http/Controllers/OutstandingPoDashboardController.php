<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesOrderLine;
use Illuminate\Http\Request;

class OutstandingPoDashboardController extends Controller
{
    /**
     * Display the operational outstanding PO overview table.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $customerId = $request->input('customer_id');
        $status = $request->input('status');

        $query = SalesOrderLine::with(['salesOrder.customer', 'product'])
            ->join('sales_orders', 'sales_order_lines.sales_order_id', '=', 'sales_orders.id')
            ->join('products', 'sales_order_lines.product_id', '=', 'products.id')
            ->select('sales_order_lines.*');

        // Apply dynamic query search filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('sales_orders.so_number', 'like', "%{$search}%")
                  ->orWhere('products.product_code', 'like', "%{$search}%")
                  ->orWhere('products.product_name', 'like', "%{$search}%");
            });
        }

        if ($customerId) {
            $query->where('sales_orders.customer_id', $customerId);
        }

        if ($status) {
            $query->where('sales_order_lines.status', $status);
        } else {
            // Default: do not show fully completed items to keep focus on actionable outstanding orders
            $query->where('sales_order_lines.status', '!=', 'completed');
        }

        $lines = $query->orderBy('sales_orders.order_date', 'asc')
            ->paginate(15)
            ->withQueryString();

        $customers = Customer::orderBy('customer_name', 'asc')->get();

        return view('dashboard.outstanding', compact('lines', 'customers'));
    }
}
