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
        Schema::create('favoritos', function (Blueprint $table) {
            $table->unsignedBigInteger('Usuario_ID');
            $table->foreign('Usuario_ID')->references('Usuario_ID')->on('usuarios')->onDelete('cascade');

            $table->unsignedBigInteger('Recurso_ID');
            $table->foreign('Recurso_ID')->references('Recurso_ID')->on('recursos_catalogo')->onDelete('cascade');

            $table->timestamps();

            // Clave primaria compuesta para evitar duplicados
            $table->primary(['Usuario_ID', 'Recurso_ID']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favoritos');
    }
};