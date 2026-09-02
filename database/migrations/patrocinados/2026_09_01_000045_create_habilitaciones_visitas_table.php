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
        Schema::create('habilitaciones_visitas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('visita_id')->constrained('visitas')->onDelete('cascade');
            $table->foreignUuid('tecnico_id')->constrained('usuarios')->onDelete('restrict');
            $table->foreignUuid('dispositivo_id')->constrained('dispositivos')->onDelete('restrict');
            $table->foreignUuid('authorized_by')->constrained('usuarios')->onDelete('restrict');
            $table->timestampTz('fecha_habilitacion');
            $table->timestampTz('fecha_expiracion');
            $table->string('estado', 20)->default('ACTIVA');
            $table->timestampTz('fecha_revocacion')->nullable();
            $table->foreignUuid('revoked_by')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->index(['visita_id', 'dispositivo_id']);
        });

        DB::statement("ALTER TABLE habilitaciones_visitas ADD CONSTRAINT chk_habilitaciones_visitas_estado CHECK (estado IN ('ACTIVA','EXPIRADA','REVOCADA'))");

        // Como mucho una habilitación activa por (visita, dispositivo):
        DB::statement("CREATE UNIQUE INDEX uq_habilitaciones_visitas_activa ON habilitaciones_visitas (visita_id, dispositivo_id) WHERE estado = 'ACTIVA'");
    }

    public function down(): void
    {
        Schema::dropIfExists('habilitaciones_visitas');
    }
};
