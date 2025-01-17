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
        Schema::create('articles', function (Blueprint $table) {
            $table->bigIncrements('article_id');
            $table->unsignedBigInteger('category_id');
            $table->string('code')->nullable();
            $table->text('name');
            $table->bigInteger('stock');
            $table->double('price_in');
            $table->double('price_out');
            $table->mediumText('description')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('category_id')->on('categories');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
