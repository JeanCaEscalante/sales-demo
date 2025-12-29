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
        Schema::create('purchases', function (Blueprint $table) {
            $table->bigIncrements('purchase_id');
            
            // Relaciones
            $table->unsignedBigInteger('supplier_id');
            $table->foreign('supplier_id')
                ->references('supplier_id')
                ->on('suppliers')
                ->onDelete('cascade');
            
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            
            // Información del comprobante
            $table->string('document_type', 50);
            $table->string('series', 10)->nullable();
            $table->string('receipt_number', 50);
            $table->date('purchase_date');
            
            // Moneda
            $table->string('currency', 10)->nullable();
            $table->decimal('exchange_rate', 10, 4)->nullable()->comment('Tipo de cambio al momento de compra');
            
            // Resumen de compra (campos completos para reportes fiscales)
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('taxable_base', 12, 2)->default(0)->comment('Subtotal de productos gravados');
            $table->decimal('total_exempt', 12, 2)->default(0)->comment('Subtotal de productos exentos');
            $table->decimal('total_tax', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            
            $table->timestamps();
            
            // Índice único: proveedor + número de comprobante
            $table->unique(['supplier_id', 'receipt_number'], 'purchases_supplier_receipt_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
