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
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->bigIncrements('sale_payment_id');
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('user_id'); // quien registra el pago
            $table->decimal('amount', 10, 2);
            $table->string('payment_method'); // efectivo, transferencia, etc
            $table->string('currency', 10)->nullable();
            $table->decimal('exchange_rate', 10, 4)->nullable()->comment('Tasa de cambio del dia');
            $table->text('reference')->nullable();
            $table->date('payment_date');
            $table->timestamps();

            $table->foreign('sale_id')->references('sale_id')->on('sales')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
