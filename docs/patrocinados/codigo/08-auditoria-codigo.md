# Código completo — Etapa 8: Auditoría transversal

> Fuente de verdad de columnas: `database/migrations/patrocinados/2026_09_01_000060_create_registros_auditoria_table.php`.
> Estructura DDD acordada: [../08-auditoria-transversal.md](../08-auditoria-transversal.md).
> Tabla `registros_auditoria`: PK `BIGINT` autoincremental (única excepción a UUID en el módulo), `user_id`/`dispositivo_id` UUID nullable con `onDelete('set null')`, `entidad_id` UUID nullable, `valores_anteriores`/`valores_nuevos` JSONB nullable, `direccion_ip` inet nullable, `user_agent` text nullable, solo `created_at` (tabla insert-only, sin `updated_at`).

---

## Domain/Auditoria

#### `app/Domain/Auditoria/Contracts/RegistroAuditoriaRepositoryInterface.php`

```php
<?php

namespace App\Domain\Auditoria\Contracts;

use App\Infrastructure\Auditoria\Models\RegistroAuditoria;

interface RegistroAuditoriaRepositoryInterface
{
    /**
     * Inserta un registro de auditoría. Nunca genera el id manualmente:
     * delega en el autoincremental BIGINT de la tabla (insertGetId vía Eloquent::create()).
     */
    public function create(array $data): RegistroAuditoria;

    /**
     * Listado paginado con filtros. Devuelve ['data' => RegistroAuditoriaDTO[], 'total' => int]
     * siguiendo la misma forma de respuesta que el resto del proyecto (ver CLAUDE.md).
     */
    public function paginate(
        ?string $tipoEntidad,
        ?string $entidadId,
        ?string $userId,
        ?string $desde,
        ?string $hasta,
        int $pageIndex,
        int $pageSize,
    ): array;
}
```

> No hay `Exceptions/` en este módulo — `docs/patrocinados/08-auditoria-transversal.md` lo deja explícito: es un módulo de solo escritura interna (vía `AuditoriaService`) y lectura pública, sin reglas de negocio que puedan fallar con una excepción de dominio propia.

---

## Application/Auditoria

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

#### `app/Application/Auditoria/Services/AuditoriaService.php`

```php
<?php

namespace App\Application\Auditoria\Services;

use App\Domain\Auditoria\Contracts\RegistroAuditoriaRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Único punto de entrada de auditoría para los Handlers de escritura de los 6 módulos
 * del sistema de Patrocinados. Ver docs/patrocinados/08-auditoria-transversal.md
 * "Qué se audita (mínimo obligatorio por módulo)" para el catálogo de operaciones.
 */
class AuditoriaService
{
    public function __construct(
        private readonly RegistroAuditoriaRepositoryInterface $repository
    ) {}

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
            // insertGetId vía Eloquent::create() — nunca MAX(id)+1, misma regla irrompible
            // que el resto del proyecto (ver CLAUDE.md, módulo Pagos).
            $this->repository->create([
                'user_id' => $userId,
                'dispositivo_id' => $dispositivoId,
                'accion' => $accion,
                'modulo' => $modulo,
                'tipo_entidad' => $tipoEntidad,
                'entidad_id' => $entidadId,
                'valores_anteriores' => $valoresAnteriores,
                'valores_nuevos' => $valoresNuevos,
                'direccion_ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        } catch (Throwable $e) {
            // DECISIÓN PENDIENTE (ver docs/patrocinados/08-auditoria-transversal.md, Criterios de
            // aceptación): confirmar con negocio si perder un registro de auditoría es aceptable
            // antes que bloquear la operación principal, o si AuditoriaService debe relanzar la
            // excepción para abortar la transacción del Handler que la llamó.
            Log::error('AuditoriaService: fallo al registrar auditoría', [
                'user_id' => $userId,
                'dispositivo_id' => $dispositivoId,
                'accion' => $accion,
                'modulo' => $modulo,
                'tipo_entidad' => $tipoEntidad,
                'entidad_id' => $entidadId,
                'valores_anteriores' => $valoresAnteriores,
                'valores_nuevos' => $valoresNuevos,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
```

