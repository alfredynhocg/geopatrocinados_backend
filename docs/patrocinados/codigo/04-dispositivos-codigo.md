# Código completo — Etapa 4: Dispositivos

> Complementa [../04-dispositivos.md](../04-dispositivos.md). Código PHP completo, listo para copiar, de cada archivo de la "Estructura DDD" de esa etapa. Fuente de columnas: la migración real `database/migrations/patrocinados/2026_09_01_000020_create_dispositivos_table.php`.
>
> **Reglas de negocio implementadas aquí** (no reabrir sin motivo, ver `docs/patrocinados/04-dispositivos.md`):
> 1. **Ciclo de vida vía Commands separados**, no un `UpdateDispositivoCommand` genérico para el estado: `RegistrarDispositivoCommand` (alta, estado inicial `PENDIENTE`), `AprobarDispositivoCommand` (`PENDIENTE`→`ACTIVO`), `RevocarDispositivoCommand` (`ACTIVO`→`REVOCADO`, setea `fecha_revocacion`/`revoked_by`), `UpdateDispositivoCommand` (solo campos descriptivos — nunca el estado).
> 2. **Duplicado de `identificador_dispositivo`**: `EloquentDispositivoRepository::create()` captura el `QueryException` de Postgres (SQLSTATE `23505`, unique violation) y lo relanza como `DispositivoYaRegistradoException` (422) — nunca deja escapar un 500 de constraint sin capturar.
> 3. **Auditoría pendiente**: `AprobarDispositivoHandler`/`RevocarDispositivoHandler` dejan un `// TODO` explícito para conectar `AuditoriaService` cuando la Etapa 8 esté implementada — no se inventa el servicio acá.
> 4. La tabla `dispositivos` **no tiene columna `created_at`** en el docx (solo `fecha_registro` + `updated_at`) — el modelo `Dispositivo` deshabilita el manejo automático de `created_at` de Eloquent (`const CREATED_AT = null;`).

---

## Domain/Dispositivos

#### `app/Domain/Dispositivos/Contracts/DispositivoRepositoryInterface.php`

```php
<?php

namespace App\Domain\Dispositivos\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface DispositivoRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $userId, ?string $estado): array;

    public function findById(string $id): mixed;

    /** Lanza DispositivoYaRegistradoException si identificador_dispositivo ya existe. */
    public function create(array $data): mixed;

    public function update(string $id, array $data): mixed;

    public function aprobar(string $id): mixed;

    public function revocar(string $id, string $revokedBy): mixed;
}
```

#### `app/Domain/Dispositivos/Exceptions/DispositivoNotFoundException.php`

```php
<?php

namespace App\Domain\Dispositivos\Exceptions;

class DispositivoNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Dispositivo '{$id}' no encontrado.", 404);
    }
}
```

#### `app/Domain/Dispositivos/Exceptions/DispositivoYaRegistradoException.php`

```php
<?php

namespace App\Domain\Dispositivos\Exceptions;

class DispositivoYaRegistradoException extends \RuntimeException
{
    public function __construct(string $identificadorDispositivo)
    {
        parent::__construct("El dispositivo '{$identificadorDispositivo}' ya está registrado.", 422);
    }
}
```

#### `app/Domain/Dispositivos/Exceptions/DispositivoRevocadoException.php`

```php
<?php

namespace App\Domain\Dispositivos\Exceptions;

class DispositivoRevocadoException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("El dispositivo '{$id}' está revocado y no puede usarse.", 403);
    }
}
```

---

## Application/Dispositivos

### DTOs

#### `app/Application/Dispositivos/DTOs/DispositivoDTO.php`

