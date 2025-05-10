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
        Schema::create('document_details', function (Blueprint $table) {
            $table->bigIncrements('document_detail_id');
            $table->foreignUlid('document_id');
            $table->unsignedBigInteger('article_id');
            $table->bigInteger('quantity');
            $table->double('sale_price');
            $table->double('discount');
            $table->timestamps();

            $table->foreign('document_id')->references('document_id')->on('documents')->onDelete('cascade');
            $table->foreign('article_id')->references('article_id')->on('articles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_details');
    }
};
