# Código completo — Etapa 2: AccesoPatrocinados

> Fuente de columnas: `database/migrations/patrocinados/2026_09_01_000001..000006_*`.
> Decisión ya cerrada en `02-acceso-patrocinados.md`: Sanctum multi-modelo, guard `sanctum` por defecto, rutas de auth propias bajo `/api/v1/patrocinados/auth/*`.
> Regla de bloqueo elegida (no estaba cerrada en el doc original, se fija aquí): **5 intentos fallidos consecutivos → `bloqueado_hasta = now()->addMinutes(15)`**. Ajustable en `AutenticarUsuarioHandler::MAX_INTENTOS` / `::MINUTOS_BLOQUEO` sin tocar el resto del módulo.

## Domain/AccesoPatrocinados

#### app/Domain/AccesoPatrocinados/Contracts/UsuarioRepositoryInterface.php

```php
<?php

namespace App\Domain\AccesoPatrocinados\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface UsuarioRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array;

    public function findById(string $id): mixed;

    public function findByUsername(string $username): mixed;

    public function findByEmail(string $email): mixed;

    public function create(array $data): mixed;

    public function update(string $id, array $data): mixed;

    public function delete(string|array $ids): bool;

    public function registrarLoginExitoso(string $id): mixed;

    public function registrarIntentoFallido(string $id, int $intentos, ?\DateTimeInterface $bloqueadoHasta): mixed;

    public function asignarRol(string $usuarioId, string $rolId, ?string $updatedBy): void;

    public function revocarRol(string $usuarioId, string $rolId): void;

    public function tienePermiso(string $usuarioId, string $permisoNombre): bool;
}
```

#### app/Domain/AccesoPatrocinados/Contracts/RolRepositoryInterface.php

```php
<?php

namespace App\Domain\AccesoPatrocinados\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface RolRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array;

    public function findById(string $id): mixed;

    public function create(array $data): mixed;

    public function update(string $id, array $data): mixed;

    public function delete(string|array $ids): bool;

    public function asignarPermiso(string $rolId, string $permisoId, ?string $updatedBy): void;

    public function revocarPermiso(string $rolId, string $permisoId): void;
}
```

#### app/Domain/AccesoPatrocinados/Contracts/PermisoRepositoryInterface.php

```php
<?php

namespace App\Domain\AccesoPatrocinados\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface PermisoRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array;

    public function findById(string $id): mixed;

    public function create(array $data): mixed;

    public function update(string $id, array $data): mixed;

    public function delete(string|array $ids): bool;
}
```

#### app/Domain/AccesoPatrocinados/Exceptions/UsuarioNotFoundException.php

```php
<?php

namespace App\Domain\AccesoPatrocinados\Exceptions;

class UsuarioNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Usuario '{$id}' no encontrado.", 404);
    }
}
```

#### app/Domain/AccesoPatrocinados/Exceptions/RolNotFoundException.php

```php
<?php

namespace App\Domain\AccesoPatrocinados\Exceptions;

class RolNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Rol '{$id}' no encontrado.", 404);
    }
}
```

#### app/Domain/AccesoPatrocinados/Exceptions/PermisoNotFoundException.php

```php
<?php

namespace App\Domain\AccesoPatrocinados\Exceptions;

class PermisoNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Permiso '{$id}' no encontrado.", 404);
    }
}
```

#### app/Domain/AccesoPatrocinados/Exceptions/CredencialesInvalidasException.php

```php
<?php

namespace App\Domain\AccesoPatrocinados\Exceptions;

class CredencialesInvalidasException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Usuario o contraseña incorrectos.', 401);
    }
}
```

#### app/Domain/AccesoPatrocinados/Exceptions/CuentaBloqueadaException.php

```php
<?php

namespace App\Domain\AccesoPatrocinados\Exceptions;

class CuentaBloqueadaException extends \RuntimeException
{
    public function __construct(\DateTimeInterface $bloqueadoHasta)
    {
        parent::__construct(
            'Cuenta bloqueada por intentos fallidos hasta ' . $bloqueadoHasta->format('Y-m-d H:i:s') . '.',
            403
        );
    }
}
```

## Application/AccesoPatrocinados

### DTOs

#### app/Application/AccesoPatrocinados/DTOs/UsuarioDTO.php

```php
<?php

namespace App\Application\AccesoPatrocinados\DTOs;

final readonly class UsuarioDTO
{
    // Deliberadamente sin password_hash: este DTO es lo único que puede
    // salir por un Controller. Nunca agregar el hash acá.
    public function __construct(
        public string $id,
        public string $username,
        public string $email,
        public string $nombres,
        public string $apellidos,
        public ?string $telefono,
        public string $estado,
        public ?string $ultimo_login_at,
        public array $roles,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            username: $model->username,
            email: $model->email,
            nombres: $model->nombres,
            apellidos: $model->apellidos,
            telefono: $model->telefono,
            estado: $model->estado,
            ultimo_login_at: $model->ultimo_login_at?->toIso8601String(),
            roles: $model->relationLoaded('roles')
                ? $model->roles->pluck('nombre')->all()
                : [],
            created_at: $model->created_at?->toIso8601String(),
            updated_at: $model->updated_at?->toIso8601String(),
        );
    }
}
```

#### app/Application/AccesoPatrocinados/DTOs/RolDTO.php

```php
<?php

namespace App\Application\AccesoPatrocinados\DTOs;

final readonly class RolDTO
{
    public function __construct(
        public string $id,
        public string $nombre,
        public ?string $descripcion,
        public bool $estado,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            nombre: $model->nombre,
            descripcion: $model->descripcion,
            estado: (bool) $model->estado,
            created_at: $model->created_at?->toIso8601String(),
            updated_at: $model->updated_at?->toIso8601String(),
        );
    }
}
```

#### app/Application/AccesoPatrocinados/DTOs/PermisoDTO.php

