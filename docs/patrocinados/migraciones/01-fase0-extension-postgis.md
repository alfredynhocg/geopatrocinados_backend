# Fase 0 — Extensión PostGIS

Archivo: `database/migrations/patrocinados/2026_09_01_000000_create_postgis_extension.php`

```php
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
        // Si se necesita revertir, hacerlo manualmente después de confirmar que
        // ninguna tabla depende de tipos PostGIS.
    }
};
```

## Verificación post-migración

```bash
php artisan tinker --execute="dd(DB::connection('pgsql_patrocinados')->select('select postgis_version()'));"
```

Debe devolver la versión de PostGIS sin error. Si falla con `permission denied to create extension`, el usuario de BD de la app no tiene privilegios de superusuario — la extensión debe crearse una vez, manualmente, por el DBA/infra (`psql -c "CREATE EXTENSION postgis;"` conectado como superusuario), y esta migración queda como no-op documentando el requisito (cambiar el `up()` por un `Schema::hasTable(...)`-style check que solo verifique, no intente crear).
