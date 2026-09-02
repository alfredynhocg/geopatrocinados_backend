# Código completo — Etapa 7: Sincronización

> Contraparte de código de [../07-sincronizacion.md](../07-sincronizacion.md). Tablas: `lotes_sincronizacion`, `elementos_sincronizacion`.
> Conexión: `pgsql_patrocinados` vía trait `App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados` (Etapa 1).
> Política de conflictos asumida: **last-write-wins por versión** (ver decisión en `07-sincronizacion.md`).

---

## Domain/Sincronizacion

#### app/Domain/Sincronizacion/Contracts/LoteSincronizacionRepositoryInterface.php

```php
<?php

namespace App\Domain\Sincronizacion\Contracts;

use App\Application\Sincronizacion\DTOs\LoteSincronizacionDTO;
use App\Shared\Kernel\DTOs\PaginationDTO;

interface LoteSincronizacionRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $dispositivoId, ?string $estado): array;

    public function findById(string $id): LoteSincronizacionDTO;

    public function create(array $data): LoteSincronizacionDTO;

    public function update(string $id, array $data): LoteSincronizacionDTO;
}
```

#### app/Domain/Sincronizacion/Contracts/ElementoSincronizacionRepositoryInterface.php

```php
<?php

namespace App\Domain\Sincronizacion\Contracts;

use App\Application\Sincronizacion\DTOs\ElementoSincronizacionDTO;

interface ElementoSincronizacionRepositoryInterface
{
    public function paginateByLote(string $loteId): array;

    public function findById(string $id): ElementoSincronizacionDTO;

    /**
     * Busca un elemento ya procesado con el mismo tipo_entidad + entidad_id + hash_datos,
     * usado por el Handler para detectar reenvíos idempotentes antes de reprocesar.
     */
    public function findSincronizadoPorHash(string $tipoEntidad, string $entidadId, ?string $hashDatos): ?ElementoSincronizacionDTO;

    public function create(array $data): ElementoSincronizacionDTO;

    public function update(string $id, array $data): ElementoSincronizacionDTO;

    /**
     * true si el lote tiene al menos un elemento en estado ERROR — usado por
     * CerrarLoteSincronizacionHandler para decidir el estado final del lote.
     */
    public function tieneErroresEnLote(string $loteId): bool;
}
```

#### app/Domain/Sincronizacion/Contracts/EntidadSincronizableInterface.php

```php
<?php

namespace App\Domain\Sincronizacion\Contracts;

/**
 * Contrato que implementa cada adapter de tipo_entidad (visita, patrocinado,
 * observacion_visita, foto_visita, ubicacion_visita, ...). El adapter real
 * delega en los Handlers de Application/Visitas o Application/Patrocinados
 * ya definidos en las Etapas 5/6 — este módulo solo enruta, no conoce las
 * reglas de negocio de cada entidad.
 */
interface EntidadSincronizableInterface
{
    /**
     * Aplica CREATE/UPDATE/DELETE del payload recibido de la app offline.
     * Debe lanzar ConflictoVersionException si el payload trae una versión
     * desactualizada respecto de la entidad actual (regla last-write-wins).
     */
    public function sincronizar(array $payload, string $operacion): void;

    /**
     * Versión actual conocida por el servidor para esta entidad, o null si
     * la entidad no maneja versionado optimista (ej. catálogos simples).
     */
    public function obtenerVersionActual(string $entidadId): ?int;
}
```

#### app/Domain/Sincronizacion/Exceptions/LoteSincronizacionNotFoundException.php

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

#### app/Domain/Sincronizacion/Exceptions/ConflictoVersionException.php

```php
<?php

namespace App\Domain\Sincronizacion\Exceptions;

/**
 * Se lanza cuando el payload de un elemento trae una versión desactualizada
 * respecto de la entidad en servidor (política last-write-wins). El Handler
 * la captura internamente: nunca debe abortar el procesamiento del resto
 * del lote (ver docs/patrocinados/07-sincronizacion.md, nota de diseño).
 */
class ConflictoVersionException extends \RuntimeException
{
    public function __construct(
        public readonly string $entidadId,
        public readonly ?int $versionEsperada,
        public readonly ?int $versionActual,
    ) {
        parent::__construct(
            "Conflicto de versión para la entidad '{$entidadId}': esperada {$versionEsperada}, actual {$versionActual}.",
            409
        );
    }
}
```

---

## Application/Sincronizacion

