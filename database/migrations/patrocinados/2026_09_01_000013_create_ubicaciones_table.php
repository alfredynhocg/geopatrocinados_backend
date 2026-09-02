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
        Schema::create('ubicaciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('comunidad_id')->constrained('comunidades')->onDelete('restrict');
            $table->string('nombre', 180);
            $table->string('tipo', 50)->nullable();
            $table->text('direccion')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('precision_metros', 8, 2)->nullable();
            // punto_geografico se agrega abajo vía SQL crudo — Blueprint no tiene tipo GEOGRAPHY nativo.
            $table->boolean('estado')->default(true);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');

            $table->index('comunidad_id');
        });

        DB::statement('ALTER TABLE ubicaciones ADD COLUMN punto_geografico geography(Point,4326) NULL');

        DB::statement("ALTER TABLE ubicaciones ADD CONSTRAINT chk_ubicaciones_tipo CHECK (tipo IS NULL OR tipo IN ('DOMICILIO','ESCUELA','PUNTO_REFERENCIA','OTRO'))");

        DB::statement('CREATE INDEX idx_ubicaciones_punto_geografico ON ubicaciones USING GIST (punto_geografico)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ubicaciones');
    }
};