```php
<?php

namespace App\Application\AccesoPatrocinados\DTOs;

final readonly class PermisoDTO
{
    public function __construct(
        public string $id,
        public string $nombre,
        public string $modulo,
        public string $accion,
        public ?string $descripcion,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            nombre: $model->nombre,
            modulo: $model->modulo,
            accion: $model->accion,
            descripcion: $model->descripcion,
            created_at: $model->created_at?->toIso8601String(),
            updated_at: $model->updated_at?->toIso8601String(),
        );
    }
}
```

### Commands

#### app/Application/AccesoPatrocinados/Commands/LoginCommand.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class LoginCommand
{
    public function __construct(
        public string $username,
        public string $password,
        public string $deviceName,
    ) {}
}
```

#### app/Application/AccesoPatrocinados/Commands/CreateUsuarioCommand.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class CreateUsuarioCommand
{
    public function __construct(
        public string $username,
        public string $email,
        public string $password,
        public string $nombres,
        public string $apellidos,
        public ?string $telefono,
        public ?string $updated_by,
    ) {}
}
```

#### app/Application/AccesoPatrocinados/Commands/UpdateUsuarioCommand.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class UpdateUsuarioCommand
{
    public function __construct(
        public string $id,
        public string $username,
        public string $email,
        public string $nombres,
        public string $apellidos,
        public ?string $telefono,
        public string $estado,
        public ?string $updated_by,
    ) {}
}
```

#### app/Application/AccesoPatrocinados/Commands/DeleteUsuarioCommand.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class DeleteUsuarioCommand
{
    public function __construct(public string $id) {}
}
```

#### app/Application/AccesoPatrocinados/Commands/AsignarRolCommand.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class AsignarRolCommand
{
    public function __construct(
        public string $usuarioId,
        public string $rolId,
        public ?string $updatedBy,
    ) {}
}
```

#### app/Application/AccesoPatrocinados/Commands/RevocarRolCommand.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class RevocarRolCommand
{
    public function __construct(
        public string $usuarioId,
        public string $rolId,
    ) {}
}
```

#### app/Application/AccesoPatrocinados/Commands/CreateRolCommand.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class CreateRolCommand
{
    public function __construct(
        public string $nombre,
        public ?string $descripcion,
        public bool $estado,
        public ?string $updated_by,
    ) {}
}
```

#### app/Application/AccesoPatrocinados/Commands/UpdateRolCommand.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class UpdateRolCommand
{
    public function __construct(
        public string $id,
        public string $nombre,
        public ?string $descripcion,
        public bool $estado,
        public ?string $updated_by,
    ) {}
}
```

#### app/Application/AccesoPatrocinados/Commands/DeleteRolCommand.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class DeleteRolCommand
{
    public function __construct(public string $id) {}
}
```

#### app/Application/AccesoPatrocinados/Commands/CreatePermisoCommand.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class CreatePermisoCommand
{
    public function __construct(
        public string $nombre,
        public string $modulo,
        public string $accion,
        public ?string $descripcion,
        public ?string $updated_by,
    ) {}
}
```

#### app/Application/AccesoPatrocinados/Commands/UpdatePermisoCommand.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class UpdatePermisoCommand
{
    public function __construct(
        public string $id,
        public string $nombre,
        public string $modulo,
        public string $accion,
        public ?string $descripcion,
        public ?string $updated_by,
    ) {}
}
```

#### app/Application/AccesoPatrocinados/Commands/DeletePermisoCommand.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class DeletePermisoCommand
{
    public function __construct(public string $id) {}
}
```

#### app/Application/AccesoPatrocinados/Commands/AsignarPermisoARolCommand.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class AsignarPermisoARolCommand
{
    public function __construct(
        public string $rolId,
        public string $permisoId,
        public ?string $updatedBy,
    ) {}
}
```

#### app/Application/AccesoPatrocinados/Commands/RevocarPermisoDeRolCommand.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class RevocarPermisoDeRolCommand
{
    public function __construct(
        public string $rolId,
        public string $permisoId,
    ) {}
}
```

### Handlers

#### app/Application/AccesoPatrocinados/Handlers/AutenticarUsuarioHandler.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\LoginCommand;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;
use App\Domain\AccesoPatrocinados\Exceptions\CredencialesInvalidasException;
use App\Domain\AccesoPatrocinados\Exceptions\CuentaBloqueadaException;
use App\Infrastructure\AccesoPatrocinados\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class AutenticarUsuarioHandler
{
    private const MAX_INTENTOS = 5;
    private const MINUTOS_BLOQUEO = 15;

    public function __construct(
        private readonly UsuarioRepositoryInterface $repository
    ) {}

    /**
     * @return array{usuario: Usuario, token: string}
     */
    public function handle(LoginCommand $command): array
    {
        /** @var Usuario|null $usuario */
        $usuario = $this->repository->findByUsername($command->username);

        if ($usuario === null || $usuario->estado === 'INACTIVO') {
            throw new CredencialesInvalidasException();
        }

        if ($usuario->bloqueado_hasta !== null && $usuario->bloqueado_hasta->isFuture()) {
            throw new CuentaBloqueadaException($usuario->bloqueado_hasta);
        }

        if (! Hash::check($command->password, $usuario->password_hash)) {
            $intentos = $usuario->intentos_fallidos + 1;
            $bloqueadoHasta = $intentos >= self::MAX_INTENTOS
                ? now()->addMinutes(self::MINUTOS_BLOQUEO)
                : null;

            $this->repository->registrarIntentoFallido($usuario->id, $intentos, $bloqueadoHasta);

            if ($bloqueadoHasta !== null) {
                throw new CuentaBloqueadaException($bloqueadoHasta);
            }

            throw new CredencialesInvalidasException();
        }

        $usuario = $this->repository->registrarLoginExitoso($usuario->id);

        $token = $usuario->createToken($command->deviceName)->plainTextToken;

        return ['usuario' => $usuario, 'token' => $token];
    }
}
```

#### app/Application/AccesoPatrocinados/Handlers/CreateUsuarioHandler.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\CreateUsuarioCommand;
use App\Application\AccesoPatrocinados\DTOs\UsuarioDTO;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class CreateUsuarioHandler
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $repository
    ) {}

    public function handle(CreateUsuarioCommand $command): UsuarioDTO
    {
        $model = $this->repository->create([
            'username' => $command->username,
            'email' => $command->email,
            'password_hash' => Hash::make($command->password),
            'nombres' => $command->nombres,
            'apellidos' => $command->apellidos,
            'telefono' => $command->telefono,
            'estado' => 'ACTIVO',
            'updated_by' => $command->updated_by,
        ]);

        return UsuarioDTO::fromModel($model);
    }
}
```

