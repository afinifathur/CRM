<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ImportBatch;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Services\Import\ImportPreviewService;
use App\Services\Import\OutstandingPoImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutstandingPoGroupedParserTest extends TestCase
{
    use RefreshDatabase;

    public function test_grouped_customer_erp_parser_preview_and_import()
    {
        // 1. Create a mock CSV representing Grouped Customer ERP Mode
        $csvPath = tempnam(sys_get_temp_dir(), 'po_import_');
        $file = fopen($csvPath, 'w');

        // Customer Header Row (Row 1)
        fputcsv($file, [
            'Cust. Code', '', 'Cust. Short Name', '', '', '', '', '', '', '', '', '', '', '', ''
        ]);

        // Detail Header Row (Row 2)
        fputcsv($file, [
            'SO No.', '', 'SO Date', 'PO Number', 'Product Name', '', '', 'Base Unit', 'Unit Weight', 'SO Qty',
            'Dlv Qty', 'Cancel Qty', 'SR Qty', 'UnDlv Qty > 0', 'Total Weight'
        ]);

        // Customer Group 1 Header (Row 3)
        fputcsv($file, [
            'CUSL043', '', 'PT. BADJA KARYA TECHNIK', '', '', '', '', '', '', '', '', '', '', '', ''
        ]);
        // Detail row 1 (Row 4)
        fputcsv($file, [
            'SL-2026-0720', '', '2026-05-15', 'PO-BKT/V/2026/014', 'SS304 CROSS BSP 1/2"', '', '', 'PCS', '0.5', '10.00',
            '0.00', '0.00', '0.00', '10.00', '5.00'
        ]);
        // Detail row 2 (Row 5)
        fputcsv($file, [
            'SL-2026-0720', '', '2026-05-15', 'PO-BKT/V/2026/014', 'SS316 TEE BSP 1/2"', '', '', 'PCS', '0.6', '5.00',
            '0.00', '0.00', '0.00', '5.00', '3.00'
        ]);

        // Customer Group 2 Header (Row 6)
        fputcsv($file, [
            'CUSL099', '', 'PT. ANOTHER CUSTOMER', '', '', '', '', '', '', '', '', '', '', '', ''
        ]);
        // Detail row 3 (Row 7)
        fputcsv($file, [
            'SL-2026-0800', '', '2026-05-16', 'PO-ANT/2026/09', 'SS304 ELBOW BSP 1/2"', '', '', 'PCS', '0.4', '20.00',
            '0.00', '0.00', '0.00', '20.00', '8.00'
        ]);

        fclose($file);

        // 2. Test generatePreview in ImportPreviewService
        $previewService = app(ImportPreviewService::class);
        $previewResult = $previewService->generatePreview('po', $csvPath);

        $this->assertNotEmpty($previewResult);
        $this->assertEquals(3, $previewResult['stats']['total_rows']);
        $this->assertEquals(3, $previewResult['stats']['valid_rows']);
        $this->assertEquals(3, $previewResult['stats']['warning_rows']);
        $this->assertEquals('Grouped Customer ERP Mode', $previewResult['stats']['parser_mode']);
        $this->assertEquals(1, $previewResult['stats']['detected_header_row']);
        $this->assertEquals(2, $previewResult['stats']['detected_detail_header_row']);
        $this->assertEquals(2, $previewResult['stats']['total_customer_groups']);
        $this->assertEquals(3, $previewResult['stats']['total_detail_rows']);

        // Check rows format
        $rows = $previewResult['rows'];
        $this->assertCount(3, $rows);
        $this->assertEquals('CUSL043', $rows[0]['customer_code']);
        $this->assertEquals('PT. BADJA KARYA TECHNIK', $rows[0]['customer_name']);
        $this->assertEquals('SL-2026-0720', $rows[0]['so_number']);
        $this->assertEquals('SS304 CROSS BSP 1/2"', $rows[0]['product']);
        $this->assertEquals('SS304 CROSS BSP 1/2"', $rows[0]['product_name']);
        $this->assertEquals(10.0, $rows[0]['outstanding']);

        $this->assertEquals('CUSL099', $rows[2]['customer_code']);
        $this->assertEquals('PT. ANOTHER CUSTOMER', $rows[2]['customer_name']);
        $this->assertEquals('SL-2026-0800', $rows[2]['so_number']);
        $this->assertEquals('SS304 ELBOW BSP 1/2"', $rows[2]['product_name']);
        $this->assertEquals(20.0, $rows[2]['outstanding']);

        // 3. Test import in OutstandingPoImportService
        $importService = app(OutstandingPoImportService::class);
        $batch = ImportBatch::create([
            'import_type' => 'outstanding_po',
            'source_filename' => 'test_grouped_po.csv',
            'imported_at' => now(),
            'total_rows' => 0,
            'inserted_rows' => 0,
            'skipped_rows' => 0,
            'notes' => 'Test import',
        ]);

        $importService->import($batch, $csvPath);

        // Verify Database insertion
        $this->assertEquals(2, Customer::count());
        $this->assertEquals(3, Product::count()); // Since product code is fallback to product name
        $this->assertEquals(2, SalesOrder::count()); // SL-2026-0720 and SL-2026-0800
        $this->assertEquals(3, SalesOrderLine::count());

        $customer = Customer::where('customer_code', 'CUSL043')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('PT. BADJA KARYA TECHNIK', $customer->customer_name);

        $product = Product::where('product_code', 'SS304 CROSS BSP 1/2"')->first();
        $this->assertNotNull($product);
        $this->assertEquals('SS304 CROSS BSP 1/2"', $product->product_name);

        $soLine = SalesOrderLine::whereHas('salesOrder', function($query) {
            $query->where('so_number', 'SL-2026-0720');
        })->where('product_id', $product->id)->first();
        $this->assertNotNull($soLine);
        $this->assertEquals(10.0, $soLine->outstanding_qty);

        // Check batch notes
        $batch->refresh();
        $this->assertStringContainsString('Mode: Grouped Customer ERP Mode', $batch->notes);
        $this->assertStringContainsString('Customer Groups: 2', $batch->notes);
        $this->assertStringContainsString('Detail Rows: 3', $batch->notes);

        unlink($csvPath);
    }

    public function test_header_detection_failure_generates_diagnostics_log()
    {
        // 1. Create a bad CSV with incorrect headers
        $csvPath = tempnam(sys_get_temp_dir(), 'po_bad_');
        $file = fopen($csvPath, 'w');
        fputcsv($file, ['Some Random Header', 'Another Column']);
        fputcsv($file, ['Value A', 'Value B']);
        fclose($file);

        // Delete debug log if it exists to start fresh
        $logPath = storage_path('logs/po_parser_debug.log');
        if (file_exists($logPath)) {
            unlink($logPath);
        }

        $previewService = app(ImportPreviewService::class);

        $thrown = false;
        try {
            $previewService->generatePreview('po', $csvPath);
        } catch (\Exception $e) {
            $thrown = true;
            $this->assertStringContainsString('Could not detect Outstanding PO report headers automatically', $e->getMessage());
        }

        $this->assertTrue($thrown);

        // Assert that the debug log exists and has content
        $this->assertFileExists($logPath);
        $logContent = file_get_contents($logPath);
        $this->assertStringContainsString('Row 1:', $logContent);
        $this->assertStringContainsString('A = "Some Random Header"', $logContent);
        $this->assertStringContainsString('B = "Another Column"', $logContent);
        $this->assertStringContainsString('Row 2:', $logContent);
        $this->assertStringContainsString('A = "Value A"', $logContent);
        $this->assertStringContainsString('B = "Value B"', $logContent);

        // Assert session has debug sample
        $this->assertEquals($logContent, session('po_parser_debug_sample'));

        unlink($csvPath);
        if (file_exists($logPath)) {
            unlink($logPath);
        }
    }

    public function test_preview_accuracy_and_readiness()
    {
        // Create a mock CSV representing Grouped Customer ERP Mode
        $csvPath = tempnam(sys_get_temp_dir(), 'po_accuracy_');
        $file = fopen($csvPath, 'w');

        // Customer Header Row (Row 1)
        fputcsv($file, [
            'Cust. Code', '', 'Cust. Short Name', '', '', '', '', '', '', '', '', '', '', '', ''
        ]);

        // Detail Header Row (Row 2)
        fputcsv($file, [
            'SO No.', '', 'SO Date', 'PO Number', 'Product Name', '', '', 'Base Unit', 'Unit Weight', 'SO Qty',
            'Dlv Qty', 'Cancel Qty', 'SR Qty', 'UnDlv Qty > 0', 'Total Weight'
        ]);

        // Customer Group 1 Header (Row 3)
        fputcsv($file, [
            'CUSL043', '', 'PT. BADJA KARYA TECHNIK', '', '', '', '', '', '', '', '', '', '', '', ''
        ]);
        // Detail row 1 (Row 4): numeric date, valid row
        fputcsv($file, [
            'SL-2026-0720', '', '46157', 'PO-BKT/V/2026/014', 'SS304 CROSS BSP 1/2"', '', '', 'PCS', '0.5', '10.00',
            '0.00', '0.00', '0.00', '10.00', '5.00'
        ]);
        // Detail row 2 (Row 5): normal date, valid row (same customer)
        fputcsv($file, [
            'SL-2026-0720', '', '2026-05-15', 'PO-BKT/V/2026/014', 'SS316 TEE BSP 1/2"', '', '', 'PCS', '0.6', '5.00',
            '0.00', '0.00', '0.00', '5.00', '3.00'
        ]);
        // Detail row 3 (Row 6): blocked row (missing SO number)
        fputcsv($file, [
            '', '', '2026-05-15', 'PO-BKT/V/2026/014', 'SS316 TEE BSP 1/2"', '', '', 'PCS', '0.6', '5.00',
            '0.00', '0.00', '0.00', '5.00', '3.00'
        ]);

        fclose($file);

        $previewService = app(ImportPreviewService::class);
        $previewResult = $previewService->generatePreview('po', $csvPath);

        $stats = $previewResult['stats'];

        // Assert unique counts
        $this->assertEquals(1, $stats['unique_customers']);
        $this->assertEquals(2, $stats['unique_products']);
        $this->assertEquals(1, $stats['unique_so_numbers']);

        // Assert date conversion
        $rows = $previewResult['rows'];
        $this->assertEquals('2026-05-15', $rows[0]['order_date']); // converted from 46157
        $this->assertEquals('2026-05-15', $rows[1]['order_date']); // parsed from 2026-05-15

        // Assert readiness and audited warning counters
        $this->assertEquals('BLOCKED', $stats['import_readiness']); // because row 3 of details (overall row 6) is missing SO number
        $this->assertEquals(1, $stats['blocking_warning_count']);
        $this->assertEquals(0, $stats['review_warning_count']);
        $this->assertGreaterThanOrEqual(2, $stats['informational_warning_count']);
        
        $this->assertCount(1, $stats['first_20_blocking_warnings']);
        $this->assertEquals(6, $stats['first_20_blocking_warnings'][0]['row']);
        $this->assertStringContainsString('SO Number is blank', $stats['first_20_blocking_warnings'][0]['message']);

        unlink($csvPath);
    }

    public function test_full_import_and_summary_verification()
    {
        // 1. Create a clean CSV without blocking warnings
        $csvPath = tempnam(sys_get_temp_dir(), 'po_verify_');
        $file = fopen($csvPath, 'w');

        fputcsv($file, [
            'Cust. Code', '', 'Cust. Short Name', '', '', '', '', '', '', '', '', '', '', '', ''
        ]);
        fputcsv($file, [
            'SO No.', '', 'SO Date', 'PO Number', 'Product Name', '', '', 'Base Unit', 'Unit Weight', 'SO Qty',
            'Dlv Qty', 'Cancel Qty', 'SR Qty', 'UnDlv Qty > 0', 'Total Weight'
        ]);
        fputcsv($file, [
            'CUSL999', '', 'TEST CUSTOMER GROUP', '', '', '', '', '', '', '', '', '', '', '', ''
        ]);
        fputcsv($file, [
            'SO-TEST-999', '', '2026-06-03', 'PO-TEST-999', 'TEST PRODUCT 999', '', '', 'PCS', '1.0', '15.00',
            '0.00', '0.00', '0.00', '15.00', '15.00'
        ]);
        fclose($file);

        // 2. Put it in a preview batch record
        $previewService = app(ImportPreviewService::class);
        $previewResult = $previewService->generatePreview('po', $csvPath);

        $previewBatch = \App\Models\ImportPreviewBatch::create([
            'type' => 'po',
            'source_filename' => 'test_grouped_po.csv',
            'temp_file_path' => $csvPath,
            'status' => 'preview',
            'preview_payload' => $previewResult,
        ]);

        // 3. Confirm import via POST request
        $response = $this->post(route('imports.confirm', $previewBatch->id));

        // 4. Assert response redirects to summary
        $response->assertStatus(302);
        
        $batch = \App\Models\ImportBatch::latest('id')->first();
        $response->assertRedirect(route('imports.summary', $batch->id));

        // 5. Query summary page and assert database counts are rendered
        $summaryResponse = $this->get(route('imports.summary', $batch->id));
        $summaryResponse->assertStatus(200);

        // Verify content includes inserted metrics
        $summaryResponse->assertSee('Import Completed');
        $summaryResponse->assertSee('#' . $batch->id);
        $summaryResponse->assertSee('Grouped Customer ERP Mode');

        // Check if database tables were actually updated
        $this->assertDatabaseHas('customers', ['customer_code' => 'CUSL999']);
        $this->assertDatabaseHas('products', ['product_code' => 'TEST PRODUCT 999']);
        $this->assertDatabaseHas('sales_orders', ['so_number' => 'SO-TEST-999']);
        
        if (file_exists($csvPath)) {
            unlink($csvPath);
        }
    }
}
