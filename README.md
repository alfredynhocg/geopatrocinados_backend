# mentabit API

API REST construida con **Laravel 12** para la gestión de cursos, diplomados y programas de formación continua: estudiantes, inscripciones, notas, pagos, docentes, estructura académica, contenido web institucional y certificados con QR.

Incluye además el módulo **Patrocinados / Visitas**, un bounded context independiente (app móvil Flutter offline-first + este backend) para el seguimiento de visitas a niños patrocinados de una ONG de apadrinamiento — con su propia base de datos, autenticación y reglas de negocio, aislado del resto del sistema.

Todo el proyecto sigue estrictamente **DDD (Domain-Driven Design) + CQRS**. Antes de tocar código, leé **[CLAUDE.md](CLAUDE.md)** — define los patrones, convenciones y reglas irrompibles de cada módulo.

---

## Stack

| | |
|---|---|
| Lenguaje / Framework | PHP 8.2 · Laravel 12 |
| Base de datos (mentabit) | MySQL 8 |
| Base de datos (Patrocinados) | PostgreSQL 16 + PostGIS |
| Auth | Laravel Sanctum (multi-modelo: guards independientes por bounded context) |
| Frontend build | Vite + Tailwind CSS 4 |
| PDF / QR | DomPDF · simplesoftwareio/simple-qrcode |
| Documentación API | L5-Swagger (OpenAPI) |
| Config dinámica | Spatie Laravel Settings |

---

## Arquitectura

Cada módulo se organiza en 4 capas, de afuera hacia adentro:

```text
app/
├── Domain/          Contratos (interfaces), excepciones — sin dependencias externas
├── Application/      Commands, Queries, Handlers, DTOs — orquesta el dominio
├── Infrastructure/   Modelos Eloquent, Repositorios — implementaciones concretas
└── Http/             Controllers, FormRequests, Middleware — solo entrada/salida HTTP
```

Reglas base (ver `CLAUDE.md` para el detalle completo con ejemplos):

- Los Controllers nunca acceden a Eloquent directamente — solo inyectan Handlers.
- Toda respuesta HTTP sale como DTO, nunca como modelo Eloquent crudo.
- Escrituras que tocan más de una tabla van siempre en `DB::transaction()`.
- Los repositorios implementan una interfaz declarada en `Domain/{Modulo}/Contracts`.

El módulo **Patrocinados** difiere del resto en varios puntos clave (conexión Postgres propia, PKs UUID, guard de auth aislado, PostGIS) — ver la sección "Módulo de Patrocinados / Visitas" en [CLAUDE.md](CLAUDE.md) antes de tocarlo.

---

## Requisitos

- PHP 8.2+ con extensiones `pdo_mysql`, `pdo_pgsql`, `gd`, `zip`, `intl`
- Composer 2
- Node.js 18+ / npm
- MySQL 8 (mentabit)
- PostgreSQL 16 + extensión PostGIS (módulo Patrocinados) — imagen recomendada: `postgis/postgis:16-3.4`

---

## Instalación

```bash
git clone <repo>
cd geopatrocinados_backend
make setup
```

`make setup` corre: `composer install`, `npm install`, copia `.env.example` → `.env`, `key:generate`, `storage:link`, migra la BD default (MySQL) y siembra `RoleSeeder`.

### Configurar el módulo Patrocinados (Postgres) aparte

Este módulo usa una **conexión separada** — no se migra con `make migrate`. Configurá en `.env`:

```env
PATROCINADOS_DB_HOST=127.0.0.1
PATROCINADOS_DB_PORT=5432
PATROCINADOS_DB_DATABASE=patrocinados
PATROCINADOS_DB_USERNAME=postgres
PATROCINADOS_DB_PASSWORD=
```

Levantar Postgres+PostGIS local (recomendado vía Docker si no tenés uno):

