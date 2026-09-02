# Etapa 2 — Módulo AccesoPatrocinados (usuarios, roles, permisos)

> Dependencias: Etapa 1.
> Desbloquea: todas las demás etapas (todas las tablas tienen `created_by`/`updated_by`/`user_id` hacia `usuarios`).
> Tablas: `usuarios`, `roles`, `permisos`, `usuarios_roles`, `roles_permisos`.

## Decisión previa obligatoria

Definir el mecanismo de autenticación antes de migrar (plan de revisión §5.8):

- **Recomendado**: Sanctum multi-modelo. El modelo `Usuario` de este módulo usa `HasApiTokens` igual que el `User` de mentabit, pero es una tabla y un modelo completamente distintos. Sanctum soporta múltiples modelos "tokenable" sin conflicto porque el token guarda `tokenable_type` + `tokenable_id`.
- Rutas de auth propias: `POST /api/v1/patrocinados/auth/login`, `POST /api/v1/patrocinados/auth/logout`, `GET /api/v1/patrocinados/auth/me`. **No compartir** `/api/v1/auth/login` con mentabit.
- Guard: se puede usar el guard `sanctum` por defecto (Sanctum resuelve el modelo por el token, no por el guard), pero si se prefiere aislar explícitamente, agregar un guard `patrocinados` en `config/auth.php` con `provider` propio apuntando al modelo `Usuario` de este módulo.

## Migraciones (orden)

```text
1. roles
2. permisos
3. usuarios              (updated_by FK a sí misma, NULLABLE)
4. usuarios_roles         (PK compuesta: usuario_id + rol_id)
5. roles_permisos         (PK compuesta: rol_id + permiso_id)
```

Notas de columnas (del docx, sin reinterpretar):

- `usuarios.password_hash TEXT NOT NULL` → usar `Hash::make()` de Laravel (bcrypt/argon2 según config), nunca guardar el hash de otro sistema sin verificar el algoritmo.
- `usuarios.estado VARCHAR(20) DEFAULT 'ACTIVO'` → cerrar el enum antes de migrar. Mínimo viable: `ACTIVO`, `INACTIVO`, `BLOQUEADO`. Agregar `CHECK (estado IN (...))` en la migración.
- `usuarios.intentos_fallidos SMALLINT DEFAULT 0` + `bloqueado_hasta TIMESTAMPTZ` → lógica de bloqueo por intentos fallidos vive en `Application/AccesoPatrocinados/Handlers/AutenticarUsuarioHandler.php`, no en el Controller.
- `usuarios_roles` y `roles_permisos` son pivotes puros con timestamps propios → usar Eloquent pivot models (`UsuarioRol`, `RolPermiso` con `public $incrementing = false;` no aplica porque no tienen PK propia, pero sí necesitan `use Illuminate\Database\Eloquent\Relations\Pivot;`).

## Estructura DDD a crear

```text
Domain/AccesoPatrocinados/
  Contracts/
    UsuarioRepositoryInterface.php
    RolRepositoryInterface.php
    PermisoRepositoryInterface.php
  Exceptions/
    UsuarioNotFoundException.php
    RolNotFoundException.php
    PermisoNotFoundException.php
    CredencialesInvalidasException.php   ← 401
    CuentaBloqueadaException.php         ← 403

Application/AccesoPatrocinados/
  DTOs/
    UsuarioDTO.php            ← nunca incluye password_hash
    RolDTO.php
    PermisoDTO.php
  Commands/
    CreateUsuarioCommand.php
    UpdateUsuarioCommand.php
    DeleteUsuarioCommand.php
    AsignarRolCommand.php
    RevocarRolCommand.php
    CreateRolCommand.php / UpdateRolCommand.php / DeleteRolCommand.php
    CreatePermisoCommand.php / UpdatePermisoCommand.php / DeletePermisoCommand.php
    AsignarPermisoARolCommand.php / RevocarPermisoDeRolCommand.php
  Handlers/                    ← uno por Command
    AutenticarUsuarioHandler.php   ← login: valida intentos_fallidos/bloqueado_hasta, resetea contador en éxito
  Queries/
    GetUsuariosQuery.php / GetUsuarioByIdQuery.php
    GetRolesQuery.php / GetPermisosQuery.php
  QueryHandlers/                ← uno por Query

Infrastructure/AccesoPatrocinados/
  Models/
    Usuario.php     → tabla usuarios, HasUuids, HasApiTokens, SoftDeletes, UsaConexionPatrocinados
    Rol.php         → tabla roles
    Permiso.php     → tabla permisos
    UsuarioRol.php  → pivot usuarios_roles
    RolPermiso.php  → pivot roles_permisos
  Repositories/
    EloquentUsuarioRepository.php
    EloquentRolRepository.php
    EloquentPermisoRepository.php

Http/
  Controllers/Api/Patrocinados/
    AuthController.php       ← login/logout/me propios del módulo
    UsuarioController.php
    RolController.php
    PermisoController.php
  Requests/Patrocinados/AccesoPatrocinados/
    LoginRequest.php
    StoreUsuarioRequest.php / UpdateUsuarioRequest.php
    StoreRolRequest.php / UpdateRolRequest.php
    StorePermisoRequest.php / UpdatePermisoRequest.php
```

