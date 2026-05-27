<?php

namespace App\Services\Import;

use App\Models\Customer;
use App\Models\ImportBatch;
use App\Models\Product;
use App\Models\SalesOrderLine;
use App\Models\Shipment;
use App\Models\ShipmentLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ShipmentImportService
{
    /**
     * Parse semi-structured shipment reports using carry-forward product mapping.
     *
     * @param ImportBatch $batch
     * @param \Illuminate\Http\UploadedFile $file
     * @return array Ingestion stats and batch instance
     * @throws \Exception
     */
    public function import(ImportBatch $batch, $file): array
    {
        $sheets = Excel::toArray([], $file);

        if (empty($sheets) || empty($sheets[0])) {
            throw new \Exception("The spreadsheet is empty or has no readable sheets.");
        }

        $rows = $sheets[0];

        $currentProduct = null;
        $totalRows = 0;
        $insertedRows = 0;
        $skippedRows = 0;
        $candidatesCount = 0;

        // Default base column map fallbacks
        $columnMap = [
            'customer_code' => 0,
            'customer_name' => 1,
            'sj_number' => 2,
            'shipped_qty' => 3,
            'shipment_date' => 4,
        ];

        foreach ($rows as $index => $row) {
            $cleaned = array_map(function ($val) {
                return trim((string)$val);
            }, $row);

            // Filter out completely empty rows
            $nonEmptyCells = array_filter($cleaned, function ($val) {
                return $val !== '';
            });

            if (empty($nonEmptyCells)) {
                continue;
            }

            // 1. Detect if this is a detail column header row (e.g. "Customer", "SJ No", "Qty")
            $isHeader = false;
            foreach ($cleaned as $cellIndex => $cellVal) {
                $valLower = strtolower($cellVal);
                if (str_contains($valLower, 'customer') || str_contains($valLower, 'pelanggan') || $valLower === 'cust') {
                    $columnMap['customer_code'] = $cellIndex;
                    $isHeader = true;
                } elseif (str_contains($valLower, 'customer name') || str_contains($valLower, 'nama customer')) {
                    $columnMap['customer_name'] = $cellIndex;
                    $isHeader = true;
                } elseif (str_contains($valLower, 'sj') || str_contains($valLower, 'surat jalan') || str_contains($valLower, 'no. sj') || $valLower === 'sj_number') {
                    $columnMap['sj_number'] = $cellIndex;
                    $isHeader = true;
                } elseif (str_contains($valLower, 'qty') || str_contains($valLower, 'shipped') || str_contains($valLower, 'jumlah') || $valLower === 'shipped_qty') {
                    $columnMap['shipped_qty'] = $cellIndex;
                    $isHeader = true;
                } elseif (str_contains($valLower, 'tanggal') || str_contains($valLower, 'date')) {
                    $columnMap['shipment_date'] = $cellIndex;
                    $isHeader = true;
                }
            }

            if ($isHeader) {
                continue; // Skip the column headers from data insertions
            }

            // 2. Identify if this is a Product Header row:
            // Product headers typically contain only a single populated cell in the first column
            $firstCell = $cleaned[0] ?? '';
            $custCell = $cleaned[$columnMap['customer_code']] ?? '';
            $sjCell = $cleaned[$columnMap['sj_number']] ?? '';
            $qtyCell = $cleaned[$columnMap['shipped_qty']] ?? '';

            if (empty($custCell) && empty($sjCell) && !empty($firstCell) && count($nonEmptyCells) === 1) {
                // Carry-forward Product Section Found!
                $productCode = $firstCell;
                $productName = $firstCell;

                // Handle pipe dividers in exports e.g., "P-102 | Flange SS304"
                if (str_contains($firstCell, '|')) {
                    $parts = explode('|', $firstCell);
                    $productCode = trim($parts[0]);
                    $productName = trim($parts[1]);
                }

                if ($this->isIgnoredKeyword($productCode)) {
                    continue;
                }

                $currentProduct = Product::firstOrCreate(
                    ['product_code' => $productCode],
                    ['product_name' => $productName]
                );
                continue;
            }

            // 3. Process Detail Row (Customer, SJ Number, Qty) and inherit $currentProduct
            if (!empty($custCell) && !empty($sjCell) && !empty($qtyCell) && $currentProduct) {
                
                // Find or create Customer
                $custName = $cleaned[$columnMap['customer_name']] ?? $custCell;
                if (empty($custName)) {
                    $custName = $custCell;
                }

                $customer = Customer::firstOrCreate(
                    ['customer_code' => $custCell],
                    ['customer_name' => $custName]
                );

                $shippedQty = (float) str_replace([',', ' '], '', $qtyCell);
                $dateStr = $cleaned[$columnMap['shipment_date']] ?? null;
                $shipmentDate = $this->parseDate($dateStr);

                $totalRows++;

                // Transactional insertion and validation
                DB::transaction(function () use (
                    $sjCell,
                    $shipmentDate,
                    $customer,
                    $currentProduct,
                    $shippedQty,
                    $batch,
                    &$insertedRows,
                    &$skippedRows,
                    &$candidatesCount
                ) {
                    // Find or create Shipment Header
                    $shipment = Shipment::firstOrCreate(
                        ['sj_number' => $sjCell],
                        [
                            'shipment_date' => $shipmentDate,
                            'customer_id' => $customer->id,
                            'import_batch_id' => $batch->id,
                        ]
                    );

                    // Check for duplicate ShipmentLine inside this Shipment
                    $exists = ShipmentLine::where('shipment_id', $shipment->id)
                        ->where('product_id', $currentProduct->id)
                        ->exists();

                    if ($exists) {
                        $skippedRows++;
                        Log::info("Skipped duplicate shipment line: SJ '{$sjCell}' already contains product '{$currentProduct->product_code}'.");
                    } else {
                        // Create unallocated Shipment Line
                        ShipmentLine::create([
                            'shipment_id' => $shipment->id,
                            'product_id' => $currentProduct->id,
                            'shipped_qty' => $shippedQty,
                            'sales_order_line_id' => null, // waiting for allocation match
                            'allocation_status' => 'unallocated',
                        ]);
                        $insertedRows++;

                        // Detect real-time allocation candidates count (Outstanding POs with positive remaining balance)
                        $hasCandidate = SalesOrderLine::join('sales_orders', 'sales_order_lines.sales_order_id', '=', 'sales_orders.id')
                            ->where('sales_orders.customer_id', $customer->id)
                            ->where('sales_order_lines.product_id', $currentProduct->id)
                            ->where('sales_order_lines.outstanding_qty', '>', 0)
                            ->exists();

                        if ($hasCandidate) {
                            $candidatesCount++;
                        }
                    }
                });
            }
        }

        // Update import batch status
        $batch->update([
            'total_rows' => $totalRows,
            'inserted_rows' => $insertedRows,
            'skipped_rows' => $skippedRows,
            'notes' => "Successfully parsed. Processed: {$totalRows}, Inserted Lines: {$insertedRows}, Skipped Duplicates: {$skippedRows}. Outstanding Candidates Detected: {$candidatesCount}.",
        ]);

        return [
            'batch' => $batch,
            'total_rows' => $totalRows,
            'inserted_rows' => $insertedRows,
            'skipped_rows' => $skippedRows,
            'candidates_count' => $candidatesCount,
        ];
    }

    private function isIgnoredKeyword(string $code): bool
    {
        $codeLower = strtolower($code);
        $ignoreKeywords = ['total', 'subtotal', 'grand total', 'ringkasan', 'summary', 'report', 'balance', 'page', 'halaman'];

        foreach ($ignoreKeywords as $keyword) {
            if (str_contains($codeLower, $keyword)) {
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

        if (is_numeric($val) && (int)$val > 10000 && (int)$val < 100000) {
            try {
                return Carbon::instance(Date::excelToDateTimeObject((int)$val))->toDateString();
            } catch (\Throwable $e) {
                // Fallback to normal parsing
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
