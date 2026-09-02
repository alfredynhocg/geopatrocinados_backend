# Fase 3 — Dispositivos

## 3.1 `dispositivos`

`2026_09_01_000020_create_dispositivos_table.php`

Enum cerrado para `estado` (ver `docs/patrocinados/04-dispositivos.md`): `PENDIENTE`, `ACTIVO`, `REVOCADO`.

> **Ajuste respecto del docx** (señalado en `00-indice.md`): se agrega `updated_at TIMESTAMPTZ NOT NULL`, ausente en el documento original pero necesario porque la tabla sí tiene `updated_by`. `fecha_registro` se mantiene como el equivalente semántico de `created_at` (no se duplica).

```php
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
        Schema::create('dispositivos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('usuarios')->onDelete('restrict');
            $table->string('identificador_dispositivo', 180)->unique();
            $table->string('nombre_dispositivo', 150)->nullable();
            $table->string('plataforma', 30);
            $table->string('version_sistema', 50)->nullable();
            $table->string('version_aplicacion', 50)->nullable();
            $table->string('estado', 20)->default('PENDIENTE');
            $table->timestampTz('ultima_sincronizacion_at')->nullable();
            $table->timestampTz('fecha_registro');
            $table->timestampTz('fecha_revocacion')->nullable();
            $table->foreignUuid('revoked_by')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->timestampTz('updated_at'); // ajuste respecto del docx, ver nota arriba
            $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');

            $table->index('user_id');
            $table->index('estado');
        });

        DB::statement("ALTER TABLE dispositivos ADD CONSTRAINT chk_dispositivos_estado CHECK (estado IN ('PENDIENTE','ACTIVO','REVOCADO'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('dispositivos');
    }
};
```

## Verificación de la fase

- [ ] Insertar dos dispositivos con el mismo `identificador_dispositivo` falla por el `UNIQUE` (verificar que el `EloquentDispositivoRepository` traduce la excepción de BD a `DispositivoYaRegistradoException`, no deja pasar un 500 crudo).
- [ ] `estado` fuera del enum falla por el `CHECK`.
- [ ] Eliminar un `usuario` que tiene dispositivos asociados falla por `restrict` en `user_id` (comportamiento esperado — un dispositivo no puede quedar huérfano de dueño).