#### app/Application/AccesoPatrocinados/Handlers/UpdateUsuarioHandler.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\UpdateUsuarioCommand;
use App\Application\AccesoPatrocinados\DTOs\UsuarioDTO;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;
use App\Domain\AccesoPatrocinados\Exceptions\UsuarioNotFoundException;

class UpdateUsuarioHandler
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $repository
    ) {}

    public function handle(UpdateUsuarioCommand $command): UsuarioDTO
    {
        if ($this->repository->findById($command->id) === null) {
            throw new UsuarioNotFoundException($command->id);
        }

        // password_hash deliberadamente no se toca acá — un cambio de
        // contraseña es un flujo propio (fuera del alcance de esta etapa
        // base), nunca un campo más del update genérico de perfil.
        $model = $this->repository->update($command->id, [
            'username' => $command->username,
            'email' => $command->email,
            'nombres' => $command->nombres,
            'apellidos' => $command->apellidos,
            'telefono' => $command->telefono,
            'estado' => $command->estado,
            'updated_by' => $command->updated_by,
        ]);

        return UsuarioDTO::fromModel($model);
    }
}
```

#### app/Application/AccesoPatrocinados/Handlers/DeleteUsuarioHandler.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\DeleteUsuarioCommand;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;

class DeleteUsuarioHandler
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $repository
    ) {}

    public function handle(DeleteUsuarioCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
```

#### app/Application/AccesoPatrocinados/Handlers/AsignarRolHandler.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\AsignarRolCommand;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;

class AsignarRolHandler
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $repository
    ) {}

    public function handle(AsignarRolCommand $command): void
    {
        $this->repository->asignarRol($command->usuarioId, $command->rolId, $command->updatedBy);
    }
}
```

#### app/Application/AccesoPatrocinados/Handlers/RevocarRolHandler.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\RevocarRolCommand;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;

class RevocarRolHandler
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $repository
    ) {}

    public function handle(RevocarRolCommand $command): void
    {
        $this->repository->revocarRol($command->usuarioId, $command->rolId);
    }
}
```

#### app/Application/AccesoPatrocinados/Handlers/CreateRolHandler.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\CreateRolCommand;
use App\Application\AccesoPatrocinados\DTOs\RolDTO;
use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;

class CreateRolHandler
{
    public function __construct(
        private readonly RolRepositoryInterface $repository
    ) {}

    public function handle(CreateRolCommand $command): RolDTO
    {
        $model = $this->repository->create([
            'nombre' => $command->nombre,
            'descripcion' => $command->descripcion,
            'estado' => $command->estado,
            'updated_by' => $command->updated_by,
        ]);

        return RolDTO::fromModel($model);
    }
}
```

#### app/Application/AccesoPatrocinados/Handlers/UpdateRolHandler.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\UpdateRolCommand;
use App\Application\AccesoPatrocinados\DTOs\RolDTO;
use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;
use App\Domain\AccesoPatrocinados\Exceptions\RolNotFoundException;

class UpdateRolHandler
{
    public function __construct(
        private readonly RolRepositoryInterface $repository
    ) {}

    public function handle(UpdateRolCommand $command): RolDTO
    {
        if ($this->repository->findById($command->id) === null) {
            throw new RolNotFoundException($command->id);
        }

        $model = $this->repository->update($command->id, [
            'nombre' => $command->nombre,
            'descripcion' => $command->descripcion,
            'estado' => $command->estado,
            'updated_by' => $command->updated_by,
        ]);

        return RolDTO::fromModel($model);
    }
}
```

#### app/Application/AccesoPatrocinados/Handlers/DeleteRolHandler.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\DeleteRolCommand;
use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;

class DeleteRolHandler
{
    public function __construct(
        private readonly RolRepositoryInterface $repository
    ) {}

