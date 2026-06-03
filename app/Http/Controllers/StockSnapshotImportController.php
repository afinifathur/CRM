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
            'file' => 'required|file|mimes:xlsx,xls,csv,txt',
        ]);

        $file = $request->file('file');
        $date = $request->input('snapshot_date');

        try {
            // Save file temporarily in local storage
            $filename = uniqid() . '_' . $file->getClientOriginalName();
            $tempDir = storage_path('app/temp_imports');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            $file->move($tempDir, $filename);
            $tempFilePath = $tempDir . '/' . $filename;

            // Execute dry-run parsing
            $previewService = app(\App\Services\Import\ImportPreviewService::class);
            $previewData = $previewService->generatePreview('stock', $tempFilePath, $date);

            // Create ImportPreviewBatch
            $previewBatch = \App\Models\ImportPreviewBatch::create([
                'type' => 'stock',
                'user_session' => session()->getId(),
                'status' => 'preview',
                'total_rows' => $previewData['stats']['total_rows'],
                'valid_rows' => $previewData['stats']['valid_rows'],
                'warning_rows' => $previewData['stats']['warning_rows'],
                'duplicate_rows' => $previewData['stats']['duplicate_rows'],
                'preview_payload' => $previewData['stats'] + ['rows' => $previewData['rows']],
                'raw_header_json' => $previewData['raw_headers'],
                'raw_sample_rows_json' => $previewData['raw_samples'],
                'temp_file_path' => $tempFilePath,
                'source_filename' => $file->getClientOriginalName(),
                'snapshot_date' => $date,
            ]);

            return redirect()->route('imports.preview', $previewBatch->id);

        } catch (\Throwable $e) {
            Log::error('Stock Import Dry Run Failed: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return redirect()
                ->route('import.stock.index')
                ->withErrors(['file' => 'Failed to parse file: ' . $e->getMessage()]);
        }
    }
}
