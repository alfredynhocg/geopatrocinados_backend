# Código completo — Etapa 4: Dispositivos

> Fuente de columnas: `database/migrations/patrocinados/2026_09_01_000020_create_dispositivos_table.php`.
> Nota de la migración real: la tabla **no tiene `created_at`** (solo `fecha_registro` + `updated_at`) — el Modelo debe desactivar el timestamp de creación estándar de Eloquent (`const CREATED_AT = null;`).
> Depende de `AuditoriaService` (Etapa 8, ver [08-auditoria-transversal-codigo.md](08-auditoria-transversal-codigo.md)) para `AprobarDispositivoHandler`/`RevocarDispositivoHandler` — dependencia hacia adelante ya resuelta en este código, no un TODO.

## Domain/Dispositivos

#### app/Domain/Dispositivos/Contracts/DispositivoRepositoryInterface.php

```php
<?php

namespace App\Domain\Dispositivos\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface DispositivoRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $userId = null, ?string $estado = null): array;

    public function findById(string $id): mixed;

    public function findByIdentificador(string $identificador): mixed;

    public function create(array $data): mixed;

    public function update(string $id, array $data): mixed;

    public function aprobar(string $id): mixed;

    public function revocar(string $id, string $revokedBy): mixed;

    public function delete(string|array $ids): bool;
}
```

#### app/Domain/Dispositivos/Exceptions/DispositivoNotFoundException.php

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

#### app/Domain/Dispositivos/Exceptions/DispositivoYaRegistradoException.php

```php
<?php

namespace App\Domain\Dispositivos\Exceptions;

class DispositivoYaRegistradoException extends \RuntimeException
{
    public function __construct(string $identificadorDispositivo)
    {
        parent::__construct(
            "El dispositivo '{$identificadorDispositivo}' ya está registrado.",
            422
        );
    }
}
```

#### app/Domain/Dispositivos/Exceptions/DispositivoRevocadoException.php

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

## Application/Dispositivos

#### app/Application/Dispositivos/DTOs/DispositivoDTO.php

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
        public ?string $updated_at,
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
            updated_at: $model->updated_at?->toIso8601String(),
        );
    }
}
```

#### app/Application/Dispositivos/Commands/RegistrarDispositivoCommand.php

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

#### app/Application/Dispositivos/Commands/AprobarDispositivoCommand.php

```php
<?php

namespace App\Application\Dispositivos\Commands;

final readonly class AprobarDispositivoCommand
{
    public function __construct(
        public string $id,
        public string $aprobadoPor,
    ) {}
}
```

#### app/Application/Dispositivos/Commands/RevocarDispositivoCommand.php

```php
<?php

namespace App\Application\Dispositivos\Commands;

final readonly class RevocarDispositivoCommand
{
    public function __construct(
        public string $id,
        public string $revokedBy,
    ) {}
}
```

#### app/Application/Dispositivos/Commands/UpdateDispositivoCommand.php

```php
<?php

namespace App\Application\Dispositivos\Commands;

final readonly class UpdateDispositivoCommand
{
    public function __construct(
        public string $id,
        public ?string $nombre_dispositivo,
        public ?string $version_sistema,
        public ?string $version_aplicacion,
        public string $updated_by,
    ) {}
}
```

#### app/Application/Dispositivos/Handlers/RegistrarDispositivoHandler.php

```php
<?php

namespace App\Application\Dispositivos\Handlers;

use App\Application\Dispositivos\Commands\RegistrarDispositivoCommand;
use App\Application\Dispositivos\DTOs\DispositivoDTO;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;
use App\Domain\Dispositivos\Exceptions\DispositivoYaRegistradoException;
use Illuminate\Support\Facades\DB;

class RegistrarDispositivoHandler
{
    public function __construct(
        private readonly DispositivoRepositoryInterface $repository
    ) {}