```php
<?php

namespace App\Application\Dispositivos\DTOs;

final readonly class DispositivoDTO
{
    public function __construct(
        public string $id,
        public string $user_id,
        public string $identificador_dispositivo,
        public ?string $nombre_dispositivo,
        public string $plataforma,
        public ?string $version_sistema,
        public ?string $version_aplicacion,
        public string $estado,
        public ?string $ultima_sincronizacion_at,
        public string $fecha_registro,
        public ?string $fecha_revocacion,
        public ?string $revoked_by,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            user_id: $model->user_id,
            identificador_dispositivo: $model->identificador_dispositivo,
            nombre_dispositivo: $model->nombre_dispositivo,
            plataforma: $model->plataforma,
            version_sistema: $model->version_sistema,
            version_aplicacion: $model->version_aplicacion,
            estado: $model->estado,
            ultima_sincronizacion_at: $model->ultima_sincronizacion_at?->toIso8601String(),
            fecha_registro: $model->fecha_registro->toIso8601String(),
            fecha_revocacion: $model->fecha_revocacion?->toIso8601String(),
            revoked_by: $model->revoked_by,
        );
    }
}
```

### Commands

#### `app/Application/Dispositivos/Commands/RegistrarDispositivoCommand.php`

```php
<?php

namespace App\Application\Dispositivos\Commands;

final readonly class RegistrarDispositivoCommand
{
    public function __construct(
        public string $user_id,
        public string $identificador_dispositivo,
        public ?string $nombre_dispositivo,
        public string $plataforma,
        public ?string $version_sistema,
        public ?string $version_aplicacion,
    ) {}
}
```

#### `app/Application/Dispositivos/Commands/AprobarDispositivoCommand.php`

```php
<?php

namespace App\Application\Dispositivos\Commands;

final readonly class AprobarDispositivoCommand
{
    public function __construct(public string $id) {}
}
```

#### `app/Application/Dispositivos/Commands/RevocarDispositivoCommand.php`

```php
<?php

namespace App\Application\Dispositivos\Commands;

final readonly class RevocarDispositivoCommand
{
    public function __construct(
        public string $id,
        public string $revoked_by,
    ) {}
}
```

#### `app/Application/Dispositivos/Commands/UpdateDispositivoCommand.php`

```php
<?php

namespace App\Application\Dispositivos\Commands;

/** Deliberadamente sin `estado`: el ciclo de vida se cambia vía Aprobar/Revocar. */
final readonly class UpdateDispositivoCommand
{
    public function __construct(
        public string $id,
        public ?string $nombre_dispositivo,
        public ?string $version_sistema,
        public ?string $version_aplicacion,
    ) {}
}
```

### Handlers

#### `app/Application/Dispositivos/Handlers/RegistrarDispositivoHandler.php`

```php
<?php

namespace App\Application\Dispositivos\Handlers;

use App\Application\Dispositivos\Commands\RegistrarDispositivoCommand;
use App\Application\Dispositivos\DTOs\DispositivoDTO;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;

class RegistrarDispositivoHandler
{
    public function __construct(private readonly DispositivoRepositoryInterface $repository) {}

    public function handle(RegistrarDispositivoCommand $command): DispositivoDTO
    {
        $model = $this->repository->create([
            'user_id'                    => $command->user_id,
            'identificador_dispositivo'  => $command->identificador_dispositivo,
            'nombre_dispositivo'         => $command->nombre_dispositivo,
            'plataforma'                 => $command->plataforma,
            'version_sistema'            => $command->version_sistema,
            'version_aplicacion'         => $command->version_aplicacion,
            'estado'                     => 'PENDIENTE',
            'fecha_registro'             => now(),
        ]);

        return DispositivoDTO::fromModel($model);
    }
}
```

#### `app/Application/Dispositivos/Handlers/AprobarDispositivoHandler.php`

```php
<?php

namespace App\Application\Dispositivos\Handlers;

use App\Application\Dispositivos\Commands\AprobarDispositivoCommand;
use App\Application\Dispositivos\DTOs\DispositivoDTO;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;

class AprobarDispositivoHandler
{
    public function __construct(private readonly DispositivoRepositoryInterface $repository) {}

    public function handle(AprobarDispositivoCommand $command): DispositivoDTO
    {
        $model = $this->repository->aprobar($command->id);

        // TODO: registrar en registros_auditoria vía AuditoriaService cuando la Etapa 8 esté implementada.

        return DispositivoDTO::fromModel($model);
    }
}
```

#### `app/Application/Dispositivos/Handlers/RevocarDispositivoHandler.php`

