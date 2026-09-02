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
        // Ajuste respecto del docx: se agrega updated_at (ausente en el original
        // pese a existir updated_by). Ver docs/patrocinados/migraciones/00-indice.md.
        Schema::create('dispositivos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('usuarios')->onDelete('restrict');
            $table->string('identificador_dispositivo', 180)->unique();
            $table->string('nombre_dispositivo', 150)->nullable();
            $table->string('plataforma', 30);
            $table->string('version_sistema', 50)->nullable();
            $table->string('version_aplicacion', 50)->nullable();
            $table->string('estado', 20)->default('PENDIENTE');
            $table->timestampTz('ultima_sincronizacion_at')->nullable();
            $table->timestampTz('fecha_registro');
            $table->timestampTz('fecha_revocacion')->nullable();
            $table->foreignUuid('revoked_by')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->timestampTz('updated_at');
            $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');

            $table->index('user_id');
            $table->index('estado');
        });

        DB::statement("ALTER TABLE dispositivos ADD CONSTRAINT chk_dispositivos_estado CHECK (estado IN ('PENDIENTE','ACTIVO','REVOCADO'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('dispositivos');
    }
};
