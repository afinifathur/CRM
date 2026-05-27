<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('import_type'); // stock_snapshot, outstanding_po, shipment
            $table->string('source_filename')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->integer('total_rows')->default(0);
            $table->integer('inserted_rows')->default(0);
            $table->integer('skipped_rows')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
