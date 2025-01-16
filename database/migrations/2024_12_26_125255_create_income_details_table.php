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
        Schema::create('income_details', function (Blueprint $table) {
            $table->bigIncrements('income_detail_id');
            $table->unsignedBigInteger('income_id');
            $table->unsignedBigInteger('article_id');
            $table->bigInteger('quantity');
            $table->double('purchase_price');
            $table->double('sale_price');
            $table->timestamps();

            $table->foreign('income_id')->references('income_id')->on('incomes')->onDelete('cascade');
            $table->foreign('article_id')->references('article_id')->on('articles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('income_details');
    }
};
