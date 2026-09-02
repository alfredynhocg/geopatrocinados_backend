# Código completo — Etapa 7: Sincronización

> Complementa [../07-sincronizacion.md](../07-sincronizacion.md). Código PHP completo, listo para copiar, de cada archivo de la "Estructura DDD" de esa etapa. Fuente de columnas: las migraciones reales `database/migrations/patrocinados/2026_09_01_00005{0,1}_*.php`.
>
> **Reglas de negocio implementadas aquí** (no reabrir sin motivo, ver `docs/patrocinados/07-sincronizacion.md`):
> 1. **Política de conflicto = last-write-wins** (asumida en el doc madre): `ConflictoVersionException` nunca se propaga como 5xx — `ProcesarElementoSincronizacionHandler` la captura y la traduce a `estado = 'ERROR'` + `mensaje_error = 'conflicto_version'` en `elementos_sincronizacion`.
> 2. **Cada elemento es su propia unidad transaccional**: la transacción envuelve el procesamiento de UN elemento, nunca el lote completo — así un elemento con error no aborta los demás.
> 3. **Idempotencia**: antes de procesar, se busca un elemento ya `SINCRONIZADO` con el mismo `tipo_entidad`+`entidad_id`+`hash_datos` — si existe, se devuelve tal cual sin reprocesar (reintento de red del cliente).
> 4. **`SincronizacionRouterService`** define el contrato de enrutamiento (`tipo_entidad` → adapter) pero **no implementa los adapters concretos** (`VisitaSyncAdapter`, etc.) — esos se agregan cuando el módulo Visitas/Patrocinados correspondiente esté implementado. El contrato (`SincronizacionAdapterInterface`) sí se define acá porque el Router lo necesita para compilar.
> 5. La tabla `lotes_sincronizacion` **no tiene columna `created_at`** (solo `fecha_inicio` + `updated_at`) — el modelo deshabilita el manejo automático de `created_at` de Eloquent.

---

## Domain/Sincronizacion

#### `app/Domain/Sincronizacion/Contracts/LoteSincronizacionRepositoryInterface.php`

```php
<?php

namespace App\Domain\Sincronizacion\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface LoteSincronizacionRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $dispositivoId, ?string $estado): array;

    public function findById(string $id): mixed;

    public function create(array $data): mixed;

    public function cerrar(string $id, int $registrosEnviados, int $registrosRecibidos, string $estado): mixed;
}
```

#### `app/Domain/Sincronizacion/Contracts/ElementoSincronizacionRepositoryInterface.php`

```php
<?php

namespace App\Domain\Sincronizacion\Contracts;

interface ElementoSincronizacionRepositoryInterface
{
    /** Para idempotencia de reenvío: null si no existe un elemento ya SINCRONIZADO con ese hash. */
    public function findSincronizadoPorEntidadYHash(string $tipoEntidad, string $entidadId, ?string $hashDatos): mixed;

    public function create(array $data): mixed;

    public function marcarSincronizado(string $id): mixed;

    public function marcarError(string $id, string $mensajeError): mixed;

    public function listPendientesByLote(string $loteId): array;
}
```

#### `app/Domain/Sincronizacion/Contracts/SincronizacionAdapterInterface.php`

```php
<?php

namespace App\Domain\Sincronizacion\Contracts;

/**
 * Contrato que debe implementar cada adapter concreto (VisitaSyncAdapter,
 * PatrocinadoSyncAdapter, ...) cuando el módulo de negocio correspondiente
 * esté implementado. SincronizacionRouterService solo conoce esta interfaz.
 */
interface SincronizacionAdapterInterface
{
    /**
     * @throws \App\Domain\Sincronizacion\Exceptions\ConflictoVersionException
     */
    public function procesar(string $operacion, string $entidadId, array $payload): void;
}
```

#### `app/Domain/Sincronizacion/Exceptions/LoteSincronizacionNotFoundException.php`

```php
<?php

namespace App\Domain\Sincronizacion\Exceptions;

class LoteSincronizacionNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Lote de sincronización '{$id}' no encontrado.", 404);
    }
}
```

#### `app/Domain/Sincronizacion/Exceptions/ConflictoVersionException.php`