    public function handle(DeleteRolCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
```

#### app/Application/AccesoPatrocinados/Handlers/CreatePermisoHandler.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\CreatePermisoCommand;
use App\Application\AccesoPatrocinados\DTOs\PermisoDTO;
use App\Domain\AccesoPatrocinados\Contracts\PermisoRepositoryInterface;

class CreatePermisoHandler
{
    public function __construct(
        private readonly PermisoRepositoryInterface $repository
    ) {}

    public function handle(CreatePermisoCommand $command): PermisoDTO
    {
        $model = $this->repository->create([
            'nombre' => $command->nombre,
            'modulo' => $command->modulo,
            'accion' => $command->accion,
            'descripcion' => $command->descripcion,
            'updated_by' => $command->updated_by,
        ]);

        return PermisoDTO::fromModel($model);
    }
}
```

#### app/Application/AccesoPatrocinados/Handlers/UpdatePermisoHandler.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\UpdatePermisoCommand;
use App\Application\AccesoPatrocinados\DTOs\PermisoDTO;
use App\Domain\AccesoPatrocinados\Contracts\PermisoRepositoryInterface;
use App\Domain\AccesoPatrocinados\Exceptions\PermisoNotFoundException;

class UpdatePermisoHandler
{
    public function __construct(
        private readonly PermisoRepositoryInterface $repository
    ) {}

    public function handle(UpdatePermisoCommand $command): PermisoDTO
    {
        if ($this->repository->findById($command->id) === null) {
            throw new PermisoNotFoundException($command->id);
        }

        $model = $this->repository->update($command->id, [
            'nombre' => $command->nombre,
            'modulo' => $command->modulo,
            'accion' => $command->accion,
            'descripcion' => $command->descripcion,
            'updated_by' => $command->updated_by,
        ]);

        return PermisoDTO::fromModel($model);
    }
}
```

#### app/Application/AccesoPatrocinados/Handlers/DeletePermisoHandler.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\DeletePermisoCommand;
use App\Domain\AccesoPatrocinados\Contracts\PermisoRepositoryInterface;

class DeletePermisoHandler
{
    public function __construct(
        private readonly PermisoRepositoryInterface $repository
    ) {}

    public function handle(DeletePermisoCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
```

#### app/Application/AccesoPatrocinados/Handlers/AsignarPermisoARolHandler.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\AsignarPermisoARolCommand;
use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;

class AsignarPermisoARolHandler
{
    public function __construct(
        private readonly RolRepositoryInterface $repository
    ) {}

    public function handle(AsignarPermisoARolCommand $command): void
    {
        $this->repository->asignarPermiso($command->rolId, $command->permisoId, $command->updatedBy);
    }
}
```

#### app/Application/AccesoPatrocinados/Handlers/RevocarPermisoDeRolHandler.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\RevocarPermisoDeRolCommand;
use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;

class RevocarPermisoDeRolHandler
{
    public function __construct(
        private readonly RolRepositoryInterface $repository
    ) {}

    public function handle(RevocarPermisoDeRolCommand $command): void
    {
        $this->repository->revocarPermiso($command->rolId, $command->permisoId);
    }
}
```

### Queries

#### app/Application/AccesoPatrocinados/Queries/GetUsuariosQuery.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetUsuariosQuery
{
    public function __construct(public PaginationDTO $pagination) {}
}
```

#### app/Application/AccesoPatrocinados/Queries/GetUsuarioByIdQuery.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Queries;

final readonly class GetUsuarioByIdQuery
{
    public function __construct(public string $id) {}
}
```

#### app/Application/AccesoPatrocinados/Queries/GetRolesQuery.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetRolesQuery
{
    public function __construct(public PaginationDTO $pagination) {}
}
```

#### app/Application/AccesoPatrocinados/Queries/GetPermisosQuery.php

```php
<?php

namespace App\Application\AccesoPatrocinados\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetPermisosQuery
{
    public function __construct(public PaginationDTO $pagination) {}
}
```

### QueryHandlers

#### app/Application/AccesoPatrocinados/QueryHandlers/GetUsuariosQueryHandler.php

```php
<?php

namespace App\Application\AccesoPatrocinados\QueryHandlers;

use App\Application\AccesoPatrocinados\Queries\GetUsuariosQuery;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;

class GetUsuariosQueryHandler
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $repository
    ) {}

    public function handle(GetUsuariosQuery $query): array
    {
        return $this->repository->paginate($query->pagination);
    }
}
```

#### app/Application/AccesoPatrocinados/QueryHandlers/GetUsuarioByIdQueryHandler.php

```php
<?php

namespace App\Application\AccesoPatrocinados\QueryHandlers;

use App\Application\AccesoPatrocinados\DTOs\UsuarioDTO;
use App\Application\AccesoPatrocinados\Queries\GetUsuarioByIdQuery;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;
use App\Domain\AccesoPatrocinados\Exceptions\UsuarioNotFoundException;

class GetUsuarioByIdQueryHandler
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $repository
    ) {}

    public function handle(GetUsuarioByIdQuery $query): UsuarioDTO
    {
        $model = $this->repository->findById($query->id);
        if ($model === null) {
            throw new UsuarioNotFoundException($query->id);
        }

        return UsuarioDTO::fromModel($model);
    }
}
```

#### app/Application/AccesoPatrocinados/QueryHandlers/GetRolesQueryHandler.php

```php
<?php

namespace App\Application\AccesoPatrocinados\QueryHandlers;

use App\Application\AccesoPatrocinados\Queries\GetRolesQuery;
use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;

class GetRolesQueryHandler
{
    public function __construct(
        private readonly RolRepositoryInterface $repository
    ) {}

    public function handle(GetRolesQuery $query): array
    {
        return $this->repository->paginate($query->pagination);
    }
}
```

#### app/Application/AccesoPatrocinados/QueryHandlers/GetPermisosQueryHandler.php

```php
<?php

namespace App\Application\AccesoPatrocinados\QueryHandlers;

use App\Application\AccesoPatrocinados\Queries\GetPermisosQuery;
use App\Domain\AccesoPatrocinados\Contracts\PermisoRepositoryInterface;

class GetPermisosQueryHandler
{
    public function __construct(
        private readonly PermisoRepositoryInterface $repository
    ) {}

    public function handle(GetPermisosQuery $query): array
    {
        return $this->repository->paginate($query->pagination);
    }
}
```

## Infrastructure/AccesoPatrocinados

### Models

#### app/Infrastructure/AccesoPatrocinados/Models/Usuario.php

```php
<?php

namespace App\Infrastructure\AccesoPatrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Model
{
    use HasApiTokens, HasUuids, SoftDeletes, UsaConexionPatrocinados;

    protected $table = 'usuarios';

    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'nombres',
        'apellidos',
        'telefono',
        'estado',
        'intentos_fallidos',
        'bloqueado_hasta',
        'ultimo_login_at',
        'password_cambiado_at',
        'updated_by',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'bloqueado_hasta' => 'datetime',
        'ultimo_login_at' => 'datetime',
        'password_cambiado_at' => 'datetime',
    ];

    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'usuarios_roles', 'usuario_id', 'rol_id')
            ->using(UsuarioRol::class)
            ->withTimestamps();
    }
}
```

#### app/Infrastructure/AccesoPatrocinados/Models/Rol.php

```php
<?php

namespace App\Infrastructure\AccesoPatrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'roles';

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
        'updated_by',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function permisos(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'roles_permisos', 'rol_id', 'permiso_id')
            ->using(RolPermiso::class)
            ->withTimestamps();
    }

    public function usuarios(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'usuarios_roles', 'rol_id', 'usuario_id')
            ->using(UsuarioRol::class)
            ->withTimestamps();
    }
}
```

#### app/Infrastructure/AccesoPatrocinados/Models/Permiso.php

```php
<?php

