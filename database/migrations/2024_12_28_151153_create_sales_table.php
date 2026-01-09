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
        Schema::create('sales', function (Blueprint $table) {
            $table->bigIncrements('sale_id');

            // Relaciones
            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')
                ->references('customer_id')
                ->on('customers')
                ->onDelete('cascade');

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Información del comprobante
            $table->string('document_type', 50);
            $table->string('series')->nullable();
            $table->string('number');
            $table->date('sale_date');

            // Resumen de venta (campos completos para reportes fiscales)
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('taxable_base', 12, 2)->default(0)->comment('Subtotal de productos gravados');
            $table->decimal('total_exempt', 12, 2)->default(0)->comment('Subtotal de productos exentos');
            $table->decimal('total_tax', 12, 2)->default(0);
            $table->decimal('total_discounts', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);

            //Control de pagos
            $table->string('payment_status')
                ->default('paid');

            $table->decimal('paid_amount', 12, 2)
                ->default(0)
                ->unsigned()
                ->comment('Total pagado (suma de abonos)');

            $table->decimal('balance', 12, 2)
                ->default(0)
                ->unsigned()
                ->comment('Saldo pendiente (total_amount - paid_amount)');

            $table->timestamps();

            // Índice para consultas frecuentes
            $table->index(['customer_id', 'sale_date']);

            // Índice único para evitar duplicados en la numeración
            $table->unique(['series', 'number'], 'sales_series_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