```php
<?php

namespace App\Domain\Sincronizacion\Exceptions;

/**
 * Se lanza dentro de un adapter cuando la versión del cliente no coincide
 * con la del servidor (last-write-wins). ProcesarElementoSincronizacionHandler
 * la captura SIEMPRE — nunca debe llegar a un Controller como 5xx.
 */
class ConflictoVersionException extends \RuntimeException
{
    public function __construct(string $entidadId)
    {
        parent::__construct("Conflicto de versión al sincronizar la entidad '{$entidadId}'.");
    }
}
```

---

## Application/Sincronizacion

### DTOs

#### `app/Application/Sincronizacion/DTOs/LoteSincronizacionDTO.php`

```php
<?php

namespace App\Application\Sincronizacion\DTOs;

final readonly class LoteSincronizacionDTO
{
    public function __construct(
        public string $id,
        public string $dispositivo_id,
        public string $user_id,
        public string $fecha_inicio,
        public ?string $fecha_fin,
        public int $registros_enviados,
        public int $registros_recibidos,
        public string $estado,
        public ?string $mensaje_error,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            dispositivo_id: $model->dispositivo_id,
            user_id: $model->user_id,
            fecha_inicio: $model->fecha_inicio->toIso8601String(),
            fecha_fin: $model->fecha_fin?->toIso8601String(),
            registros_enviados: $model->registros_enviados,
            registros_recibidos: $model->registros_recibidos,
            estado: $model->estado,
            mensaje_error: $model->mensaje_error,
        );
    }
}
```

#### `app/Application/Sincronizacion/DTOs/ElementoSincronizacionDTO.php`

```php
<?php

namespace App\Application\Sincronizacion\DTOs;

final readonly class ElementoSincronizacionDTO
{
    public function __construct(
        public string $id,
        public string $lote_sincronizacion_id,
        public string $tipo_entidad,
        public string $entidad_id,
        public string $operacion,
        public ?string $hash_datos,
        public string $estado,
        public int $intentos,
        public ?string $mensaje_error,
        public ?string $fecha_sincronizacion,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            lote_sincronizacion_id: $model->lote_sincronizacion_id,
            tipo_entidad: $model->tipo_entidad,
            entidad_id: $model->entidad_id,
            operacion: $model->operacion,
            hash_datos: $model->hash_datos,
            estado: $model->estado,
            intentos: $model->intentos,
            mensaje_error: $model->mensaje_error,
            fecha_sincronizacion: $model->fecha_sincronizacion?->toIso8601String(),
        );
    }
}
```

#### `app/Application/Sincronizacion/DTOs/ResultadoSincronizacionDTO.php`

```php
<?php

namespace App\Application\Sincronizacion\DTOs;

/** Resumen devuelto al cerrar un lote — la app decide reintentos en base a esto. */
final readonly class ResultadoSincronizacionDTO
{
    public function __construct(
        public string $lote_id,
        public string $estado,
        public int $registros_enviados,
        public int $registros_recibidos,
        public int $elementos_sincronizados,
        public int $elementos_con_error,
    ) {}
}
```

### Commands

#### `app/Application/Sincronizacion/Commands/IniciarLoteSincronizacionCommand.php`

```php
<?php

namespace App\Application\Sincronizacion\Commands;

final readonly class IniciarLoteSincronizacionCommand
{
    public function __construct(
        public string $dispositivo_id,
        public string $user_id,
    ) {}
}
```

#### `app/Application/Sincronizacion/Commands/ProcesarElementoSincronizacionCommand.php`

```php
<?php

namespace App\Application\Sincronizacion\Commands;

final readonly class ProcesarElementoSincronizacionCommand
{
    public function __construct(
        public string $lote_id,
        public string $tipo_entidad,
        public string $entidad_id,
        public string $operacion,
        public ?string $hash_datos,
        public array $payload,
    ) {}
}
```

#### `app/Application/Sincronizacion/Commands/CerrarLoteSincronizacionCommand.php`

```php
<?php

namespace App\Application\Sincronizacion\Commands;

final readonly class CerrarLoteSincronizacionCommand
{
    public function __construct(
        public string $lote_id,
        public int $registros_enviados,
        public int $registros_recibidos,
    ) {}
}
```

### Services

#### `app/Application/Sincronizacion/Services/SincronizacionRouterService.php`

