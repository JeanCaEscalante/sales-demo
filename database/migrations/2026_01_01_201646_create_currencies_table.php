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
        Schema::create('currencies', function (Blueprint $table) {
            $table->bigIncrements('currency_id');
            $table->string('code', 3)->unique(); // USD, EUR, COP, etc.
            $table->string('name');
            $table->string('symbol', 10);
            $table->boolean('is_base')->default(false); // USD será la base
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->bigIncrements('exchange_rate_id');
            $table->unsignedBigInteger('currency_id');
            $table->decimal('rate', 15, 6); // Tasa respecto al USD
            $table->date('effective_date');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('currency_id')->references('currency_id')->on('currencies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->unique(['currency_id', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
    }
};