#### app/Application/Sincronizacion/DTOs/LoteSincronizacionDTO.php

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
        public ?string $updated_at,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            dispositivo_id: $model->dispositivo_id,
            user_id: $model->user_id,
            fecha_inicio: $model->fecha_inicio?->toIso8601String(),
            fecha_fin: $model->fecha_fin?->toIso8601String(),
            registros_enviados: (int) $model->registros_enviados,
            registros_recibidos: (int) $model->registros_recibidos,
            estado: $model->estado,
            mensaje_error: $model->mensaje_error,
            updated_at: $model->updated_at?->toIso8601String(),
        );
    }
}
```

#### app/Application/Sincronizacion/DTOs/ElementoSincronizacionDTO.php

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
        public ?string $created_at,
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
            intentos: (int) $model->intentos,
            mensaje_error: $model->mensaje_error,
            fecha_sincronizacion: $model->fecha_sincronizacion?->toIso8601String(),
            created_at: $model->created_at?->toIso8601String(),
        );
    }
}
```

#### app/Application/Sincronizacion/DTOs/ResultadoSincronizacionDTO.php

```php
<?php

namespace App\Application\Sincronizacion\DTOs;

final readonly class ResultadoSincronizacionDTO
{
    /** @param ElementoSincronizacionDTO[] $elementos */
    public function __construct(
        public string $lote_id,
        public string $estado,
        public int $registros_enviados,
        public int $registros_recibidos,
        public int $sincronizados,
        public int $errores,
        public array $elementos,
    ) {}
}
```

#### app/Application/Sincronizacion/Commands/IniciarLoteSincronizacionCommand.php

```php
<?php

namespace App\Application\Sincronizacion\Commands;

final readonly class IniciarLoteSincronizacionCommand
{
    public function __construct(
        public string $dispositivoId,
        public string $userId,
    ) {}
}
```

#### app/Application/Sincronizacion/Commands/ProcesarElementoSincronizacionCommand.php

```php
<?php

namespace App\Application\Sincronizacion\Commands;

final readonly class ProcesarElementoSincronizacionCommand
{
    public function __construct(
        public string $loteId,
        public string $tipoEntidad,
        public string $entidadId,
        public string $operacion,
        public ?string $hashDatos,
        public array $payload,
    ) {}
}
```

#### app/Application/Sincronizacion/Commands/CerrarLoteSincronizacionCommand.php

```php
<?php

namespace App\Application\Sincronizacion\Commands;

final readonly class CerrarLoteSincronizacionCommand
{
    public function __construct(
        public string $loteId,
        public int $registrosEnviados,
        public int $registrosRecibidos,
    ) {}
}
```

#### app/Application/Sincronizacion/Services/SincronizacionRouterService.php

```php
<?php

namespace App\Application\Sincronizacion\Services;

use App\Domain\Sincronizacion\Contracts\EntidadSincronizableInterface;
use InvalidArgumentException;

/**
 * Mapa tipo_entidad => clase adapter, resuelta vía el contenedor de Laravel.
 * Evita un switch gigante en ProcesarElementoSincronizacionHandler.
 *
 * TODO: los adapters concretos (VisitaSyncAdapter, PatrocinadoSyncAdapter,
 * ObservacionVisitaSyncAdapter, FotoVisitaSyncAdapter, UbicacionVisitaSyncAdapter)
 * se implementan cuando existan los Handlers reales de Application/Visitas y
 * Application/Patrocinados (Etapas 5 y 6) — ver docs/patrocinados/06-visitas.md
 * y docs/patrocinados/05-patrocinados.md. Cada adapter delega en el Handler
 * correspondiente, nunca reimplementa la regla de negocio acá.
 */
class SincronizacionRouterService
{
    /** @var array<string, class-string<EntidadSincronizableInterface>> */
    private const MAPA_ADAPTERS = [
        // 'visita'              => \App\Infrastructure\Sincronizacion\Adapters\VisitaSyncAdapter::class,
        // 'patrocinado'         => \App\Infrastructure\Sincronizacion\Adapters\PatrocinadoSyncAdapter::class,
        // 'observacion_visita'  => \App\Infrastructure\Sincronizacion\Adapters\ObservacionVisitaSyncAdapter::class,
        // 'foto_visita'         => \App\Infrastructure\Sincronizacion\Adapters\FotoVisitaSyncAdapter::class,
        // 'ubicacion_visita'    => \App\Infrastructure\Sincronizacion\Adapters\UbicacionVisitaSyncAdapter::class,
    ];

    public function resolver(string $tipoEntidad): EntidadSincronizableInterface
    {
        $clase = self::MAPA_ADAPTERS[$tipoEntidad] ?? null;

        if ($clase === null) {
            throw new InvalidArgumentException("No hay adapter de sincronización registrado para tipo_entidad '{$tipoEntidad}'.");
        }

        return app($clase);
    }

    public function soportaTipo(string $tipoEntidad): bool
    {
        return array_key_exists($tipoEntidad, self::MAPA_ADAPTERS);
    }
}
```

