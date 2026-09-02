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
        // Ajuste respecto del docx: se agrega updated_at (ausente en el original).
        // Ver docs/patrocinados/migraciones/00-indice.md.
        Schema::create('lotes_sincronizacion', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dispositivo_id')->constrained('dispositivos')->onDelete('restrict');
            $table->foreignUuid('user_id')->constrained('usuarios')->onDelete('restrict');
            $table->timestampTz('fecha_inicio');
            $table->timestampTz('fecha_fin')->nullable();
            $table->integer('registros_enviados')->default(0);
            $table->integer('registros_recibidos')->default(0);
            $table->string('estado', 20)->default('SINCRONIZANDO');
            $table->text('mensaje_error')->nullable();
            $table->timestampTz('updated_at');

            $table->index('dispositivo_id');
            $table->index('estado');
        });

        DB::statement("ALTER TABLE lotes_sincronizacion ADD CONSTRAINT chk_lotes_sincronizacion_estado CHECK (estado IN ('SINCRONIZANDO','COMPLETADO','ERROR'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes_sincronizacion');
    }
};
