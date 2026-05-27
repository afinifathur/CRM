<?php

namespace App\Services\Import;

use App\Models\ImportBatch;

class ImportService
{
    /**
     * Start a new import batch run.
     *
     * @param string $importType (stock_snapshot, outstanding_po, shipment)
     * @param string|null $filename
     * @return ImportBatch
     */
    public function createBatch(string $importType, ?string $filename = null): ImportBatch
    {
        return ImportBatch::create([
            'import_type' => $importType,
            'source_filename' => $filename,
            'imported_at' => now(),
            'total_rows' => 0,
            'inserted_rows' => 0,
            'skipped_rows' => 0,
            'notes' => 'Batch created, waiting for execution.',
        ]);
    }
}
