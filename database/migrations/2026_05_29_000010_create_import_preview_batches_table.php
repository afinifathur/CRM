<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('import_preview_batches', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // stock, po, shipment
            $table->string('user_session')->nullable();
            $table->string('status')->default('preview'); // preview, confirmed, cancelled
            
            $table->integer('total_rows')->default(0);
            $table->integer('valid_rows')->default(0);
            $table->integer('warning_rows')->default(0);
            $table->integer('duplicate_rows')->default(0);
            
            $table->longText('preview_payload'); // using longText/json for safe payload storage
            
            // TASK 1: Audit parser headers and sample raw rows
            $table->json('raw_header_json')->nullable();
            $table->json('raw_sample_rows_json')->nullable();
            
            $table->string('temp_file_path');
            $table->string('source_filename');
            $table->date('snapshot_date')->nullable();
            $table->unsignedBigInteger('import_batch_id')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_preview_batches');
    }
};
