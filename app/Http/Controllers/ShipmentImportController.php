<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Services\Import\ShipmentImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShipmentImportController extends Controller
{
    protected ShipmentImportService $importService;

    public function __construct(ShipmentImportService $importService)
    {
        $this->importService = $importService;
    }

    public function index()
    {
        // Fetch last 5 shipment import batches to display history/summary on screen
        $batches = ImportBatch::where('import_type', 'shipment')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        return view('import.shipment', compact('batches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt',
        ]);

        $file = $request->file('file');

        // Create the tracking batch
        $batch = ImportBatch::create([
            'import_type' => 'shipment',
            'source_filename' => $file->getClientOriginalName(),
            'imported_at' => now(),
            'total_rows' => 0,
            'inserted_rows' => 0,
            'skipped_rows' => 0,
            'notes' => 'Initializing shipment import run...',
        ]);

        try {
            // Execute service parsing
            $result = $this->importService->import($batch, $file);

            return redirect()
                ->route('import.shipment.index')
                ->with('success', 'Shipment report successfully parsed and processed!')
                ->with('latest_batch', $result);

        } catch (\Throwable $e) {
            Log::error('Shipment Import Failed: ' . $e->getMessage(), [
                'exception' => $e,
                'batch_id' => $batch->id
            ]);

            $batch->update([
                'notes' => 'Failed: ' . substr($e->getMessage(), 0, 250)
            ]);

            return redirect()
                ->route('import.shipment.index')
                ->withErrors(['file' => 'Failed to process file: ' . $e->getMessage()]);
        }
    }
}
