# Código completo — Etapa 2: AccesoPatrocinados

> Complementa [../02-acceso-patrocinados.md](../02-acceso-patrocinados.md). Código PHP completo, listo para copiar, de cada archivo de la "Estructura DDD" de esa etapa. Fuente de columnas: las migraciones reales `database/migrations/patrocinados/2026_09_01_00000{1,2,3,4,5,6}_*.php`.
>
> **Reglas de negocio implementadas aquí** (no reabrir sin motivo, ver `docs/patrocinados/02-acceso-patrocinados.md`):
> 1. **Sanctum multi-modelo**: `Usuario` extiende `Illuminate\Foundation\Auth\User` (alias `Authenticatable`) + `HasApiTokens` — es un modelo de auth completo, independiente de `App\Models\User` de mentabit. El guard `sanctum` resuelve el modelo por el token (`tokenable_type`), no por un provider fijo, así que no hace falta un guard nuevo en `config/auth.php`.
> 2. **`password_hash` nunca se expone**: `$hidden` en el modelo + ningún DTO lo declara como propiedad.
> 3. **Bloqueo por intentos fallidos**: `AutenticarUsuarioHandler` — 5 intentos fallidos consecutivos bloquean la cuenta 30 minutos (`MAX_INTENTOS` / `MINUTOS_BLOQUEO` como constantes del Handler, ajustables). Si `bloqueado_hasta` está en el futuro, ni siquiera se verifica la contraseña.
> 4. **Permisos sin depender de Gate/Policy de Laravel**: `Usuario::tienePermiso(string $nombre): bool` resuelve `usuarios_roles → roles_permisos → permisos` directamente. `PermisoPatrocinadosMiddleware` lo usa tal cual — es un sistema propio, no reutiliza el middleware `permiso:` legado de mentabit.
> 5. **Pivotes puros gestionados vía relaciones, no Repository propio**: `usuarios_roles`/`roles_permisos` no tienen Contract ni EloquentRepository — se gestionan con `Usuario::roles()->syncWithoutDetaching()/detach()` y `Rol::permisos()->syncWithoutDetaching()/detach()`, invocados desde `AsignarRolHandler`/`RevocarRolHandler`/`AsignarPermisoARolHandler`/`RevocarPermisoDeRolHandler`.

---

## Domain/AccesoPatrocinados

#### `app/Domain/AccesoPatrocinados/Contracts/UsuarioRepositoryInterface.php`

```php
<?php

namespace App\Domain\AccesoPatrocinados\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface UsuarioRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array;

    public function findById(string $id): mixed;

    public function findByUsernameOrEmail(string $login): mixed;

    public function create(array $data): mixed;

    public function update(string $id, array $data): mixed;

    public function delete(string|array $ids): bool;
}
```

#### `app/Domain/AccesoPatrocinados/Contracts/RolRepositoryInterface.php`

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
}
```

#### `app/Domain/AccesoPatrocinados/Contracts/PermisoRepositoryInterface.php`

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

#### `app/Domain/AccesoPatrocinados/Exceptions/UsuarioNotFoundException.php`

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

#### `app/Domain/AccesoPatrocinados/Exceptions/RolNotFoundException.php`

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

#### `app/Domain/AccesoPatrocinados/Exceptions/PermisoNotFoundException.php`

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

#### `app/Domain/AccesoPatrocinados/Exceptions/CredencialesInvalidasException.php`

```php
<?php

namespace App\Domain\AccesoPatrocinados\Exceptions;

class CredencialesInvalidasException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Credenciales inválidas.', 401);
    }
}
```

#### `app/Domain/AccesoPatrocinados/Exceptions/CuentaBloqueadaException.php`

```php
<?php

namespace App\Domain\AccesoPatrocinados\Exceptions;

class CuentaBloqueadaException extends \RuntimeException
{
    public function __construct(\DateTimeInterface $bloqueadoHasta)
    {
        parent::__construct(
            "Cuenta bloqueada hasta {$bloqueadoHasta->format('Y-m-d H:i:s')} por intentos fallidos.",
            403,
        );
    }
}
```

---

## Application/AccesoPatrocinados

### DTOs

#### `app/Application/AccesoPatrocinados/DTOs/UsuarioDTO.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\DTOs;

/** Nunca declara password_hash. */
final readonly class UsuarioDTO
{
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
        );
    }
}
```

#### `app/Application/AccesoPatrocinados/DTOs/RolDTO.php`

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
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            nombre: $model->nombre,
            descripcion: $model->descripcion,
            estado: (bool) $model->estado,
        );
    }
}
```

