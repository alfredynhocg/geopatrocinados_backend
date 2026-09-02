<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'pgsql_patrocinados';

    public function up(): void
    {
        // El docx no cierra el enum de `estado` aquí; se usa el mismo dominio
        // que visitas.estado_revision para que la regla de sincronía tenga sentido.
        Schema::create('revisiones_visitas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('visita_id')->constrained('visitas')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('usuarios')->onDelete('restrict');
            $table->timestampTz('fecha_revision');
            $table->string('estado', 20);
            $table->text('comentarios')->nullable();
            $table->boolean('requiere_correccion')->default(false);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->index('visita_id');
        });

        DB::statement("ALTER TABLE revisiones_visitas ADD CONSTRAINT chk_revisiones_visitas_estado CHECK (estado IN ('APROBADA','RECHAZADA','REQUIERE_CORRECCION'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('revisiones_visitas');
    }
};