    public function handle(RegistrarDispositivoCommand $command): DispositivoDTO
    {
        if ($this->repository->findByIdentificador($command->identificador_dispositivo) !== null) {
            throw new DispositivoYaRegistradoException($command->identificador_dispositivo);
        }

        $model = DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            return $this->repository->create([
                'user_id' => $command->user_id,
                'identificador_dispositivo' => $command->identificador_dispositivo,
                'nombre_dispositivo' => $command->nombre_dispositivo,
                'plataforma' => $command->plataforma,
                'version_sistema' => $command->version_sistema,
                'version_aplicacion' => $command->version_aplicacion,
                'estado' => 'PENDIENTE',
                'fecha_registro' => now(),
            ]);
        });

        return DispositivoDTO::fromModel($model);
    }
}
```

#### app/Application/Dispositivos/Handlers/AprobarDispositivoHandler.php

```php
<?php

namespace App\Application\Dispositivos\Handlers;

use App\Application\Auditoria\Services\AuditoriaService;
use App\Application\Dispositivos\Commands\AprobarDispositivoCommand;
use App\Application\Dispositivos\DTOs\DispositivoDTO;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;
use App\Domain\Dispositivos\Exceptions\DispositivoNotFoundException;
use Illuminate\Support\Facades\DB;

class AprobarDispositivoHandler
{
    public function __construct(
        private readonly DispositivoRepositoryInterface $repository,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function handle(AprobarDispositivoCommand $command): DispositivoDTO
    {
        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $anterior = $this->repository->findById($command->id);
            if ($anterior === null) {
                throw new DispositivoNotFoundException($command->id);
            }

            $model = $this->repository->aprobar($command->id);

            $this->auditoria->registrar(
                userId: $command->aprobadoPor,
                dispositivoId: $command->id,
                accion: 'aprobar',
                modulo: 'Dispositivos',
                tipoEntidad: 'dispositivo',
                entidadId: $command->id,
                valoresAnteriores: ['estado' => $anterior->estado],
                valoresNuevos: ['estado' => 'ACTIVO'],
            );

            return DispositivoDTO::fromModel($model);
        });
    }
}
```

#### app/Application/Dispositivos/Handlers/RevocarDispositivoHandler.php

```php
<?php

namespace App\Application\Dispositivos\Handlers;

use App\Application\Auditoria\Services\AuditoriaService;
use App\Application\Dispositivos\Commands\RevocarDispositivoCommand;
use App\Application\Dispositivos\DTOs\DispositivoDTO;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;
use App\Domain\Dispositivos\Exceptions\DispositivoNotFoundException;
use Illuminate\Support\Facades\DB;

class RevocarDispositivoHandler
{
    public function __construct(
        private readonly DispositivoRepositoryInterface $repository,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function handle(RevocarDispositivoCommand $command): DispositivoDTO
    {
        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $anterior = $this->repository->findById($command->id);
            if ($anterior === null) {
                throw new DispositivoNotFoundException($command->id);
            }

            $model = $this->repository->revocar($command->id, $command->revokedBy);

            $this->auditoria->registrar(
                userId: $command->revokedBy,
                dispositivoId: $command->id,
                accion: 'revocar',
                modulo: 'Dispositivos',
                tipoEntidad: 'dispositivo',
                entidadId: $command->id,
                valoresAnteriores: ['estado' => $anterior->estado],
                valoresNuevos: ['estado' => 'REVOCADO'],
            );

            return DispositivoDTO::fromModel($model);
        });
    }
}
```

#### app/Application/Dispositivos/Handlers/UpdateDispositivoHandler.php

```php
<?php

namespace App\Application\Dispositivos\Handlers;

use App\Application\Dispositivos\Commands\UpdateDispositivoCommand;
use App\Application\Dispositivos\DTOs\DispositivoDTO;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;
use App\Domain\Dispositivos\Exceptions\DispositivoNotFoundException;

class UpdateDispositivoHandler
{
    public function __construct(
        private readonly DispositivoRepositoryInterface $repository
    ) {}

    public function handle(UpdateDispositivoCommand $command): DispositivoDTO
    {
        if ($this->repository->findById($command->id) === null) {
            throw new DispositivoNotFoundException($command->id);
        }

        $model = $this->repository->update($command->id, [
            'nombre_dispositivo' => $command->nombre_dispositivo,
            'version_sistema' => $command->version_sistema,
            'version_aplicacion' => $command->version_aplicacion,
            'updated_by' => $command->updated_by,
        ]);

        return DispositivoDTO::fromModel($model);
    }
}
```

#### app/Application/Dispositivos/Queries/GetDispositivosQuery.php

```php
<?php

