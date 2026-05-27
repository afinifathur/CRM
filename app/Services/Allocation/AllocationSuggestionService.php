<?php

namespace App\Services\Allocation;

use App\Models\SalesOrderLine;
use App\Models\ShipmentLine;

class AllocationSuggestionService
{
    /**
     * Analyze outstanding order candidates for a given ShipmentLine.
     *
     * @param ShipmentLine $line
     * @return array|null Recommendation results and candidate array
     */
    public function getSuggestionForLine(ShipmentLine $line): ?array
    {
        $shipment = $line->shipment;
        if (!$shipment) {
            return null;
        }

        $customerId = $shipment->customer_id;
        $productId = $line->product_id;

        // Query outstanding SalesOrderLines for this customer and product ordered by date (FIFO baseline)
        $candidates = SalesOrderLine::join('sales_orders', 'sales_order_lines.sales_order_id', '=', 'sales_orders.id')
            ->where('sales_orders.customer_id', $customerId)
            ->where('sales_order_lines.product_id', $productId)
            ->where('sales_order_lines.outstanding_qty', '>', 0)
            ->select('sales_order_lines.*')
            ->orderBy('sales_orders.order_date', 'asc')
            ->orderBy('sales_order_lines.id', 'asc')
            ->get();

        if ($candidates->isEmpty()) {
            return [
                'status' => 'conflict',
                'suggested_line' => null,
                'confidence' => 'NONE',
                'priority' => 5,
                'message' => 'No outstanding outstanding PO items found for this customer and product.',
                'candidates' => [],
            ];
        }

        // 1. Explicit SO reference match (Priority 1)
        if ($line->sales_order_line_id) {
            $explicitLine = $candidates->firstWhere('id', $line->sales_order_line_id);
            if ($explicitLine) {
                return [
                    'status' => 'suggested',
                    'suggested_line' => $explicitLine,
                    'confidence' => 'HIGH',
                    'priority' => 1,
                    'message' => 'Matched dynamically via explicit order item reference code.',
                    'candidates' => $candidates,
                ];
            }
        }

        // 2. Exact quantity match (Priority 2)
        foreach ($candidates as $candidate) {
            if (bccomp($candidate->outstanding_qty, $line->shipped_qty, 4) === 0) {
                return [
                    'status' => 'suggested',
                    'suggested_line' => $candidate,
                    'confidence' => 'HIGH',
                    'priority' => 2,
                    'message' => 'Matched dynamically via exact outstanding quantity match.',
                    'candidates' => $candidates,
                ];
            }
        }

        // 3. Single candidate exists (Priority 3)
        if ($candidates->count() === 1) {
            return [
                'status' => 'suggested',
                'suggested_line' => $candidates->first(),
                'confidence' => 'HIGH',
                'priority' => 3,
                'message' => 'Matched as the single outstanding sales order candidate.',
                'candidates' => $candidates,
            ];
        }

        // 4. FIFO fallback by oldest order_date (Priority 4)
        $oldestCandidate = $candidates->first();

        // If the oldest candidate's outstanding_qty is larger than or equal to shipped_qty, we suggest it with MEDIUM confidence.
        // Otherwise, it requires a partial split, which makes it LOW confidence and highlights manual review.
        $confidence = bccomp($oldestCandidate->outstanding_qty, $line->shipped_qty, 4) >= 0 ? 'MEDIUM' : 'LOW';

        return [
            'status' => 'suggested',
            'suggested_line' => $oldestCandidate,
            'confidence' => $confidence,
            'priority' => 4,
            'message' => $confidence === 'MEDIUM'
                ? 'Suggested via FIFO logic (oldest outstanding order).'
                : 'Multiple candidates detected. Oldest order has insufficient balance (requires partial allocation split).',
            'candidates' => $candidates,
        ];
    }
}