#### app/Application/Sincronizacion/Handlers/IniciarLoteSincronizacionHandler.php

```php
<?php

namespace App\Application\Sincronizacion\Handlers;

use App\Application\Sincronizacion\Commands\IniciarLoteSincronizacionCommand;
use App\Application\Sincronizacion\DTOs\LoteSincronizacionDTO;
use App\Domain\Sincronizacion\Contracts\LoteSincronizacionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class IniciarLoteSincronizacionHandler
{
    public function __construct(
        private readonly LoteSincronizacionRepositoryInterface $repository
    ) {}

    public function handle(IniciarLoteSincronizacionCommand $command): LoteSincronizacionDTO
    {
        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            return $this->repository->create([
                'dispositivo_id' => $command->dispositivoId,
                'user_id'        => $command->userId,
                'fecha_inicio'   => now(),
                'estado'         => 'SINCRONIZANDO',
            ]);
        });
    }
}
```

#### app/Application/Sincronizacion/Handlers/ProcesarElementoSincronizacionHandler.php

```php
<?php

namespace App\Application\Sincronizacion\Handlers;

use App\Application\Sincronizacion\Commands\ProcesarElementoSincronizacionCommand;
use App\Application\Sincronizacion\DTOs\ElementoSincronizacionDTO;
use App\Application\Sincronizacion\Services\SincronizacionRouterService;
use App\Domain\Sincronizacion\Contracts\ElementoSincronizacionRepositoryInterface;
use App\Domain\Sincronizacion\Exceptions\ConflictoVersionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cada elemento del lote es su propia unidad transaccional: si uno falla,
 * los demás del mismo lote se procesan igual (ver docs/patrocinados/
 * 07-sincronizacion.md, "Nota de diseño importante"). Por eso este Handler
 * NUNCA se invoca dentro de la transacción de otro elemento ni del lote.
 */
class ProcesarElementoSincronizacionHandler
{
    public function __construct(
        private readonly ElementoSincronizacionRepositoryInterface $repository,
        private readonly SincronizacionRouterService $router,
    ) {}

    public function handle(ProcesarElementoSincronizacionCommand $command): ElementoSincronizacionDTO
    {
        // Idempotencia: reenvío duplicado por reintento de red del cliente.
        $existente = $this->repository->findSincronizadoPorHash(
            $command->tipoEntidad,
            $command->entidadId,
            $command->hashDatos,
        );
        if ($existente !== null) {
            return $existente;
        }

        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $elemento = $this->repository->create([
                'lote_sincronizacion_id' => $command->loteId,
                'tipo_entidad'           => $command->tipoEntidad,
                'entidad_id'             => $command->entidadId,
                'operacion'              => $command->operacion,
                'hash_datos'             => $command->hashDatos,
                'estado'                 => 'PENDIENTE',
                'intentos'               => 1,
            ]);

            try {
                if (! $this->router->soportaTipo($command->tipoEntidad)) {
                    throw new \InvalidArgumentException("tipo_entidad '{$command->tipoEntidad}' no soportado.");
                }

                $adapter = $this->router->resolver($command->tipoEntidad);

                $versionEsperada = $command->payload['version'] ?? null;
                $versionActual = $adapter->obtenerVersionActual($command->entidadId);

                if ($versionActual !== null && $versionEsperada !== null && (int) $versionEsperada !== (int) $versionActual) {
                    throw new ConflictoVersionException($command->entidadId, (int) $versionEsperada, $versionActual);
                }

                $adapter->sincronizar($command->payload, $command->operacion);

                return $this->repository->update($elemento->id, [
                    'estado'               => 'SINCRONIZADO',
                    'fecha_sincronizacion' => now(),
                    'mensaje_error'        => null,
                ]);
            } catch (ConflictoVersionException $e) {
                return $this->repository->update($elemento->id, [
                    'estado'        => 'ERROR',
                    'mensaje_error' => 'conflicto_version',
                ]);
            } catch (Throwable $e) {
                Log::error('Error sincronizando elemento', [
                    'elemento_id'  => $elemento->id,
                    'tipo_entidad' => $command->tipoEntidad,
                    'entidad_id'   => $command->entidadId,
                    'error'        => $e->getMessage(),
                ]);

                return $this->repository->update($elemento->id, [
                    'estado'        => 'ERROR',
                    'mensaje_error' => $e->getMessage(),
                ]);
            }
        });
    }
}
```

