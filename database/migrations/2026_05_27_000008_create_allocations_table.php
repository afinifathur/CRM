<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_line_id')->constrained('shipment_lines')->cascadeOnDelete();
            $table->foreignId('sales_order_line_id')->constrained('sales_order_lines')->cascadeOnDelete();
            $table->decimal('allocated_qty', 15, 4);
            $table->string('allocation_method')->default('system_auto'); // explicit_ref, exact_match, single_candidate, fifo_fallback, manual
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allocations');
    }
};
