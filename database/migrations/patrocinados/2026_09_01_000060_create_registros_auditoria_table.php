<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_patrocinados';

    public function up(): void
    {
        // PK BIGINT autoincremental: única excepción intencional a la regla de UUID
        // del resto del módulo (tabla insert-only de alto volumen). Ver plan de
        // revisión §8 y docs/patrocinados/migraciones/08-fase7-auditoria.md.
        Schema::create('registros_auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->foreignUuid('dispositivo_id')->nullable()->constrained('dispositivos')->onDelete('set null');
            $table->string('accion', 80);
            $table->string('modulo', 80);
            $table->string('tipo_entidad', 100)->nullable();
            $table->uuid('entidad_id')->nullable();
            $table->jsonb('valores_anteriores')->nullable();
            $table->jsonb('valores_nuevos')->nullable();
            $table->ipAddress('direccion_ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('created_at');

            $table->index(['tipo_entidad', 'entidad_id']);
            $table->index('user_id');
            $table->index('modulo');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_auditoria');
    }
};
