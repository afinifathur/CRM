<?php

namespace App\Services\Allocation;

use App\Models\ShipmentLine;

class AllocationService
{
    /**
     * Run the automatic allocation process for all unallocated shipment lines.
     *
     * @return array Summary of processed lines and results
     */
    public function runAutomaticAllocation(): array
    {
        $unallocatedLines = ShipmentLine::where('allocation_status', 'unallocated')->get();
        $processedCount = 0;
        $ambiguousCount = 0;

        foreach ($unallocatedLines as $line) {
            $result = $this->allocateLine($line);
            
            if ($result === 'ambiguous') {
                $ambiguousCount++;
            } else {
                $processedCount++;
            }
        }

        return [
            'total_processed' => $processedCount,
            'marked_ambiguous' => $ambiguousCount,
        ];
    }

    /**
     * Allocate a single shipment line based on the priority matrix rules.
     *
     * @param ShipmentLine $line
     * @return string State outcome ('allocated', 'partially_allocated', 'ambiguous', 'unallocated')
     */
    public function allocateLine(ShipmentLine $line): string
    {
        // TODO: Implement actual execution matching logic:
        // Priority 1: Explicit Reference (if sales_order_line_id is given)
        // Priority 2: Exact quantity match
        // Priority 3: Single candidate match
        // Priority 4: FIFO oldest fallback
        // Priority 5: Ambiguity hold (mark shipment line as ambiguous)
        
        return 'unallocated';
    }
}
