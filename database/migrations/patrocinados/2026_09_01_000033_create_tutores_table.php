<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_patrocinados';

    public function up(): void
    {
        // Ajuste respecto del docx (decisión de negocio ya cerrada): el campo
        // `Parentesco VARCHAR(100)` libre del documento se reemplaza por FK a
        // tipos_parentescos. Ver docs/patrocinados/05-patrocinados.md, decisión 1.
        Schema::create('tutores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patrocinado_id')->constrained('patrocinados')->onDelete('cascade');
            $table->string('nombres', 120);
            $table->string('apellidos', 160);
            $table->foreignUuid('tipo_parentesco_id')->constrained('tipos_parentescos')->onDelete('restrict');
            $table->string('telefono', 40)->nullable();
            $table->string('direccion', 160);
            $table->boolean('estado')->default(true);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->timestamp('deleted_at')->nullable();
            $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');

            $table->index('patrocinado_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutores');
    }
};