```php
<?php

namespace App\Application\Sincronizacion\Services;

/**
 * Mapa tipo_entidad => adapter concreto. Los adapters (VisitaSyncAdapter,
 * PatrocinadoSyncAdapter, etc.) se implementan cuando el módulo
 * correspondiente esté listo — este Service solo define el contrato de
 * enrutamiento, no inventa la lógica de negocio de cada adapter.
 */
class SincronizacionRouterService
{
    private const MAPA = [
        // 'visita'      => \App\Application\Visitas\Sincronizacion\VisitaSyncAdapter::class,
        // 'patrocinado' => \App\Application\Patrocinados\Sincronizacion\PatrocinadoSyncAdapter::class,
        // 'observacion' => \App\Application\Visitas\Sincronizacion\ObservacionVisitaSyncAdapter::class,
        // 'foto'        => \App\Application\Visitas\Sincronizacion\FotoVisitaSyncAdapter::class,
        // 'ubicacion'   => \App\Application\Visitas\Sincronizacion\UbicacionVisitaSyncAdapter::class,
    ];

    public function despachar(string $tipoEntidad, string $operacion, string $entidadId, array $payload): void
    {
        if (! isset(self::MAPA[$tipoEntidad])) {
            throw new \InvalidArgumentException("Tipo de entidad de sincronización sin adapter registrado: {$tipoEntidad}");
        }

        /** @var \App\Domain\Sincronizacion\Contracts\SincronizacionAdapterInterface $adapter */
        $adapter = app()->make(self::MAPA[$tipoEntidad]);
        $adapter->procesar($operacion, $entidadId, $payload);
    }
}
```

### Handlers

#### `app/Application/Sincronizacion/Handlers/IniciarLoteSincronizacionHandler.php`

```php
<?php

namespace App\Application\Sincronizacion\Handlers;

use App\Application\Sincronizacion\Commands\IniciarLoteSincronizacionCommand;
use App\Application\Sincronizacion\DTOs\LoteSincronizacionDTO;
use App\Domain\Sincronizacion\Contracts\LoteSincronizacionRepositoryInterface;

class IniciarLoteSincronizacionHandler
{
    public function __construct(private readonly LoteSincronizacionRepositoryInterface $repository) {}

    public function handle(IniciarLoteSincronizacionCommand $command): LoteSincronizacionDTO
    {
        $model = $this->repository->create([
            'dispositivo_id' => $command->dispositivo_id,
            'user_id'        => $command->user_id,
            'fecha_inicio'   => now(),
            'estado'         => 'SINCRONIZANDO',
        ]);

        return LoteSincronizacionDTO::fromModel($model);
    }
}
```

#### `app/Application/Sincronizacion/Handlers/ProcesarElementoSincronizacionHandler.php`

```php
<?php

namespace App\Application\Sincronizacion\Handlers;

use App\Application\Sincronizacion\Commands\ProcesarElementoSincronizacionCommand;
use App\Application\Sincronizacion\DTOs\ElementoSincronizacionDTO;
use App\Application\Sincronizacion\Services\SincronizacionRouterService;
use App\Domain\Sincronizacion\Contracts\ElementoSincronizacionRepositoryInterface;
use App\Domain\Sincronizacion\Exceptions\ConflictoVersionException;
use Illuminate\Support\Facades\DB;

/**
 * Cada elemento es su propia unidad transaccional — un fallo en uno no
 * aborta el resto del lote. Idempotente por tipo_entidad+entidad_id+hash_datos.
 */
class ProcesarElementoSincronizacionHandler
{
    public function __construct(
        private readonly ElementoSincronizacionRepositoryInterface $repository,
        private readonly SincronizacionRouterService $router,
    ) {}

    public function handle(ProcesarElementoSincronizacionCommand $command): ElementoSincronizacionDTO
    {
        $existente = $this->repository->findSincronizadoPorEntidadYHash(
            $command->tipo_entidad,
            $command->entidad_id,
            $command->hash_datos,
        );

        if ($existente !== null) {
            return ElementoSincronizacionDTO::fromModel($existente);
        }

        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $elemento = $this->repository->create([
                'lote_sincronizacion_id' => $command->lote_id,
                'tipo_entidad'           => $command->tipo_entidad,
                'entidad_id'             => $command->entidad_id,
                'operacion'              => $command->operacion,
                'hash_datos'             => $command->hash_datos,
                'estado'                 => 'PENDIENTE',
            ]);

            try {
                $this->router->despachar($command->tipo_entidad, $command->operacion, $command->entidad_id, $command->payload);

                $elemento = $this->repository->marcarSincronizado($elemento->id);
            } catch (ConflictoVersionException) {
                $elemento = $this->repository->marcarError($elemento->id, 'conflicto_version');
            } catch (\Throwable $e) {
                $elemento = $this->repository->marcarError($elemento->id, $e->getMessage());
            }

            return ElementoSincronizacionDTO::fromModel($elemento);
        });
    }
}
```

