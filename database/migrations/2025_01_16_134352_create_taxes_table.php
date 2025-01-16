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
        Schema::create('taxes', function (Blueprint $table) {
            $table->bigIncrements('taxe_id');
            $table->string('country');
            $table->string('state');
            $table->string('name');
            $table->decimal('rate', 5, 2);
            $table->bigInteger('priority')->default(0);
            $table->boolean('is_composed')->default(false);
            $table->boolean('is_shipping')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