```php
<?php

namespace App\Application\Dispositivos\Handlers;

use App\Application\Dispositivos\Commands\RevocarDispositivoCommand;
use App\Application\Dispositivos\DTOs\DispositivoDTO;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;

class RevocarDispositivoHandler
{
    public function __construct(private readonly DispositivoRepositoryInterface $repository) {}

    public function handle(RevocarDispositivoCommand $command): DispositivoDTO
    {
        $model = $this->repository->revocar($command->id, $command->revoked_by);

        // TODO: registrar en registros_auditoria vía AuditoriaService cuando la Etapa 8 esté implementada.

        return DispositivoDTO::fromModel($model);
    }
}
```

#### `app/Application/Dispositivos/Handlers/UpdateDispositivoHandler.php`

```php
<?php

namespace App\Application\Dispositivos\Handlers;

use App\Application\Dispositivos\Commands\UpdateDispositivoCommand;
use App\Application\Dispositivos\DTOs\DispositivoDTO;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;

class UpdateDispositivoHandler
{
    public function __construct(private readonly DispositivoRepositoryInterface $repository) {}

    public function handle(UpdateDispositivoCommand $command): DispositivoDTO
    {
        $model = $this->repository->update($command->id, [
            'nombre_dispositivo' => $command->nombre_dispositivo,
            'version_sistema'    => $command->version_sistema,
            'version_aplicacion' => $command->version_aplicacion,
        ]);

        return DispositivoDTO::fromModel($model);
    }
}
```

### Queries

#### `app/Application/Dispositivos/Queries/GetDispositivosQuery.php`

```php
<?php

namespace App\Application\Dispositivos\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetDispositivosQuery
{
    public function __construct(
        public PaginationDTO $pagination,
        public ?string $user_id = null,
        public ?string $estado = null,
    ) {}
}
```

#### `app/Application/Dispositivos/Queries/GetDispositivoByIdQuery.php`

```php
<?php

namespace App\Application\Dispositivos\Queries;

final readonly class GetDispositivoByIdQuery
{
    public function __construct(public string $id) {}
}
```

### QueryHandlers

#### `app/Application/Dispositivos/QueryHandlers/GetDispositivosQueryHandler.php`

```php
<?php

namespace App\Application\Dispositivos\QueryHandlers;

use App\Application\Dispositivos\DTOs\DispositivoDTO;
use App\Application\Dispositivos\Queries\GetDispositivosQuery;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;

class GetDispositivosQueryHandler
{
    public function __construct(private readonly DispositivoRepositoryInterface $repository) {}

    public function handle(GetDispositivosQuery $query): array
    {
        $paginated = $this->repository->paginate($query->pagination, $query->user_id, $query->estado);

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => DispositivoDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
```

#### `app/Application/Dispositivos/QueryHandlers/GetDispositivoByIdQueryHandler.php`

```php
<?php

namespace App\Application\Dispositivos\QueryHandlers;

use App\Application\Dispositivos\DTOs\DispositivoDTO;
use App\Application\Dispositivos\Queries\GetDispositivoByIdQuery;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;

class GetDispositivoByIdQueryHandler
{
    public function __construct(private readonly DispositivoRepositoryInterface $repository) {}

    public function handle(GetDispositivoByIdQuery $query): DispositivoDTO
    {
        return DispositivoDTO::fromModel($this->repository->findById($query->id));
    }
}
```

---

## Infrastructure/Dispositivos

### Models

#### `app/Infrastructure/Dispositivos/Models/Dispositivo.php`

```php
<?php

namespace App\Infrastructure\Dispositivos\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Sin columna created_at en el docx (solo fecha_registro + updated_at). */
class Dispositivo extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    public const CREATED_AT = null;

    protected $table = 'dispositivos';

    protected $fillable = [
        'user_id', 'identificador_dispositivo', 'nombre_dispositivo', 'plataforma',
        'version_sistema', 'version_aplicacion', 'estado', 'ultima_sincronizacion_at',
        'fecha_registro', 'fecha_revocacion', 'revoked_by', 'updated_by',
    ];

    protected $casts = [
        'ultima_sincronizacion_at' => 'datetime',
        'fecha_registro'           => 'datetime',
        'fecha_revocacion'         => 'datetime',
    ];
}
```

