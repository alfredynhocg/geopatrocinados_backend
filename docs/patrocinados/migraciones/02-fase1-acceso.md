# Fase 1 — Acceso (roles, permisos, usuarios)

## 1.1 `roles`

`2026_09_01_000001_create_roles_table.php`

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
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 80)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->uuid('updated_by')->nullable();
            // FK a usuarios se agrega en una migración posterior (add_foreign),
            // porque usuarios.updated_by referencia a usuarios y roles se crea antes que usuarios.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
```

## 1.2 `permisos`

`2026_09_01_000002_create_permisos_table.php`

```php
Schema::create('permisos', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('nombre', 120)->unique();
    $table->string('modulo', 80);
    $table->string('accion', 80);
    $table->string('descripcion', 255)->nullable();
    $table->timestamp('created_at');
    $table->timestamp('updated_at');
    $table->uuid('updated_by')->nullable();
});
```

## 1.3 `usuarios`

`2026_09_01_000003_create_usuarios_table.php`

Enum cerrado para `estado` (ver `docs/patrocinados/02-acceso-patrocinados.md`): `ACTIVO`, `INACTIVO`, `BLOQUEADO`.

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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('username', 80)->unique();
            $table->string('email', 180)->unique();
            $table->text('password_hash');
            $table->string('nombres', 100);
            $table->string('apellidos', 120);
            $table->string('telefono', 40)->nullable();
            $table->string('estado', 20)->default('ACTIVO');
            $table->smallInteger('intentos_fallidos')->default(0);
            $table->timestampTz('bloqueado_hasta')->nullable();
            $table->timestampTz('ultimo_login_at')->nullable();
            $table->timestampTz('password_cambiado_at')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');
            $table->timestampTz('deleted_at')->nullable();
            $table->uuid('updated_by')->nullable();
        });

        // Auto-referencia: se agrega después de crear la tabla para poder declarar la FK sobre sí misma.
        Schema::table('usuarios', function (Blueprint $table) {
            $table->foreign('updated_by')->references('id')->on('usuarios')->onDelete('set null');
        });

        DB::statement("ALTER TABLE usuarios ADD CONSTRAINT chk_usuarios_estado CHECK (estado IN ('ACTIVO','INACTIVO','BLOQUEADO'))");
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
        });
        Schema::dropIfExists('usuarios');
    }
};
```

Ahora que `usuarios` existe, agregar las FK pendientes de `roles` y `permisos`:

`2026_09_01_000004_add_updated_by_foreign_to_roles_and_permisos.php`

```php
Schema::table('roles', function (Blueprint $table) {
    $table->foreign('updated_by')->references('id')->on('usuarios')->onDelete('set null');
});

Schema::table('permisos', function (Blueprint $table) {
    $table->foreign('updated_by')->references('id')->on('usuarios')->onDelete('set null');
});
```

> Nota general de convención: **todas** las FK `updated_by → usuarios.id` del resto de las tablas del módulo (fases 2 a 7) usan `->onDelete('set null')`, no `restrict` — si el usuario que editó un registro se elimina más adelante, el registro no debe quedar bloqueado ni desaparecer, solo pierde la referencia a "quién lo editó". Se aplica igual en todas las fases siguientes sin repetir la nota.

## 1.4 `usuarios_roles`

`2026_09_01_000005_create_usuarios_roles_table.php`

```php
Schema::create('usuarios_roles', function (Blueprint $table) {
    $table->foreignUuid('usuario_id')->constrained('usuarios')->onDelete('cascade');
    $table->foreignUuid('rol_id')->constrained('roles')->onDelete('cascade');
    $table->timestamp('created_at');
    $table->timestamp('updated_at');
    $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');

    $table->primary(['usuario_id', 'rol_id']);
});
```

`onDelete('cascade')` aquí sí es intencional (a diferencia de la regla general `restrict`): si se elimina un usuario o un rol, sus filas de asignación pivote no tienen sentido de mantenerse — es una tabla puramente relacional, no una entidad de negocio con historial propio.

## 1.5 `roles_permisos`

`2026_09_01_000006_create_roles_permisos_table.php`

```php
Schema::create('roles_permisos', function (Blueprint $table) {
    $table->foreignUuid('rol_id')->constrained('roles')->onDelete('cascade');
    $table->foreignUuid('permiso_id')->constrained('permisos')->onDelete('cascade');
    $table->timestamp('created_at');
    $table->timestamp('updated_at');
    $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');

    $table->primary(['rol_id', 'permiso_id']);
});
```

## Verificación de la fase

```bash
php artisan migrate --path=database/migrations/patrocinados
```

- [ ] `\d usuarios` (psql) muestra la FK `updated_by` apuntando a sí misma.
- [ ] Insertar un rol con `estado` inválido (ej. `'X'`) contra `usuarios.estado` falla por el `CHECK` — probarlo con un insert directo de psql, no solo desde Eloquent, para confirmar que la barrera es de BD y no solo de Laravel.
- [ ] Eliminar un usuario con roles asignados borra en cascada sus filas de `usuarios_roles` (comportamiento esperado, distinto del `restrict` general del módulo).
