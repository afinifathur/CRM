<?php

namespace App\Services\Import;

use App\Models\ImportBatch;
use App\Models\Product;
use App\Models\StockSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class StockSnapshotImportService
{
    /**
     * Parse the uploaded file, discover and normalize records, and insert them safely.
     *
     * @param ImportBatch $batch
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $date
     * @return ImportBatch
     * @throws \Exception
     */
    public function import(ImportBatch $batch, $file, string $date): ImportBatch
    {
        // Load the spreadsheet into array sheets
        $sheets = Excel::toArray([], $file);

        if (empty($sheets) || empty($sheets[0])) {
            throw new \Exception("The spreadsheet is empty or has no readable sheets.");
        }

        $rows = $sheets[0];

        // 1. Discover header row dynamically
        $headerRowIndex = -1;
        $columnMap = [
            'product_code' => -1,
            'product_name' => -1,
            'stock_pcs' => -1,
            'stock_kg' => -1,
        ];

        foreach ($rows as $index => $row) {
            $cleanedRow = array_map(function ($val) {
                return strtolower(trim((string)$val));
            }, $row);

            foreach ($cleanedRow as $cellIndex => $cellVal) {
                if (empty($cellVal)) {
                    continue;
                }

                // Check Product Code match
                if ($this->matchPattern($cellVal, ['product code', 'kode produk', 'item code', 'kode barang', 'part number', 'product_code'])) {
                    $columnMap['product_code'] = $cellIndex;
                }
                // Check Product Name match
                elseif ($this->matchPattern($cellVal, ['product name', 'nama produk', 'item name', 'nama barang', 'deskripsi', 'description', 'product_name'])) {
                    $columnMap['product_name'] = $cellIndex;
                }
                // Check Stock PCS match
                elseif ($this->matchPattern($cellVal, ['pcs', 'piece', 'stock pcs', 'balance pcs', 'saldo pcs', 'qty', 'pcs balance'])) {
                    $columnMap['stock_pcs'] = $cellIndex;
                }
                // Check Stock KG match
                elseif ($this->matchPattern($cellVal, ['kg', 'kilogram', 'stock kg', 'balance kg', 'saldo kg', 'weight', 'kg balance'])) {
                    $columnMap['stock_kg'] = $cellIndex;
                }
            }

            // We consider it the header row if we mapped at least Product Code and one quantity field
            if ($columnMap['product_code'] !== -1 && ($columnMap['stock_pcs'] !== -1 || $columnMap['stock_kg'] !== -1)) {
                $headerRowIndex = $index;
                break;
            }
        }

        if ($headerRowIndex === -1) {
            throw new \Exception("Could not detect stock report headers automatically. Please make sure the sheet contains columns like: 'Product Code', 'Product Name', 'Stock PCS' and 'Stock KG'.");
        }

        $totalRows = 0;
        $insertedRows = 0;
        $skippedRows = 0;

        // 2. Parse and upsert row by row starting from after header
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            $code = $this->getMappedVal($row, $columnMap['product_code']);
            $name = $this->getMappedVal($row, $columnMap['product_name']);
            $pcsStr = $this->getMappedVal($row, $columnMap['stock_pcs']);
            $kgStr = $this->getMappedVal($row, $columnMap['stock_kg']);

            // Skip empty rows, subtotals, footers, headers
            if (empty($code) || $this->isIgnoredRow($code, $name)) {
                continue;
            }

            // Parse numbers (handling formats with commas)
            $pcs = $pcsStr !== null ? (int) str_replace([',', ' '], '', $pcsStr) : 0;
            
            $kg = $kgStr !== null ? (float) str_replace([',', ' '], '', $kgStr) : 0.0000;
            // Format weight cleanly to 4 decimal places
            $kg = round($kg, 4);

            $totalRows++;

            // Use database transaction for atomic product/snapshot insert
            DB::transaction(function () use ($code, $name, $date, $pcs, $kg, $batch, &$insertedRows, &$skippedRows) {
                // Upsert product (firstOrCreate to avoid duplicates)
                $productName = !empty($name) ? $name : $code;
                $product = Product::firstOrCreate(
                    ['product_code' => $code],
                    ['product_name' => $productName]
                );

                // Check for duplicate daily snapshot for this product
                $exists = StockSnapshot::where('snapshot_date', $date)
                    ->where('product_id', $product->id)
                    ->exists();

                if ($exists) {
                    $skippedRows++;
                    Log::info("Skipped stock snapshot row: product '{$code}' already exists for date '{$date}'.");
                } else {
                    StockSnapshot::create([
                        'snapshot_date' => $date,
                        'product_id' => $product->id,
                        'stock_pcs' => $pcs,
                        'stock_kg' => $kg,
                        'import_batch_id' => $batch->id,
                    ]);
                    $insertedRows++;
                }
            });
        }

        // 3. Update batch status
        $batch->update([
            'total_rows' => $totalRows,
            'inserted_rows' => $insertedRows,
            'skipped_rows' => $skippedRows,
            'notes' => "Successfully parsed. Processed: {$totalRows}, Inserted: {$insertedRows}, Skipped Duplicates: {$skippedRows}.",
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

    private function isIgnoredRow(string $code, ?string $name): bool
    {
        $codeLower = strtolower($code);
        $nameLower = $name ? strtolower($name) : '';

        // Standard ERP report footer texts to exclude
        $ignoreKeywords = ['total', 'subtotal', 'grand total', 'ringkasan', 'summary', 'report', 'balance', 'page', 'halaman'];

        foreach ($ignoreKeywords as $keyword) {
            if (str_contains($codeLower, $keyword) || str_contains($nameLower, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