### Repositories

#### `app/Infrastructure/Dispositivos/Repositories/EloquentDispositivoRepository.php`

```php
<?php

namespace App\Infrastructure\Dispositivos\Repositories;

use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;
use App\Domain\Dispositivos\Exceptions\DispositivoNotFoundException;
use App\Domain\Dispositivos\Exceptions\DispositivoYaRegistradoException;
use App\Infrastructure\Dispositivos\Models\Dispositivo;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Database\QueryException;

class EloquentDispositivoRepository implements DispositivoRepositoryInterface
{
    private const SQLSTATE_UNIQUE_VIOLATION = '23505';

    public function paginate(PaginationDTO $pagination, ?string $userId, ?string $estado): array
    {
        $q = Dispositivo::query();

        if ($userId) {
            $q->where('user_id', $userId);
        }
        if ($estado) {
            $q->where('estado', $estado);
        }

        $paginated = $q->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'fecha_registro', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return ['data' => $paginated->items(), 'total' => $paginated->total()];
    }

    public function findById(string $id): mixed
    {
        $dispositivo = Dispositivo::find($id);

        if (! $dispositivo) {
            throw new DispositivoNotFoundException($id);
        }

        return $dispositivo;
    }

    public function create(array $data): mixed
    {
        try {
            return Dispositivo::create($data);
        } catch (QueryException $e) {
            if ($e->getCode() === self::SQLSTATE_UNIQUE_VIOLATION) {
                throw new DispositivoYaRegistradoException($data['identificador_dispositivo']);
            }

            throw $e;
        }
    }

    public function update(string $id, array $data): mixed
    {
        $dispositivo = $this->findById($id);
        $dispositivo->update($data);

        return $dispositivo->fresh();
    }

    public function aprobar(string $id): mixed
    {
        $dispositivo = $this->findById($id);
        $dispositivo->update(['estado' => 'ACTIVO']);

        return $dispositivo->fresh();
    }

    public function revocar(string $id, string $revokedBy): mixed
    {
        $dispositivo = $this->findById($id);
        $dispositivo->update([
            'estado'           => 'REVOCADO',
            'fecha_revocacion' => now(),
            'revoked_by'       => $revokedBy,
        ]);

        return $dispositivo->fresh();
    }
}
```

---

## Http

### Controllers

#### `app/Http/Controllers/Api/Patrocinados/DispositivoController.php`

```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Dispositivos\Commands\AprobarDispositivoCommand;
use App\Application\Dispositivos\Commands\RegistrarDispositivoCommand;
use App\Application\Dispositivos\Commands\RevocarDispositivoCommand;
use App\Application\Dispositivos\Commands\UpdateDispositivoCommand;
use App\Application\Dispositivos\Handlers\AprobarDispositivoHandler;
use App\Application\Dispositivos\Handlers\RegistrarDispositivoHandler;
use App\Application\Dispositivos\Handlers\RevocarDispositivoHandler;
use App\Application\Dispositivos\Handlers\UpdateDispositivoHandler;
use App\Application\Dispositivos\Queries\GetDispositivoByIdQuery;
use App\Application\Dispositivos\Queries\GetDispositivosQuery;
use App\Application\Dispositivos\QueryHandlers\GetDispositivoByIdQueryHandler;
use App\Application\Dispositivos\QueryHandlers\GetDispositivosQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Dispositivos\AprobarDispositivoRequest;
use App\Http\Requests\Patrocinados\Dispositivos\RegistrarDispositivoRequest;
use App\Http\Requests\Patrocinados\Dispositivos\RevocarDispositivoRequest;
use App\Http\Requests\Patrocinados\Dispositivos\UpdateDispositivoRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DispositivoController extends Controller
{
    public function __construct(
        private readonly GetDispositivosQueryHandler $getDispositivosHandler,
        private readonly GetDispositivoByIdQueryHandler $getDispositivoByIdHandler,
        private readonly RegistrarDispositivoHandler $registrarHandler,
        private readonly UpdateDispositivoHandler $updateHandler,
        private readonly AprobarDispositivoHandler $aprobarHandler,
        private readonly RevocarDispositivoHandler $revocarHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json($this->getDispositivosHandler->handle(new GetDispositivosQuery(
            pagination: $pagination,
            user_id: $request->get('user_id'),
            estado: $request->get('estado'),
        )));
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->getDispositivoByIdHandler->handle(new GetDispositivoByIdQuery($id)));
    }

    public function store(RegistrarDispositivoRequest $request): JsonResponse
    {
        $dto = $this->registrarHandler->handle(new RegistrarDispositivoCommand(
            user_id: auth()->id(),
            identificador_dispositivo: $request->identificador_dispositivo,
            nombre_dispositivo: $request->nombre_dispositivo,
            plataforma: $request->plataforma,
            version_sistema: $request->version_sistema,
            version_aplicacion: $request->version_aplicacion,
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateDispositivoRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateDispositivoCommand(
            id: $id,
            nombre_dispositivo: $request->nombre_dispositivo,
            version_sistema: $request->version_sistema,
            version_aplicacion: $request->version_aplicacion,
        ));

        return response()->json($dto);
    }

    public function aprobar(AprobarDispositivoRequest $request, string $id): JsonResponse
    {
        return response()->json($this->aprobarHandler->handle(new AprobarDispositivoCommand($id)));
    }

    public function revocar(RevocarDispositivoRequest $request, string $id): JsonResponse
    {
        return response()->json($this->revocarHandler->handle(new RevocarDispositivoCommand(
            id: $id,
            revoked_by: auth()->id(),
        )));
    }
}
```