namespace App\Application\Dispositivos\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetDispositivosQuery
{
    public function __construct(
        public PaginationDTO $pagination,
        public ?string $userId = null,
        public ?string $estado = null,
    ) {}
}
```

#### app/Application/Dispositivos/Queries/GetDispositivoByIdQuery.php

```php
<?php

namespace App\Application\Dispositivos\Queries;

final readonly class GetDispositivoByIdQuery
{
    public function __construct(public string $id) {}
}
```

#### app/Application/Dispositivos/QueryHandlers/GetDispositivosQueryHandler.php

```php
<?php

namespace App\Application\Dispositivos\QueryHandlers;

use App\Application\Dispositivos\Queries\GetDispositivosQuery;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;

class GetDispositivosQueryHandler
{
    public function __construct(
        private readonly DispositivoRepositoryInterface $repository
    ) {}

    public function handle(GetDispositivosQuery $query): array
    {
        return $this->repository->paginate($query->pagination, $query->userId, $query->estado);
    }
}
```

#### app/Application/Dispositivos/QueryHandlers/GetDispositivoByIdQueryHandler.php

```php
<?php

namespace App\Application\Dispositivos\QueryHandlers;

use App\Application\Dispositivos\DTOs\DispositivoDTO;
use App\Application\Dispositivos\Queries\GetDispositivoByIdQuery;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;
use App\Domain\Dispositivos\Exceptions\DispositivoNotFoundException;

class GetDispositivoByIdQueryHandler
{
    public function __construct(
        private readonly DispositivoRepositoryInterface $repository
    ) {}

    public function handle(GetDispositivoByIdQuery $query): DispositivoDTO
    {
        $model = $this->repository->findById($query->id);
        if ($model === null) {
            throw new DispositivoNotFoundException($query->id);
        }

        return DispositivoDTO::fromModel($model);
    }
}
```

## Infrastructure/Dispositivos

#### app/Infrastructure/Dispositivos/Models/Dispositivo.php

```php
<?php

namespace App\Infrastructure\Dispositivos\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Dispositivo extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    // La tabla no tiene columna created_at (solo fecha_registro + updated_at).
    const CREATED_AT = null;

    protected $table = 'dispositivos';

    protected $fillable = [
        'user_id',
        'identificador_dispositivo',
        'nombre_dispositivo',
        'plataforma',
        'version_sistema',
        'version_aplicacion',
        'estado',
        'ultima_sincronizacion_at',
        'fecha_registro',
        'fecha_revocacion',
        'revoked_by',
        'updated_by',
    ];

    protected $casts = [
        'ultima_sincronizacion_at' => 'datetime',
        'fecha_registro' => 'datetime',
        'fecha_revocacion' => 'datetime',
    ];
}
```

#### app/Infrastructure/Dispositivos/Repositories/EloquentDispositivoRepository.php

```php
<?php

namespace App\Infrastructure\Dispositivos\Repositories;

