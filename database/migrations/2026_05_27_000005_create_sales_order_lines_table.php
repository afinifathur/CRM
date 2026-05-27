<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('ordered_qty', 15, 4);
            $table->decimal('allocated_qty', 15, 4)->default(0.0000);
            $table->decimal('outstanding_qty', 15, 4);
            $table->string('status')->nullable()->default('open');
            $table->timestamps();

            // Unique constraint to prevent duplicate product lines inside the same Sales Order
            $table->unique(['sales_order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_lines');
    }
};
