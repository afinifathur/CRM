<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Services\Import\StockSnapshotImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StockSnapshotImportController extends Controller
{
    protected StockSnapshotImportService $importService;

    public function __construct(StockSnapshotImportService $importService)
    {
        $this->importService = $importService;
    }

    public function index()
    {
        // Fetch last 5 stock snapshot import batches to display history/summary on screen
        $batches = ImportBatch::where('import_type', 'stock_snapshot')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        return view('import.stock', compact('batches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'snapshot_date' => 'required|date|before_or_equal:today',
            'file' => 'required|file|mimes:xlsx,xls,csv,txt', // txt allowed for csv sometimes
        ]);

        $file = $request->file('file');
        $date = $request->input('snapshot_date');

        // 1. Create a tracking batch
        $batch = ImportBatch::create([
            'import_type' => 'stock_snapshot',
            'source_filename' => $file->getClientOriginalName(),
            'imported_at' => now(),
            'total_rows' => 0,
            'inserted_rows' => 0,
            'skipped_rows' => 0,
            'notes' => 'Initializing import run...',
        ]);

        try {
            // 2. Execute service parsing
            $result = $this->importService->import($batch, $file, $date);

            return redirect()
                ->route('import.stock.index')
                ->with('success', 'Excel file successfully parsed and processed!')
                ->with('latest_batch', $result);

        } catch (\Throwable $e) {
            Log::error('Stock Import Failed: ' . $e->getMessage(), [
                'exception' => $e,
                'batch_id' => $batch->id
            ]);

            $batch->update([
                'notes' => 'Failed: ' . substr($e->getMessage(), 0, 250)
            ]);

            return redirect()
                ->route('import.stock.index')
                ->withErrors(['file' => 'Failed to process file: ' . $e->getMessage()]);
        }
    }
}