### Requests

#### `app/Http/Requests/Patrocinados/Dispositivos/RegistrarDispositivoRequest.php`

```php
<?php

namespace App\Http\Requests\Patrocinados\Dispositivos;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarDispositivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identificador_dispositivo' => ['required', 'string', 'max:180'],
            'nombre_dispositivo'        => ['nullable', 'string', 'max:150'],
            'plataforma'                => ['required', 'string', 'max:30'],
            'version_sistema'           => ['nullable', 'string', 'max:50'],
            'version_aplicacion'        => ['nullable', 'string', 'max:50'],
        ];
    }
}
```

#### `app/Http/Requests/Patrocinados/Dispositivos/UpdateDispositivoRequest.php`

```php
<?php

namespace App\Http\Requests\Patrocinados\Dispositivos;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDispositivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_dispositivo' => ['nullable', 'string', 'max:150'],
            'version_sistema'    => ['nullable', 'string', 'max:50'],
            'version_aplicacion' => ['nullable', 'string', 'max:50'],
        ];
    }
}
```

#### `app/Http/Requests/Patrocinados/Dispositivos/AprobarDispositivoRequest.php`

```php
<?php

namespace App\Http\Requests\Patrocinados\Dispositivos;

use Illuminate\Foundation\Http\FormRequest;

/** Sin body requerido — la aprobación no lleva datos adicionales por ahora. */
class AprobarDispositivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
```

#### `app/Http/Requests/Patrocinados/Dispositivos/RevocarDispositivoRequest.php`

```php
<?php

namespace App\Http\Requests\Patrocinados\Dispositivos;

use Illuminate\Foundation\Http\FormRequest;

class RevocarDispositivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

---

## Rutas de referencia (para `routes/api/patrocinados.php`, se cablean formalmente en la Etapa 1/9)

```php
Route::get('/dispositivos', [DispositivoController::class, 'index'])->middleware('permiso-patrocinados:dispositivos.ver');
Route::get('/dispositivos/{id}', [DispositivoController::class, 'show'])->middleware('permiso-patrocinados:dispositivos.ver');
Route::post('/dispositivos', [DispositivoController::class, 'store']); // el propio usuario autenticado, sin permiso adicional
Route::put('/dispositivos/{id}', [DispositivoController::class, 'update'])->middleware('permiso-patrocinados:dispositivos.editar');
Route::post('/dispositivos/{id}/aprobar', [DispositivoController::class, 'aprobar'])->middleware('permiso-patrocinados:dispositivos.aprobar');
Route::post('/dispositivos/{id}/revocar', [DispositivoController::class, 'revocar'])->middleware('permiso-patrocinados:dispositivos.revocar');
```