#### `app/Application/Auditoria/Queries/GetRegistrosAuditoriaQuery.php`

```php
<?php

namespace App\Application\Auditoria\Queries;

final readonly class GetRegistrosAuditoriaQuery
{
    public function __construct(
        public ?string $tipoEntidad = null,
        public ?string $entidadId = null,
        public ?string $userId = null,
        public ?string $desde = null,
        public ?string $hasta = null,
        public int $pageIndex = 1,
        public int $pageSize = 15,
    ) {}
}
```

#### `app/Application/Auditoria/QueryHandlers/GetRegistrosAuditoriaQueryHandler.php`

```php
<?php

namespace App\Application\Auditoria\QueryHandlers;

use App\Application\Auditoria\Queries\GetRegistrosAuditoriaQuery;
use App\Domain\Auditoria\Contracts\RegistroAuditoriaRepositoryInterface;

class GetRegistrosAuditoriaQueryHandler
{
    public function __construct(
        private readonly RegistroAuditoriaRepositoryInterface $repository
    ) {}

    public function handle(GetRegistrosAuditoriaQuery $query): array
    {
        return $this->repository->paginate(
            tipoEntidad: $query->tipoEntidad,
            entidadId: $query->entidadId,
            userId: $query->userId,
            desde: $query->desde,
            hasta: $query->hasta,
            pageIndex: $query->pageIndex,
            pageSize: $query->pageSize,
        );
    }
}
```

---

## Infrastructure/Auditoria

#### `app/Infrastructure/Auditoria/Models/RegistroAuditoria.php`

```php
<?php

namespace App\Infrastructure\Auditoria\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Model;

class RegistroAuditoria extends Model
{
    use UsaConexionPatrocinados;

    protected $table = 'registros_auditoria';

    // Tabla insert-only: solo existe created_at en el schema, no updated_at.
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'dispositivo_id',
        'accion',
        'modulo',
        'tipo_entidad',
        'entidad_id',
        'valores_anteriores',
        'valores_nuevos',
        'direccion_ip',
        'user_agent',
    ];

    protected $casts = [
        'valores_anteriores' => 'array',
        'valores_nuevos' => 'array',
        'created_at' => 'datetime',
    ];
}
```

> No usa `HasUuids` (PK autoincremental estándar) ni `SoftDeletes` (tabla insert-only, nunca se borra un registro de auditoría) — a diferencia del resto de modelos del módulo. Ver `app/Infrastructure/Patrocinados/Concerns/UsaConexionPatrocinados.php` (Etapa 1) para el trait de conexión.

#### `app/Infrastructure/Auditoria/Repositories/EloquentRegistroAuditoriaRepository.php`

```php
<?php

namespace App\Infrastructure\Auditoria\Repositories;

use App\Application\Auditoria\DTOs\RegistroAuditoriaDTO;
use App\Domain\Auditoria\Contracts\RegistroAuditoriaRepositoryInterface;
use App\Infrastructure\Auditoria\Models\RegistroAuditoria;

class EloquentRegistroAuditoriaRepository implements RegistroAuditoriaRepositoryInterface
{
    public function create(array $data): RegistroAuditoria
    {
        return RegistroAuditoria::create($data);
    }

    public function paginate(
        ?string $tipoEntidad,
        ?string $entidadId,
        ?string $userId,
        ?string $desde,
        ?string $hasta,
        int $pageIndex,
        int $pageSize,
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

        $paginated = $q->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $pageIndex);

        return [
            'data' => collect($paginated->items())
                ->map(fn ($r) => RegistroAuditoriaDTO::fromModel($r))
                ->all(),
            'total' => $paginated->total(),
        ];
    }
}
```

---

## Http

#### `app/Http/Requests/Patrocinados/Auditoria/IndexRegistroAuditoriaRequest.php`

