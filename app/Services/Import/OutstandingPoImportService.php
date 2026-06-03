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
        $detailHeaderRowIndex = -1;
        $parserMode = 'Flat Table Mode';
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

            // Try to match Flat Table Mode headers first
            $flatCols = [
                'customer_code' => -1, 'customer_name' => -1, 'so_number' => -1,
                'product_code' => -1, 'product_name' => -1, 'ordered_qty' => -1,
                'outstanding_qty' => -1, 'order_date' => -1
            ];

            foreach ($cleanedRow as $cellIndex => $cellVal) {
                if (empty($cellVal)) {
                    continue;
                }

                if ($this->matchPattern($cellVal, ['customer code', 'kode customer', 'kode pelanggan', 'cust code', 'customer_code'])) {
                    $flatCols['customer_code'] = $cellIndex;
                } elseif ($this->matchPattern($cellVal, ['customer name', 'nama customer', 'nama pelanggan', 'cust name', 'customer_name'])) {
                    $flatCols['customer_name'] = $cellIndex;
                } elseif ($this->matchPattern($cellVal, ['so number', 'nomor so', 'no so', 'sales order number', 'po number', 'nomor po', 'so_number'])) {
                    $flatCols['so_number'] = $cellIndex;
                } elseif ($this->matchPattern($cellVal, ['product code', 'kode produk', 'item code', 'kode barang', 'product_code'])) {
                    $flatCols['product_code'] = $cellIndex;
                } elseif ($this->matchPattern($cellVal, ['product name', 'nama produk', 'item name', 'nama barang', 'deskripsi', 'description', 'product_name'])) {
                    $flatCols['product_name'] = $cellIndex;
                } elseif ($this->matchPattern($cellVal, ['ordered qty', 'order qty', 'jumlah order', 'ordered quantity', 'qty order', 'ordered_qty'])) {
                    $flatCols['ordered_qty'] = $cellIndex;
                } elseif ($this->matchPattern($cellVal, ['undelivered qty', 'outstanding qty', 'outstanding', 'sisa order', 'undelivered quantity', 'outstanding_qty'])) {
                    $flatCols['outstanding_qty'] = $cellIndex;
                } elseif ($this->matchPattern($cellVal, ['order date', 'tanggal order', 'so date', 'tanggal so', 'order_date'])) {
                    $flatCols['order_date'] = $cellIndex;
                }
            }

            if ($flatCols['customer_code'] !== -1 && $flatCols['so_number'] !== -1 && $flatCols['product_code'] !== -1 && $flatCols['outstanding_qty'] !== -1) {
                $headerRowIndex = $index;
                $columnMap = $flatCols;
                $parserMode = 'Flat Table Mode';
                break;
            }

            // Try to match Grouped Customer ERP Mode headers (Two-Row Structure)
            $custCodeIdx = -1;
            $custNameIdx = -1;
            foreach ($cleanedRow as $cellIndex => $cellVal) {
                if (empty($cellVal)) continue;
                if ($this->matchPattern($cellVal, ['cust. code', 'cust code', 'customer code', 'customer_code'])) {
                    $custCodeIdx = $cellIndex;
                } elseif ($this->matchPattern($cellVal, ['cust. short name', 'cust short name', 'customer name', 'customer_name'])) {
                    $custNameIdx = $cellIndex;
                }
            }

            if ($custCodeIdx !== -1 && $custNameIdx !== -1) {
                $nextIndex = $index + 1;
                if (isset($rows[$nextIndex])) {
                    $nextCleanedRow = array_map(fn($v) => strtolower(trim((string)$v)), $rows[$nextIndex]);
                    
                    $groupedCols = [
                        'customer_code' => $custCodeIdx,
                        'customer_name' => $custNameIdx,
                        'so_number' => -1,
                        'order_date' => -1,
                        'product_name' => -1,
                        'ordered_qty' => -1,
                        'outstanding_qty' => -1,
                    ];

                    foreach ($nextCleanedRow as $cellIndex => $cellVal) {
                        if (empty($cellVal)) continue;

                        if ($this->matchPattern($cellVal, ['so no.', 'so no', 'so number', 'so_number'])) {
                            $groupedCols['so_number'] = $cellIndex;
                        } elseif ($this->matchPattern($cellVal, ['so date', 'order date', 'tanggal so', 'order_date'])) {
                            $groupedCols['order_date'] = $cellIndex;
                        } elseif ($this->matchPattern($cellVal, ['product name', 'nama produk', 'item name', 'nama barang', 'product_name'])) {
                            $groupedCols['product_name'] = $cellIndex;
                        } elseif ($this->matchPattern($cellVal, ['so qty', 'ordered qty', 'ordered quantity', 'ordered_qty'])) {
                            $groupedCols['ordered_qty'] = $cellIndex;
                        } elseif ($this->matchPattern($cellVal, ['undlv qty > 0', 'undlv qty', 'outstanding qty', 'outstanding_qty'])) {
                            $groupedCols['outstanding_qty'] = $cellIndex;
                        }
                    }

                    if ($groupedCols['so_number'] !== -1 && $groupedCols['product_name'] !== -1 && $groupedCols['outstanding_qty'] !== -1) {
                        $headerRowIndex = $index;
                        $detailHeaderRowIndex = $nextIndex;
                        $columnMap = $groupedCols;
                        $columnMap['product_code'] = -1; // No product code column
                        $parserMode = 'Grouped Customer ERP Mode';
                        break;
                    }
                }
            }
        }

        if ($headerRowIndex === -1) {
            // Generate temporary parser diagnostics
            $getColLetter = function ($index) {
                $letter = '';
                while ($index >= 0) {
                    $letter = chr(($index % 26) + 65) . $letter;
                    $index = intval($index / 26) - 1;
                }
                return $letter;
            };

            $debugDump = "";
            for ($r = 0; $r < min(30, count($rows)); $r++) {
                $row = $rows[$r];
                $rowNum = $r + 1;
                $rowLines = [];
                foreach ($row as $cIndex => $val) {
                    $cleanVal = trim((string)$val);
                    if ($cleanVal !== '') {
                        $colLetter = $getColLetter($cIndex);
                        $rowLines[] = "{$colLetter} = \"{$cleanVal}\"";
                    }
                }
                $debugDump .= "Row {$rowNum}:\n";
                if (!empty($rowLines)) {
                    $debugDump .= implode("\n", $rowLines) . "\n\n";
                } else {
                    $debugDump .= "(empty)\n\n";
                }
            }

            file_put_contents(storage_path('logs/po_parser_debug.log'), $debugDump);
            session()->flash('po_parser_debug_sample', $debugDump);

            throw new \Exception("Could not detect Outstanding PO report headers automatically. Please make sure the sheet contains columns like: 'Customer Code', 'SO Number', 'Product Code', 'Ordered Qty', and 'Undelivered Qty' (or 'Cust. Code', 'SO No.', 'Product Name', 'SO Qty', and 'UnDlv Qty > 0').");
        }

        $totalRows = 0;
        $insertedRows = 0;
        $skippedRows = 0;
        $totalCustomerGroups = 0;

        $insertedCustomersCount = 0;
        $insertedProductsCount = 0;
        $insertedSalesOrdersCount = 0;
        $insertedSalesOrderLinesCount = 0;

        $currentCustomerCode = null;
        $currentCustomerName = null;

        $startRowIndex = ($parserMode === 'Grouped Customer ERP Mode') ? $detailHeaderRowIndex + 1 : $headerRowIndex + 1;

        // 2. Parse and upsert row by row starting from after header
        for ($i = $startRowIndex; $i < count($rows); $i++) {
            $row = $rows[$i];

            // Skip completely empty rows
            $nonEmptyCells = array_filter($row, fn($v) => trim((string)$v) !== '');
            if (empty($nonEmptyCells)) {
                continue;
            }

            $custCodeVal = $this->getMappedVal($row, $columnMap['customer_code']);
            $custNameVal = $this->getMappedVal($row, $columnMap['customer_name']);
            $soNumberVal = $this->getMappedVal($row, $columnMap['so_number']);
            $prodNameVal = $this->getMappedVal($row, $columnMap['product_name']);

            // Detect Customer Header Row in Grouped Customer ERP Mode
            if ($parserMode === 'Grouped Customer ERP Mode') {
                if (!empty($custCodeVal) && !empty($custNameVal) && empty($prodNameVal)) {
                    if ($this->isIgnoredRow('', $custCodeVal)) {
                        continue;
                    }
                    $currentCustomerCode = $custCodeVal;
                    $currentCustomerName = $custNameVal;
                    $totalCustomerGroups++;
                    continue;
                }
            }

            if ($parserMode === 'Grouped Customer ERP Mode') {
                $custCode = $currentCustomerCode;
                $custName = $currentCustomerName;
                $soNumber = $soNumberVal;
                $prodCode = $prodNameVal;
                $prodName = $prodNameVal;
            } else {
                $custCode = $custCodeVal;
                $custName = $custNameVal;
                $soNumber = $soNumberVal;
                $prodCode = $this->getMappedVal($row, $columnMap['product_code']);
                $prodName = $prodNameVal;
            }

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
            DB::transaction(function () use (
                $custCode, $custName, $soNumber, $prodCode, $prodName, $orderedQty, $outstandingQty, $orderDate, $batch,
                &$insertedRows, &$skippedRows,
                &$insertedCustomersCount, &$insertedProductsCount, &$insertedSalesOrdersCount, &$insertedSalesOrderLinesCount
            ) {
                // Find or create Customer
                $customerName = !empty($custName) ? $custName : $custCode;
                $customer = Customer::firstOrCreate(
                    ['customer_code' => $custCode],
                    ['customer_name' => $customerName]
                );
                if ($customer->wasRecentlyCreated) {
                    $insertedCustomersCount++;
                }

                // Find or create Product
                $productName = !empty($prodName) ? $prodName : $prodCode;
                $product = Product::firstOrCreate(
                    ['product_code' => $prodCode],
                    ['product_name' => $productName]
                );
                if ($product->wasRecentlyCreated) {
                    $insertedProductsCount++;
                }

                // Find or create Sales Order
                $salesOrder = SalesOrder::firstOrCreate(
                    ['so_number' => $soNumber],
                    [
                        'customer_id' => $customer->id,
                        'order_date' => $orderDate,
                        'import_batch_id' => $batch->id,
                    ]
                );
                if ($salesOrder->wasRecentlyCreated) {
                    $insertedSalesOrdersCount++;
                }

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
                    $insertedSalesOrderLinesCount++;
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
                        $insertedSalesOrderLinesCount++;
                    } else {
                        // Scenario B: Shipments have been allocated -> Skip updates to preserve local ledger integrity
                        $skippedRows++;
                        Log::warning("Skipped updating sales order line (SO: {$soNumber}, Prod: {$prodCode}) during import: already has active allocations (Allocated: {$orderLine->allocated_qty} pcs).");
                    }
                }
            });
        }

        // 3. Update batch status
        $stats = [
            'success' => true,
            'parser_mode' => $parserMode,
            'inserted_customers' => $insertedCustomersCount,
            'inserted_products' => $insertedProductsCount,
            'inserted_so_headers' => $insertedSalesOrdersCount,
            'inserted_so_lines' => $insertedSalesOrderLinesCount,
            'skipped_records' => $skippedRows,
            'text_summary' => "Successfully parsed. Mode: {$parserMode}. Detected Customer Header Row: " . ($headerRowIndex + 1) . ". Detected Detail Header Row: " . (($parserMode === 'Grouped Customer ERP Mode') ? ($detailHeaderRowIndex + 1) : 'N/A') . ". Customer Groups: {$totalCustomerGroups}. Detail Rows: {$totalRows}. Processed: {$totalRows}, Inserted/Updated: {$insertedRows}, Skipped Active Allocations: {$skippedRows}."
        ];

        $batch->update([
            'total_rows' => $totalRows,
            'inserted_rows' => $insertedRows,
            'skipped_rows' => $skippedRows,
            'notes' => json_encode($stats),
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
