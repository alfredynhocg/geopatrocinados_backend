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
        Schema::create('planes_visitas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('plan', 180);
            $table->smallInteger('anio');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('estado', 30)->default('ACTIVO');
            $table->foreignUuid('created_by')->constrained('usuarios')->onDelete('restrict');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');

            $table->index('anio');
        });

        DB::statement("ALTER TABLE planes_visitas ADD CONSTRAINT chk_planes_visitas_estado CHECK (estado IN ('ACTIVO','CERRADO','CANCELADO'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('planes_visitas');
    }
};