#### `app/Application/AccesoPatrocinados/DTOs/PermisoDTO.php`

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
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            nombre: $model->nombre,
            modulo: $model->modulo,
            accion: $model->accion,
            descripcion: $model->descripcion,
        );
    }
}
```

### Commands

#### `app/Application/AccesoPatrocinados/Commands/AutenticarUsuarioCommand.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class AutenticarUsuarioCommand
{
    public function __construct(
        public string $login,
        public string $password,
    ) {}
}
```

#### `app/Application/AccesoPatrocinados/Commands/CreateUsuarioCommand.php`

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
        public string $estado,
    ) {}
}
```

#### `app/Application/AccesoPatrocinados/Commands/UpdateUsuarioCommand.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class UpdateUsuarioCommand
{
    public function __construct(
        public string $id,
        public string $nombres,
        public string $apellidos,
        public ?string $telefono,
        public string $estado,
    ) {}
}
```

#### `app/Application/AccesoPatrocinados/Commands/DeleteUsuarioCommand.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class DeleteUsuarioCommand
{
    public function __construct(public string $id) {}
}
```

#### `app/Application/AccesoPatrocinados/Commands/AsignarRolCommand.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class AsignarRolCommand
{
    public function __construct(
        public string $usuario_id,
        public string $rol_id,
        public ?string $asignado_por,
    ) {}
}
```

#### `app/Application/AccesoPatrocinados/Commands/RevocarRolCommand.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class RevocarRolCommand
{
    public function __construct(
        public string $usuario_id,
        public string $rol_id,
    ) {}
}
```

#### `app/Application/AccesoPatrocinados/Commands/CreateRolCommand.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class CreateRolCommand
{
    public function __construct(
        public string $nombre,
        public ?string $descripcion,
        public bool $estado,
    ) {}
}
```

#### `app/Application/AccesoPatrocinados/Commands/UpdateRolCommand.php`

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
    ) {}
}
```

#### `app/Application/AccesoPatrocinados/Commands/DeleteRolCommand.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class DeleteRolCommand
{
    public function __construct(public string $id) {}
}
```

#### `app/Application/AccesoPatrocinados/Commands/CreatePermisoCommand.php`

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
    ) {}
}
```

#### `app/Application/AccesoPatrocinados/Commands/UpdatePermisoCommand.php`

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
    ) {}
}
```

#### `app/Application/AccesoPatrocinados/Commands/DeletePermisoCommand.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class DeletePermisoCommand
{
    public function __construct(public string $id) {}
}
```

#### `app/Application/AccesoPatrocinados/Commands/AsignarPermisoARolCommand.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class AsignarPermisoARolCommand
{
    public function __construct(
        public string $rol_id,
        public string $permiso_id,
    ) {}
}
```

#### `app/Application/AccesoPatrocinados/Commands/RevocarPermisoDeRolCommand.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class RevocarPermisoDeRolCommand
{
    public function __construct(
        public string $rol_id,
        public string $permiso_id,
    ) {}
}
```

### Handlers

#### `app/Application/AccesoPatrocinados/Handlers/AutenticarUsuarioHandler.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\AutenticarUsuarioCommand;
use App\Application\AccesoPatrocinados\DTOs\UsuarioDTO;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;
use App\Domain\AccesoPatrocinados\Exceptions\CredencialesInvalidasException;
use App\Domain\AccesoPatrocinados\Exceptions\CuentaBloqueadaException;
use Illuminate\Support\Facades\Hash;

class AutenticarUsuarioHandler
{
    private const MAX_INTENTOS = 5;
    private const MINUTOS_BLOQUEO = 30;

    public function __construct(private readonly UsuarioRepositoryInterface $repository) {}

