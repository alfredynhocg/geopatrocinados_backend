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
        Schema::create('elementos_sincronizacion', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lote_sincronizacion_id')->constrained('lotes_sincronizacion')->onDelete('cascade');
            $table->string('tipo_entidad', 100);
            $table->uuid('entidad_id');
            $table->string('operacion', 20);
            $table->char('hash_datos', 64)->nullable();
            $table->string('estado', 20)->default('PENDIENTE');
            $table->smallInteger('intentos')->default(0);
            $table->text('mensaje_error')->nullable();
            $table->timestampTz('fecha_sincronizacion')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->index('lote_sincronizacion_id');
            $table->index(['tipo_entidad', 'entidad_id']);
            // Soporta idempotencia de reenvío. OJO: un UNIQUE index de Postgres no
            // bloquea duplicados cuando hash_datos es NULL (ver docs/patrocinados/
            // migraciones/07-fase6-sincronizacion.md) — reforzar en el Handler.
            $table->unique(['tipo_entidad', 'entidad_id', 'hash_datos'], 'uq_elementos_sync_entidad_hash');
        });

        DB::statement("ALTER TABLE elementos_sincronizacion ADD CONSTRAINT chk_elementos_sincronizacion_estado CHECK (estado IN ('PENDIENTE','SINCRONIZADO','ERROR'))");
        DB::statement("ALTER TABLE elementos_sincronizacion ADD CONSTRAINT chk_elementos_sincronizacion_operacion CHECK (operacion IN ('CREATE','UPDATE','DELETE'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('elementos_sincronizacion');
    }
};
