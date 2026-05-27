<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('shipped_qty', 15, 4);
            $table->foreignId('sales_order_line_id')->nullable()->constrained('sales_order_lines')->nullOnDelete();
            $table->string('allocation_status')->default('unallocated'); // unallocated, ambiguous, partially_allocated, allocated
            $table->timestamps();

            // Unique constraint to prevent duplicate product lines on the same shipment document
            $table->unique(['shipment_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_lines');
    }
};
