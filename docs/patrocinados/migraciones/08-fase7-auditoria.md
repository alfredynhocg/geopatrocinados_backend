# Fase 7 — Auditoría transversal

## 7.1 `registros_auditoria`

`2026_09_01_000060_create_registros_auditoria_table.php`

PK `BIGINT` autoincremental — única excepción intencional a la regla de UUID del resto del módulo (ver plan de revisión §8, acierto de diseño validado, no un descuido): es una tabla insert-only de alto volumen, y un PK secuencial evita la fragmentación de índice que causaría un UUID aleatorio en una tabla así.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_patrocinados';

    public function up(): void
    {
        Schema::create('registros_auditoria', function (Blueprint $table) {
            $table->id(); // BIGINT autoincremental — intencional, ver nota arriba
            $table->foreignUuid('user_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->foreignUuid('dispositivo_id')->nullable()->constrained('dispositivos')->onDelete('set null');
            $table->string('accion', 80);
            $table->string('modulo', 80);
            $table->string('tipo_entidad', 100)->nullable();
            $table->uuid('entidad_id')->nullable();
            $table->jsonb('valores_anteriores')->nullable();
            $table->jsonb('valores_nuevos')->nullable();
            $table->ipAddress('direccion_ip')->nullable(); // mapea a `inet` en el grammar de Postgres
            $table->text('user_agent')->nullable();
            $table->timestampTz('created_at');

            $table->index(['tipo_entidad', 'entidad_id']);
            $table->index('user_id');
            $table->index('modulo');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_auditoria');
    }
};
```

Notas:
- Sin `updated_at`: es un log append-only, un registro de auditoría nunca se edita después de creado.
- `entidad_id UUID` sin FK real, igual razón que `elementos_sincronizacion.entidad_id` (Fase 6): referencia filas de tablas distintas según `tipo_entidad`, no expresable como FK única.
- El índice `(tipo_entidad, entidad_id)` es el que sostiene la query más común: "historial de cambios de esta entidad puntual" (ej. una visita, un patrocinado).
- Si el volumen esperado es alto desde el día 1 (muchas visitas/día × varias acciones auditadas cada una), evaluar particionar `registros_auditoria` por rango de `created_at` (partición nativa de Postgres) antes de que la tabla crezca sin control — no es necesario para el lanzamiento inicial, mencionarlo aquí para no olvidarlo si el volumen real supera lo estimado.

## Verificación de la fase

- [ ] Insertar un registro con `direccion_ip = '192.168.1.10'` se guarda correctamente como tipo `inet` (`\d registros_auditoria` en psql debe mostrar `direccion_ip | inet`).
- [ ] `valores_anteriores`/`valores_nuevos` aceptan JSON arbitrario y se pueden consultar con operadores `->`/`->>`/`@>` de Postgres (útil para reportes futuros sin tener que deserializar en PHP).
- [ ] Eliminar un `usuario` o `dispositivo` referenciado en auditoría no borra ni bloquea el registro — solo pone `user_id`/`dispositivo_id` en `NULL` (`set null`), preservando el historial.

---

## Cierre de la implementación de BD

Con las 8 fases migradas (`0` a `7`), las 25 tablas del docx + los 3 ajustes documentados en `00-indice.md` (updated_at en `dispositivos` y `lotes_sincronizacion`, `intentos_reprogramacion` en `visitas`) quedan creadas en `pgsql_patrocinados` en el orden correcto de dependencias, con:

- Todos los `CHECK constraints` de enums cerrados.
- Los 2 índices únicos parciales que refuerzan las reglas de sincronía "estado actual + historial" identificadas en el plan de revisión (`asignaciones_visitas`, `habilitaciones_visitas`) más el de `historial_ubicaciones`.
- Índices `GIST` en las 2 columnas `GEOGRAPHY`.
- La FK de `tutores` a `tipos_parentescos` reemplazando el campo de texto libre del docx.

Siguiente paso: implementar los Modelos Eloquent + Repositories de `docs/patrocinados/01-...09-...md` sobre este schema ya migrado, empezando por la Etapa 2 (Acceso) como indica la hoja de ruta.