#### app/Application/Sincronizacion/Handlers/CerrarLoteSincronizacionHandler.php

```php
<?php

namespace App\Application\Sincronizacion\Handlers;

use App\Application\Sincronizacion\Commands\CerrarLoteSincronizacionCommand;
use App\Application\Sincronizacion\DTOs\ElementoSincronizacionDTO;
use App\Application\Sincronizacion\DTOs\ResultadoSincronizacionDTO;
use App\Domain\Sincronizacion\Contracts\ElementoSincronizacionRepositoryInterface;
use App\Domain\Sincronizacion\Contracts\LoteSincronizacionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CerrarLoteSincronizacionHandler
{
    public function __construct(
        private readonly LoteSincronizacionRepositoryInterface $loteRepository,
        private readonly ElementoSincronizacionRepositoryInterface $elementoRepository,
    ) {}

    public function handle(CerrarLoteSincronizacionCommand $command): ResultadoSincronizacionDTO
    {
        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $tieneErrores = $this->elementoRepository->tieneErroresEnLote($command->loteId);

            $lote = $this->loteRepository->update($command->loteId, [
                'registros_enviados'  => $command->registrosEnviados,
                'registros_recibidos' => $command->registrosRecibidos,
                'estado'              => $tieneErrores ? 'ERROR' : 'COMPLETADO',
                'fecha_fin'           => now(),
            ]);

            $elementos = $this->elementoRepository->paginateByLote($command->loteId)['data'];

            $sincronizados = count(array_filter($elementos, fn (ElementoSincronizacionDTO $e) => $e->estado === 'SINCRONIZADO'));
            $errores = count(array_filter($elementos, fn (ElementoSincronizacionDTO $e) => $e->estado === 'ERROR'));

            return new ResultadoSincronizacionDTO(
                lote_id: $lote->id,
                estado: $lote->estado,
                registros_enviados: $lote->registros_enviados,
                registros_recibidos: $lote->registros_recibidos,
                sincronizados: $sincronizados,
                errores: $errores,
                elementos: $elementos,
            );
        });
    }
}
```

#### app/Application/Sincronizacion/Queries/GetLotesSincronizacionQuery.php

```php
<?php

namespace App\Application\Sincronizacion\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetLotesSincronizacionQuery
{
    public function __construct(
        public PaginationDTO $pagination,
        public ?string $dispositivoId = null,
        public ?string $estado = null,
    ) {}
}
```

#### app/Application/Sincronizacion/Queries/GetElementosPendientesQuery.php

```php
<?php

namespace App\Application\Sincronizacion\Queries;

final readonly class GetElementosPendientesQuery
{
    public function __construct(public string $loteId) {}
}
```

#### app/Application/Sincronizacion/QueryHandlers/GetLotesSincronizacionQueryHandler.php

```php
<?php

namespace App\Application\Sincronizacion\QueryHandlers;

use App\Application\Sincronizacion\Queries\GetLotesSincronizacionQuery;
use App\Domain\Sincronizacion\Contracts\LoteSincronizacionRepositoryInterface;

class GetLotesSincronizacionQueryHandler
{
    public function __construct(
        private readonly LoteSincronizacionRepositoryInterface $repository
    ) {}

    public function handle(GetLotesSincronizacionQuery $query): array
    {
        return $this->repository->paginate($query->pagination, $query->dispositivoId, $query->estado);
    }
}
```

#### app/Application/Sincronizacion/QueryHandlers/GetElementosPendientesQueryHandler.php

```php
<?php

namespace App\Application\Sincronizacion\QueryHandlers;

use App\Application\Sincronizacion\DTOs\ElementoSincronizacionDTO;
use App\Application\Sincronizacion\Queries\GetElementosPendientesQuery;
use App\Domain\Sincronizacion\Contracts\ElementoSincronizacionRepositoryInterface;

class GetElementosPendientesQueryHandler
{
    public function __construct(
        private readonly ElementoSincronizacionRepositoryInterface $repository
    ) {}

    public function handle(GetElementosPendientesQuery $query): array
    {
        $elementos = $this->repository->paginateByLote($query->loteId)['data'];

        return array_values(array_filter(
            $elementos,
            fn (ElementoSincronizacionDTO $e) => $e->estado === 'PENDIENTE'
        ));
    }
}
```

