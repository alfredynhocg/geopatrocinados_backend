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
        Schema::create('historial_ubicaciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patrocinado_id')->constrained('patrocinados')->onDelete('cascade');
            $table->foreignUuid('comunidad_id')->constrained('comunidades')->onDelete('restrict');
            $table->foreignUuid('ubicacion_id')->nullable()->constrained('ubicaciones')->onDelete('restrict');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');

            $table->index('patrocinado_id');
        });

        // Regla de sincronía "estado actual + historial" (plan de revisión §8.1):
        // como mucho una fila abierta (fecha_fin IS NULL) por patrocinado.
        DB::statement('CREATE UNIQUE INDEX uq_historial_ubicaciones_activa ON historial_ubicaciones (patrocinado_id) WHERE fecha_fin IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_ubicaciones');
    }
};
