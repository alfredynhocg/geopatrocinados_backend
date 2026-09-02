# Etapa 1 — Infraestructura base

> Dependencias: ninguna. Bloquea a todas las demás etapas.
> Referencia: [PLAN_INTEGRACION_PATROCINADOS.md](../../PLAN_INTEGRACION_PATROCINADOS.md) §2.

## Objetivo

Dejar el proyecto listo para migrar y desarrollar contra `pgsql_patrocinados` sin tocar la conexión `mysql` default, y sin que ningún modelo mentabit pueda "heredar" la conexión Postgres por accidente.

## Tareas

### 1.1 Configuración de conexión

- [ ] Agregar bloque `pgsql_patrocinados` en `config/database.php` → `connections` (ver plan de revisión §2 para el snippet completo).
- [ ] Agregar variables a `.env.example`:
  ```
  PATROCINADOS_DB_HOST=127.0.0.1
  PATROCINADOS_DB_PORT=5432
  PATROCINADOS_DB_DATABASE=patrocinados
  PATROCINADOS_DB_USERNAME=postgres
  PATROCINADOS_DB_PASSWORD=
  PATROCINADOS_DB_SSLMODE=prefer
  ```
- [ ] Confirmar en `.env` local (no versionado) los valores reales del Postgres de desarrollo.

### 1.2 Extensión PostGIS

- [ ] Levantar Postgres local con PostGIS (recomendado: imagen Docker `postgis/postgis:16-3.4` en vez de instalar la extensión a mano).
- [ ] Primera migración del módulo, **antes que cualquier otra**:
  ```php
  // database/migrations/patrocinados/2026_09_01_000000_create_postgis_extension.php
  public function up(): void
  {
      DB::connection('pgsql_patrocinados')->statement('CREATE EXTENSION IF NOT EXISTS postgis');
  }

  public function down(): void
  {
      // No hacer DROP EXTENSION en down() — podría tumbar otras columnas GEOGRAPHY si llegaran a existir.
  }
  ```
- [ ] Verificar con `SELECT PostGIS_Version();` contra `pgsql_patrocinados`.

### 1.3 Path de migraciones separado

- [ ] Crear carpeta `database/migrations/patrocinados/`.
- [ ] Confirmar que el comando de migración del módulo es:
  ```bash
  php artisan migrate --path=database/migrations/patrocinados --database=pgsql_patrocinados
  ```
- [ ] Documentar este comando en `CLAUDE.md` (sección nueva del módulo), igual que ya existe el comando `--filter=cenefco` para el legado.

### 1.4 Esqueleto de carpetas DDD

Crear las carpetas vacías (con un `.gitkeep` si el repo lo requiere) para los 6 módulos + auditoría, siguiendo el patrón de `CLAUDE.md`:

```text
app/Domain/AccesoPatrocinados/{Contracts,Exceptions}
app/Domain/Geografia/{Contracts,Exceptions}
app/Domain/Dispositivos/{Contracts,Exceptions}
app/Domain/Patrocinados/{Contracts,Exceptions}
app/Domain/Visitas/{Contracts,Exceptions}
app/Domain/Sincronizacion/{Contracts,Exceptions}
app/Domain/Auditoria/{Contracts,Exceptions}

app/Application/AccesoPatrocinados/{DTOs,Commands,Handlers,Queries,QueryHandlers}
app/Application/Geografia/{DTOs,Commands,Handlers,Queries,QueryHandlers}
app/Application/Dispositivos/{DTOs,Commands,Handlers,Queries,QueryHandlers}
app/Application/Patrocinados/{DTOs,Commands,Handlers,Queries,QueryHandlers}
app/Application/Visitas/{DTOs,Commands,Handlers,Queries,QueryHandlers}
app/Application/Sincronizacion/{DTOs,Commands,Handlers,Queries,QueryHandlers}
app/Application/Auditoria/Services

app/Infrastructure/AccesoPatrocinados/{Models,Repositories}
app/Infrastructure/Geografia/{Models,Repositories}
app/Infrastructure/Dispositivos/{Models,Repositories}
app/Infrastructure/Patrocinados/{Models,Repositories}
app/Infrastructure/Visitas/{Models,Repositories}
app/Infrastructure/Sincronizacion/{Models,Repositories}
app/Infrastructure/Auditoria/{Models,Repositories}

app/Http/Controllers/Api/Patrocinados/
app/Http/Requests/Patrocinados/{AccesoPatrocinados,Geografia,Dispositivos,Patrocinados,Visitas,Sincronizacion}/
```

### 1.5 Base común de modelos

Crear un trait o clase base para no repetir `$connection` en cada modelo:

```php
// app/Infrastructure/Patrocinados/Concerns/UsaConexionPatrocinados.php
trait UsaConexionPatrocinados
{
    public function getConnectionName(): string
    {
        return 'pgsql_patrocinados';
    }
}
```

Todo modelo Eloquent de los 6 módulos usa este trait (o extiende una `ModeloPatrocinados` base abstracta con `use HasUuids, UsaConexionPatrocinados, SoftDeletes` cuando aplique) — así queda imposible olvidar la conexión al crear un modelo nuevo.

### 1.6 Rutas

- [ ] Crear `routes/api/patrocinados.php`.
- [ ] Registrar el `require` en `routes/api.php` (o en `bootstrap/app.php` según cómo esté cableado el resto de `routes/api/v1.php`) bajo el prefijo `patrocinados`.
- [ ] Dejar el archivo con un único endpoint de smoke test al final de esta etapa: `GET /api/v1/patrocinados/ping`.

### 1.7 Registrar el ServiceProvider del módulo (opcional pero recomendado)

Dado que `DomainServiceProvider.php` ya es grande (30+ bindings de mentabit), conviene un provider separado para no mezclar:

```php
// app/Providers/PatrocinadosServiceProvider.php
class PatrocinadosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // bindings de las Etapas 2-8 se agregan aquí, no en DomainServiceProvider
    }
}
```

Registrarlo en `bootstrap/providers.php`.

## Criterios de aceptación (DoD de la etapa)

- [ ] `php artisan tinker --execute="DB::connection('pgsql_patrocinados')->select('select postgis_version()');"` devuelve versión sin error.
- [ ] `php artisan migrate --path=database/migrations/patrocinados --database=pgsql_patrocinados` corre sin tablas aún, sin error de extensión.
- [ ] `GET /api/v1/patrocinados/ping` responde `200 {"status":"ok"}`.
- [ ] Ningún modelo de mentabit fue tocado; `php artisan migrate` (default, MySQL) sigue funcionando exactamente igual que antes de esta etapa.
- [ ] `.env.example` actualizado y commiteado (nunca el `.env` real).
