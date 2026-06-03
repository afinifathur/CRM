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
            $previewData = $previewService->generatePreview('po', $tempFilePath);

            // Create ImportPreviewBatch
            $previewBatch = \App\Models\ImportPreviewBatch::create([
                'type' => 'po',
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
                'snapshot_date' => null,
            ]);

            return redirect()->route('imports.preview', $previewBatch->id);

        } catch (\Throwable $e) {
            Log::error('Outstanding PO Import Dry Run Failed: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return redirect()
                ->route('import.po.index')
                ->withErrors(['file' => 'Failed to parse file: ' . $e->getMessage()]);
        }
    }
}
