<?php

namespace App\Services\Import;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrderLine;
use App\Models\ShipmentLine;
use App\Models\StockSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportPreviewService
{
    /**
     * Parse the uploaded file, extract headers and raw data, run the warning engine,
     * and calculate parser confidence levels.
     *
     * @param string $type
     * @param string $filePath
     * @param string|null $snapshotDate
     * @return array
     * @throws \Exception
     */
    public function generatePreview(string $type, string $filePath, ?string $snapshotDate = null): array
    {
        $sheets = Excel::toArray([], $filePath);

        if (empty($sheets) || empty($sheets[0])) {
            throw new \Exception("The spreadsheet is empty or has no readable sheets.");
        }

        $rows = $sheets[0];

        // Capture raw sample rows (first 5 non-empty rows for audit)
        $rawSamples = [];
        $sampleCount = 0;
        foreach ($rows as $row) {
            $nonEmpty = array_filter($row, fn($v) => trim((string)$v) !== '');
            if (!empty($nonEmpty)) {
                $rawSamples[] = $row;
                $sampleCount++;
                if ($sampleCount >= 5) {
                    break;
                }
            }
        }

        // Cache existing codes for super fast lookup
        $existingProducts = array_flip(Product::pluck('product_code')->toArray());
        $existingCustomers = array_flip(Customer::pluck('customer_code')->toArray());

        $parsedRows = [];
        $rawHeaders = [];
        $totalRowsCount = 0;
        $duplicateRowsCount = 0;
        $warningRowsCount = 0;
        $validRowsCount = 0;

        $parserMode = 'Flat Table Mode';
        $detectedHeaderRow = -1;
        $totalCustomerGroups = 0;
        $totalDetailRows = 0;

        // Keep track of unique new entities in this file
        $newProductsInFile = [];
        $newCustomersInFile = [];

        // Import readiness indicator
        $overallStatus = 'READY';

        // Warning detail list
        $warningDetails = [];

        if ($type === 'stock') {
            // 1. Discover header row dynamically
            $headerRowIndex = -1;
            $columnMap = ['product_code' => -1, 'product_name' => -1, 'stock_pcs' => -1, 'stock_kg' => -1];

            foreach ($rows as $index => $row) {
                $cleanedRow = array_map(fn($v) => strtolower(trim((string)$v)), $row);
                foreach ($cleanedRow as $cellIndex => $cellVal) {
                    if (empty($cellVal)) continue;

                    if ($this->matchPattern($cellVal, ['product code', 'kode produk', 'item code', 'kode barang', 'part number', 'product_code'])) {
                        $columnMap['product_code'] = $cellIndex;
                    } elseif ($this->matchPattern($cellVal, ['product name', 'nama produk', 'item name', 'nama barang', 'deskripsi', 'description', 'product_name'])) {
                        $columnMap['product_name'] = $cellIndex;
                    } elseif ($this->matchPattern($cellVal, ['pcs', 'piece', 'stock pcs', 'balance pcs', 'saldo pcs', 'qty', 'pcs balance'])) {
                        $columnMap['stock_pcs'] = $cellIndex;
                    } elseif ($this->matchPattern($cellVal, ['kg', 'kilogram', 'stock kg', 'balance kg', 'saldo kg', 'weight', 'kg balance'])) {
                        $columnMap['stock_kg'] = $cellIndex;
                    }
                }
                if ($columnMap['product_code'] !== -1 && ($columnMap['stock_pcs'] !== -1 || $columnMap['stock_kg'] !== -1)) {
                    $headerRowIndex = $index;
                    $rawHeaders = $row;
                    break;
                }
            }

            if ($headerRowIndex === -1) {
                throw new \Exception("Could not detect stock report headers automatically.");
            }

            // Pre-load existing daily snapshots to check duplicates instantly
            $existingSnapshots = [];
            if ($snapshotDate) {
                $existingSnapshots = array_flip(
                    StockSnapshot::where('snapshot_date', $snapshotDate)
                        ->join('products', 'stock_snapshots.product_id', '=', 'products.id')
                        ->pluck('products.product_code')
                        ->toArray()
                );
            }

            // Parse rows
            for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $code = $this->getMappedVal($row, $columnMap['product_code']);
                $name = $this->getMappedVal($row, $columnMap['product_name']);
                $pcsStr = $this->getMappedVal($row, $columnMap['stock_pcs']);
                $kgStr = $this->getMappedVal($row, $columnMap['stock_kg']);

                if (empty($code) || $this->isIgnoredRow($code, $name)) {
                    continue;
                }

                $pcs = $pcsStr !== null ? (int) str_replace([',', ' '], '', $pcsStr) : 0;
                $kg = $kgStr !== null ? (float) str_replace([',', ' '], '', $kgStr) : 0.0000;
                $kg = round($kg, 4);

                $totalRowsCount++;
                $rowWarnings = [];
                $isDuplicate = false;
                $isBlocked = false;
                $isReview = false;

                // Warning checks
                if (!isset($existingProducts[$code])) {
                    $rowWarnings[] = "New Product: '" . ($name ?? $code) . "' will be registered.";
                    $newProductsInFile[$name ?? $code] = true;
                }

                if (isset($existingSnapshots[$code])) {
                    $rowWarnings[] = "Duplicate Record: Daily stock snapshot already exists in DB.";
                    $isDuplicate = true;
                    $duplicateRowsCount++;
                    $isReview = true;
                }

                if ($pcs === 0 && $kg === 0.0) {
                    $rowWarnings[] = "Empty Quantity: Both PCS and KG balances are zero.";
                    $isReview = true;
                }

                if ($isBlocked) {
                    $overallStatus = 'BLOCKED';
                } elseif ($isReview) {
                    if ($overallStatus !== 'BLOCKED') {
                        $overallStatus = 'REVIEW';
                    }
                }

                if ($isBlocked || $isReview) {
                    // Parser failures or duplicate records reduce Valid Rows
                } else {
                    $validRowsCount++;
                }

                if ($isDuplicate) {
                    // duplicate rows count separately
                } elseif (!empty($rowWarnings)) {
                    $warningRowsCount++;
                }

                foreach ($rowWarnings as $wMsg) {
                    $warningDetails[] = [
                        'row' => $i + 1,
                        'message' => $wMsg
                    ];
                }

                $parsedRows[] = [
                    'row_index' => $i + 1,
                    'product_code' => $code,
                    'product_name' => $name ?? $code,
                    'stock_pcs' => $pcs,
                    'stock_kg' => $kg,
                    '_warnings' => $rowWarnings,
                    '_duplicate' => $isDuplicate,
                    '_confidence' => $isDuplicate ? 'LOW' : (!empty($rowWarnings) ? 'MEDIUM' : 'HIGH')
                ];
            }

        } elseif ($type === 'po') {
            // 2. Discover PO headers
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
                $cleanedRow = array_map(fn($v) => strtolower(trim((string)$v)), $row);

                // Try to match Flat Table Mode headers first
                $flatCols = [
                    'customer_code' => -1, 'customer_name' => -1, 'so_number' => -1,
                    'product_code' => -1, 'product_name' => -1, 'ordered_qty' => -1,
                    'outstanding_qty' => -1, 'order_date' => -1
                ];

                foreach ($cleanedRow as $cellIndex => $cellVal) {
                    if (empty($cellVal)) continue;

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

            $rawHeaders = ($parserMode === 'Grouped Customer ERP Mode') ? $rows[$detailHeaderRowIndex] : $rows[$headerRowIndex];
            $detectedHeaderRow = $headerRowIndex + 1;

            // Pre-load existing active lines with allocations to check duplicates instantly
            $activeAllocatedPoLines = array_flip(
                SalesOrderLine::join('sales_orders', 'sales_order_lines.sales_order_id', '=', 'sales_orders.id')
                    ->join('products', 'sales_order_lines.product_id', '=', 'products.id')
                    ->where('sales_order_lines.allocated_qty', '>', 0)
                    ->select('sales_orders.so_number', 'products.product_code')
                    ->get()
                    ->map(fn($line) => $line->so_number . '|' . $line->product_code)
                    ->toArray()
            );

            $currentCustomerCode = null;
            $currentCustomerName = null;
            $totalCustomerGroups = 0;
            $totalDetailRows = 0;

            $startRowIndex = ($parserMode === 'Grouped Customer ERP Mode') ? $detailHeaderRowIndex + 1 : $headerRowIndex + 1;

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

                // Skip ignored rows (headers, footers, totals)
                if ($this->isIgnoredRow($soNumber ?? '', $custCode ?? '')) {
                    continue;
                }

                // If completely empty on all key fields, skip
                if (empty($soNumber) && empty($custCode) && empty($prodCode)) {
                    continue;
                }

                $orderedQty = $orderedQtyStr !== null ? (float) str_replace([',', ' '], '', $orderedQtyStr) : 0.0000;
                $outstandingQty = $outstandingQtyStr !== null ? (float) str_replace([',', ' '], '', $outstandingQtyStr) : 0.0000;

                $totalRowsCount++;
                $totalDetailRows++;
                $rowWarnings = [];
                $isDuplicate = false;
                $isBlocked = false;
                $isReview = false;

                // Check missing required fields
                if (empty($soNumber)) {
                    $rowWarnings[] = "Missing Required Field: SO Number is blank.";
                    $isBlocked = true;
                }
                if (empty($custCode)) {
                    $rowWarnings[] = "Missing Required Field: Customer Code is blank.";
                    $isBlocked = true;
                }
                if (empty($prodCode)) {
                    $rowWarnings[] = "Missing Required Field: Product Name/Code is blank.";
                    $isBlocked = true;
                }

                // Warnings check
                if (!empty($prodCode) && !isset($existingProducts[$prodCode])) {
                    $rowWarnings[] = "New Product: '" . ($prodName ?? $prodCode) . "' will be registered.";
                    $newProductsInFile[$prodName ?? $prodCode] = true;
                }

                if (!empty($custCode) && !isset($existingCustomers[$custCode])) {
                    $rowWarnings[] = "New Customer: '{$custCode}' will be registered.";
                    $newCustomersInFile[$custCode] = true;
                }

                if (!empty($soNumber) && !empty($prodCode)) {
                    $key = $soNumber . '|' . $prodCode;
                    if (isset($activeAllocatedPoLines[$key])) {
                        $rowWarnings[] = "Duplicate Record: Already has active allocations locally. Ingestion will skip this row to protect allocations ledger.";
                        $isDuplicate = true;
                        $duplicateRowsCount++;
                        $isReview = true;
                    }
                }

                if ($outstandingQty <= 0) {
                    $rowWarnings[] = "Empty Quantity: Outstanding quantity is zero or negative.";
                    $isReview = true;
                }

                if ($isBlocked) {
                    $overallStatus = 'BLOCKED';
                } elseif ($isReview) {
                    if ($overallStatus !== 'BLOCKED') {
                        $overallStatus = 'REVIEW';
                    }
                }

                if ($isBlocked || $isReview) {
                    // Parser failures, duplicates, or empty quantities reduce Valid Rows
                } else {
                    $validRowsCount++;
                }

                if ($isDuplicate) {
                    // Duplicate count separately
                } elseif (!empty($rowWarnings)) {
                    $warningRowsCount++;
                }

                foreach ($rowWarnings as $wMsg) {
                    $warningDetails[] = [
                        'row' => $i + 1,
                        'message' => $wMsg
                    ];
                }

                $parsedRows[] = [
                    'row_index' => $i + 1,
                    'customer_code' => $custCode,
                    'customer_name' => $custName ?? $custCode,
                    'so_number' => $soNumber,
                    'product' => $prodCode,
                    'product_name' => $prodName ?? $prodCode,
                    'qty' => $orderedQty,
                    'outstanding' => $outstandingQty,
                    'order_date' => $this->parseDate($orderDateStr),
                    '_warnings' => $rowWarnings,
                    '_duplicate' => $isDuplicate,
                    '_confidence' => $isDuplicate ? 'LOW' : (!empty($rowWarnings) ? 'MEDIUM' : 'HIGH')
                ];
            }

        } elseif ($type === 'shipment') {
            // 3. Discover Shipment headers (Carry-forward parser)
            $columnMap = [
                'customer_code' => 0, 'customer_name' => 1, 'sj_number' => 2,
                'shipped_qty' => 3, 'shipment_date' => 4,
            ];

            // Cache standard headers for audit raw headers
            foreach ($rows as $index => $row) {
                $cleaned = array_map(fn($v) => trim((string)$v), $row);
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
                    $rawHeaders = $row;
                    break;
                }
            }

            if (empty($rawHeaders)) {
                $rawHeaders = ["Customer Code", "Customer Name", "SJ Number", "Shipped Qty", "Shipment Date"];
            }

            // Pre-load existing ShipmentLines to check duplicates instantly
            $existingShipmentLines = array_flip(
                ShipmentLine::join('shipments', 'shipment_lines.shipment_id', '=', 'shipments.id')
                    ->join('products', 'shipment_lines.product_id', '=', 'products.id')
                    ->select(DB::raw("CONCAT(shipments.sj_number, '|', products.product_code) as unique_key"))
                    ->pluck('unique_key')
                    ->toArray()
            );

            $currentProductCode = null;
            $currentProductName = null;
            $productSourceConfidence = 'HIGH'; // Carry-forward is HIGH

            foreach ($rows as $index => $row) {
                $cleaned = array_map(fn($v) => trim((string)$v), $row);
                $nonEmptyCells = array_filter($cleaned, fn($v) => $v !== '');

                if (empty($nonEmptyCells)) {
                    continue;
                }

                // Check if column header row
                $isHeader = false;
                foreach ($cleaned as $cellIndex => $cellVal) {
                    $valLower = strtolower($cellVal);
                    if (str_contains($valLower, 'customer') || str_contains($valLower, 'pelanggan') || $valLower === 'sj' || str_contains($valLower, 'surat jalan')) {
                        $isHeader = true;
                        break;
                    }
                }
                if ($isHeader) {
                    continue;
                }

                // Product Header Detection
                $firstCell = $cleaned[0] ?? '';
                $custCell = $cleaned[$columnMap['customer_code']] ?? '';
                $sjCell = $cleaned[$columnMap['sj_number']] ?? '';
                $qtyCell = $cleaned[$columnMap['shipped_qty']] ?? '';

                if (empty($custCell) && empty($sjCell) && !empty($firstCell) && count($nonEmptyCells) === 1) {
                    // Carry-forward Product Section Found!
                    $productCode = $firstCell;
                    $productName = $firstCell;

                    if (str_contains($firstCell, '|')) {
                        $parts = explode('|', $firstCell);
                        $productCode = trim($parts[0]);
                        $productName = trim($parts[1]);
                    }

                    if ($this->isIgnoredRow($productCode, null)) {
                        continue;
                    }

                    $currentProductCode = $productCode;
                    $currentProductName = $productName;
                    $productSourceConfidence = 'HIGH';
                    continue;
                }

                // Detail Row processing
                if (!empty($custCell) && !empty($sjCell) && !empty($qtyCell)) {
                    $custName = $cleaned[$columnMap['customer_name']] ?? $custCell;
                    if (empty($custName)) {
                        $custName = $custCell;
                    }

                    $shippedQty = (float) str_replace([',', ' '], '', $qtyCell);
                    $dateStr = $cleaned[$columnMap['shipment_date']] ?? null;

                    $totalRowsCount++;
                    $rowWarnings = [];
                    $isDuplicate = false;
                    $isBlocked = false;
                    $isReview = false;
                    $confidence = $productSourceConfidence;

                    // If carry-forward product was not found yet
                    if (!$currentProductCode || $currentProductCode === "UNKNOWN") {
                        $rowWarnings[] = "Parser Ambiguity: Detail row processed without active Product Header carry-forward.";
                        $currentProductCode = "UNKNOWN";
                        $currentProductName = "UNKNOWN";
                        $confidence = 'LOW';
                        $isReview = true;
                    }

                    if ($currentProductCode !== "UNKNOWN" && !isset($existingProducts[$currentProductCode])) {
                        $rowWarnings[] = "New Product: '{$currentProductName}' will be registered.";
                        $newProductsInFile[$currentProductName] = true;
                        $confidence = 'MEDIUM';
                    }

                    if (!isset($existingCustomers[$custCell])) {
                        $rowWarnings[] = "New Customer: '{$custCell}' will be registered.";
                        $newCustomersInFile[$custCell] = true;
                        $confidence = 'MEDIUM';
                    }

                    // Duplicate check
                    $key = $sjCell . '|' . $currentProductCode;
                    if (isset($existingShipmentLines[$key])) {
                        $rowWarnings[] = "Duplicate Record: Shipment Line already registered in DB under SJ '{$sjCell}'.";
                        $isDuplicate = true;
                        $duplicateRowsCount++;
                        $confidence = 'LOW';
                        $isReview = true;
                    }

                    if ($shippedQty <= 0) {
                        $rowWarnings[] = "Empty Quantity: Shipped quantity is zero or empty.";
                        $confidence = 'LOW';
                        $isReview = true;
                    }

                    if ($isBlocked) {
                        $overallStatus = 'BLOCKED';
                    } elseif ($isReview) {
                        if ($overallStatus !== 'BLOCKED') {
                            $overallStatus = 'REVIEW';
                        }
                    }

                    if ($isBlocked || $isReview) {
                        // Parser failures or duplicate records reduce Valid Rows
                    } else {
                        $validRowsCount++;
                    }

                    if ($isDuplicate) {
                        // duplicate rows count separately
                    } elseif (!empty($rowWarnings)) {
                        $warningRowsCount++;
                    }

                    foreach ($rowWarnings as $wMsg) {
                        $warningDetails[] = [
                            'row' => $index + 1,
                            'message' => $wMsg
                        ];
                    }

                    $parsedRows[] = [
                        'row_index' => $index + 1,
                        'shipment_date' => $this->parseDate($dateStr),
                        'sj_number' => $sjCell,
                        'customer_code' => $custCell,
                        'customer_name' => $custName,
                        'product' => $currentProductCode,
                        'product_name' => $currentProductName,
                        'qty' => $shippedQty,
                        '_warnings' => $rowWarnings,
                        '_duplicate' => $isDuplicate,
                        '_confidence' => $confidence
                    ];
                }
            }
        }

        // Count unique products and customers in this file
        $uniqueProductsFound = 0;
        $uniqueCustomersFound = 0;
        $allProducts = [];
        $allCustomers = [];
        $allSoNumbers = [];

        foreach ($parsedRows as $pRow) {
            $pCode = $pRow['product'] ?? ($pRow['product_code'] ?? null);
            $cCode = $pRow['customer_code'] ?? null;
            $soNum = $pRow['so_number'] ?? null;
            if ($pCode) $allProducts[$pCode] = true;
            if ($cCode) $allCustomers[$cCode] = true;
            if ($soNum) $allSoNumbers[$soNum] = true;
        }

        // Shipment Confidence Counts
        $confidenceCounts = ['HIGH' => 0, 'MEDIUM' => 0, 'LOW' => 0];
        if ($type === 'shipment') {
            foreach ($parsedRows as $pRow) {
                $c = $pRow['_confidence'] ?? 'HIGH';
                if (isset($confidenceCounts[$c])) {
                    $confidenceCounts[$c]++;
                }
            }
        }

        // Categorize warnings and compute readiness metrics
        $blockingWarningCount = 0;
        $reviewWarningCount = 0;
        $informationalWarningCount = 0;
        $blockingRowsCount = 0;

        $first20BlockingWarnings = [];
        $first20ReviewWarnings = [];

        // Group warnings by row
        $warningsByRow = [];
        foreach ($warningDetails as $w) {
            $warningsByRow[$w['row']][] = $w['message'];
        }

        foreach ($warningsByRow as $rowIndex => $messages) {
            $rowHasBlocking = false;
            foreach ($messages as $msg) {
                $category = $this->classifyWarning($msg);
                if ($category === 'BLOCKING') {
                    $blockingWarningCount++;
                    $rowHasBlocking = true;
                    if (count($first20BlockingWarnings) < 20) {
                        $first20BlockingWarnings[] = [
                            'row' => $rowIndex,
                            'message' => $msg
                        ];
                    }
                } elseif ($category === 'REVIEW') {
                    $reviewWarningCount++;
                    if (count($first20ReviewWarnings) < 20) {
                        $first20ReviewWarnings[] = [
                            'row' => $rowIndex,
                            'message' => $msg
                        ];
                    }
                } elseif ($category === 'INFORMATIONAL') {
                    $informationalWarningCount++;
                }
            }
            if ($rowHasBlocking) {
                $blockingRowsCount++;
            }
        }

        // Determine overall status based on warning metrics
        if ($blockingWarningCount > 0) {
            $overallStatus = 'BLOCKED';
        } elseif ($reviewWarningCount > 0) {
            $overallStatus = 'REVIEW';
        } else {
            $overallStatus = 'READY';
        }

        $stats = [
            'total_rows' => $totalRowsCount,
            'valid_rows' => $validRowsCount,
            'warning_rows' => $warningRowsCount,
            'duplicate_rows' => $duplicateRowsCount,
            'products_found' => count($allProducts),
            'customers_found' => count($allCustomers),
            'new_products_count' => count($newProductsInFile),
            'new_customers_count' => count($newCustomersInFile),
            'shipment_confidence' => $confidenceCounts,
            'warning_details' => $warningDetails, // Detailed drawer data
            'unique_customers' => count($allCustomers),
            'unique_products' => count($allProducts),
            'unique_so_numbers' => count($allSoNumbers),
            'import_readiness' => $overallStatus,
            'blocking_warning_count' => $blockingWarningCount,
            'review_warning_count' => $reviewWarningCount,
            'informational_warning_count' => $informationalWarningCount,
            'blocking_rows_count' => $blockingRowsCount,
            'first_20_blocking_warnings' => $first20BlockingWarnings,
            'first_20_review_warnings' => $first20ReviewWarnings,
        ];

        if ($type === 'po') {
            $stats['parser_mode'] = $parserMode;
            $stats['detected_header_row'] = $detectedHeaderRow;
            $stats['detected_detail_header_row'] = ($parserMode === 'Grouped Customer ERP Mode') ? $detailHeaderRowIndex + 1 : null;
            $stats['total_customer_groups'] = $totalCustomerGroups;
            $stats['total_detail_rows'] = $totalDetailRows;
        }

        return [
            'rows' => $parsedRows,
            'stats' => $stats,
            'raw_headers' => $rawHeaders,
            'raw_samples' => $rawSamples
        ];
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

    private function isIgnoredRow(string $code, ?string $name): bool
    {
        $codeLower = strtolower($code);
        $nameLower = $name ? strtolower($name) : '';
        $ignoreKeywords = ['total', 'subtotal', 'grand total', 'ringkasan', 'summary', 'report', 'balance', 'page', 'halaman'];

        foreach ($ignoreKeywords as $keyword) {
            if (str_contains($codeLower, $keyword) || str_contains($nameLower, $keyword)) {
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

        $val = trim($val);

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

    private function classifyWarning(string $message): string
    {
        $messageLower = strtolower($message);

        // Check BLOCKING keywords
        if (str_contains($messageLower, 'missing required field') || 
            str_contains($messageLower, 'missing customer code') ||
            str_contains($messageLower, 'missing so number') ||
            str_contains($messageLower, 'missing product name') ||
            str_contains($messageLower, 'missing outstanding qty')) {
            return 'BLOCKING';
        }

        // Check INFORMATIONAL keywords
        if (str_contains($messageLower, 'new product') || 
            str_contains($messageLower, 'new customer')) {
            return 'INFORMATIONAL';
        }

        // Default to REVIEW
        return 'REVIEW';
    }
}
