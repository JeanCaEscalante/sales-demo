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
            $table->unsignedBigInteger('supplier_id');
            $table->enum('document_type', ['bill', 'ticket']);
            $table->string('series')->nullable();
            $table->string('receipt_number');
            $table->date('purchase_date');
            $table->double('total_tax');
            $table->double('total_amount');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