```php
<?php

namespace App\Http\Requests\Patrocinados\Auditoria;

use Illuminate\Foundation\Http\FormRequest;

class IndexRegistroAuditoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autorización real vía middleware ->middleware('permiso-patrocinados:auditoria.ver')
        // en routes/api/patrocinados.php — este FormRequest solo valida forma de los filtros.
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_entidad' => ['sometimes', 'nullable', 'string', 'max:100'],
            'entidad_id'   => ['sometimes', 'nullable', 'uuid'],
            'user_id'      => ['sometimes', 'nullable', 'uuid'],
            'desde'        => ['sometimes', 'nullable', 'date'],
            'hasta'        => ['sometimes', 'nullable', 'date', 'after_or_equal:desde'],
            'pageIndex'    => ['sometimes', 'integer', 'min:1'],
            'pageSize'     => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
```

#### `app/Http/Controllers/Api/Patrocinados/RegistroAuditoriaController.php`

```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Auditoria\QueryHandlers\GetRegistrosAuditoriaQueryHandler;
use App\Application\Auditoria\Queries\GetRegistrosAuditoriaQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Auditoria\IndexRegistroAuditoriaRequest;
use Illuminate\Http\JsonResponse;

/**
 * Solo lectura — sin store/update/destroy. Los registros de auditoría se crean
 * exclusivamente vía AuditoriaService::registrar() desde los Handlers de otros módulos.
 */
class RegistroAuditoriaController extends Controller
{
    public function __construct(
        private readonly GetRegistrosAuditoriaQueryHandler $getRegistrosHandler,
    ) {}

    public function index(IndexRegistroAuditoriaRequest $request): JsonResponse
    {
        $query = new GetRegistrosAuditoriaQuery(
            tipoEntidad: $request->get('tipo_entidad'),
            entidadId: $request->get('entidad_id'),
            userId: $request->get('user_id'),
            desde: $request->get('desde'),
            hasta: $request->get('hasta'),
            pageIndex: (int) $request->get('pageIndex', 1),
            pageSize: (int) $request->get('pageSize', 15),
        );

        return response()->json($this->getRegistrosHandler->handle($query));
    }
}
```

---

## Binding en el ServiceProvider del módulo

```php
// app/Providers/PatrocinadosServiceProvider.php
$this->app->bind(
    \App\Domain\Auditoria\Contracts\RegistroAuditoriaRepositoryInterface::class,
    \App\Infrastructure\Auditoria\Repositories\EloquentRegistroAuditoriaRepository::class,
);
```

`AuditoriaService` no requiere binding explícito: Laravel lo resuelve por autowiring (tiene un único constructor con una dependencia ya bindeada).

---

## Ejemplo de uso desde un Handler de otro módulo

Reproducido de `docs/patrocinados/08-auditoria-transversal.md` — cualquier Handler de escritura de las Etapas 2-7 inyecta `AuditoriaService` igual que inyecta su repositorio:

```php
// app/Application/Dispositivos/Handlers/RevocarDispositivoHandler.php
class RevocarDispositivoHandler
{
    public function __construct(
        private readonly DispositivoRepositoryInterface $repository,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function handle(RevocarDispositivoCommand $command): DispositivoDTO
    {
        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $anterior = $this->repository->findById($command->dispositivoId);

            $model = $this->repository->revocar($command->dispositivoId, $command->revokedBy);

            $this->auditoria->registrar(
                userId: $command->revokedBy,
                dispositivoId: $command->dispositivoId,
                accion: 'revocar',
                modulo: 'Dispositivos',
                tipoEntidad: 'dispositivo',
                entidadId: $command->dispositivoId,
                valoresAnteriores: ['estado' => $anterior->estado],
                valoresNuevos: ['estado' => 'REVOCADO'],
            );

            return DispositivoDTO::fromModel($model);
        });
    }
}
```

Regla general: la llamada a `$this->auditoria->registrar()` va **dentro** de la misma `DB::transaction()` que la escritura de negocio (no después, no en un `finally`) — así, si la transacción hace rollback por otro motivo, el intento de auditoría también se revierte y no queda un registro huérfano de una operación que nunca se confirmó.
