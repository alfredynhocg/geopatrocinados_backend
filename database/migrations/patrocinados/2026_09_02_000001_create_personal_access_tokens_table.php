<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_patrocinados';

    /**
     * Tabla propia de Sanctum en esta conexión: Eloquent propaga la conexión
     * del modelo padre (`Usuario`, en pgsql_patrocinados) al crear el token
     * vía la relación morphMany de HasApiTokens, así que personal_access_tokens
     * debe existir también aquí — es independiente de la tabla homónima que
     * ya usa mentabit en la conexión mysql default.
     *
     * `tokenable_id` es UUID (no bigint, a diferencia del stub de Sanctum) porque
     * el único modelo tokenable de esta conexión es Usuario, con PK UUID.
     */
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuidMorphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