namespace App\Infrastructure\AccesoPatrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'permisos';

    protected $fillable = [
        'nombre',
        'modulo',
        'accion',
        'descripcion',
        'updated_by',
    ];
}
```

#### app/Infrastructure/AccesoPatrocinados/Models/UsuarioRol.php

```php
<?php

namespace App\Infrastructure\AccesoPatrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UsuarioRol extends Pivot
{
    use UsaConexionPatrocinados;

    protected $table = 'usuarios_roles';

    // PK compuesta (usuario_id, rol_id), sin columna id propia.
    public $incrementing = false;

    protected $fillable = [
        'usuario_id',
        'rol_id',
        'updated_by',
    ];
}
```

#### app/Infrastructure/AccesoPatrocinados/Models/RolPermiso.php

```php
<?php

namespace App\Infrastructure\AccesoPatrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Relations\Pivot;

class RolPermiso extends Pivot
{
    use UsaConexionPatrocinados;

    protected $table = 'roles_permisos';

    // PK compuesta (rol_id, permiso_id), sin columna id propia.
    public $incrementing = false;

    protected $fillable = [
        'rol_id',
        'permiso_id',
        'updated_by',
    ];
}
```

### Repositories

#### app/Infrastructure/AccesoPatrocinados/Repositories/EloquentUsuarioRepository.php

```php
<?php

namespace App\Infrastructure\AccesoPatrocinados\Repositories;

