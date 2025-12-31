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
        Schema::create('contacts', function (Blueprint $table) {
            $table->bigIncrements('contact_id');
            $table->unsignedBigInteger('contactable_id');
            $table->string('contactable_type');
            $table->string('type_contact');
            $table->string('contact');
            $table->boolean('is_primary')->default(false); //'Indica si es el contacto principal
            $table->timestamps();

            $table->index(['contactable_id', 'contactable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