    /**
     * @return array{token: string, usuario: UsuarioDTO}
     */
    public function handle(AutenticarUsuarioCommand $command): array
    {
        $usuario = $this->repository->findByUsernameOrEmail($command->login);

        if ($usuario === null) {
            throw new CredencialesInvalidasException();
        }

        if ($usuario->bloqueado_hasta !== null && $usuario->bloqueado_hasta->isFuture()) {
            throw new CuentaBloqueadaException($usuario->bloqueado_hasta);
        }

        if (! Hash::check($command->password, $usuario->password_hash)) {
            $intentos = $usuario->intentos_fallidos + 1;

            $this->repository->update($usuario->id, [
                'intentos_fallidos' => $intentos,
                'bloqueado_hasta'   => $intentos >= self::MAX_INTENTOS
                    ? now()->addMinutes(self::MINUTOS_BLOQUEO)
                    : null,
            ]);

            throw new CredencialesInvalidasException();
        }

        $this->repository->update($usuario->id, [
            'intentos_fallidos' => 0,
            'bloqueado_hasta'   => null,
            'ultimo_login_at'   => now(),
        ]);

        $usuario = $this->repository->findById($usuario->id);
        $token = $usuario->createToken('api')->plainTextToken;

        return [
            'token'   => $token,
            'usuario' => UsuarioDTO::fromModel($usuario->load('roles')),
        ];
    }
}
```

#### `app/Application/AccesoPatrocinados/Handlers/CreateUsuarioHandler.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\CreateUsuarioCommand;
use App\Application\AccesoPatrocinados\DTOs\UsuarioDTO;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class CreateUsuarioHandler
{
    public function __construct(private readonly UsuarioRepositoryInterface $repository) {}

    public function handle(CreateUsuarioCommand $command): UsuarioDTO
    {
        $model = $this->repository->create([
            'username'      => $command->username,
            'email'         => $command->email,
            'password_hash' => Hash::make($command->password),
            'nombres'       => $command->nombres,
            'apellidos'     => $command->apellidos,
            'telefono'      => $command->telefono,
            'estado'        => $command->estado,
        ]);

        return UsuarioDTO::fromModel($model);
    }
}
```

#### `app/Application/AccesoPatrocinados/Handlers/UpdateUsuarioHandler.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\UpdateUsuarioCommand;
use App\Application\AccesoPatrocinados\DTOs\UsuarioDTO;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;

class UpdateUsuarioHandler
{
    public function __construct(private readonly UsuarioRepositoryInterface $repository) {}

    public function handle(UpdateUsuarioCommand $command): UsuarioDTO
    {
        $model = $this->repository->update($command->id, [
            'nombres'   => $command->nombres,
            'apellidos' => $command->apellidos,
            'telefono'  => $command->telefono,
            'estado'    => $command->estado,
        ]);

        return UsuarioDTO::fromModel($model);
    }
}
```

#### `app/Application/AccesoPatrocinados/Handlers/DeleteUsuarioHandler.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\DeleteUsuarioCommand;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;

class DeleteUsuarioHandler
{
    public function __construct(private readonly UsuarioRepositoryInterface $repository) {}