```bash
docker run -d --name patrocinados_pg \
  -e POSTGRES_USER=postgres -e POSTGRES_PASSWORD=postgres -e POSTGRES_DB=patrocinados \
  -p 5432:5432 postgis/postgis:16-3.4
```

Migrar y sembrar datos demo:

```bash
php artisan migrate --path=database/migrations/patrocinados --database=pgsql_patrocinados
php artisan db:seed --class="Database\Seeders\Patrocinados\PatrocinadosDatabaseSeeder"
```

El seeder crea: los 9 departamentos de Bolivia, comunidades/ubicaciones demo en Cochabamba, 2 niños patrocinados con tutores, catálogos de visitas, y dos usuarios de prueba —

| Usuario | Password | Rol |
|---|---|---|
| `superadmin` | `changeme123` | SUPERADMIN |
| `tecnico1` | `changeme123` | TECNICO_CAMPO |

Login: `POST /api/v1/patrocinados/auth/login` con `{"login": "...", "password": "..."}`.

---

## Comandos frecuentes (`make help` lista todos)

| Comando | Qué hace |
|---|---|
| `make dev` | Servidor + worker de colas + logs en paralelo |
| `make serve` | Servidor local (`php artisan serve`) |
| `make migrate` | Migraciones pendientes (BD default, MySQL) |
| `make fresh` | `migrate:fresh` + seed completo |
| `make tinker` | Consola REPL |
| `make routes` | Listar rutas registradas |
| `make test` | Suite de tests completa |
| `make test-filter f=NombreTest` | Un test específico |
| `make lint` / `make format` | Revisar / aplicar formato (Pint) |
| `make swagger` | Regenerar documentación OpenAPI |
| `make logs` | Logs en tiempo real (`pail`) |

Migraciones y tests del módulo Patrocinados no pasan por estos comandos (conexión distinta) — ver la sección anterior y `CLAUDE.md`.

---

## Testing

```bash
make test                              # todos los tests (mentabit)
make test-filter f=CreateNoticiaTest   # test específico
php artisan test --coverage            # con cobertura
```

- Tests de **Handlers** → unitarios con Mockery (mockeando el repositorio).
- Tests de **Controllers/Endpoints** → Feature tests con `RefreshDatabase`.
- El módulo Patrocinados requiere Postgres real en los Feature Tests (SQLite no soporta PostGIS) — ver `docs/patrocinados/09-testing-y-qa.md`.

---

## Documentación

- **[CLAUDE.md](CLAUDE.md)** — guía técnica completa: patrones DDD+CQRS, convenciones de nomenclatura, manejo de errores, sistema de permisos, y la referencia detallada de cada módulo grande (Pagos, Patrocinados/Visitas).
- **`docs/patrocinados/`** — planificación por etapas del módulo Patrocinados: estructura DDD, endpoints, criterios de aceptación y código completo por módulo (`docs/patrocinados/codigo/`).
- **`PLAN_INTEGRACION_PATROCINADOS.md`** — revisión técnica original del diseño de base de datos del módulo Patrocinados.
- **Swagger / OpenAPI** — `make swagger` regenera la spec; documentación servida en `/api/documentation` una vez generada.

---

## Estructura del repositorio

```text
app/
  Domain/            Contratos y excepciones por módulo
  Application/        Commands, Queries, Handlers, DTOs por módulo
  Infrastructure/      Modelos Eloquent y Repositorios por módulo
  Http/                Controllers, FormRequests, Middleware
  Providers/           Service Providers (bindings de dominio)
database/
  migrations/          Migraciones mentabit (MySQL) + legado cenefco/SIASEC
  migrations/patrocinados/   Migraciones del módulo Patrocinados (PostgreSQL)
  seeders/             Seeders por módulo
routes/
  api.php              Punto de entrada de rutas API
  api/v1.php           Rutas mentabit v1
  api/patrocinados.php Rutas del módulo Patrocinados
docs/
  patrocinados/        Planificación e implementación del módulo Patrocinados
```