#### `app/Application/Sincronizacion/Handlers/CerrarLoteSincronizacionHandler.php`

```php
<?php

namespace App\Application\Sincronizacion\Handlers;

use App\Application\Sincronizacion\Commands\CerrarLoteSincronizacionCommand;
use App\Application\Sincronizacion\DTOs\ResultadoSincronizacionDTO;
use App\Domain\Sincronizacion\Contracts\ElementoSincronizacionRepositoryInterface;
use App\Domain\Sincronizacion\Contracts\LoteSincronizacionRepositoryInterface;

class CerrarLoteSincronizacionHandler
{
    public function __construct(
        private readonly LoteSincronizacionRepositoryInterface $loteRepository,
        private readonly ElementoSincronizacionRepositoryInterface $elementoRepository,
    ) {}

    public function handle(CerrarLoteSincronizacionCommand $command): ResultadoSincronizacionDTO
    {
        $pendientes = $this->elementoRepository->listPendientesByLote($command->lote_id);
        $conError = collect($pendientes)->where('estado', 'ERROR')->count();

        $estadoFinal = $conError > 0 ? 'ERROR' : 'COMPLETADO';

        $lote = $this->loteRepository->cerrar(
            $command->lote_id,
            $command->registros_enviados,
            $command->registros_recibidos,
            $estadoFinal,
        );

        return new ResultadoSincronizacionDTO(
            lote_id: $lote->id,
            estado: $lote->estado,
            registros_enviados: $lote->registros_enviados,
            registros_recibidos: $lote->registros_recibidos,
            elementos_sincronizados: collect($pendientes)->where('estado', 'SINCRONIZADO')->count(),
            elementos_con_error: $conError,
        );
    }
}
```

### Queries

#### `app/Application/Sincronizacion/Queries/GetLotesSincronizacionQuery.php`

```php
<?php

namespace App\Application\Sincronizacion\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetLotesSincronizacionQuery
{
    public function __construct(
        public PaginationDTO $pagination,
        public ?string $dispositivo_id = null,
        public ?string $estado = null,
    ) {}
}
```

#### `app/Application/Sincronizacion/Queries/GetElementosPendientesQuery.php`

```php
<?php

namespace App\Application\Sincronizacion\Queries;

final readonly class GetElementosPendientesQuery
{
    public function __construct(public string $lote_id) {}
}
```

### QueryHandlers

#### `app/Application/Sincronizacion/QueryHandlers/GetLotesSincronizacionQueryHandler.php`

```php
<?php

namespace App\Application\Sincronizacion\QueryHandlers;

use App\Application\Sincronizacion\DTOs\LoteSincronizacionDTO;
use App\Application\Sincronizacion\Queries\GetLotesSincronizacionQuery;
use App\Domain\Sincronizacion\Contracts\LoteSincronizacionRepositoryInterface;

class GetLotesSincronizacionQueryHandler
{
    public function __construct(private readonly LoteSincronizacionRepositoryInterface $repository) {}

    public function handle(GetLotesSincronizacionQuery $query): array
    {
        $paginated = $this->repository->paginate($query->pagination, $query->dispositivo_id, $query->estado);

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => LoteSincronizacionDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
```

#### `app/Application/Sincronizacion/QueryHandlers/GetElementosPendientesQueryHandler.php`

```php
<?php

namespace App\Application\Sincronizacion\QueryHandlers;

use App\Application\Sincronizacion\DTOs\ElementoSincronizacionDTO;
use App\Application\Sincronizacion\Queries\GetElementosPendientesQuery;
use App\Domain\Sincronizacion\Contracts\ElementoSincronizacionRepositoryInterface;

class GetElementosPendientesQueryHandler
{
    public function __construct(private readonly ElementoSincronizacionRepositoryInterface $repository) {}

    public function handle(GetElementosPendientesQuery $query): array
    {
        $elementos = $this->repository->listPendientesByLote($query->lote_id);

        return array_map(fn (object $m) => ElementoSincronizacionDTO::fromModel($m), $elementos);
    }
}
```

---

## Infrastructure/Sincronizacion

### Models