    public function handle(DeleteUsuarioCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
```

#### `app/Application/AccesoPatrocinados/Handlers/AsignarRolHandler.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\AsignarRolCommand;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;

class AsignarRolHandler
{
    public function __construct(private readonly UsuarioRepositoryInterface $repository) {}

    public function handle(AsignarRolCommand $command): void
    {
        $usuario = $this->repository->findById($command->usuario_id);

        $usuario->roles()->syncWithoutDetaching([
            $command->rol_id => ['updated_by' => $command->asignado_por],
        ]);
    }
}
```

#### `app/Application/AccesoPatrocinados/Handlers/RevocarRolHandler.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\RevocarRolCommand;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;

class RevocarRolHandler
{
    public function __construct(private readonly UsuarioRepositoryInterface $repository) {}

    public function handle(RevocarRolCommand $command): void
    {
        $usuario = $this->repository->findById($command->usuario_id);

        $usuario->roles()->detach($command->rol_id);
    }
}
```

#### `app/Application/AccesoPatrocinados/Handlers/CreateRolHandler.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\CreateRolCommand;
use App\Application\AccesoPatrocinados\DTOs\RolDTO;
use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;

class CreateRolHandler
{
    public function __construct(private readonly RolRepositoryInterface $repository) {}

    public function handle(CreateRolCommand $command): RolDTO
    {
        $model = $this->repository->create([
            'nombre'      => $command->nombre,
            'descripcion' => $command->descripcion,
            'estado'      => $command->estado,
        ]);

        return RolDTO::fromModel($model);
    }
}
```

#### `app/Application/AccesoPatrocinados/Handlers/UpdateRolHandler.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\UpdateRolCommand;
use App\Application\AccesoPatrocinados\DTOs\RolDTO;
use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;

class UpdateRolHandler
{
    public function __construct(private readonly RolRepositoryInterface $repository) {}

    public function handle(UpdateRolCommand $command): RolDTO
    {
        $model = $this->repository->update($command->id, [
            'nombre'      => $command->nombre,
            'descripcion' => $command->descripcion,
            'estado'      => $command->estado,
        ]);

        return RolDTO::fromModel($model);
    }
}
```

#### `app/Application/AccesoPatrocinados/Handlers/DeleteRolHandler.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\DeleteRolCommand;
use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;

class DeleteRolHandler
{
    public function __construct(private readonly RolRepositoryInterface $repository) {}

    public function handle(DeleteRolCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
```

#### `app/Application/AccesoPatrocinados/Handlers/CreatePermisoHandler.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\CreatePermisoCommand;
use App\Application\AccesoPatrocinados\DTOs\PermisoDTO;
use App\Domain\AccesoPatrocinados\Contracts\PermisoRepositoryInterface;

class CreatePermisoHandler
{
    public function __construct(private readonly PermisoRepositoryInterface $repository) {}

    public function handle(CreatePermisoCommand $command): PermisoDTO
    {
        $model = $this->repository->create([
            'nombre'      => $command->nombre,
            'modulo'      => $command->modulo,
            'accion'      => $command->accion,
            'descripcion' => $command->descripcion,
        ]);

        return PermisoDTO::fromModel($model);
    }
}
```

#### `app/Application/AccesoPatrocinados/Handlers/UpdatePermisoHandler.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\UpdatePermisoCommand;
use App\Application\AccesoPatrocinados\DTOs\PermisoDTO;
use App\Domain\AccesoPatrocinados\Contracts\PermisoRepositoryInterface;

class UpdatePermisoHandler
{
    public function __construct(private readonly PermisoRepositoryInterface $repository) {}

    public function handle(UpdatePermisoCommand $command): PermisoDTO
    {
        $model = $this->repository->update($command->id, [
            'nombre'      => $command->nombre,
            'modulo'      => $command->modulo,
            'accion'      => $command->accion,
            'descripcion' => $command->descripcion,
        ]);

        return PermisoDTO::fromModel($model);
    }
}
```

#### `app/Application/AccesoPatrocinados/Handlers/DeletePermisoHandler.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\DeletePermisoCommand;
use App\Domain\AccesoPatrocinados\Contracts\PermisoRepositoryInterface;

class DeletePermisoHandler
{
    public function __construct(private readonly PermisoRepositoryInterface $repository) {}

    public function handle(DeletePermisoCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
```

#### `app/Application/AccesoPatrocinados/Handlers/AsignarPermisoARolHandler.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\AsignarPermisoARolCommand;
use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;

class AsignarPermisoARolHandler
{
    public function __construct(private readonly RolRepositoryInterface $repository) {}

    public function handle(AsignarPermisoARolCommand $command): void
    {
        $rol = $this->repository->findById($command->rol_id);

        $rol->permisos()->syncWithoutDetaching([$command->permiso_id]);
    }
}
```

#### `app/Application/AccesoPatrocinados/Handlers/RevocarPermisoDeRolHandler.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\RevocarPermisoDeRolCommand;
use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;

class RevocarPermisoDeRolHandler
{
    public function __construct(private readonly RolRepositoryInterface $repository) {}

    public function handle(RevocarPermisoDeRolCommand $command): void
    {
        $rol = $this->repository->findById($command->rol_id);

        $rol->permisos()->detach($command->permiso_id);
    }
}
```

### Queries

#### `app/Application/AccesoPatrocinados/Queries/GetUsuariosQuery.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetUsuariosQuery
{
    public function __construct(public PaginationDTO $pagination) {}
}
```

#### `app/Application/AccesoPatrocinados/Queries/GetUsuarioByIdQuery.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Queries;

final readonly class GetUsuarioByIdQuery
{
    public function __construct(public string $id) {}
}
```

#### `app/Application/AccesoPatrocinados/Queries/GetRolesQuery.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetRolesQuery
{
    public function __construct(public PaginationDTO $pagination) {}
}
```

#### `app/Application/AccesoPatrocinados/Queries/GetPermisosQuery.php`

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

#### `app/Application/AccesoPatrocinados/QueryHandlers/GetUsuariosQueryHandler.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\QueryHandlers;

use App\Application\AccesoPatrocinados\DTOs\UsuarioDTO;
use App\Application\AccesoPatrocinados\Queries\GetUsuariosQuery;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;

class GetUsuariosQueryHandler
{
    public function __construct(private readonly UsuarioRepositoryInterface $repository) {}

    public function handle(GetUsuariosQuery $query): array
    {
        $paginated = $this->repository->paginate($query->pagination);

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => UsuarioDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
```

#### `app/Application/AccesoPatrocinados/QueryHandlers/GetUsuarioByIdQueryHandler.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\QueryHandlers;

use App\Application\AccesoPatrocinados\DTOs\UsuarioDTO;
use App\Application\AccesoPatrocinados\Queries\GetUsuarioByIdQuery;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;

class GetUsuarioByIdQueryHandler
{
    public function __construct(private readonly UsuarioRepositoryInterface $repository) {}

    public function handle(GetUsuarioByIdQuery $query): UsuarioDTO
    {
        return UsuarioDTO::fromModel($this->repository->findById($query->id));
    }
}
```

#### `app/Application/AccesoPatrocinados/QueryHandlers/GetRolesQueryHandler.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\QueryHandlers;

use App\Application\AccesoPatrocinados\DTOs\RolDTO;
use App\Application\AccesoPatrocinados\Queries\GetRolesQuery;
use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;

class GetRolesQueryHandler
{
    public function __construct(private readonly RolRepositoryInterface $repository) {}

    public function handle(GetRolesQuery $query): array
    {
        $paginated = $this->repository->paginate($query->pagination);

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => RolDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
```

#### `app/Application/AccesoPatrocinados/QueryHandlers/GetPermisosQueryHandler.php`

```php
<?php

namespace App\Application\AccesoPatrocinados\QueryHandlers;

use App\Application\AccesoPatrocinados\DTOs\PermisoDTO;
use App\Application\AccesoPatrocinados\Queries\GetPermisosQuery;
use App\Domain\AccesoPatrocinados\Contracts\PermisoRepositoryInterface;

class GetPermisosQueryHandler
{
    public function __construct(private readonly PermisoRepositoryInterface $repository) {}

    public function handle(GetPermisosQuery $query): array
    {
        $paginated = $this->repository->paginate($query->pagination);

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => PermisoDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
```

---

## Infrastructure/AccesoPatrocinados

### Models

#### `app/Infrastructure/AccesoPatrocinados/Models/Usuario.php`

```php
<?php

namespace App\Infrastructure\AccesoPatrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo de autenticación propio del módulo Patrocinados, independiente de
 * App\Models\User (mentabit). Sanctum multi-modelo: el guard `sanctum`
 * resuelve el modelo dueño del token (tokenable_type), no un provider fijo.
 */
class Usuario extends Authenticatable
{
    use HasApiTokens, HasUuids, Notifiable, SoftDeletes, UsaConexionPatrocinados;

    protected $table = 'usuarios';

    protected $fillable = [
        'username', 'email', 'password_hash', 'nombres', 'apellidos', 'telefono',
        'estado', 'intentos_fallidos', 'bloqueado_hasta', 'ultimo_login_at',
        'password_cambiado_at', 'updated_by',
    ];

    protected $hidden = ['password_hash'];

    protected $casts = [
        'intentos_fallidos'    => 'integer',
        'bloqueado_hasta'      => 'datetime',
        'ultimo_login_at'      => 'datetime',
        'password_cambiado_at' => 'datetime',
    ];

    /** Authenticatable espera este accessor para Hash::check() vía los guards estándar. */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'usuarios_roles', 'usuario_id', 'rol_id')
            ->using(UsuarioRol::class)
            ->withTimestamps();
    }

    /** Resuelve el permiso vía roles, sin pasar por Gate/Policy de Laravel. */
    public function tienePermiso(string $nombrePermiso): bool
    {
        return $this->roles()
            ->whereHas('permisos', fn ($q) => $q->where('nombre', $nombrePermiso))
            ->exists();
    }
}
```

#### `app/Infrastructure/AccesoPatrocinados/Models/Rol.php`

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

    protected $fillable = ['nombre', 'descripcion', 'estado', 'updated_by'];

    protected $casts = ['estado' => 'boolean'];

    public function permisos()
    {
        return $this->belongsToMany(Permiso::class, 'roles_permisos', 'rol_id', 'permiso_id')
            ->using(RolPermiso::class)
            ->withTimestamps();
    }

    public function usuarios()
    {
        return $this->belongsToMany(Usuario::class, 'usuarios_roles', 'rol_id', 'usuario_id')
            ->using(UsuarioRol::class)
            ->withTimestamps();
    }
}
```

#### `app/Infrastructure/AccesoPatrocinados/Models/Permiso.php`

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

    protected $fillable = ['nombre', 'modulo', 'accion', 'descripcion', 'updated_by'];

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'roles_permisos', 'permiso_id', 'rol_id')
            ->using(RolPermiso::class)
            ->withTimestamps();
    }
}
```

#### `app/Infrastructure/AccesoPatrocinados/Models/UsuarioRol.php`

```php
<?php

namespace App\Infrastructure\AccesoPatrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Relations\Pivot;

/** Pivot puro (PK compuesta usuario_id+rol_id, sin id propio). */
class UsuarioRol extends Pivot
{
    use UsaConexionPatrocinados;

    public $incrementing = false;

    protected $table = 'usuarios_roles';

    protected $fillable = ['usuario_id', 'rol_id', 'updated_by'];
}
```

#### `app/Infrastructure/AccesoPatrocinados/Models/RolPermiso.php`

```php
<?php

namespace App\Infrastructure\AccesoPatrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Relations\Pivot;

/** Pivot puro (PK compuesta rol_id+permiso_id, sin id propio). */
class RolPermiso extends Pivot
{
    use UsaConexionPatrocinados;

    public $incrementing = false;

    protected $table = 'roles_permisos';

    protected $fillable = ['rol_id', 'permiso_id', 'updated_by'];
}
```

### Repositories

#### `app/Infrastructure/AccesoPatrocinados/Repositories/EloquentUsuarioRepository.php`

```php
<?php

namespace App\Infrastructure\AccesoPatrocinados\Repositories;

use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;
use App\Domain\AccesoPatrocinados\Exceptions\UsuarioNotFoundException;
use App\Infrastructure\AccesoPatrocinados\Models\Usuario;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentUsuarioRepository implements UsuarioRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array
    {
        $q = Usuario::query()->with('roles')->whereNull('deleted_at');

        if ($pagination->query !== '') {
            $q->where(fn ($sub) => $sub
                ->where('username', 'ilike', "%{$pagination->query}%")
                ->orWhere('email', 'ilike', "%{$pagination->query}%")
                ->orWhere('nombres', 'ilike', "%{$pagination->query}%")
                ->orWhere('apellidos', 'ilike', "%{$pagination->query}%"));
        }

        $paginated = $q->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'created_at', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => $paginated->items(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): mixed
    {
        $usuario = Usuario::whereNull('deleted_at')->find($id);

        if (! $usuario) {
            throw new UsuarioNotFoundException($id);
        }

        return $usuario;
    }

    public function findByUsernameOrEmail(string $login): mixed
    {
        return Usuario::whereNull('deleted_at')
            ->where(fn ($q) => $q->where('username', $login)->orWhere('email', $login))
            ->first();
    }

    public function create(array $data): mixed
    {
        return Usuario::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $usuario = $this->findById($id);
        $usuario->update($data);

        return $usuario->fresh('roles');
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Usuario::whereIn('id', (array) $ids)->delete();
    }
}
```

#### `app/Infrastructure/AccesoPatrocinados/Repositories/EloquentRolRepository.php`

```php
<?php

namespace App\Infrastructure\AccesoPatrocinados\Repositories;

use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;
use App\Domain\AccesoPatrocinados\Exceptions\RolNotFoundException;
use App\Infrastructure\AccesoPatrocinados\Models\Rol;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentRolRepository implements RolRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array
    {
        $paginated = Rol::query()
            ->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'created_at', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => $paginated->items(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): mixed
    {
        $rol = Rol::with('permisos')->find($id);

        if (! $rol) {
            throw new RolNotFoundException($id);
        }

        return $rol;
    }

    public function create(array $data): mixed
    {
        return Rol::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $rol = $this->findById($id);
        $rol->update($data);

        return $rol->fresh('permisos');
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Rol::whereIn('id', (array) $ids)->delete();
    }
}
```

#### `app/Infrastructure/AccesoPatrocinados/Repositories/EloquentPermisoRepository.php`

```php
<?php

namespace App\Infrastructure\AccesoPatrocinados\Repositories;

use App\Domain\AccesoPatrocinados\Contracts\PermisoRepositoryInterface;
use App\Domain\AccesoPatrocinados\Exceptions\PermisoNotFoundException;
use App\Infrastructure\AccesoPatrocinados\Models\Permiso;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentPermisoRepository implements PermisoRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array
    {
        $paginated = Permiso::query()
            ->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'created_at', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => $paginated->items(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): mixed
    {
        $permiso = Permiso::find($id);

        if (! $permiso) {
            throw new PermisoNotFoundException($id);
        }

        return $permiso;
    }

    public function create(array $data): mixed
    {
        return Permiso::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $permiso = $this->findById($id);
        $permiso->update($data);

        return $permiso->fresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Permiso::whereIn('id', (array) $ids)->delete();
    }
}
```

---

## Http

### Middleware

#### `app/Http/Middleware/PermisoPatrocinadosMiddleware.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Equivalente propio del middleware `permiso:` legado de mentabit, resuelto
 * contra usuarios_roles/roles_permisos/permisos de este módulo — no reutiliza
 * el middleware legado ni sus tablas t_grupopermiso/t_permiso.
 */
class PermisoPatrocinadosMiddleware
{
    /** Uso: ->middleware('permiso-patrocinados:visitas.crear') o 'permiso-patrocinados:usuarios.ver|crear' */
    public function handle(Request $request, Closure $next, string $permisos): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            abort(401, 'No autenticado.');
        }

        foreach (explode('|', $permisos) as $permiso) {
            if ($usuario->tienePermiso($permiso)) {
                return $next($request);
            }
        }

        abort(403, 'No tiene permiso para esta acción.');
    }
}
```

Registro del alias (ejemplo — se aplica de verdad en la Etapa 1/9, no editar `bootstrap/app.php` real desde este documento):

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    // ...alias existentes de mentabit...
    $middleware->alias([
        'permiso-patrocinados' => \App\Http\Middleware\PermisoPatrocinadosMiddleware::class,
    ]);
})
```

### Controllers

#### `app/Http/Controllers/Api/Patrocinados/AuthController.php`

```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\AccesoPatrocinados\Commands\AutenticarUsuarioCommand;
use App\Application\AccesoPatrocinados\DTOs\UsuarioDTO;
use App\Application\AccesoPatrocinados\Handlers\AutenticarUsuarioHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\AccesoPatrocinados\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AutenticarUsuarioHandler $autenticarHandler) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $resultado = $this->autenticarHandler->handle(new AutenticarUsuarioCommand(
            login: $request->login,
            password: $request->password,
        ));

        return response()->json($resultado);
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

#### `app/Http/Controllers/Api/Patrocinados/UsuarioController.php`

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

        return response()->json($this->getUsuariosHandler->handle(new GetUsuariosQuery($pagination)));
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->getUsuarioByIdHandler->handle(new GetUsuarioByIdQuery($id)));
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
            estado: $request->estado ?? 'ACTIVO',
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateUsuarioRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateUsuarioCommand(
            id: $id,
            nombres: $request->nombres,
            apellidos: $request->apellidos,
            telefono: $request->telefono,
            estado: $request->estado,
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
            usuario_id: $id,
            rol_id: $request->rol_id,
            asignado_por: auth()->id(),
        ));

        return response()->json(['status' => 'ok']);
    }

    public function revocarRol(string $id, string $rolId): JsonResponse
    {
        $this->revocarRolHandler->handle(new RevocarRolCommand(usuario_id: $id, rol_id: $rolId));

        return response()->json(['status' => 'ok']);
    }
}
```

#### `app/Http/Controllers/Api/Patrocinados/RolController.php`

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

        return response()->json($this->getRolesHandler->handle(new GetRolesQuery($pagination)));
    }

    public function store(StoreRolRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateRolCommand(
            nombre: $request->nombre,
            descripcion: $request->descripcion,
            estado: $request->boolean('estado', true),
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
            rol_id: $id,
            permiso_id: $request->permiso_id,
        ));

        return response()->json(['status' => 'ok']);
    }

    public function revocarPermiso(string $id, string $permisoId): JsonResponse
    {
        $this->revocarPermisoHandler->handle(new RevocarPermisoDeRolCommand(rol_id: $id, permiso_id: $permisoId));

        return response()->json(['status' => 'ok']);
    }
}
```

#### `app/Http/Controllers/Api/Patrocinados/PermisoController.php`

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

        return response()->json($this->getPermisosHandler->handle(new GetPermisosQuery($pagination)));
    }

    public function store(StorePermisoRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreatePermisoCommand(
            nombre: $request->nombre,
            modulo: $request->modulo,
            accion: $request->accion,
            descripcion: $request->descripcion,
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

#### `app/Http/Requests/Patrocinados/AccesoPatrocinados/LoginRequest.php`

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
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
}
```

#### `app/Http/Requests/Patrocinados/AccesoPatrocinados/StoreUsuarioRequest.php`

```php
<?php

namespace App\Http\Requests\Patrocinados\AccesoPatrocinados;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username'  => ['required', 'string', 'max:80', 'unique:pgsql_patrocinados.usuarios,username'],
            'email'     => ['required', 'email', 'max:180', 'unique:pgsql_patrocinados.usuarios,email'],
            'password'  => ['required', 'string', 'min:8'],
            'nombres'   => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:120'],
            'telefono'  => ['nullable', 'string', 'max:40'],
            'estado'    => ['nullable', Rule::in(['ACTIVO', 'INACTIVO', 'BLOQUEADO'])],
        ];
    }
}
```

#### `app/Http/Requests/Patrocinados/AccesoPatrocinados/UpdateUsuarioRequest.php`

```php
<?php

namespace App\Http\Requests\Patrocinados\AccesoPatrocinados;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Sin username/email/password: cambios de credenciales van por un flujo aparte, fuera del alcance de esta etapa. */
class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombres'   => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:120'],
            'telefono'  => ['nullable', 'string', 'max:40'],
            'estado'    => ['required', Rule::in(['ACTIVO', 'INACTIVO', 'BLOQUEADO'])],
        ];
    }
}
```

#### `app/Http/Requests/Patrocinados/AccesoPatrocinados/StoreRolRequest.php`

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
            'nombre'      => ['required', 'string', 'max:80', 'unique:pgsql_patrocinados.roles,nombre'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estado'      => ['boolean'],
        ];
    }
}
```

#### `app/Http/Requests/Patrocinados/AccesoPatrocinados/UpdateRolRequest.php`

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
            'nombre'      => ['required', 'string', 'max:80', Rule::unique('pgsql_patrocinados.roles', 'nombre')->ignore($this->route('id'))],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estado'      => ['boolean'],
        ];
    }
}
```

