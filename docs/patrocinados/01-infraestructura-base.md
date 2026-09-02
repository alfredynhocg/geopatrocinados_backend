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

## Código completo

#### `config/database.php` (dentro del array `connections`)

```php
'pgsql_patrocinados' => [
    'driver' => 'pgsql',
    'url' => env('PATROCINADOS_DB_URL'),
    'host' => env('PATROCINADOS_DB_HOST', '127.0.0.1'),
    'port' => env('PATROCINADOS_DB_PORT', '5432'),
    'database' => env('PATROCINADOS_DB_DATABASE', 'patrocinados'),
    'username' => env('PATROCINADOS_DB_USERNAME', 'postgres'),
    'password' => env('PATROCINADOS_DB_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'public',
    'sslmode' => env('PATROCINADOS_DB_SSLMODE', 'prefer'),
],
```

#### `.env.example` (agregar al final del bloque de BD)

```env
PATROCINADOS_DB_HOST=127.0.0.1
PATROCINADOS_DB_PORT=5432
PATROCINADOS_DB_DATABASE=patrocinados
PATROCINADOS_DB_USERNAME=postgres
PATROCINADOS_DB_PASSWORD=
PATROCINADOS_DB_SSLMODE=prefer
```

#### `app/Infrastructure/Patrocinados/Concerns/UsaConexionPatrocinados.php`

```php
<?php

namespace App\Infrastructure\Patrocinados\Concerns;

trait UsaConexionPatrocinados
{
    public function getConnectionName(): string
    {
        return 'pgsql_patrocinados';
    }
}
```

> Todo modelo Eloquent de los 6 módulos (`use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;`) importa este trait — así queda imposible olvidar la conexión al crear un modelo nuevo. No se crea una clase base abstracta adicional (`ModeloPatrocinados`): el trait solo, combinado con `HasUuids`/`SoftDeletes` importados directamente en cada modelo que los necesite, ya cumple el objetivo sin una capa de herencia extra que el proyecto no pidió.

#### `app/Providers/PatrocinadosServiceProvider.php`

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class PatrocinadosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Etapa 2 — AccesoPatrocinados
        // $this->app->bind(UsuarioRepositoryInterface::class, EloquentUsuarioRepository::class);
        // $this->app->bind(RolRepositoryInterface::class, EloquentRolRepository::class);
        // $this->app->bind(PermisoRepositoryInterface::class, EloquentPermisoRepository::class);

        // Etapa 3 — Geografia
        // $this->app->bind(DepartamentoRepositoryInterface::class, EloquentDepartamentoRepository::class);
        // $this->app->bind(MunicipioRepositoryInterface::class, EloquentMunicipioRepository::class);
        // $this->app->bind(ComunidadRepositoryInterface::class, EloquentComunidadRepository::class);
        // $this->app->bind(UbicacionRepositoryInterface::class, EloquentUbicacionRepository::class);

        // Etapa 4 — Dispositivos
        // $this->app->bind(DispositivoRepositoryInterface::class, EloquentDispositivoRepository::class);

        // Etapa 5 — Patrocinados
        // $this->app->bind(PatrocinadoRepositoryInterface::class, EloquentPatrocinadoRepository::class);
        // $this->app->bind(TutorRepositoryInterface::class, EloquentTutorRepository::class);
        // $this->app->bind(EstadoPatrocinadoRepositoryInterface::class, EloquentEstadoPatrocinadoRepository::class);
        // $this->app->bind(TipoParentescoRepositoryInterface::class, EloquentTipoParentescoRepository::class);
        // $this->app->bind(HistorialUbicacionRepositoryInterface::class, EloquentHistorialUbicacionRepository::class);

        // Etapa 6 — Visitas
        // $this->app->bind(MotivoVisitaRepositoryInterface::class, EloquentMotivoVisitaRepository::class);
        // $this->app->bind(CategoriaObservacionRepositoryInterface::class, EloquentCategoriaObservacionRepository::class);
        // $this->app->bind(PlanVisitaRepositoryInterface::class, EloquentPlanVisitaRepository::class);
        // $this->app->bind(VisitaRepositoryInterface::class, EloquentVisitaRepository::class);
        // $this->app->bind(AsignacionVisitaRepositoryInterface::class, EloquentAsignacionVisitaRepository::class);
        // $this->app->bind(HabilitacionVisitaRepositoryInterface::class, EloquentHabilitacionVisitaRepository::class);
        // $this->app->bind(UbicacionVisitaRepositoryInterface::class, EloquentUbicacionVisitaRepository::class);
        // $this->app->bind(ObservacionVisitaRepositoryInterface::class, EloquentObservacionVisitaRepository::class);
        // $this->app->bind(FotoVisitaRepositoryInterface::class, EloquentFotoVisitaRepository::class);
        // $this->app->bind(RevisionVisitaRepositoryInterface::class, EloquentRevisionVisitaRepository::class);

        // Etapa 7 — Sincronizacion
        // $this->app->bind(LoteSincronizacionRepositoryInterface::class, EloquentLoteSincronizacionRepository::class);
        // $this->app->bind(ElementoSincronizacionRepositoryInterface::class, EloquentElementoSincronizacionRepository::class);

        // Etapa 8 — Auditoria
        // $this->app->bind(RegistroAuditoriaRepositoryInterface::class, EloquentRegistroAuditoriaRepository::class);
    }
}
```

Cada etapa, al implementarse, descomenta sus propias líneas — así el diff de cada PR muestra exactamente qué módulo se activó.

#### `bootstrap/providers.php` (agregar a la lista)

```php
return [
    // ...providers existentes...
    App\Providers\PatrocinadosServiceProvider::class,
];
```

#### `routes/api/patrocinados.php`

```php
<?php

use App\Http\Controllers\Api\Patrocinados\PingController;
use Illuminate\Support\Facades\Route;

Route::prefix('patrocinados')->group(function () {
    Route::get('/ping', PingController::class);

    // Etapa 2 — auth, usuarios, roles, permisos
    // Etapa 3 — departamentos, municipios, comunidades, ubicaciones
    // Etapa 4 — dispositivos
    // Etapa 5 — ninos (patrocinados), tutores, estados-patrocinados, tipos-parentescos
    // Etapa 6 — planes-visitas, visitas, habilitaciones-visitas
    // Etapa 7 — sincronizacion
    // Etapa 8 — registros-auditoria
});
```

#### `routes/api.php` (agregar el require junto a los demás grupos de rutas)

```php
require __DIR__ . '/api/patrocinados.php';
```

#### `app/Http/Controllers/Api/Patrocinados/PingController.php`

```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PingController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
```

## Criterios de aceptación (DoD de la etapa)

- [ ] `php artisan tinker --execute="DB::connection('pgsql_patrocinados')->select('select postgis_version()');"` devuelve versión sin error.
- [ ] `php artisan migrate --path=database/migrations/patrocinados --database=pgsql_patrocinados` corre sin tablas aún, sin error de extensión.
- [ ] `GET /api/v1/patrocinados/ping` responde `200 {"status":"ok"}`.
- [ ] Ningún modelo de mentabit fue tocado; `php artisan migrate` (default, MySQL) sigue funcionando exactamente igual que antes de esta etapa.
- [ ] `.env.example` actualizado y commiteado (nunca el `.env` real).