use App\Application\AccesoPatrocinados\DTOs\UsuarioDTO;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;
use App\Domain\AccesoPatrocinados\Exceptions\UsuarioNotFoundException;
use App\Infrastructure\AccesoPatrocinados\Models\Usuario;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentUsuarioRepository implements UsuarioRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array
    {
        $q = Usuario::query()->with('roles');

        if ($pagination->query !== '') {
            $q->where(function ($sub) use ($pagination) {
                $sub->where('username', 'ilike', "%{$pagination->query}%")
                    ->orWhere('email', 'ilike', "%{$pagination->query}%")
                    ->orWhere('nombres', 'ilike', "%{$pagination->query}%")
                    ->orWhere('apellidos', 'ilike', "%{$pagination->query}%");
            });
        }

        $paginated = $q->orderBy($pagination->sortKey ?: 'created_at', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data' => collect($paginated->items())->map(fn ($m) => UsuarioDTO::fromModel($m))->all(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): ?Usuario
    {
        return Usuario::with('roles')->find($id);
    }

    public function findByUsername(string $username): ?Usuario
    {
        return Usuario::where('username', $username)->first();
    }

    public function findByEmail(string $email): ?Usuario
    {
        return Usuario::where('email', $email)->first();
    }

    public function create(array $data): Usuario
    {
        return Usuario::create($data);
    }

    public function update(string $id, array $data): Usuario
    {
        $model = Usuario::find($id);
        if ($model === null) {
            throw new UsuarioNotFoundException($id);
        }

        $model->update($data);

        return $model->refresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Usuario::destroy($ids);
    }

    public function registrarLoginExitoso(string $id): Usuario
    {
        $model = Usuario::find($id);
        if ($model === null) {
            throw new UsuarioNotFoundException($id);
        }

        $model->update([
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'ultimo_login_at' => now(),
        ]);

        return $model->refresh();
    }

    public function registrarIntentoFallido(string $id, int $intentos, ?\DateTimeInterface $bloqueadoHasta): Usuario
    {
        $model = Usuario::find($id);
        if ($model === null) {
            throw new UsuarioNotFoundException($id);
        }

        $model->update([
            'intentos_fallidos' => $intentos,
            'bloqueado_hasta' => $bloqueadoHasta,
        ]);

        return $model->refresh();
    }

    public function asignarRol(string $usuarioId, string $rolId, ?string $updatedBy): void
    {
        $usuario = Usuario::findOrFail($usuarioId);
        $usuario->roles()->syncWithoutDetaching([
            $rolId => ['updated_by' => $updatedBy],
        ]);
    }

    public function revocarRol(string $usuarioId, string $rolId): void
    {
        $usuario = Usuario::findOrFail($usuarioId);
        $usuario->roles()->detach($rolId);
    }

    public function tienePermiso(string $usuarioId, string $permisoNombre): bool
    {
        return Usuario::query()
            ->whereKey($usuarioId)
            ->whereHas('roles.permisos', fn ($q) => $q->where('nombre', $permisoNombre))
            ->exists();
    }
}
```

#### app/Infrastructure/AccesoPatrocinados/Repositories/EloquentRolRepository.php

```php
<?php

namespace App\Infrastructure\AccesoPatrocinados\Repositories;

use App\Application\AccesoPatrocinados\DTOs\RolDTO;
use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;
use App\Domain\AccesoPatrocinados\Exceptions\RolNotFoundException;
use App\Infrastructure\AccesoPatrocinados\Models\Rol;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentRolRepository implements RolRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array
    {
        $q = Rol::query();

        if ($pagination->query !== '') {
            $q->where('nombre', 'ilike', "%{$pagination->query}%");
        }

        $paginated = $q->orderBy($pagination->sortKey ?: 'nombre', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data' => collect($paginated->items())->map(fn ($m) => RolDTO::fromModel($m))->all(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): ?Rol
    {
        return Rol::find($id);
    }

    public function create(array $data): Rol
    {
        return Rol::create($data);
    }

    public function update(string $id, array $data): Rol
    {
        $model = Rol::find($id);
        if ($model === null) {
            throw new RolNotFoundException($id);
        }

        $model->update($data);

        return $model->refresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Rol::destroy($ids);
    }

    public function asignarPermiso(string $rolId, string $permisoId, ?string $updatedBy): void
    {
        $rol = Rol::findOrFail($rolId);
        $rol->permisos()->syncWithoutDetaching([
            $permisoId => ['updated_by' => $updatedBy],
        ]);
    }

    public function revocarPermiso(string $rolId, string $permisoId): void
    {
        $rol = Rol::findOrFail($rolId);
        $rol->permisos()->detach($permisoId);
    }
}
```

#### app/Infrastructure/AccesoPatrocinados/Repositories/EloquentPermisoRepository.php

```php
<?php

namespace App\Infrastructure\AccesoPatrocinados\Repositories;

use App\Application\AccesoPatrocinados\DTOs\PermisoDTO;
use App\Domain\AccesoPatrocinados\Contracts\PermisoRepositoryInterface;
use App\Domain\AccesoPatrocinados\Exceptions\PermisoNotFoundException;
use App\Infrastructure\AccesoPatrocinados\Models\Permiso;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentPermisoRepository implements PermisoRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array
    {
        $q = Permiso::query();

        if ($pagination->query !== '') {
            $q->where('nombre', 'ilike', "%{$pagination->query}%");
        }

        $paginated = $q->orderBy($pagination->sortKey ?: 'modulo', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data' => collect($paginated->items())->map(fn ($m) => PermisoDTO::fromModel($m))->all(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): ?Permiso
    {
        return Permiso::find($id);
    }

    public function create(array $data): Permiso
    {
        return Permiso::create($data);
    }

    public function update(string $id, array $data): Permiso
    {
        $model = Permiso::find($id);
        if ($model === null) {
            throw new PermisoNotFoundException($id);
        }

        $model->update($data);

        return $model->refresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Permiso::destroy($ids);
    }
}
```

## Middleware de permisos propio

#### app/Http/Middleware/PermisoPatrocinadosMiddleware.php

```php
<?php

namespace App\Http\Middleware;

use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermisoPatrocinadosMiddleware
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $repository
    ) {}

    /**
     * Uso: ->middleware('permiso-patrocinados:visitas.crear|visitas.editar')
     * Autoriza si el usuario autenticado tiene AL MENOS UNO de los permisos
     * separados por "|" (mismo formato que el middleware `permiso:` legado).
     */
    public function handle(Request $request, Closure $next, string $permisos): Response
    {
        $usuario = $request->user();

        if ($usuario === null) {
            abort(401, 'No autenticado.');
        }

        foreach (explode('|', $permisos) as $permiso) {
            if ($this->repository->tienePermiso($usuario->id, trim($permiso))) {
                return $next($request);
            }
        }

        abort(403, 'No tiene permiso para esta acción.');
    }
}
```

Registrar el alias en `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        // ...alias existentes...
        'permiso-patrocinados' => \App\Http\Middleware\PermisoPatrocinadosMiddleware::class,
    ]);
})
```

## Http

### Controllers

#### app/Http/Controllers/Api/Patrocinados/AuthController.php

```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\AccesoPatrocinados\Commands\LoginCommand;
use App\Application\AccesoPatrocinados\DTOs\UsuarioDTO;
use App\Application\AccesoPatrocinados\Handlers\AutenticarUsuarioHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\AccesoPatrocinados\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AutenticarUsuarioHandler $autenticarHandler,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $resultado = $this->autenticarHandler->handle(new LoginCommand(
            username: $request->username,
            password: $request->password,
            deviceName: $request->device_name ?? 'api',
        ));

        return response()->json([
            'usuario' => UsuarioDTO::fromModel($resultado['usuario']),
            'token' => $resultado['token'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(UsuarioDTO::fromModel($request->user()->load('roles')));
    }
}
```

#### app/Http/Controllers/Api/Patrocinados/UsuarioController.php

```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\AccesoPatrocinados\Commands\AsignarRolCommand;
use App\Application\AccesoPatrocinados\Commands\CreateUsuarioCommand;
use App\Application\AccesoPatrocinados\Commands\DeleteUsuarioCommand;
use App\Application\AccesoPatrocinados\Commands\RevocarRolCommand;
use App\Application\AccesoPatrocinados\Commands\UpdateUsuarioCommand;
use App\Application\AccesoPatrocinados\Handlers\AsignarRolHandler;
use App\Application\AccesoPatrocinados\Handlers\CreateUsuarioHandler;
use App\Application\AccesoPatrocinados\Handlers\DeleteUsuarioHandler;
use App\Application\AccesoPatrocinados\Handlers\RevocarRolHandler;
use App\Application\AccesoPatrocinados\Handlers\UpdateUsuarioHandler;
use App\Application\AccesoPatrocinados\Queries\GetUsuarioByIdQuery;
use App\Application\AccesoPatrocinados\Queries\GetUsuariosQuery;
use App\Application\AccesoPatrocinados\QueryHandlers\GetUsuarioByIdQueryHandler;
use App\Application\AccesoPatrocinados\QueryHandlers\GetUsuariosQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\AccesoPatrocinados\AsignarRolRequest;
use App\Http\Requests\Patrocinados\AccesoPatrocinados\StoreUsuarioRequest;
use App\Http\Requests\Patrocinados\AccesoPatrocinados\UpdateUsuarioRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function __construct(
        private readonly GetUsuariosQueryHandler $getUsuariosHandler,
        private readonly GetUsuarioByIdQueryHandler $getUsuarioByIdHandler,
        private readonly CreateUsuarioHandler $createHandler,
        private readonly UpdateUsuarioHandler $updateHandler,
        private readonly DeleteUsuarioHandler $deleteHandler,
        private readonly AsignarRolHandler $asignarRolHandler,
        private readonly RevocarRolHandler $revocarRolHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json(
            $this->getUsuariosHandler->handle(new GetUsuariosQuery($pagination))
        );
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(
            $this->getUsuarioByIdHandler->handle(new GetUsuarioByIdQuery($id))
        );
    }

    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateUsuarioCommand(
            username: $request->username,
            email: $request->email,
            password: $request->password,
            nombres: $request->nombres,
            apellidos: $request->apellidos,
            telefono: $request->telefono,
            updated_by: auth()->id(),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateUsuarioRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateUsuarioCommand(
            id: $id,
            username: $request->username,
            email: $request->email,
            nombres: $request->nombres,
            apellidos: $request->apellidos,
            telefono: $request->telefono,
            estado: $request->estado,
            updated_by: auth()->id(),
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteUsuarioCommand($id));

        return response()->json(null, 204);
    }

    public function asignarRol(AsignarRolRequest $request, string $id): JsonResponse
    {
        $this->asignarRolHandler->handle(new AsignarRolCommand(
            usuarioId: $id,
            rolId: $request->rol_id,
            updatedBy: auth()->id(),
        ));

        return response()->json(
            $this->getUsuarioByIdHandler->handle(new GetUsuarioByIdQuery($id))
        );
    }

    public function revocarRol(string $id, string $rolId): JsonResponse
    {
        $this->revocarRolHandler->handle(new RevocarRolCommand($id, $rolId));

        return response()->json(null, 204);
    }
}
```

#### app/Http/Controllers/Api/Patrocinados/RolController.php

```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\AccesoPatrocinados\Commands\AsignarPermisoARolCommand;
use App\Application\AccesoPatrocinados\Commands\CreateRolCommand;
use App\Application\AccesoPatrocinados\Commands\DeleteRolCommand;
use App\Application\AccesoPatrocinados\Commands\RevocarPermisoDeRolCommand;
use App\Application\AccesoPatrocinados\Commands\UpdateRolCommand;
use App\Application\AccesoPatrocinados\Handlers\AsignarPermisoARolHandler;
use App\Application\AccesoPatrocinados\Handlers\CreateRolHandler;
use App\Application\AccesoPatrocinados\Handlers\DeleteRolHandler;
use App\Application\AccesoPatrocinados\Handlers\RevocarPermisoDeRolHandler;
use App\Application\AccesoPatrocinados\Handlers\UpdateRolHandler;
use App\Application\AccesoPatrocinados\Queries\GetRolesQuery;
use App\Application\AccesoPatrocinados\QueryHandlers\GetRolesQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\AccesoPatrocinados\AsignarPermisoRequest;
use App\Http\Requests\Patrocinados\AccesoPatrocinados\StoreRolRequest;
use App\Http\Requests\Patrocinados\AccesoPatrocinados\UpdateRolRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RolController extends Controller
{
    public function __construct(
        private readonly GetRolesQueryHandler $getRolesHandler,
        private readonly CreateRolHandler $createHandler,
        private readonly UpdateRolHandler $updateHandler,
        private readonly DeleteRolHandler $deleteHandler,
        private readonly AsignarPermisoARolHandler $asignarPermisoHandler,
        private readonly RevocarPermisoDeRolHandler $revocarPermisoHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json(
            $this->getRolesHandler->handle(new GetRolesQuery($pagination))
        );
    }

    public function store(StoreRolRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateRolCommand(
            nombre: $request->nombre,
            descripcion: $request->descripcion,
            estado: $request->boolean('estado', true),
            updated_by: auth()->id(),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateRolRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateRolCommand(
            id: $id,
            nombre: $request->nombre,
            descripcion: $request->descripcion,
            estado: $request->boolean('estado', true),
            updated_by: auth()->id(),
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteRolCommand($id));

        return response()->json(null, 204);
    }

    public function asignarPermiso(AsignarPermisoRequest $request, string $id): JsonResponse
    {
        $this->asignarPermisoHandler->handle(new AsignarPermisoARolCommand(
            rolId: $id,
            permisoId: $request->permiso_id,
            updatedBy: auth()->id(),
        ));

        return response()->json(null, 204);
    }

    public function revocarPermiso(string $id, string $permisoId): JsonResponse
    {
        $this->revocarPermisoHandler->handle(new RevocarPermisoDeRolCommand($id, $permisoId));

        return response()->json(null, 204);
    }
}
```

#### app/Http/Controllers/Api/Patrocinados/PermisoController.php

```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\AccesoPatrocinados\Commands\CreatePermisoCommand;
use App\Application\AccesoPatrocinados\Commands\DeletePermisoCommand;
use App\Application\AccesoPatrocinados\Commands\UpdatePermisoCommand;
use App\Application\AccesoPatrocinados\Handlers\CreatePermisoHandler;
use App\Application\AccesoPatrocinados\Handlers\DeletePermisoHandler;
use App\Application\AccesoPatrocinados\Handlers\UpdatePermisoHandler;
use App\Application\AccesoPatrocinados\Queries\GetPermisosQuery;
use App\Application\AccesoPatrocinados\QueryHandlers\GetPermisosQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\AccesoPatrocinados\StorePermisoRequest;
use App\Http\Requests\Patrocinados\AccesoPatrocinados\UpdatePermisoRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermisoController extends Controller
{
    public function __construct(
        private readonly GetPermisosQueryHandler $getPermisosHandler,
        private readonly CreatePermisoHandler $createHandler,
        private readonly UpdatePermisoHandler $updateHandler,
        private readonly DeletePermisoHandler $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json(
            $this->getPermisosHandler->handle(new GetPermisosQuery($pagination))
        );
    }

    public function store(StorePermisoRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreatePermisoCommand(
            nombre: $request->nombre,
            modulo: $request->modulo,
            accion: $request->accion,
            descripcion: $request->descripcion,
            updated_by: auth()->id(),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdatePermisoRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdatePermisoCommand(
            id: $id,
            nombre: $request->nombre,
            modulo: $request->modulo,
            accion: $request->accion,
            descripcion: $request->descripcion,
            updated_by: auth()->id(),
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeletePermisoCommand($id));

        return response()->json(null, 204);
    }
}
```

### Requests

#### app/Http/Requests/Patrocinados/AccesoPatrocinados/LoginRequest.php

```php
<?php

namespace App\Http\Requests\Patrocinados\AccesoPatrocinados;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/AccesoPatrocinados/StoreUsuarioRequest.php

```php
<?php

namespace App\Http\Requests\Patrocinados\AccesoPatrocinados;

use Illuminate\Foundation\Http\FormRequest;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:80', 'unique:pgsql_patrocinados.usuarios,username'],
            'email' => ['required', 'email', 'max:180', 'unique:pgsql_patrocinados.usuarios,email'],
            'password' => ['required', 'string', 'min:8'],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:40'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/AccesoPatrocinados/UpdateUsuarioRequest.php

```php
<?php

namespace App\Http\Requests\Patrocinados\AccesoPatrocinados;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => [
                'required', 'string', 'max:80',
                Rule::unique('pgsql_patrocinados.usuarios', 'username')->ignore($this->route('id')),
            ],
            'email' => [
                'required', 'email', 'max:180',
                Rule::unique('pgsql_patrocinados.usuarios', 'email')->ignore($this->route('id')),
            ],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:40'],
            'estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO', 'BLOQUEADO'])],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/AccesoPatrocinados/AsignarRolRequest.php

