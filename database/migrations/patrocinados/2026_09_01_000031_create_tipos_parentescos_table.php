<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_patrocinados';

    public function up(): void
    {
        Schema::create('tipos_parentescos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('parentesco', 120);
            $table->boolean('estado')->default(true);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_parentescos');
    }
};
