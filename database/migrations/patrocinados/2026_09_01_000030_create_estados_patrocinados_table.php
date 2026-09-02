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
        Schema::create('estados_patrocinados', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('estado', 20)->default('ACTIVO');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->timestamp('deleted_at')->nullable();
            $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');
        });

        DB::statement("ALTER TABLE estados_patrocinados ADD CONSTRAINT chk_estados_patrocinados_estado CHECK (estado IN ('ACTIVO','NO_ENCONTRADO','INACTIVO_NO_UBICADO','MAYOR_DE_EDAD'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_patrocinados');
    }
};
