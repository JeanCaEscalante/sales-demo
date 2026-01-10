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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->bigIncrements('purchase_item_id');

            // Relaciones
            $table->unsignedBigInteger('purchase_id');
            $table->foreign('purchase_id')
                ->references('purchase_id')
                ->on('purchases')
                ->onDelete('cascade');

            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')
                ->references('product_id')
                ->on('products')
                ->onDelete('cascade');

            // Datos de compra
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 12, 4)->default(0)->comment('4 decimales para precisión en redondeos');
            $table->decimal('discount', 5, 2)->default(0)->comment('Porcentaje 0-100');
            $table->decimal('subtotal', 12, 2)->default(0)->comment('quantity * unit_price * (1 - discount/100)');

            // Impuestos (snapshot al momento de compra)
            $table->boolean('tax_exempt')->default(false);

            $table->unsignedBigInteger('tax_rate_id')->nullable();
            $table->foreign('tax_rate_id')
                ->references('tax_rate_id')
                ->on('tax_rates')
                ->nullOnDelete();

            $table->decimal('tax_rate', 5, 2)->nullable()->comment('Snapshot: tasa al momento de compra');
            $table->string('tax_name', 100)->nullable()->comment('Snapshot: nombre al momento de compra');
            $table->decimal('tax_amount', 12, 2)->default(0)->comment('Impuesto calculado de la línea');

            // Configuración de venta (snapshot al momento de compra)
            $table->decimal('profit', 5, 2)->default(0)->comment('Porcentaje de ganancia 0-100');
            $table->boolean('update_sale_price')->default(false);
            $table->decimal('sale_price', 12, 2)->default(0)->comment('Precio venta sin IVA');

            $table->timestamps();

            // Índice para consultas frecuentes
            $table->index(['purchase_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
