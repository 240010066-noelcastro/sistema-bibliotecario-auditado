<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('audiovisuales', function (Blueprint $table) {
            $table->unsignedBigInteger('Recurso_ID')->primary();
            $table->foreign('Recurso_ID')->references('Recurso_ID')->on('recursos_catalogo')->onDelete('cascade');
            $table->string('Marca', 100)->nullable();
            $table->string('NumSerie', 100)->nullable();
        });
    }
    public function down(): void {
        Schema::dropIfExists('audiovisuales');
    }
};