use App\Application\Dispositivos\DTOs\DispositivoDTO;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;
use App\Domain\Dispositivos\Exceptions\DispositivoNotFoundException;
use App\Infrastructure\Dispositivos\Models\Dispositivo;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentDispositivoRepository implements DispositivoRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $userId = null, ?string $estado = null): array
    {
        $q = Dispositivo::query();

        if ($userId !== null) {
            $q->where('user_id', $userId);
        }

        if ($estado !== null) {
            $q->where('estado', $estado);
        }

        if ($pagination->query !== '') {
            $q->where('identificador_dispositivo', 'ilike', "%{$pagination->query}%");
        }

        $paginated = $q->orderBy($pagination->sortKey ?: 'fecha_registro', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data' => collect($paginated->items())->map(fn ($m) => DispositivoDTO::fromModel($m))->all(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): ?Dispositivo
    {
        return Dispositivo::find($id);
    }

    public function findByIdentificador(string $identificador): ?Dispositivo
    {
        return Dispositivo::where('identificador_dispositivo', $identificador)->first();
    }

    public function create(array $data): Dispositivo
    {
        return Dispositivo::create($data);
    }

    public function update(string $id, array $data): Dispositivo
    {
        $model = Dispositivo::find($id);
        if ($model === null) {
            throw new DispositivoNotFoundException($id);
        }

        $model->update($data);

        return $model->refresh();
    }

    public function aprobar(string $id): Dispositivo
    {
        $model = Dispositivo::find($id);
        if ($model === null) {
            throw new DispositivoNotFoundException($id);
        }

        $model->update(['estado' => 'ACTIVO']);

        return $model->refresh();
    }

    public function revocar(string $id, string $revokedBy): Dispositivo
    {
        $model = Dispositivo::find($id);
        if ($model === null) {
            throw new DispositivoNotFoundException($id);
        }

        $model->update([
            'estado' => 'REVOCADO',
            'fecha_revocacion' => now(),
            'revoked_by' => $revokedBy,
        ]);

        return $model->refresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Dispositivo::destroy($ids);
    }
}
```

## Http

#### app/Http/Controllers/Api/Patrocinados/DispositivoController.php

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
        $pagination = PaginationDTO::fromArray($request->all(), 'fecha_registro');

        return response()->json(
            $this->getDispositivosHandler->handle(new GetDispositivosQuery(
                $pagination,
                $request->string('user_id')->toString() ?: null,
                $request->string('estado')->toString() ?: null,
            ))
        );
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(
            $this->getDispositivoByIdHandler->handle(new GetDispositivoByIdQuery($id))
        );
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
            updated_by: auth()->id(),
        ));

        return response()->json($dto);
    }

    public function aprobar(AprobarDispositivoRequest $request, string $id): JsonResponse
    {
        $dto = $this->aprobarHandler->handle(new AprobarDispositivoCommand(
            id: $id,
            aprobadoPor: auth()->id(),
        ));

        return response()->json($dto);
    }

    public function revocar(RevocarDispositivoRequest $request, string $id): JsonResponse
    {
        $dto = $this->revocarHandler->handle(new RevocarDispositivoCommand(
            id: $id,
            revokedBy: auth()->id(),
        ));

        return response()->json($dto);
    }
}
```

#### app/Http/Requests/Patrocinados/Dispositivos/RegistrarDispositivoRequest.php

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
            'identificador_dispositivo' => [
                'required', 'string', 'max:180',
                'unique:pgsql_patrocinados.dispositivos,identificador_dispositivo',
            ],
            'nombre_dispositivo' => ['nullable', 'string', 'max:150'],
            'plataforma' => ['required', 'string', 'max:30'],
            'version_sistema' => ['nullable', 'string', 'max:50'],
            'version_aplicacion' => ['nullable', 'string', 'max:50'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/Dispositivos/UpdateDispositivoRequest.php

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
            'version_sistema' => ['nullable', 'string', 'max:50'],
            'version_aplicacion' => ['nullable', 'string', 'max:50'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/Dispositivos/AprobarDispositivoRequest.php

```php
<?php

namespace App\Http\Requests\Patrocinados\Dispositivos;

use Illuminate\Foundation\Http\FormRequest;

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

#### app/Http/Requests/Patrocinados/Dispositivos/RevocarDispositivoRequest.php

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
        return [];
    }
}
```

## Rutas (extracto de `routes/api/patrocinados.php`)

```php
Route::prefix('dispositivos')->group(function () {
    Route::post('/', [DispositivoController::class, 'store']);
    Route::get('/', [DispositivoController::class, 'index'])->middleware('permiso-patrocinados:dispositivos.ver');
    Route::get('/{id}', [DispositivoController::class, 'show'])->middleware('permiso-patrocinados:dispositivos.ver');
    Route::put('/{id}', [DispositivoController::class, 'update'])->middleware('permiso-patrocinados:dispositivos.editar');
    Route::post('/{id}/aprobar', [DispositivoController::class, 'aprobar'])->middleware('permiso-patrocinados:dispositivos.aprobar');
    Route::post('/{id}/revocar', [DispositivoController::class, 'revocar'])->middleware('permiso-patrocinados:dispositivos.revocar');
});
```
