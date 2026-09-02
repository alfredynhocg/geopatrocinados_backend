# Código completo — Etapa 8: Auditoría transversal

> Complementa [../08-auditoria-transversal.md](../08-auditoria-transversal.md). Código PHP completo, listo para copiar, de cada archivo de la "Estructura DDD" de esa etapa. Fuente de columnas: la migración real `database/migrations/patrocinados/2026_09_01_000060_create_registros_auditoria_table.php`.
>
> **Reglas de negocio implementadas aquí** (no reabrir sin motivo, ver `docs/patrocinados/08-auditoria-transversal.md`):
> 1. **PK `BIGINT` autoincremental, no UUID** — única excepción intencional del módulo completo. `RegistroAuditoria` no usa `HasUuids`; usa el autoincrement estándar de Eloquent (`insertGetId()` implícito en `create()`, nunca `MAX(id)+1`).
> 2. **Insert-only**: no hay Commands/Handlers de update/delete, ni `updated_at` (la tabla solo tiene `created_at`). El único punto de escritura es `AuditoriaService::registrar()`.
> 3. **Nunca aborta la transacción del Handler llamante**: si el insert de auditoría falla, `AuditoriaService::registrar()` lo captura y hace `Log::error()`, pero no relanza la excepción — perder un registro de auditoría se considera preferible a bloquear una operación de negocio (a confirmar con negocio, ver criterios de aceptación del documento madre).
> 4. `Domain/Auditoria/Exceptions/` queda sin archivos — el propio doc madre dice "ninguna necesaria" (módulo de solo lectura/escritura simple, sin reglas de negocio que puedan fallar de forma esperada).

---

## Domain/Auditoria

#### `app/Domain/Auditoria/Contracts/RegistroAuditoriaRepositoryInterface.php`

```php
<?php

namespace App\Domain\Auditoria\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface RegistroAuditoriaRepositoryInterface
{
    public function create(array $data): mixed;

    public function paginate(
        PaginationDTO $pagination,
        ?string $tipoEntidad,
        ?string $entidadId,
        ?string $userId,
        ?string $desde,
        ?string $hasta,
    ): array;
}
```

---

## Application/Auditoria

### DTOs

#### `app/Application/Auditoria/DTOs/RegistroAuditoriaDTO.php`

```php
<?php

namespace App\Application\Auditoria\DTOs;

final readonly class RegistroAuditoriaDTO
{
    public function __construct(
        public int $id,
        public ?string $user_id,
        public ?string $dispositivo_id,
        public string $accion,
        public string $modulo,
        public ?string $tipo_entidad,
        public ?string $entidad_id,
        public ?array $valores_anteriores,
        public ?array $valores_nuevos,
        public ?string $direccion_ip,
        public ?string $user_agent,
        public ?string $created_at,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            user_id: $model->user_id,
            dispositivo_id: $model->dispositivo_id,
            accion: $model->accion,
            modulo: $model->modulo,
            tipo_entidad: $model->tipo_entidad,
            entidad_id: $model->entidad_id,
            valores_anteriores: $model->valores_anteriores,
            valores_nuevos: $model->valores_nuevos,
            direccion_ip: $model->direccion_ip,
            user_agent: $model->user_agent,
            created_at: $model->created_at?->toIso8601String(),
        );
    }
}
```

### Services

#### `app/Application/Auditoria/Services/AuditoriaService.php`

Contrato ya cerrado en el documento madre — implementación tal cual:

```php
<?php

namespace App\Application\Auditoria\Services;

use App\Domain\Auditoria\Contracts\RegistroAuditoriaRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Único punto de entrada de escritura, inyectado en los Handlers de escritura
 * de los demás módulos. Ejemplo de uso (referencia, no un archivo real):
 *
 *   public function handle(RevocarDispositivoCommand $command): DispositivoDTO
 *   {
 *       return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
 *           $anterior = $this->repository->findById($command->dispositivoId);
 *           $model = $this->repository->revocar($command->dispositivoId, $command->revokedBy);
 *
 *           $this->auditoria->registrar(
 *               userId: $command->revokedBy,
 *               dispositivoId: $command->dispositivoId,
 *               accion: 'revocar',
 *               modulo: 'Dispositivos',
 *               tipoEntidad: 'dispositivo',
 *               entidadId: $command->dispositivoId,
 *               valoresAnteriores: ['estado' => $anterior->estado],
 *               valoresNuevos: ['estado' => 'REVOCADO'],
 *           );
 *
 *           return DispositivoDTO::fromModel($model);
 *       });
 *   }
 */
class AuditoriaService
{
    public function __construct(private readonly RegistroAuditoriaRepositoryInterface $repository) {}

    public function registrar(
        ?string $userId,
        ?string $dispositivoId,
        string $accion,
        string $modulo,
        ?string $tipoEntidad,
        ?string $entidadId,
        ?array $valoresAnteriores,
        ?array $valoresNuevos,
    ): void {
        try {
            $this->repository->create([
                'user_id'             => $userId,
                'dispositivo_id'      => $dispositivoId,
                'accion'              => $accion,
                'modulo'              => $modulo,
                'tipo_entidad'        => $tipoEntidad,
                'entidad_id'          => $entidadId,
                'valores_anteriores'  => $valoresAnteriores,
                'valores_nuevos'      => $valoresNuevos,
                'direccion_ip'        => request()?->ip(),
                'user_agent'          => request()?->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Deliberado: un fallo al auditar no debe abortar la operación de
            // negocio que lo disparó. Si negocio exige auditoría obligatoria,
            // cambiar este catch por un re-throw.
            Log::error('AuditoriaService: fallo al registrar auditoría', [
                'accion'  => $accion,
                'modulo'  => $modulo,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
```

