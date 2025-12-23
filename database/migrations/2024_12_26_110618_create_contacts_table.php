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
            $table->enum('type_contact', ['email', 'phone']);
            $table->string('contact');
            $table->string('label')->nullable(); //'Etiqueta para identificar el contacto (ej: Oficina, Casa, Principal)'
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
