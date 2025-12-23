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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->bigIncrements('sale_item_id');
            $table->foreignUlid('sale_id');
            $table->unsignedBigInteger('product_id');
            $table->bigInteger('quantity');
            $table->double('unit_price');
            $table->double('discount_amount')->default(0);
            $table->double('subtotal')->nullable();
            $table->unsignedBigInteger('tax_rate_id')->nullable();
            $table->double('tax_amount')->nullable();
            $table->timestamps();

            $table->foreign('sale_id')->references('sale_id')->on('sales')->onDelete('cascade');
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
            $table->foreign('tax_rate_id')->references('tax_rate_id')->on('tax_rates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
