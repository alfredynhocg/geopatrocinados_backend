<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'pgsql_patrocinados';

    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
    }

    public function down(): void
    {
        // Intencionalmente vacío: DROP EXTENSION podría afectar columnas GEOGRAPHY
        // ya creadas por migraciones posteriores (ubicaciones, ubicaciones_visitas).
    }
};