#### `app/Infrastructure/Sincronizacion/Models/LoteSincronizacion.php`

```php
<?php

namespace App\Infrastructure\Sincronizacion\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Sin columna created_at en el docx (solo fecha_inicio + updated_at). */
class LoteSincronizacion extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    public const CREATED_AT = null;

    protected $table = 'lotes_sincronizacion';

    protected $fillable = [
        'dispositivo_id', 'user_id', 'fecha_inicio', 'fecha_fin',
        'registros_enviados', 'registros_recibidos', 'estado', 'mensaje_error',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin'    => 'datetime',
    ];

    public function elementos()
    {
        return $this->hasMany(ElementoSincronizacion::class, 'lote_sincronizacion_id');
    }
}
```

#### `app/Infrastructure/Sincronizacion/Models/ElementoSincronizacion.php`

```php
<?php

namespace App\Infrastructure\Sincronizacion\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ElementoSincronizacion extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'elementos_sincronizacion';

    protected $fillable = [
        'lote_sincronizacion_id', 'tipo_entidad', 'entidad_id', 'operacion',
        'hash_datos', 'estado', 'intentos', 'mensaje_error', 'fecha_sincronizacion',
    ];

    protected $casts = [
        'fecha_sincronizacion' => 'datetime',
        'intentos'             => 'integer',
    ];

    public function lote()
    {
        return $this->belongsTo(LoteSincronizacion::class, 'lote_sincronizacion_id');
    }
}
```

### Repositories

#### `app/Infrastructure/Sincronizacion/Repositories/EloquentLoteSincronizacionRepository.php`

```php
<?php

namespace App\Infrastructure\Sincronizacion\Repositories;

use App\Domain\Sincronizacion\Contracts\LoteSincronizacionRepositoryInterface;
use App\Domain\Sincronizacion\Exceptions\LoteSincronizacionNotFoundException;
use App\Infrastructure\Sincronizacion\Models\LoteSincronizacion;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentLoteSincronizacionRepository implements LoteSincronizacionRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $dispositivoId, ?string $estado): array
    {
        $q = LoteSincronizacion::query();

        if ($dispositivoId) {
            $q->where('dispositivo_id', $dispositivoId);
        }
        if ($estado) {
            $q->where('estado', $estado);
        }

        $paginated = $q->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'fecha_inicio', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return ['data' => $paginated->items(), 'total' => $paginated->total()];
    }

    public function findById(string $id): mixed
    {
        $lote = LoteSincronizacion::find($id);

        if (! $lote) {
            throw new LoteSincronizacionNotFoundException($id);
        }

        return $lote;
    }

    public function create(array $data): mixed
    {
        return LoteSincronizacion::create($data);
    }

    public function cerrar(string $id, int $registrosEnviados, int $registrosRecibidos, string $estado): mixed
    {
        $lote = $this->findById($id);

        $lote->update([
            'fecha_fin'            => now(),
            'registros_enviados'   => $registrosEnviados,
            'registros_recibidos'  => $registrosRecibidos,
            'estado'               => $estado,
        ]);

        return $lote->fresh();
    }
}
```

#### `app/Infrastructure/Sincronizacion/Repositories/EloquentElementoSincronizacionRepository.php`

```php
<?php

namespace App\Infrastructure\Sincronizacion\Repositories;

use App\Domain\Sincronizacion\Contracts\ElementoSincronizacionRepositoryInterface;
use App\Infrastructure\Sincronizacion\Models\ElementoSincronizacion;

class EloquentElementoSincronizacionRepository implements ElementoSincronizacionRepositoryInterface
{
    public function findSincronizadoPorEntidadYHash(string $tipoEntidad, string $entidadId, ?string $hashDatos): mixed
    {
        return ElementoSincronizacion::query()
            ->where('tipo_entidad', $tipoEntidad)
            ->where('entidad_id', $entidadId)
            ->where('hash_datos', $hashDatos)
            ->where('estado', 'SINCRONIZADO')
            ->first();
    }

    public function create(array $data): mixed
    {
        return ElementoSincronizacion::create($data);
    }

    public function marcarSincronizado(string $id): mixed
    {
        $elemento = ElementoSincronizacion::findOrFail($id);
        $elemento->update([
            'estado'                => 'SINCRONIZADO',
            'fecha_sincronizacion'  => now(),
        ]);

        return $elemento->fresh();
    }

    public function marcarError(string $id, string $mensajeError): mixed
    {
        $elemento = ElementoSincronizacion::findOrFail($id);
        $elemento->update([
            'estado'         => 'ERROR',
            'intentos'       => $elemento->intentos + 1,
            'mensaje_error'  => $mensajeError,
        ]);

        return $elemento->fresh();
    }

    public function listPendientesByLote(string $loteId): array
    {
        return ElementoSincronizacion::where('lote_sincronizacion_id', $loteId)->get()->all();
    }
}
```

