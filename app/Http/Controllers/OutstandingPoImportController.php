<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Services\Import\OutstandingPoImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OutstandingPoImportController extends Controller
{
    protected OutstandingPoImportService $importService;

    public function __construct(OutstandingPoImportService $importService)
    {
        $this->importService = $importService;
    }

    public function index()
    {
        // Fetch last 5 outstanding PO import batches to display history/summary on screen
        $batches = ImportBatch::where('import_type', 'outstanding_po')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        return view('import.po', compact('batches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt',
        ]);

        $file = $request->file('file');

        // Create the tracking batch
        $batch = ImportBatch::create([
            'import_type' => 'outstanding_po',
            'source_filename' => $file->getClientOriginalName(),
            'imported_at' => now(),
            'total_rows' => 0,
            'inserted_rows' => 0,
            'skipped_rows' => 0,
            'notes' => 'Initializing outstanding PO import run...',
        ]);

        try {
            // Execute service parsing
            $result = $this->importService->import($batch, $file);

            return redirect()
                ->route('import.po.index')
                ->with('success', 'Outstanding PO report successfully parsed and processed!')
                ->with('latest_batch', $result);

        } catch (\Throwable $e) {
            Log::error('Outstanding PO Import Failed: ' . $e->getMessage(), [
                'exception' => $e,
                'batch_id' => $batch->id
            ]);

            $batch->update([
                'notes' => 'Failed: ' . substr($e->getMessage(), 0, 250)
            ]);

            return redirect()
                ->route('import.po.index')
                ->withErrors(['file' => 'Failed to process file: ' . $e->getMessage()]);
        }
    }
}