---

## Infrastructure/Sincronizacion

#### app/Infrastructure/Sincronizacion/Models/LoteSincronizacion.php

```php
<?php

namespace App\Infrastructure\Sincronizacion\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LoteSincronizacion extends Model
{
    use HasUuids;
    use UsaConexionPatrocinados;

    protected $table = 'lotes_sincronizacion';

    public $incrementing = false;
    protected $keyType = 'string';

    // La tabla no tiene created_at (ver migración) — solo fecha_inicio + updated_at.
    const CREATED_AT = null;
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'dispositivo_id',
        'user_id',
        'fecha_inicio',
        'fecha_fin',
        'registros_enviados',
        'registros_recibidos',
        'estado',
        'mensaje_error',
    ];

    protected $casts = [
        'fecha_inicio'         => 'datetime',
        'fecha_fin'            => 'datetime',
        'registros_enviados'   => 'integer',
        'registros_recibidos'  => 'integer',
    ];

    public function elementos()
    {
        return $this->hasMany(ElementoSincronizacion::class, 'lote_sincronizacion_id');
    }
}
```

#### app/Infrastructure/Sincronizacion/Models/ElementoSincronizacion.php

```php
<?php

namespace App\Infrastructure\Sincronizacion\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ElementoSincronizacion extends Model
{
    use HasUuids;
    use UsaConexionPatrocinados;

    protected $table = 'elementos_sincronizacion';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'lote_sincronizacion_id',
        'tipo_entidad',
        'entidad_id',
        'operacion',
        'hash_datos',
        'estado',
        'intentos',
        'mensaje_error',
        'fecha_sincronizacion',
    ];

    protected $casts = [
        'intentos'             => 'integer',
        'fecha_sincronizacion' => 'datetime',
    ];

    public function lote()
    {
        return $this->belongsTo(LoteSincronizacion::class, 'lote_sincronizacion_id');
    }
}
```

#### app/Infrastructure/Sincronizacion/Repositories/EloquentLoteSincronizacionRepository.php

```php
<?php

namespace App\Infrastructure\Sincronizacion\Repositories;

use App\Application\Sincronizacion\DTOs\LoteSincronizacionDTO;
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

        $paginated = $q->orderBy('fecha_inicio', 'desc')
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => collect($paginated->items())->map(fn ($m) => LoteSincronizacionDTO::fromModel($m))->all(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): LoteSincronizacionDTO
    {
        $model = LoteSincronizacion::find($id);
        if (! $model) {
            throw new LoteSincronizacionNotFoundException($id);
        }

        return LoteSincronizacionDTO::fromModel($model);
    }

    public function create(array $data): LoteSincronizacionDTO
    {
        $model = LoteSincronizacion::create($data);

        return LoteSincronizacionDTO::fromModel($model);
    }

    public function update(string $id, array $data): LoteSincronizacionDTO
    {
        $model = LoteSincronizacion::find($id);
        if (! $model) {
            throw new LoteSincronizacionNotFoundException($id);
        }
        $model->update($data);

        return LoteSincronizacionDTO::fromModel($model);
    }
}
```

#### app/Infrastructure/Sincronizacion/Repositories/EloquentElementoSincronizacionRepository.php

