<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date');
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('stock_pcs');
            $table->decimal('stock_kg', 15, 4);
            $table->foreignId('import_batch_id')->nullable()->constrained('import_batches')->nullOnDelete();
            $table->timestamps();

            // Enforce that a product can only have one stock record per day
            $table->unique(['snapshot_date', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_snapshots');
    }
};
