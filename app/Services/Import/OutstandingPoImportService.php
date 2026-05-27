<?php

namespace App\Services\Import;

use App\Models\Customer;
use App\Models\ImportBatch;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class OutstandingPoImportService
{
    /**
     * Parse and import the Outstanding PO report.
     *
     * @param ImportBatch $batch
     * @param \Illuminate\Http\UploadedFile $file
     * @return ImportBatch
     * @throws \Exception
     */
    public function import(ImportBatch $batch, $file): ImportBatch
    {
        $sheets = Excel::toArray([], $file);

        if (empty($sheets) || empty($sheets[0])) {
            throw new \Exception("The spreadsheet is empty or has no readable sheets.");
        }

        $rows = $sheets[0];

        // 1. Discover header row dynamically
        $headerRowIndex = -1;
        $columnMap = [
            'customer_code' => -1,
            'customer_name' => -1,
            'so_number' => -1,
            'product_code' => -1,
            'product_name' => -1,
            'ordered_qty' => -1,
            'outstanding_qty' => -1,
            'order_date' => -1,
        ];

        foreach ($rows as $index => $row) {
            $cleanedRow = array_map(function ($val) {
                return strtolower(trim((string)$val));
            }, $row);

            foreach ($cleanedRow as $cellIndex => $cellVal) {
                if (empty($cellVal)) {
                    continue;
                }

                // Match Customer Code
                if ($this->matchPattern($cellVal, ['customer code', 'kode customer', 'kode pelanggan', 'cust code', 'customer_code'])) {
                    $columnMap['customer_code'] = $cellIndex;
                }
                // Match Customer Name
                elseif ($this->matchPattern($cellVal, ['customer name', 'nama customer', 'nama pelanggan', 'cust name', 'customer_name'])) {
                    $columnMap['customer_name'] = $cellIndex;
                }
                // Match SO Number
                elseif ($this->matchPattern($cellVal, ['so number', 'nomor so', 'no so', 'sales order number', 'po number', 'nomor po', 'so_number'])) {
                    $columnMap['so_number'] = $cellIndex;
                }
                // Match Product Code
                elseif ($this->matchPattern($cellVal, ['product code', 'kode produk', 'item code', 'kode barang', 'product_code'])) {
                    $columnMap['product_code'] = $cellIndex;
                }
                // Match Product Name
                elseif ($this->matchPattern($cellVal, ['product name', 'nama produk', 'item name', 'nama barang', 'deskripsi', 'description', 'product_name'])) {
                    $columnMap['product_name'] = $cellIndex;
                }
                // Match Ordered Qty
                elseif ($this->matchPattern($cellVal, ['ordered qty', 'order qty', 'jumlah order', 'ordered quantity', 'qty order', 'ordered_qty'])) {
                    $columnMap['ordered_qty'] = $cellIndex;
                }
                // Match Undelivered Qty (Outstanding Qty)
                elseif ($this->matchPattern($cellVal, ['undelivered qty', 'outstanding qty', 'outstanding', 'sisa order', 'undelivered quantity', 'outstanding_qty'])) {
                    $columnMap['outstanding_qty'] = $cellIndex;
                }
                // Match Order Date
                elseif ($this->matchPattern($cellVal, ['order date', 'tanggal order', 'so date', 'tanggal so', 'order_date'])) {
                    $columnMap['order_date'] = $cellIndex;
                }
            }

            // Consider it the header row if we successfully mapped Customer Code, SO Number, Product Code, and Outstanding Qty
            if ($columnMap['customer_code'] !== -1 && $columnMap['so_number'] !== -1 && $columnMap['product_code'] !== -1 && $columnMap['outstanding_qty'] !== -1) {
                $headerRowIndex = $index;
                break;
            }
        }

        if ($headerRowIndex === -1) {
            throw new \Exception("Could not detect Outstanding PO report headers automatically. Please make sure the sheet contains columns like: 'Customer Code', 'SO Number', 'Product Code', 'Ordered Qty', and 'Undelivered Qty'.");
        }

        $totalRows = 0;
        $insertedRows = 0;
        $skippedRows = 0;

        // 2. Parse and upsert row by row starting from after header
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            $custCode = $this->getMappedVal($row, $columnMap['customer_code']);
            $custName = $this->getMappedVal($row, $columnMap['customer_name']);
            $soNumber = $this->getMappedVal($row, $columnMap['so_number']);
            $prodCode = $this->getMappedVal($row, $columnMap['product_code']);
            $prodName = $this->getMappedVal($row, $columnMap['product_name']);
            $orderedQtyStr = $this->getMappedVal($row, $columnMap['ordered_qty']);
            $outstandingQtyStr = $this->getMappedVal($row, $columnMap['outstanding_qty']);
            $orderDateStr = $this->getMappedVal($row, $columnMap['order_date']);

            // Skip empty rows, subtotals, footers, headers
            if (empty($soNumber) || empty($custCode) || empty($prodCode) || $this->isIgnoredRow($soNumber, $custCode)) {
                continue;
            }

            // Normalization
            $orderedQty = $orderedQtyStr !== null ? (float) str_replace([',', ' '], '', $orderedQtyStr) : 0.0000;
            $outstandingQty = $outstandingQtyStr !== null ? (float) str_replace([',', ' '], '', $outstandingQtyStr) : 0.0000;
            $orderDate = $this->parseDate($orderDateStr);

            $totalRows++;

            // Use database transaction for atomic inserts/updates
            DB::transaction(function () use ($custCode, $custName, $soNumber, $prodCode, $prodName, $orderedQty, $outstandingQty, $orderDate, $batch, &$insertedRows, &$skippedRows) {
                // Find or create Customer
                $customerName = !empty($custName) ? $custName : $custCode;
                $customer = Customer::firstOrCreate(
                    ['customer_code' => $custCode],
                    ['customer_name' => $customerName]
                );

                // Find or create Product
                $productName = !empty($prodName) ? $prodName : $prodCode;
                $product = Product::firstOrCreate(
                    ['product_code' => $prodCode],
                    ['product_name' => $productName]
                );

                // Find or create Sales Order
                $salesOrder = SalesOrder::firstOrCreate(
                    ['so_number' => $soNumber],
                    [
                        'customer_id' => $customer->id,
                        'order_date' => $orderDate,
                        'import_batch_id' => $batch->id,
                    ]
                );

                // Safe Conflict Resolution (Idempotency Strategy)
                $orderLine = SalesOrderLine::where('sales_order_id', $salesOrder->id)
                    ->where('product_id', $product->id)
                    ->first();

                if (!$orderLine) {
                    // Line does not exist: create fresh
                    SalesOrderLine::create([
                        'sales_order_id' => $salesOrder->id,
                        'product_id' => $product->id,
                        'ordered_qty' => $orderedQty,
                        'allocated_qty' => 0.0000,
                        'outstanding_qty' => $outstandingQty, // Ingest undelivered qty from ERP
                        'status' => $outstandingQty <= 0 ? 'completed' : 'open',
                    ]);
                    $insertedRows++;
                } else {
                    // Line already exists: check if shipments have already been allocated locally
                    if (bccomp($orderLine->allocated_qty, '0', 4) === 0) {
                        // Scenario A: No allocations registered yet -> Safe to overwrite with fresh ERP snapshot
                        $orderLine->update([
                            'ordered_qty' => $orderedQty,
                            'outstanding_qty' => $outstandingQty,
                            'status' => $outstandingQty <= 0 ? 'completed' : 'open',
                        ]);
                        $insertedRows++;
                    } else {
                        // Scenario B: Shipments have been allocated -> Skip updates to preserve local ledger integrity
                        $skippedRows++;
                        Log::warning("Skipped updating sales order line (SO: {$soNumber}, Prod: {$prodCode}) during import: already has active allocations (Allocated: {$orderLine->allocated_qty} pcs).");
                    }
                }
            });
        }

        // 3. Update batch status
        $batch->update([
            'total_rows' => $totalRows,
            'inserted_rows' => $insertedRows,
            'skipped_rows' => $skippedRows,
            'notes' => "Successfully parsed. Processed: {$totalRows}, Inserted/Updated: {$insertedRows}, Skipped Active Allocations: {$skippedRows}.",
        ]);

        return $batch;
    }

    private function matchPattern(string $val, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($val === $pattern || str_contains($val, $pattern)) {
                return true;
            }
        }
        return false;
    }

    private function getMappedVal(array $row, int $index): ?string
    {
        if ($index === -1 || !isset($row[$index])) {
            return null;
        }
        $val = trim((string)$row[$index]);
        return $val === '' ? null : $val;
    }

    private function isIgnoredRow(string $so, string $custCode): bool
    {
        $soLower = strtolower($so);
        $custLower = strtolower($custCode);

        $ignoreKeywords = ['total', 'subtotal', 'grand total', 'ringkasan', 'summary', 'report', 'balance', 'page', 'halaman'];

        foreach ($ignoreKeywords as $keyword) {
            if (str_contains($soLower, $keyword) || str_contains($custLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function parseDate(?string $val): string
    {
        if (empty($val)) {
            return now()->toDateString();
        }

        // Check for serialized Excel numeric dates (usually 5 digits)
        if (is_numeric($val) && (int)$val > 10000 && (int)$val < 100000) {
            try {
                return Carbon::instance(Date::excelToDateTimeObject((int)$val))->toDateString();
            } catch (\Throwable $e) {
                // fall through to normal parsing
            }
        }

        try {
            $cleanVal = str_replace('/', '-', $val);
            return Carbon::parse($cleanVal)->toDateString();
        } catch (\Throwable $e) {
            return now()->toDateString();
        }
    }
}
