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

            // Relaciones
            $table->unsignedBigInteger('sale_id');
            $table->foreign('sale_id')
                ->references('sale_id')
                ->on('sales')
                ->onDelete('cascade');

            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')
                ->references('product_id')
                ->on('products')
                ->onDelete('cascade');

            // Datos de venta
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 12, 4)->default(0)->comment('4 decimales para precisión en redondeos');
            $table->decimal('discount', 5, 2)->default(0)->comment('Porcentaje 0-100');
            $table->decimal('subtotal', 12, 2)->default(0);

            // Impuestos (snapshot al momento de venta)
            $table->boolean('tax_exempt')->default(false);

            $table->unsignedBigInteger('tax_rate_id')->nullable();
            $table->foreign('tax_rate_id')
                ->references('tax_rate_id')
                ->on('tax_rates')
                ->nullOnDelete();

            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->string('tax_name', 100)->nullable();
            $table->decimal('tax_amount', 12, 2)->default(0);

            $table->timestamps();

            // Índice para consultas frecuentes
            $table->index(['sale_id', 'product_id']);
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
