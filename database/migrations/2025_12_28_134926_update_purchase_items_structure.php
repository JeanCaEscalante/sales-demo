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
        Schema::table('purchase_items', function (Blueprint $table) {
            // Modificar columnas existentes
            $table->decimal('quantity', 10, 2)->change();
            $table->decimal('unit_price', 10, 2)->change();
            $table->decimal('net_total', 10, 2)->default(0)->change();
            $table->decimal('sale_price', 10, 2)->default(0)->change();

            // Agregar nuevas columnas
            $table->decimal('profit', 5, 2)->after('unit_price');
            
            // Sistema de impuestos (híbrido: relación + snapshot)
            $table->boolean('tax_exempt')->default(false)->after('profit');
            
            // Relación con taxes (tax_rates)
            // Asumiendo que la tabla es 'tax_rates' y el PK es 'tax_rate_id' según migraciones existentes
            $table->foreignId('tax_id')->nullable()->after('tax_exempt')->constrained('tax_rates', 'tax_rate_id')->nullOnDelete();
            
            $table->decimal('tax_rate', 5, 4)->nullable()->comment('Snapshot: tasa al momento de compra')->after('tax_id');
            $table->string('tax_name', 100)->nullable()->comment('Snapshot: nombre al momento de compra')->after('tax_rate');
            
            // Totales calculados adicionales
            $table->decimal('subtotal', 10, 2)->default(0)->after('tax_name');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('subtotal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            // Eliminar columnas creadas
            $table->dropForeign(['tax_id']);
            $table->dropColumn(['profit', 'tax_exempt', 'tax_id', 'tax_rate', 'tax_name', 'subtotal', 'tax_amount']);

            // Revertir cambios de tipo (aproximación a lo original)
            $table->bigInteger('quantity')->change();
            $table->double('unit_price')->change();
            $table->double('net_total')->change();
            $table->double('sale_price')->change();
        });
    }
};