```php
<?php

namespace App\Http\Requests\Patrocinados\AccesoPatrocinados;

use Illuminate\Foundation\Http\FormRequest;

class AsignarRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rol_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.roles,id'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/AccesoPatrocinados/StoreRolRequest.php

```php
<?php

namespace App\Http\Requests\Patrocinados\AccesoPatrocinados;

use Illuminate\Foundation\Http\FormRequest;

class StoreRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:80', 'unique:pgsql_patrocinados.roles,nombre'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estado' => ['sometimes', 'boolean'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/AccesoPatrocinados/UpdateRolRequest.php

```php
<?php

namespace App\Http\Requests\Patrocinados\AccesoPatrocinados;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required', 'string', 'max:80',
                Rule::unique('pgsql_patrocinados.roles', 'nombre')->ignore($this->route('id')),
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estado' => ['sometimes', 'boolean'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/AccesoPatrocinados/AsignarPermisoRequest.php

```php
<?php

namespace App\Http\Requests\Patrocinados\AccesoPatrocinados;

use Illuminate\Foundation\Http\FormRequest;

class AsignarPermisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permiso_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.permisos,id'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/AccesoPatrocinados/StorePermisoRequest.php

```php
<?php

namespace App\Http\Requests\Patrocinados\AccesoPatrocinados;

use Illuminate\Foundation\Http\FormRequest;

class StorePermisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120', 'unique:pgsql_patrocinados.permisos,nombre'],
            'modulo' => ['required', 'string', 'max:80'],
            'accion' => ['required', 'string', 'max:80'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/AccesoPatrocinados/UpdatePermisoRequest.php

```php
<?php

namespace App\Http\Requests\Patrocinados\AccesoPatrocinados;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required', 'string', 'max:120',
                Rule::unique('pgsql_patrocinados.permisos', 'nombre')->ignore($this->route('id')),
            ],
            'modulo' => ['required', 'string', 'max:80'],
            'accion' => ['required', 'string', 'max:80'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

## Seeder inicial

#### database/seeders/patrocinados/AccesoPatrocinadosSeeder.php

```php
<?php

namespace Database\Seeders\Patrocinados;

use App\Infrastructure\AccesoPatrocinados\Models\Permiso;
use App\Infrastructure\AccesoPatrocinados\Models\Rol;
use App\Infrastructure\AccesoPatrocinados\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AccesoPatrocinadosSeeder extends Seeder
{
    public function run(): void
    {
        $permisosVisitas = collect(['ver', 'crear', 'editar', 'revisar'])
            ->map(fn ($accion) => Permiso::create([
                'nombre' => "visitas.{$accion}",
                'modulo' => 'Visitas',
                'accion' => $accion,
            ]));

        $superadmin = Rol::create(['nombre' => 'SUPERADMIN', 'descripcion' => 'Acceso total']);
        $superadmin->permisos()->attach(Permiso::all()->pluck('id'));

        $tecnicoCampo = Rol::create(['nombre' => 'TECNICO_CAMPO', 'descripcion' => 'Captura de visitas en campo']);
        $tecnicoCampo->permisos()->attach(
            $permisosVisitas->whereIn('accion', ['ver', 'crear'])->pluck('id')
        );

        $supervisor = Rol::create(['nombre' => 'SUPERVISOR', 'descripcion' => 'Revisión de visitas']);
        $supervisor->permisos()->attach(
            $permisosVisitas->whereIn('accion', ['ver', 'revisar'])->pluck('id')
        );

        // Único usuario legítimo con updated_by = NULL (no existe usuario previo).
        $admin = Usuario::create([
            'username' => 'admin',
            'email' => 'admin@patrocinados.local',
            'password_hash' => Hash::make('CAMBIAR-EN-PRODUCCION'),
            'nombres' => 'Administrador',
            'apellidos' => 'Sistema',
            'estado' => 'ACTIVO',
            'updated_by' => null,
        ]);
        $admin->roles()->attach($superadmin->id);
    }
}
```

## Rutas (extracto de `routes/api/patrocinados.php`)

```php
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('usuarios', UsuarioController::class)->except(['show'])->middleware('permiso-patrocinados:usuarios.ver|usuarios.crear|usuarios.editar|usuarios.eliminar');
    Route::get('usuarios/{id}', [UsuarioController::class, 'show'])->middleware('permiso-patrocinados:usuarios.ver');
    Route::post('usuarios/{id}/roles', [UsuarioController::class, 'asignarRol'])->middleware('permiso-patrocinados:usuarios.editar');
    Route::delete('usuarios/{id}/roles/{rolId}', [UsuarioController::class, 'revocarRol'])->middleware('permiso-patrocinados:usuarios.editar');

    Route::apiResource('roles', RolController::class)->except(['show'])->middleware('permiso-patrocinados:roles.ver|roles.crear|roles.editar|roles.eliminar');
    Route::post('roles/{id}/permisos', [RolController::class, 'asignarPermiso'])->middleware('permiso-patrocinados:roles.editar');
    Route::delete('roles/{id}/permisos/{permisoId}', [RolController::class, 'revocarPermiso'])->middleware('permiso-patrocinados:roles.editar');

    Route::apiResource('permisos', PermisoController::class)->except(['show'])->middleware('permiso-patrocinados:permisos.ver|permisos.crear|permisos.editar|permisos.eliminar');
});
```
