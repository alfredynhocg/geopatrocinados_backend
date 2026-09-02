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
        // Ajuste respecto del docx: se agrega intentos_reprogramacion, requerido
        // por la regla de negocio de reprogramación (docs/patrocinados/06-visitas.md,
        // decisión 1) y ausente en el documento original.
        Schema::create('visitas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('plan_visita_id')->nullable()->constrained('planes_visitas')->onDelete('set null');
            $table->foreignUuid('patrocinado_id')->constrained('patrocinados')->onDelete('restrict');
            $table->foreignUuid('user_id')->constrained('usuarios')->onDelete('restrict');
            $table->foreignUuid('motivo_visita_id')->nullable()->constrained('motivos_visitas')->onDelete('set null');
            $table->date('fecha_programada')->nullable();
            $table->timestampTz('fecha_habilitacion')->nullable();
            $table->timestampTz('fecha_inicio')->nullable();
            $table->timestampTz('fecha_finalizacion')->nullable();
            $table->string('estado', 30)->default('PLANIFICADA');
            $table->string('estado_revision', 30)->default('PENDIENTE');
            $table->string('estado_sincronizacion', 30)->default('PENDIENTE');
            $table->integer('version')->default(1);
            $table->smallInteger('intentos_reprogramacion')->default(0);
            $table->foreignUuid('created_by')->constrained('usuarios')->onDelete('restrict');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->timestamp('deleted_at')->nullable();
            // Sin updated_by: intencional, ver docs/patrocinados/migraciones/00-indice.md.

            $table->index('patrocinado_id');
            $table->index(['patrocinado_id', 'estado']);
            $table->index('user_id');
            $table->index('plan_visita_id');
        });

        DB::statement("ALTER TABLE visitas ADD CONSTRAINT chk_visitas_estado CHECK (estado IN ('PLANIFICADA','EN_CURSO','FINALIZADA','NO_ENCONTRADO','REPROGRAMADA','CANCELADA'))");
        DB::statement("ALTER TABLE visitas ADD CONSTRAINT chk_visitas_estado_revision CHECK (estado_revision IN ('PENDIENTE','APROBADA','RECHAZADA','REQUIERE_CORRECCION'))");
        DB::statement("ALTER TABLE visitas ADD CONSTRAINT chk_visitas_estado_sincronizacion CHECK (estado_sincronizacion IN ('PENDIENTE','SINCRONIZADO','ERROR'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('visitas');
    }
};
