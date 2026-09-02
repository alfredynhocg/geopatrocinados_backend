<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_patrocinados';

    public function up(): void
    {
        Schema::create('municipios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('departamento_id')->constrained('departamento')->onDelete('restrict');
            $table->string('codigo', 30)->unique()->nullable();
            $table->string('municipio', 150);
            $table->boolean('estado')->default(true);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');

            $table->index('departamento_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipios');
    }
};
