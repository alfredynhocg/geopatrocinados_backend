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
        Schema::create('asignaciones_visitas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('visita_id')->constrained('visitas')->onDelete('cascade');
            $table->foreignUuid('tecnico_id')->constrained('usuarios')->onDelete('restrict');
            $table->foreignUuid('assigned_by')->constrained('usuarios')->onDelete('restrict');
            $table->timestampTz('fecha_asignacion');
            $table->timestampTz('fecha_desasignacion')->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->index('visita_id');
        });

        // Refuerza la regla de sincronía visitas.user_id <-> asignaciones_visitas
        // (plan de revisión §8.1/§8.2): como mucho una asignación activa por visita.
        DB::statement('CREATE UNIQUE INDEX uq_asignaciones_visitas_activa ON asignaciones_visitas (visita_id) WHERE estado = TRUE');
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_visitas');
    }
};