```php
<?php

namespace App\Infrastructure\Sincronizacion\Repositories;

use App\Application\Sincronizacion\DTOs\ElementoSincronizacionDTO;
use App\Domain\Sincronizacion\Contracts\ElementoSincronizacionRepositoryInterface;
use App\Infrastructure\Sincronizacion\Models\ElementoSincronizacion;
use Illuminate\Support\Facades\DB;

class EloquentElementoSincronizacionRepository implements ElementoSincronizacionRepositoryInterface
{
    public function paginateByLote(string $loteId): array
    {
        $items = ElementoSincronizacion::where('lote_sincronizacion_id', $loteId)
            ->orderBy('created_at')
            ->get();

        return [
            'data'  => $items->map(fn ($m) => ElementoSincronizacionDTO::fromModel($m))->all(),
            'total' => $items->count(),
        ];
    }

    public function findById(string $id): ElementoSincronizacionDTO
    {
        $model = ElementoSincronizacion::findOrFail($id);

        return ElementoSincronizacionDTO::fromModel($model);
    }

    public function findSincronizadoPorHash(string $tipoEntidad, string $entidadId, ?string $hashDatos): ?ElementoSincronizacionDTO
    {
        if ($hashDatos === null) {
            return null;
        }

        $model = ElementoSincronizacion::where('tipo_entidad', $tipoEntidad)
            ->where('entidad_id', $entidadId)
            ->where('hash_datos', $hashDatos)
            ->where('estado', 'SINCRONIZADO')
            ->first();

        return $model ? ElementoSincronizacionDTO::fromModel($model) : null;
    }

    public function create(array $data): ElementoSincronizacionDTO
    {
        $model = ElementoSincronizacion::create($data);

        return ElementoSincronizacionDTO::fromModel($model);
    }

    public function update(string $id, array $data): ElementoSincronizacionDTO
    {
        $model = ElementoSincronizacion::findOrFail($id);
        $model->update($data);

        return ElementoSincronizacionDTO::fromModel($model);
    }

    public function tieneErroresEnLote(string $loteId): bool
    {
        return DB::connection('pgsql_patrocinados')
            ->table('elementos_sincronizacion')
            ->where('lote_sincronizacion_id', $loteId)
            ->where('estado', 'ERROR')
            ->exists();
    }
}
```

---

## Http

#### app/Http/Controllers/Api/Patrocinados/SincronizacionController.php

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
            dispositivoId: $request->dispositivo_id,
            userId: auth()->id(),
        ));

        return response()->json($dto, 201);
    }

    public function procesarElemento(ProcesarElementoRequest $request, string $loteId): JsonResponse
    {
        $dto = $this->procesarElementoHandler->handle(new ProcesarElementoSincronizacionCommand(
            loteId: $loteId,
            tipoEntidad: $request->tipo_entidad,
            entidadId: $request->entidad_id,
            operacion: $request->operacion,
            hashDatos: $request->hash_datos,
            payload: $request->payload,
        ));

        return response()->json($dto, 200);
    }

    public function cerrarLote(string $loteId): JsonResponse
    {
        $resultado = $this->cerrarLoteHandler->handle(new CerrarLoteSincronizacionCommand(
            loteId: $loteId,
            registrosEnviados: request()->integer('registros_enviados', 0),
            registrosRecibidos: request()->integer('registros_recibidos', 0),
        ));

        return response()->json($resultado, 200);
    }
}
```

#### app/Http/Requests/Patrocinados/Sincronizacion/IniciarLoteRequest.php

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

#### app/Http/Requests/Patrocinados/Sincronizacion/ProcesarElementoRequest.php

```php
<?php

namespace App\Http\Requests\Patrocinados\Sincronizacion;

use Illuminate\Foundation\Http\FormRequest;

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
            'operacion'    => ['required', 'string', 'in:CREATE,UPDATE,DELETE'],
            'hash_datos'   => ['nullable', 'string', 'size:64'],
            'payload'      => ['required', 'array'],
        ];
    }
}
```

---

## Rutas (extracto de `routes/api/patrocinados.php`)

```php
Route::prefix('sincronizacion')->group(function () {
    Route::post('lotes', [SincronizacionController::class, 'iniciarLote']);
    Route::post('lotes/{loteId}/elementos', [SincronizacionController::class, 'procesarElemento']);
    Route::post('lotes/{loteId}/cerrar', [SincronizacionController::class, 'cerrarLote']);
});
```

## Binding en `PatrocinadosServiceProvider`

```php
$this->app->bind(LoteSincronizacionRepositoryInterface::class, EloquentLoteSincronizacionRepository::class);
$this->app->bind(ElementoSincronizacionRepositoryInterface::class, EloquentElementoSincronizacionRepository::class);
```

## Pendiente explícito (no bloquea el resto del módulo)

- Los adapters concretos de `SincronizacionRouterService::MAPA_ADAPTERS` (`VisitaSyncAdapter`, `PatrocinadoSyncAdapter`, etc.) se implementan cuando las Etapas 5 y 6 estén codificadas — quedan comentados a propósito.
- `EntidadSincronizableInterface::obtenerVersionActual()` asume que la entidad destino expone una columna `Version` entera (confirmado solo para `visitas` en el plan de revisión); para entidades sin versionado el adapter debe devolver `null` y el Handler omite el chequeo de conflicto.