### Queries

#### `app/Application/Auditoria/Queries/GetRegistrosAuditoriaQuery.php`

```php
<?php

namespace App\Application\Auditoria\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetRegistrosAuditoriaQuery
{
    public function __construct(
        public PaginationDTO $pagination,
        public ?string $tipo_entidad = null,
        public ?string $entidad_id = null,
        public ?string $user_id = null,
        public ?string $desde = null,
        public ?string $hasta = null,
    ) {}
}
```

### QueryHandlers

#### `app/Application/Auditoria/QueryHandlers/GetRegistrosAuditoriaQueryHandler.php`

```php
<?php

namespace App\Application\Auditoria\QueryHandlers;

use App\Application\Auditoria\DTOs\RegistroAuditoriaDTO;
use App\Application\Auditoria\Queries\GetRegistrosAuditoriaQuery;
use App\Domain\Auditoria\Contracts\RegistroAuditoriaRepositoryInterface;

class GetRegistrosAuditoriaQueryHandler
{
    public function __construct(private readonly RegistroAuditoriaRepositoryInterface $repository) {}

    public function handle(GetRegistrosAuditoriaQuery $query): array
    {
        $paginated = $this->repository->paginate(
            $query->pagination,
            $query->tipo_entidad,
            $query->entidad_id,
            $query->user_id,
            $query->desde,
            $query->hasta,
        );

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => RegistroAuditoriaDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
```

---

## Infrastructure/Auditoria

### Models

#### `app/Infrastructure/Auditoria/Models/RegistroAuditoria.php`

```php
<?php

namespace App\Infrastructure\Auditoria\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Model;

/**
 * Única tabla del módulo con PK bigint autoincremental (no UUID) — insert-only
 * de alto volumen, sin updated_at.
 */
class RegistroAuditoria extends Model
{
    use UsaConexionPatrocinados;

    public const UPDATED_AT = null;

    protected $table = 'registros_auditoria';

    protected $fillable = [
        'user_id', 'dispositivo_id', 'accion', 'modulo', 'tipo_entidad', 'entidad_id',
        'valores_anteriores', 'valores_nuevos', 'direccion_ip', 'user_agent',
    ];

    protected $casts = [
        'valores_anteriores' => 'array',
        'valores_nuevos'     => 'array',
    ];
}
```

### Repositories

#### `app/Infrastructure/Auditoria/Repositories/EloquentRegistroAuditoriaRepository.php`

```php
<?php

namespace App\Infrastructure\Auditoria\Repositories;

use App\Domain\Auditoria\Contracts\RegistroAuditoriaRepositoryInterface;
use App\Infrastructure\Auditoria\Models\RegistroAuditoria;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentRegistroAuditoriaRepository implements RegistroAuditoriaRepositoryInterface
{
    public function create(array $data): mixed
    {
        return RegistroAuditoria::create($data);
    }

    public function paginate(
        PaginationDTO $pagination,
        ?string $tipoEntidad,
        ?string $entidadId,
        ?string $userId,
        ?string $desde,
        ?string $hasta,
    ): array {
        $q = RegistroAuditoria::query();

        if ($tipoEntidad) {
            $q->where('tipo_entidad', $tipoEntidad);
        }
        if ($entidadId) {
            $q->where('entidad_id', $entidadId);
        }
        if ($userId) {
            $q->where('user_id', $userId);
        }
        if ($desde) {
            $q->where('created_at', '>=', $desde);
        }
        if ($hasta) {
            $q->where('created_at', '<=', $hasta);
        }

        $paginated = $q->orderByDesc('created_at')
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return ['data' => $paginated->items(), 'total' => $paginated->total()];
    }
}
```

---

## Http

### Controllers

#### `app/Http/Controllers/Api/Patrocinados/RegistroAuditoriaController.php`

```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Auditoria\Queries\GetRegistrosAuditoriaQuery;
use App\Application\Auditoria\QueryHandlers\GetRegistrosAuditoriaQueryHandler;
use App\Http\Controllers\Controller;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Solo lectura — sin store/update/destroy, la tabla es insert-only vía AuditoriaService. */
class RegistroAuditoriaController extends Controller
{
    public function __construct(private readonly GetRegistrosAuditoriaQueryHandler $getRegistrosHandler) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json($this->getRegistrosHandler->handle(new GetRegistrosAuditoriaQuery(
            pagination: $pagination,
            tipo_entidad: $request->get('tipo_entidad'),
            entidad_id: $request->get('entidad_id'),
            user_id: $request->get('user_id'),
            desde: $request->get('desde'),
            hasta: $request->get('hasta'),
        )));
    }
}
```

---

## Rutas de referencia (para `routes/api/patrocinados.php`, se cablean formalmente en la Etapa 1/9)

```php
Route::get('/registros-auditoria', [RegistroAuditoriaController::class, 'index'])
    ->middleware('permiso-patrocinados:auditoria.ver');
```

## Binding en el ServiceProvider del módulo

```php
// app/Providers/PatrocinadosServiceProvider.php
$this->app->bind(
    \App\Domain\Auditoria\Contracts\RegistroAuditoriaRepositoryInterface::class,
    \App\Infrastructure\Auditoria\Repositories\EloquentRegistroAuditoriaRepository::class,
);
```