## Middleware de permisos propio

El middleware `permiso:recurso.accion` existente resuelve contra `t_grupopermiso`/`t_permiso` (legado). Clonar su forma, no reutilizarlo:

```php
// app/Http/Middleware/PermisoPatrocinadosMiddleware.php
// Alias de ruta: 'permiso-patrocinados'
// Uso: ->middleware('permiso-patrocinados:visitas.crear')
// Resuelve: auth()->user() (modelo Usuario de este módulo) → usuarios_roles → roles_permisos → permisos.nombre
```

Registrar el alias en `bootstrap/app.php` junto al resto de middlewares.

## Endpoints

| Método | Ruta | Middleware |
|---|---|---|
| POST | `/api/v1/patrocinados/auth/login` | `throttle:login` |
| POST | `/api/v1/patrocinados/auth/logout` | `auth:sanctum` |
| GET | `/api/v1/patrocinados/auth/me` | `auth:sanctum` |
| GET/POST | `/api/v1/patrocinados/usuarios` | `auth:sanctum`, `permiso-patrocinados:usuarios.ver\|crear` |
| PUT/DELETE | `/api/v1/patrocinados/usuarios/{id}` | `permiso-patrocinados:usuarios.editar\|eliminar` |
| GET/POST | `/api/v1/patrocinados/roles` | `permiso-patrocinados:roles.ver\|crear` |
| POST | `/api/v1/patrocinados/usuarios/{id}/roles` | `permiso-patrocinados:usuarios.editar` (asignar rol) |
| GET/POST | `/api/v1/patrocinados/permisos` | `permiso-patrocinados:permisos.ver\|crear` |
| POST | `/api/v1/patrocinados/roles/{id}/permisos` | `permiso-patrocinados:roles.editar` (asignar permiso) |

## Seeder inicial

`database/seeders/patrocinados/AccesoPatrocinadosSeeder.php`:
- Rol `SUPERADMIN` con todos los permisos.
- Rol `TECNICO_CAMPO` con permisos mínimos de visitas (crear/editar visitas propias, subir fotos, capturar ubicación).
- Rol `SUPERVISOR` con permisos de revisión (`visitas.revisar`, `visitas.ver`).
- Un primer usuario SUPERADMIN con `updated_by = NULL` (es el único caso legítimo de este campo nulo por diseño).

## Criterios de aceptación

- [ ] Login devuelve token Sanctum válido solo para usuarios de esta tabla, nunca para `t_usuario`.
- [ ] 5 intentos fallidos consecutivos bloquean la cuenta (`bloqueado_hasta` seteado) — cubierto por test.
- [ ] `permiso-patrocinados:x.y` bloquea con 403 a un usuario sin el rol/permiso correspondiente.
- [ ] Ningún DTO de `Usuario` expone `password_hash`.
- [ ] Seeder corre limpio en una BD `pgsql_patrocinados` vacía.

## Código completo

Ver [codigo/02-acceso-patrocinados-codigo.md](codigo/02-acceso-patrocinados-codigo.md) — código PHP completo de cada archivo de la Estructura DDD de esta etapa, el middleware `PermisoPatrocinadosMiddleware` y el extracto de rutas.
