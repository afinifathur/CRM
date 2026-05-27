<?php

namespace App\Services\Allocation;

use App\Models\Allocation;
use App\Models\SalesOrderLine;
use App\Models\ShipmentLine;
use Illuminate\Support\Facades\DB;

class AllocationApprovalService
{
    /**
     * Approve manual or suggested shipment line allocation.
     *
     * @param ShipmentLine $line
     * @param int $salesOrderLineId
     * @return Allocation
     * @throws \Exception
     */
    public function approve(ShipmentLine $line, int $salesOrderLineId): Allocation
    {
        return DB::transaction(function () use ($line, $salesOrderLineId) {
            // Idempotency: if this exact allocation already exists, return it safely
            $existing = Allocation::where('shipment_line_id', $line->id)
                ->where('sales_order_line_id', $salesOrderLineId)
                ->first();

            if ($existing) {
                return $existing;
            }

            $soLine = SalesOrderLine::findOrFail($salesOrderLineId);

            // Shipped quantity is the default allocation amount
            $allocatedQty = $line->shipped_qty;

            // Cap the allocation at the remaining outstanding balance to prevent negative balances
            if (bccomp($allocatedQty, $soLine->outstanding_qty, 4) > 0) {
                $allocatedQty = $soLine->outstanding_qty;
            }

            // Create Allocation.
            // Model boot listeners automatically increment/decrement quantities and update SO status.
            $allocation = Allocation::create([
                'shipment_line_id' => $line->id,
                'sales_order_line_id' => $soLine->id,
                'allocated_qty' => $allocatedQty,
                'allocation_method' => 'manual',
                'notes' => 'Allocated via manual operational reconciliation review queue.',
            ]);

            // Update ShipmentLine allocation state
            $line->update([
                'allocation_status' => 'allocated',
                'sales_order_line_id' => $soLine->id, // Audit trail linkage
            ]);

            return $allocation;
        });
    }

    /**
     * Reset/Delete shipment line allocations, returning items to the unallocated queue.
     *
     * @param ShipmentLine $line
     * @return void
     * @throws \Exception
     */
    public function reset(ShipmentLine $line): void
    {
        DB::transaction(function () use ($line) {
            // Retrieve associated allocations
            $allocations = Allocation::where('shipment_line_id', $line->id)->get();

            // Deleting allocations automatically restores SO line balance quantities via model deletion listeners!
            foreach ($allocations as $allocation) {
                $allocation->delete();
            }

            // Put shipment line back into unallocated status
            $line->update([
                'allocation_status' => 'unallocated',
                'sales_order_line_id' => null,
            ]);
        });
    }
}
