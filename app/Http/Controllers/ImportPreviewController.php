<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Models\ImportPreviewBatch;
use App\Services\Import\StockSnapshotImportService;
use App\Services\Import\OutstandingPoImportService;
use App\Services\Import\ShipmentImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ImportPreviewController extends Controller
{
    protected StockSnapshotImportService $stockService;
    protected OutstandingPoImportService $poService;
    protected ShipmentImportService $shipmentService;

    public function __construct(
        StockSnapshotImportService $stockService,
        OutstandingPoImportService $poService,
        ShipmentImportService $shipmentService
    ) {
        $this->stockService = $stockService;
        $this->poService = $poService;
        $this->shipmentService = $shipmentService;
    }

    /**
     * Display the Handsontable preview screen.
     */
    public function show($id)
    {
        $preview = ImportPreviewBatch::findOrFail($id);

        if ($preview->status !== 'preview') {
            return redirect()
                ->route('imports.history')
                ->with('error', 'This preview session has already been ' . $preview->status . '.');
        }

        $payload = $preview->preview_payload;

        return view('import.preview', compact('preview', 'payload'));
    }

    /**
     * Confirm import and commit records into production tables.
     */
    public function confirm(Request $request, $id)
    {
        $preview = ImportPreviewBatch::findOrFail($id);

        if ($preview->status !== 'preview') {
            return redirect()->route('imports.history')->withErrors(['file' => 'This preview session is not in preview status.']);
        }

        if (!File::exists($preview->temp_file_path)) {
            return redirect()->back()->withErrors(['file' => 'Temporary file has expired or was removed.']);
        }

        // Initialize production ImportBatch
        $batch = ImportBatch::create([
            'import_type' => $preview->type === 'po' ? 'outstanding_po' : ($preview->type === 'stock' ? 'stock_snapshot' : 'shipment'),
            'source_filename' => $preview->source_filename,
            'imported_at' => now(),
            'total_rows' => 0,
            'inserted_rows' => 0,
            'skipped_rows' => 0,
            'notes' => 'Executing confirmed import from preview...',
        ]);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($preview, $batch) {
                if ($preview->type === 'stock') {
                    $snapshotDateStr = $preview->snapshot_date ? $preview->snapshot_date->toDateString() : now()->toDateString();
                    $this->stockService->import($batch, $preview->temp_file_path, $snapshotDateStr);
                } elseif ($preview->type === 'po') {
                    $this->poService->import($batch, $preview->temp_file_path);
                } elseif ($preview->type === 'shipment') {
                    $this->shipmentService->import($batch, $preview->temp_file_path);
                }
            });

            // Mark preview as confirmed
            $preview->update([
                'status' => 'confirmed',
                'import_batch_id' => $batch->id
            ]);

            // Clean up temp file
            if (File::exists($preview->temp_file_path)) {
                File::delete($preview->temp_file_path);
            }

            // Redirect to the import summary page with success
            return redirect()
                ->route('imports.summary', $batch->id)
                ->with('success', 'Import confirmed and successfully written into production tables!');

        } catch (\Throwable $e) {
            Log::error("Failed to commit import for preview batch #{$preview->id}: " . $e->getMessage(), [
                'exception' => $e
            ]);

            $batch->update([
                'notes' => 'Failed to commit: ' . substr($e->getMessage(), 0, 250)
            ]);

            return redirect()
                ->back()
                ->withErrors(['file' => 'Commit Failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Cancel import, mark cancelled, and clean up temporary storage.
     */
    public function cancel($id)
    {
        $preview = ImportPreviewBatch::findOrFail($id);

        if ($preview->status === 'preview') {
            $preview->update(['status' => 'cancelled']);
        }

        if (File::exists($preview->temp_file_path)) {
            File::delete($preview->temp_file_path);
        }

        $redirectRoute = $preview->type === 'stock' ? 'import.stock.index' : ($preview->type === 'po' ? 'import.po.index' : 'import.shipment.index');

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Import preview session was cancelled. No production data was affected.');
    }

    /**
     * Show import summary page.
     */
    public function summary($batch_id)
    {
        $batch = ImportBatch::findOrFail($batch_id);

        $notesData = [];
        if ($batch->notes) {
            $decoded = json_decode($batch->notes, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $notesData = $decoded;
            }
        }

        $dbCounts = [
            'customers' => \App\Models\Customer::count(),
            'products' => \App\Models\Product::count(),
            'sales_orders' => \App\Models\SalesOrder::count(),
            'sales_order_lines' => \App\Models\SalesOrderLine::count(),
            'shipments' => \App\Models\ShipmentLine::count(),
            'stock_snapshots' => \App\Models\StockSnapshot::count(),
        ];

        return view('import.summary', compact('batch', 'notesData', 'dbCounts'));
    }

    /**
     * Download Parsed CSV from preview payload (TASK 5).
     */
    public function downloadCsv($id)
    {
        $preview = ImportPreviewBatch::findOrFail($id);
        $payload = $preview->preview_payload;
        $rows = $payload['rows'] ?? [];

        $filename = "parsed_" . $preview->type . "_" . now()->format('Ymd_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($rows, $preview) {
            $file = fopen('php://output', 'w');
            
            if ($preview->type === 'stock') {
                fputcsv($file, ['Row Index', 'Product Code', 'Product Name', 'Stock PCS', 'Stock KG', 'Warnings']);
                foreach ($rows as $row) {
                    fputcsv($file, [
                        $row['row_index'],
                        $row['product_code'],
                        $row['product_name'],
                        $row['stock_pcs'],
                        $row['stock_kg'],
                        implode('; ', $row['_warnings'] ?? [])
                    ]);
                }
            } elseif ($preview->type === 'po') {
                $parserMode = $payload['parser_mode'] ?? 'Flat Table Mode';
                if ($parserMode === 'Grouped Customer ERP Mode') {
                    fputcsv($file, ['Row Index', 'Customer Code', 'Customer Name', 'SO Number', 'SO Date', 'Product Name', 'UnDlv Qty', 'Warnings']);
                    foreach ($rows as $row) {
                        fputcsv($file, [
                            $row['row_index'],
                            $row['customer_code'],
                            $row['customer_name'],
                            $row['so_number'],
                            $row['order_date'],
                            $row['product_name'],
                            $row['outstanding'],
                            implode('; ', $row['_warnings'] ?? [])
                        ]);
                    }
                } else {
                    fputcsv($file, ['Row Index', 'Customer Code', 'Customer Name', 'SO Number', 'Product Code', 'Product Name', 'Ordered Qty', 'Outstanding Qty', 'Order Date', 'Warnings']);
                    foreach ($rows as $row) {
                        fputcsv($file, [
                            $row['row_index'],
                            $row['customer_code'],
                            $row['customer_name'],
                            $row['so_number'],
                            $row['product'],
                            $row['product_name'],
                            $row['qty'],
                            $row['outstanding'],
                            $row['order_date'],
                            implode('; ', $row['_warnings'] ?? [])
                        ]);
                    }
                }
            } elseif ($preview->type === 'shipment') {
                fputcsv($file, ['Row Index', 'Shipment Date', 'SJ Number', 'Customer Code', 'Customer Name', 'Product Code', 'Product Name', 'Shipped Qty', 'Confidence', 'Warnings']);
                foreach ($rows as $row) {
                    fputcsv($file, [
                        $row['row_index'],
                        $row['shipment_date'],
                        $row['sj_number'],
                        $row['customer_code'],
                        $row['customer_name'],
                        $row['product'],
                        $row['product_name'],
                        $row['qty'],
                        $row['_confidence'] ?? 'HIGH',
                        implode('; ', $row['_warnings'] ?? [])
                    ]);
                }
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show audit history of all preview batches (TASK 8).
     */
    public function history()
    {
        $batches = ImportPreviewBatch::orderBy('id', 'desc')->get();

        return view('import.history', compact('batches'));
    }
}