#### `app/Http/Requests/Patrocinados/AccesoPatrocinados/StorePermisoRequest.php`

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
            'nombre'      => ['required', 'string', 'max:120', 'unique:pgsql_patrocinados.permisos,nombre'],
            'modulo'      => ['required', 'string', 'max:80'],
            'accion'      => ['required', 'string', 'max:80'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

#### `app/Http/Requests/Patrocinados/AccesoPatrocinados/UpdatePermisoRequest.php`

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
            'nombre'      => ['required', 'string', 'max:120', Rule::unique('pgsql_patrocinados.permisos', 'nombre')->ignore($this->route('id'))],
            'modulo'      => ['required', 'string', 'max:80'],
            'accion'      => ['required', 'string', 'max:80'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

#### `app/Http/Requests/Patrocinados/AccesoPatrocinados/AsignarRolRequest.php`

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

#### `app/Http/Requests/Patrocinados/AccesoPatrocinados/AsignarPermisoRequest.php`

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

---

## Rutas de referencia (para `routes/api/patrocinados.php`, se cablean formalmente en la Etapa 1/9)

```php
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/usuarios', [UsuarioController::class, 'index'])->middleware('permiso-patrocinados:usuarios.ver');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->middleware('permiso-patrocinados:usuarios.crear');
    Route::get('/usuarios/{id}', [UsuarioController::class, 'show'])->middleware('permiso-patrocinados:usuarios.ver');
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])->middleware('permiso-patrocinados:usuarios.editar');
    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy'])->middleware('permiso-patrocinados:usuarios.eliminar');
    Route::post('/usuarios/{id}/roles', [UsuarioController::class, 'asignarRol'])->middleware('permiso-patrocinados:usuarios.editar');
    Route::delete('/usuarios/{id}/roles/{rolId}', [UsuarioController::class, 'revocarRol'])->middleware('permiso-patrocinados:usuarios.editar');

    Route::get('/roles', [RolController::class, 'index'])->middleware('permiso-patrocinados:roles.ver');
    Route::post('/roles', [RolController::class, 'store'])->middleware('permiso-patrocinados:roles.crear');
    Route::put('/roles/{id}', [RolController::class, 'update'])->middleware('permiso-patrocinados:roles.editar');
    Route::delete('/roles/{id}', [RolController::class, 'destroy'])->middleware('permiso-patrocinados:roles.eliminar');
    Route::post('/roles/{id}/permisos', [RolController::class, 'asignarPermiso'])->middleware('permiso-patrocinados:roles.editar');
    Route::delete('/roles/{id}/permisos/{permisoId}', [RolController::class, 'revocarPermiso'])->middleware('permiso-patrocinados:roles.editar');

    Route::get('/permisos', [PermisoController::class, 'index'])->middleware('permiso-patrocinados:permisos.ver');
    Route::post('/permisos', [PermisoController::class, 'store'])->middleware('permiso-patrocinados:permisos.crear');
    Route::put('/permisos/{id}', [PermisoController::class, 'update'])->middleware('permiso-patrocinados:permisos.editar');
    Route::delete('/permisos/{id}', [PermisoController::class, 'destroy'])->middleware('permiso-patrocinados:permisos.eliminar');
});
```
