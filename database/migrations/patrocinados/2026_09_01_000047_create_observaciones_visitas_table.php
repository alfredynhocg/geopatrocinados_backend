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
        Schema::create('observaciones_visitas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('visita_id')->constrained('visitas')->onDelete('cascade');
            $table->foreignUuid('categoria_id')->nullable()->constrained('categorias_observaciones')->onDelete('set null');
            $table->string('tipo', 30)->default('GENERAL');
            $table->text('observacion');
            $table->foreignUuid('created_by')->constrained('usuarios')->onDelete('restrict');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->index('visita_id');
        });

        DB::statement("ALTER TABLE observaciones_visitas ADD CONSTRAINT chk_observaciones_visitas_tipo CHECK (tipo IN ('GENERAL','EDUCATIVA','SALUD','FAMILIAR'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('observaciones_visitas');
    }
};
