<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_patrocinados';

    public function up(): void
    {
        // El docx no marca `codigo` como UNIQUE en esta tabla (a diferencia de
        // departamento/municipios) — se respeta tal cual.
        Schema::create('comunidades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('municipio_id')->constrained('municipios')->onDelete('restrict');
            $table->string('codigo', 30)->nullable();
            $table->string('comunidad', 180);
            $table->boolean('estado')->default(true);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');

            $table->index('municipio_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunidades');
    }
};