---

## Http

### Controllers

#### `app/Http/Controllers/Api/Patrocinados/SincronizacionController.php`

```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Sincronizacion\Commands\CerrarLoteSincronizacionCommand;
use App\Application\Sincronizacion\Commands\IniciarLoteSincronizacionCommand;
use App\Application\Sincronizacion\Commands\ProcesarElementoSincronizacionCommand;
use App\Application\Sincronizacion\Handlers\CerrarLoteSincronizacionHandler;
use App\Application\Sincronizacion\Handlers\IniciarLoteSincronizacionHandler;
use App\Application\Sincronizacion\Handlers\ProcesarElementoSincronizacionHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Sincronizacion\IniciarLoteRequest;
use App\Http\Requests\Patrocinados\Sincronizacion\ProcesarElementoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SincronizacionController extends Controller
{
    public function __construct(
        private readonly IniciarLoteSincronizacionHandler $iniciarLoteHandler,
        private readonly ProcesarElementoSincronizacionHandler $procesarElementoHandler,
        private readonly CerrarLoteSincronizacionHandler $cerrarLoteHandler,
    ) {}

    public function iniciarLote(IniciarLoteRequest $request): JsonResponse
    {
        $dto = $this->iniciarLoteHandler->handle(new IniciarLoteSincronizacionCommand(
            dispositivo_id: $request->dispositivo_id,
            user_id: auth()->id(),
        ));

        return response()->json($dto, 201);
    }

    public function procesarElemento(ProcesarElementoRequest $request, string $loteId): JsonResponse
    {
        // Cada elemento se procesa y reporta su propio resultado — nunca aborta
        // el resto del lote (best-effort, no todo-o-nada).
        $dto = $this->procesarElementoHandler->handle(new ProcesarElementoSincronizacionCommand(
            lote_id: $loteId,
            tipo_entidad: $request->tipo_entidad,
            entidad_id: $request->entidad_id,
            operacion: $request->operacion,
            hash_datos: $request->hash_datos,
            payload: $request->payload ?? [],
        ));

        return response()->json($dto);
    }

    public function cerrarLote(Request $request, string $loteId): JsonResponse
    {
        $dto = $this->cerrarLoteHandler->handle(new CerrarLoteSincronizacionCommand(
            lote_id: $loteId,
            registros_enviados: (int) $request->input('registros_enviados', 0),
            registros_recibidos: (int) $request->input('registros_recibidos', 0),
        ));

        return response()->json($dto);
    }
}
```

### Requests

#### `app/Http/Requests/Patrocinados/Sincronizacion/IniciarLoteRequest.php`

```php
<?php

namespace App\Http\Requests\Patrocinados\Sincronizacion;

use Illuminate\Foundation\Http\FormRequest;

class IniciarLoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dispositivo_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.dispositivos,id'],
        ];
    }
}
```

#### `app/Http/Requests/Patrocinados/Sincronizacion/ProcesarElementoRequest.php`

```php
<?php

namespace App\Http\Requests\Patrocinados\Sincronizacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcesarElementoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_entidad' => ['required', 'string', 'max:100'],
            'entidad_id'   => ['required', 'uuid'],
            'operacion'    => ['required', Rule::in(['CREATE', 'UPDATE', 'DELETE'])],
            'hash_datos'   => ['nullable', 'string', 'size:64'],
            'payload'      => ['nullable', 'array'],
        ];
    }
}
```

---

## Rutas de referencia (para `routes/api/patrocinados.php`, se cablean formalmente en la Etapa 1/9)

```php
Route::prefix('sincronizacion')->group(function () {
    Route::post('/lotes', [SincronizacionController::class, 'iniciarLote']);
    Route::post('/lotes/{loteId}/elementos', [SincronizacionController::class, 'procesarElemento']);
    Route::post('/lotes/{loteId}/cerrar', [SincronizacionController::class, 'cerrarLote']);
});
```
