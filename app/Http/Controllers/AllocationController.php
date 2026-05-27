<?php

namespace App\Http\Controllers;

use App\Models\ShipmentLine;
use App\Services\Allocation\AllocationApprovalService;
use App\Services\Allocation\AllocationSuggestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AllocationController extends Controller
{
    protected AllocationSuggestionService $suggestionService;
    protected AllocationApprovalService $approvalService;

    public function __construct(
        AllocationSuggestionService $suggestionService,
        AllocationApprovalService $approvalService
    ) {
        $this->suggestionService = $suggestionService;
        $this->approvalService = $approvalService;
    }

    /**
     * Renders the operational manual review queue dashboard.
     */
    public function index()
    {
        // 1. Fetch pending reviews (unallocated/suggested)
        $pendingLines = ShipmentLine::with(['shipment.customer', 'product'])
            ->whereIn('allocation_status', ['unallocated', 'suggested'])
            ->orderBy('id', 'desc')
            ->get();

        // Dynamically compute candidates, priority rules, and confidence levels in real time
        foreach ($pendingLines as $line) {
            $line->suggestion = $this->suggestionService->getSuggestionForLine($line);
        }

        // 2. Fetch recently allocated items to support instant rollback/audit
        $allocatedLines = ShipmentLine::with(['shipment.customer', 'product', 'salesOrderLine.salesOrder'])
            ->where('allocation_status', 'allocated')
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();

        return view('allocations.review', compact('pendingLines', 'allocatedLines'));
    }

    /**
     * Approves an allocation proposal.
     */
    public function approve(Request $request, int $id)
    {
        $request->validate([
            'sales_order_line_id' => 'required|integer|exists:sales_order_lines,id',
        ]);

        $line = ShipmentLine::findOrFail($id);

        try {
            $this->approvalService->approve($line, $request->sales_order_line_id);

            return redirect()
                ->route('allocations.index')
                ->with('success', "Shipment Line (SJ: {$line->shipment->sj_number}) successfully allocated to Sales Order!");

        } catch (\Throwable $e) {
            Log::error("Manual Allocation Failed: " . $e->getMessage(), [
                'line_id' => $id,
                'so_line_id' => $request->sales_order_line_id,
                'exception' => $e
            ]);

            return redirect()
                ->route('allocations.index')
                ->withErrors(['error' => 'Failed to approve allocation: ' . $e->getMessage()]);
        }
    }

    /**
     * Resets/Rolls back an existing allocation.
     */
    public function reset(int $id)
    {
        $line = ShipmentLine::findOrFail($id);

        try {
            $this->approvalService->reset($line);

            return redirect()
                ->route('allocations.index')
                ->with('success', "Allocation for SJ Line (SJ: {$line->shipment->sj_number}) rolled back safely. Balances restored.");

        } catch (\Throwable $e) {
            Log::error("Allocation Rollback Failed: " . $e->getMessage(), [
                'line_id' => $id,
                'exception' => $e
            ]);

            return redirect()
                ->route('allocations.index')
                ->withErrors(['error' => 'Failed to reset allocation: ' . $e->getMessage()]);
        }
    }
}
