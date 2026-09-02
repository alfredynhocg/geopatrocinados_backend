# CLAUDE.md — Guía para Agentes AI y Onboarding Técnico

> Este archivo es la referencia principal para Claude Code y cualquier desarrollador nuevo.
> Describe los patrones del proyecto, convenciones estrictas y ejemplos reales de código.

---

## Qué es este proyecto

**mentabit API** es una API REST construida con Laravel 12 para la gestión de cursos, diplomados y programas de formación continua. Centraliza la administración de estudiantes, inscripciones, notas, pagos, docentes y la estructura académica de los programas.
Implementa **DDD (Domain-Driven Design) + CQRS** de forma estricta en todas las capas.

Stack: PHP 8.2 · Laravel 12 · MySQL 8 · Laravel Sanctum · DomPDF · Spatie Laravel Settings

Módulos principales: Cursos · Diplomados · Programas · Inscripciones · Notas · Pagos · Docentes · Estudiantes · Horarios · Usuarios y roles · Permisos · Contenido web · **Web Institucional** · **Certificados con QR** · **Pre-inscripción** · **WhatsApp grupos**

---

## Estructura de carpetas

```text
app/
├── Domain/                  # Contratos (interfaces), excepciones, enums — SIN dependencias externas
│   ├── Noticias/
│   │   ├── Contracts/
│   │   │   └── NoticiaRepositoryInterface.php
│   │   └── Exceptions/
│   │       └── NoticiaNotFoundException.php
│   └── ...
│
├── Application/             # Commands, Queries, Handlers, DTOs — orquesta el dominio
│   ├── Noticias/
│   │   ├── Commands/
│   │   │   ├── CreateNoticiaCommand.php
│   │   │   ├── UpdateNoticiaCommand.php
│   │   │   └── DeleteNoticiaCommand.php
│   │   ├── Handlers/
│   │   │   ├── CreateNoticiaHandler.php
│   │   │   ├── UpdateNoticiaHandler.php
│   │   │   └── DeleteNoticiaHandler.php
│   │   ├── Queries/
│   │   │   ├── GetNoticiasQuery.php
│   │   │   └── GetNoticiaBySlugQuery.php
│   │   ├── QueryHandlers/
│   │   │   ├── GetNoticiasQueryHandler.php
│   │   │   └── GetNoticiaBySlugQueryHandler.php
│   │   └── DTOs/
│   │       └── NoticiaDTO.php
│   └── ...
│
├── Infrastructure/          # Implementaciones concretas: Eloquent, APIs externas
│   ├── Noticias/
│   │   ├── Models/
│   │   │   └── Noticia.php
│   │   └── Repositories/
│   │       └── EloquentNoticiaRepository.php
│   └── ...
│
├── Http/                    # Controllers, Requests, Middleware — solo entrada/salida HTTP
│   ├── Controllers/Api/
│   │   └── NoticiaController.php
│   ├── Requests/Noticias/
│   │   ├── StoreNoticiaRequest.php
│   │   └── UpdateNoticiaRequest.php
│   └── Middleware/
│
└── Providers/
    └── DomainServiceProvider.php   # Bindea interfaces → implementaciones
```

---

## Reglas estrictas de arquitectura

### Lo que ESTÁ PROHIBIDO

```php
// ❌ NUNCA: Eloquent en la capa Domain
namespace App\Domain\Noticias\Contracts;
use App\Infrastructure\Noticias\Models\Noticia; // ← PROHIBIDO

// ❌ NUNCA: Lógica de negocio en Controllers
public function store(Request $request): JsonResponse {
    $noticia = Noticia::create($request->all()); // ← PROHIBIDO
    return response()->json($noticia);
}

// ❌ NUNCA: DB::raw o consultas SQL fuera de repositorios
DB::select("SELECT * FROM noticias WHERE ..."); // ← PROHIBIDO en Controllers/Handlers

// ❌ NUNCA: Operaciones de escritura múltiple sin DB::transaction()
$noticia = Noticia::create($data);
$noticia->etiquetas()->attach($ids); // ← si falla, la noticia queda sin etiquetas
```

### Lo que SIEMPRE se debe hacer

```php
// ✅ Handlers con múltiples writes usan DB::transaction()
public function handle(CreateNoticiaCommand $command): NoticiaDTO
{
    return DB::transaction(function () use ($command) {
        $noticia = $this->noticiaRepository->create([...]);
        $this->noticiaRepository->syncEtiquetas($noticia->id, $command->etiquetas);
        return NoticiaDTO::fromModel($noticia);
    });
}

// ✅ Controllers inyectan Handlers, no repositorios directamente
public function __construct(
    private readonly CreateNoticiaHandler $createHandler,
    private readonly GetNoticiasQueryHandler $getNoticiasHandler,
) {}

// ✅ Toda respuesta sale como DTO, nunca como modelo Eloquent raw
return response()->json(NoticiaDTO::fromModel($model));
```

---

## Patrón completo: ejemplo con Noticias

El módulo Noticias es la referencia canónica para crear nuevos módulos con slug y estado.

### 1. DTO (Application layer)

```php
// app/Application/Noticias/DTOs/NoticiaDTO.php
final readonly class NoticiaDTO
{
    public function __construct(
        public int $id,
        public string $titulo,
        public string $slug,
        public ?string $entradilla,
        public string $estado,
        public bool $destacada,
        public ?string $fecha_publicacion,
        public ?string $created_at,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            titulo: $model->titulo,
            slug: $model->slug,
            entradilla: $model->entradilla,
            estado: $model->estado,
            destacada: (bool) $model->destacada,
            fecha_publicacion: $model->fecha_publicacion?->toIso8601String(),
            created_at: $model->created_at?->toIso8601String(),
        );
    }
}
```

### 2. Interface del repositorio (Domain layer)

```php
// app/Domain/Noticias/Contracts/NoticiaRepositoryInterface.php
interface NoticiaRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array;
    public function findById(int $id): mixed;
    public function findBySlug(string $slug): mixed;
    public function create(array $data): mixed;
    public function update(int $id, array $data): mixed;
    public function delete(int|array $ids): bool;
}
```

### 3. Excepción del dominio (Domain layer)

```php
// app/Domain/Noticias/Exceptions/NoticiaNotFoundException.php
class NoticiaNotFoundException extends \RuntimeException
{
    public function __construct(int|string $id)
    {
        parent::__construct("Noticia '{$id}' no encontrada.", 404);
    }
}
```

### 4. Command (Application layer)

```php
// app/Application/Noticias/Commands/CreateNoticiaCommand.php
final readonly class CreateNoticiaCommand
{
    public function __construct(
        public int $categoria_id,
        public int $autor_id,
        public string $titulo,
        public ?string $entradilla,
        public ?string $cuerpo,
        public string $estado,
        public bool $destacada,
    ) {}
}
```

### 5. Handler de escritura (Application layer)

```php
// app/Application/Noticias/Handlers/CreateNoticiaHandler.php
class CreateNoticiaHandler
{
    public function __construct(
        private readonly NoticiaRepositoryInterface $repository
    ) {}

    public function handle(CreateNoticiaCommand $command): NoticiaDTO
    {
        $model = $this->repository->create([
            'categoria_id' => $command->categoria_id,
            'autor_id'     => $command->autor_id,
            'titulo'       => $command->titulo,
            'entradilla'   => $command->entradilla,
            'cuerpo'       => $command->cuerpo,
            'estado'       => $command->estado,
            'destacada'    => $command->destacada,
        ]);

        return NoticiaDTO::fromModel($model);
    }
}
```

### 6. Query y QueryHandler (Application layer)

```php
// app/Application/Noticias/Queries/GetNoticiasQuery.php
final readonly class GetNoticiasQuery
{
    public function __construct(public PaginationDTO $pagination) {}
}

// app/Application/Noticias/QueryHandlers/GetNoticiasQueryHandler.php
class GetNoticiasQueryHandler
{
    public function __construct(
        private readonly NoticiaRepositoryInterface $repository
    ) {}

    public function handle(GetNoticiasQuery $query): array
    {
        return $this->repository->paginate($query->pagination);
    }
}
```

### 7. Repositorio Eloquent (Infrastructure layer)

```php
// app/Infrastructure/Noticias/Repositories/EloquentNoticiaRepository.php
class EloquentNoticiaRepository implements NoticiaRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array
    {
        $q = Noticia::query()->whereNull('deleted_at');

        if ($pagination->query) {
            $q->whereFullText(['titulo', 'entradilla', 'cuerpo'], $pagination->query);
        }

        $paginated = $q->orderBy('fecha_publicacion', 'desc')
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => collect($paginated->items())->map(fn ($n) => NoticiaDTO::fromModel($n))->all(),
            'total' => $paginated->total(),
        ];
    }

    public function findBySlug(string $slug): NoticiaDTO
    {
        $noticia = Noticia::where('slug', $slug)->whereNull('deleted_at')->first();
        if (! $noticia) throw new NoticiaNotFoundException($slug);
        return NoticiaDTO::fromModel($noticia);
    }

    // ... resto de métodos
}
```

### 8. Modelo Eloquent (Infrastructure layer)

```php
// app/Infrastructure/Noticias/Models/Noticia.php
// - Slug se genera automáticamente en boot() usando Str::slug()
// - SoftDeletes en tablas críticas (deleted_at)
// - $fillable siempre explícito — nunca $guarded = []
// - Búsqueda FULLTEXT disponible con whereFullText()
```

### 9. Controller (Http layer)

```php
// app/Http/Controllers/Api/NoticiaController.php
class NoticiaController extends Controller
{
    public function __construct(
        private readonly GetNoticiasQueryHandler $getNoticiasHandler,
        private readonly GetNoticiaBySlugQueryHandler $getNoticiaBySlugHandler,
        private readonly CreateNoticiaHandler $createHandler,
        private readonly UpdateNoticiaHandler $updateHandler,
        private readonly DeleteNoticiaHandler $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray([
            'pageIndex' => $request->get('pageIndex', 1),
            'pageSize'  => $request->get('pageSize', 10),
            'query'     => $request->get('query', ''),
            'sortKey'   => $request->input('sort.key', 'fecha_publicacion'),
            'sortOrder' => $request->input('sort.order', 'desc'),
        ]);

        return response()->json(
            $this->getNoticiasHandler->handle(new GetNoticiasQuery($pagination))
        );
    }

    public function store(StoreNoticiaRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateNoticiaCommand(
            categoria_id: $request->categoria_id,
            autor_id:     auth()->id(),
            titulo:       $request->titulo,
            entradilla:   $request->entradilla,
            cuerpo:       $request->cuerpo,
            estado:       $request->estado ?? 'borrador',
            destacada:    $request->boolean('destacada', false),
        ));

        return response()->json($dto, 201);
    }
}
```

### 10. Bindear el repositorio en DomainServiceProvider

```php
// app/Providers/DomainServiceProvider.php
$this->app->bind(NoticiaRepositoryInterface::class, EloquentNoticiaRepository::class);
```

---

## Cómo crear un nuevo módulo paso a paso

Ejemplo: agregar el módulo `Secretarias`:

```text
1.  Domain/Secretarias/Contracts/SecretariaRepositoryInterface.php
2.  Domain/Secretarias/Exceptions/SecretariaNotFoundException.php
3.  Application/Secretarias/DTOs/SecretariaDTO.php
4.  Application/Secretarias/Commands/CreateSecretariaCommand.php
5.  Application/Secretarias/Commands/UpdateSecretariaCommand.php
6.  Application/Secretarias/Commands/DeleteSecretariaCommand.php
7.  Application/Secretarias/Handlers/CreateSecretariaHandler.php
8.  Application/Secretarias/Handlers/UpdateSecretariaHandler.php
9.  Application/Secretarias/Handlers/DeleteSecretariaHandler.php
10. Application/Secretarias/Queries/GetSecretariasQuery.php
11. Application/Secretarias/Queries/GetSecretariaBySlugQuery.php
12. Application/Secretarias/QueryHandlers/GetSecretariasQueryHandler.php
13. Application/Secretarias/QueryHandlers/GetSecretariaBySlugQueryHandler.php
14. Infrastructure/Secretarias/Models/Secretaria.php
15. Infrastructure/Secretarias/Repositories/EloquentSecretariaRepository.php
16. Http/Controllers/Api/SecretariaController.php
17. Http/Requests/Secretarias/StoreSecretariaRequest.php
18. Http/Requests/Secretarias/UpdateSecretariaRequest.php
19. Bindear en DomainServiceProvider
20. Registrar rutas en routes/api/v1.php
```

---

## Convenciones de nomenclatura

| Elemento | Convención | Ejemplo |
| --- | --- | --- |
| Commands | `{Accion}{Modulo}Command` | `CreateNoticiaCommand` |
| Handlers | `{Accion}{Modulo}Handler` | `CreateNoticiaHandler` |
| Queries | `Get{Modulo}Query` | `GetNoticiasQuery` |
| QueryHandlers | `Get{Modulo}QueryHandler` | `GetNoticiasQueryHandler` |
| DTOs | `{Modulo}DTO` | `NoticiaDTO` |
| Repositorios | `Eloquent{Modulo}Repository` | `EloquentNoticiaRepository` |
| Interfaces | `{Modulo}RepositoryInterface` | `NoticiaRepositoryInterface` |
| Excepciones | `{Modulo}NotFoundException` | `NoticiaNotFoundException` |
| Modelos | singular PascalCase | `Noticia`, `TipoNorma` |
| Tablas | snake_case plural | `noticias`, `tipos_norma` |
| Rutas API | kebab-case plural | `/api/v1/tipos-norma` |

---

## Manejo de errores

Las excepciones del dominio se transforman en respuestas HTTP en `bootstrap/app.php`.

| Código | Significado |
| --- | --- |
| `200` | Respuesta exitosa |
| `201` | Recurso creado |
| `204` | Eliminación exitosa (sin body) |
| `401` | No autenticado |
| `403` | Sin permisos (`permiso:recurso.accion`) |
| `404` | Recurso no encontrado (`NotFoundException`) |
| `422` | Validación fallida (Laravel FormRequest) |
| `500` | Error interno no controlado |

---

## Sistema de permisos

Los permisos usan el middleware `permiso:recurso.accion`:

```php
Route::get('/noticias', [NoticiaController::class, 'index'])
    ->middleware('permiso:noticias.ver');

Route::post('/noticias', [NoticiaController::class, 'store'])
    ->middleware('permiso:noticias.crear');
```

| Módulo | Permisos |
| --- | --- |
| usuarios | `usuarios.ver`, `usuarios.crear`, `usuarios.editar`, `usuarios.eliminar` |
| noticias | `noticias.ver`, `noticias.crear`, `noticias.editar`, `noticias.eliminar` |
| normas | `normas.ver`, `normas.crear`, `normas.editar` |
| tramites | `tramites.ver`, `tramites.crear`, `tramites.editar` |
| transparencia | `transparencia.ver`, `transparencia.crear` |
| reportes | `reportes.ver` |

---

## Slugs

Los slugs se auto-generan en el `boot()` del modelo Eloquent. **No los pases en el request.**

Modelos con slug: `Noticia`, `Comunicado`, `Evento`, `Norma`, `TramiteCatalogo`, `Secretaria`, `Autoridad`, `DocumentoTransparencia`

Los endpoints públicos usan `{slug}` como identificador en la URL (nunca `{id}`).

---

## Búsqueda de texto completo

Las tablas principales tienen índices FULLTEXT en MySQL. Usar `whereFullText()` en los repositorios:

```php
// En el repositorio Eloquent
if ($pagination->query) {
    $q->whereFullText(['titulo', 'entradilla', 'cuerpo'], $pagination->query);
}
```

Tablas con FULLTEXT: `noticias`, `comunicados`, `normas`, `tramites_catalogo`, `eventos`, `secretarias`, `autoridades`, `documentos_transparencia`

Para búsqueda global en todas las tablas, usar la vista `v_busqueda_global`.

---

## Paginación

Todos los endpoints de listado aceptan:

```text
GET /api/v1/noticias?pageIndex=1&pageSize=15&query=presupuesto&sort[key]=fecha_publicacion&sort[order]=desc
```

La respuesta siempre devuelve:

```json
{
  "data": [...],
  "total": 42
}
```

Usar siempre `->paginate()` de Eloquent en el repositorio. Nunca `->skip()->take()` manual.

---

## Configuraciones persistentes

El proyecto usa `spatie/laravel-settings` para configuraciones del sistema que el admin puede cambiar sin tocar código:

```php
// app/Settings/GeneralSettings.php
class GeneralSettings extends Settings
{
    public string $site_name;
    public bool $site_active;
    public string $contact_email;
    public int $items_per_page;
    public bool $maintenance_mode;

    public static function group(): string { return 'general'; }
}
```

Las settings se guardan en la tabla `settings`. No usar `config()` para datos que el negocio debe poder cambiar.

---

## Base de datos cenefco (legado SIASEC)

> Nota: la base de datos legado, el prefijo de los archivos de migración y el formato del código de certificado siguen usando el identificador técnico `cenefco` por compatibilidad — no forman parte del rebranding de marca a mentabit.

El proyecto incluye **145 migraciones** generadas a partir del sistema legado SIASEC (`disereco_siasec`) más **~18 migraciones nuevas** para la capa web institucional y el módulo de certificados. Todas conviven en la misma BD.

### Convención de nombres

Todos los archivos de migración del legado siguen el patrón:

```text
2026_04_14_NNNNNN_create_cenefco_{tabla}_table.php
```

Ejemplo: `2026_04_14_000134_create_cenefco_t_usuario_table.php`

### Grupos de tablas

| Grupo | Tablas principales |
| --- | --- |
| **Moodle** | `mdl_course`, `mdl_user` (+ logs) |
| **Usuarios** | `t_usuario`, `t_nivel`, `t_grupopermiso`, `t_usuariogrupopermiso`, `t_permiso` |
| **Académico** | `t_materia`, `t_plan`, `t_materia_plan`, `t_imparte`, `t_inscripcion`, `t_nota`, `t_horario` |
| **Pagos** | `t_pago`, `t_fechapago`, `t_tipopago`, `t_documento`, `t_fechadoc` |
| **Permisos** | `t_regcomponente`, `t_regform`, `t_funcionalidadform` |
| **Contenido web** | `t_pagina`, `t_modulo`, `t_menu`, `t_bloqueplantilla`, `t_bloqueajustable`, `t_seccionbloque` |
| **Catálogos** | `t_ciudad`, `t_universidad`, `t_carrera`, `t_profesion`, `t_tipoprograma`, `t_programa` |
| **Relaciones usuario** | `t_usuarioplan`, `t_usuarioprograma`, `t_usuariotipoprograma`, `t_usuarioplandoc` |
| **Logs** | Todas las tablas tienen su espejo `_log` (registran cambios históricos) |

### Convenciones de tablas legado (`t_*`, `mdl_*`)

- Los nombres originales **se conservan tal cual** (prefijo `t_` o `mdl_`). No se renombran.
- PKs compuestas: mayoría de tablas usa `PRIMARY KEY (id_campo, id_us_reg)` — se genera con `$table->primary([...])`.
- **No usar `$table->timestamps()`** en estas tablas — tienen `fecha_reg` propio.
- **No usar `$table->softDeletes()`** — usan `estado tinyint` (0=inactivo, 1=activo).
- Las tablas `_log` registran el historial de cambios y tienen un campo `tipo_log` varchar.
- `id_us_reg` = usuario que registró el dato (auditoría interna del sistema legado).

### Convenciones de tablas nuevas (`web_*`, `t_cert_*`)

Estas tablas siguen convenciones modernas de Laravel y son **incompatibles** con las del legado:

- `bigIncrements('id')` como PK simple — **nunca PK compuesta**.
- `timestampTz('created_at')`, `timestampTz('updated_at')`, `timestampTz('deleted_at')` — **usar `timestampTz`, no `timestamps()`** (sin timezone).
- Estado como `string` semántico: `borrador` / `publicado` / `archivado` — **no `tinyint`**.
- FK constraints declarados con `->foreign()` y `->index()` explícito.
- Slugs únicos en toda tabla con URL pública (`->unique()`).
- `boolean('activo')`, `boolean('destacado')`, `integer('orden')` — campos estándar de contenido web.

### Tablas `web_*` por prioridad

| Prioridad | Tablas |
|-----------|--------|
| 🔴 Crítico | `web_banner`, `web_configuracion_sitio`, `web_suscriptor`, `web_contacto_mensaje` |
| 🟠 Alta | `web_testimonio`, `web_faq`, `web_aliado`, `web_preinscripcion`, `web_descargable`, `web_descargable_registro`, `web_cifra_institucional`, `web_acreditacion` |
| 🟡 Media | `web_evento`, `web_docente_perfil`, `web_popup`, `web_etiqueta`, `web_articulo_etiqueta`, `web_programa_etiqueta`, `web_categoria_programa`, `web_programa_resena`, `web_galeria_video`, `web_hito_institucional`, `web_nota_prensa`, `web_redes_sociales`, `web_calendario_academico`, `web_whatsapp_grupo` |
| 🟢 Baja | `web_redireccion`, `web_galeria_categoria`, `web_notificacion_push`, `web_descuento_promocion` |

También se agregan campos web a tablas legado existentes: `t_articulo` (slug, SEO, vistas, destacada), `t_programa` (slug, SEO, destacado, orden), `t_pagina` (slug, contenido_html, SEO), `t_foto` (alt, orden), `t_boletin` (slug, imagen, SEO).

### Módulo de Certificados

Tablas propias del módulo (prefijo `t_cert_*`):

```text
t_cert_plantilla           → Plantilla JPG + configuración visual
t_cert_plantilla_campo     → Posición X/Y, fuente y estilo por campo
t_lista_aprobados          → Lista oficial de aprobados por apertura de curso
t_certificado              → Certificado generado con código único + QR
t_cert_verificacion        → Log de verificaciones públicas desde la web
```

- Código único: formato `cenefco-{AÑO}-{6 chars}` (ej: `cenefco-2026-A4X9K2`)
- Endpoint público: `GET /verificar/{codigo}` — devuelve VÁLIDO / ANULADO / NO ENCONTRADO
- Generación masiva: `CertificadoService::generarLote(imparteId, plantillaId)`
- Dependencias: `simplesoftwareio/simple-qrcode`, `intervention/image`

### Integración WhatsApp

Campo `whatsapp_grupo_url` en `t_imparte` (Opción A — un grupo por curso). Si un curso necesita múltiples grupos usar tabla `web_whatsapp_grupo` (Opción B). Campo `whatsapp_unido` en `t_inscripcion` para rastrear si el estudiante accedió al enlace.

### Ejecutar solo migraciones cenefco

```bash
# Correr únicamente las 145 migraciones del legado
php artisan migrate --path=database/migrations --filter=cenefco
```

### Relaciones clave entre tablas legado

```text
t_usuario         → t_nivel           (id_niv)
t_usuario         → t_universidad     (id_universidad)
t_usuario         → t_carrera         (id_carrera)
t_usuario         → t_tipoprograma    (id_tipoprograma)
t_usuariogrupopermiso → t_usuario     (id_us)
t_usuariogrupopermiso → t_grupopermiso(id_grupopermiso)
t_imparte         → t_usuario         (id_us — docente)
t_imparte         → t_materia         (id_mat)
t_inscripcion     → t_usuario         (id_us)
t_inscripcion     → t_imparte         (id_imp)
t_nota            → t_imparte + t_usuario + t_materia
t_pago            → t_usuario + t_fechapago
t_materia_plan    → t_materia + t_plan
t_permiso         → t_grupopermiso + t_regform
t_regform         → t_regcomponente
```

---

## Tests

```bash
make test                           # todos los tests
make test-filter f=CreateNoticiaTest # test específico
php artisan test --coverage         # con reporte de cobertura
```

- Tests de **Handlers** → unitarios con Mockery (mockear el repositorio)
- Tests de **Controllers/Endpoints** → feature tests con `RefreshDatabase`
- Los tests viven en `tests/Unit/` y `tests/Feature/`
- Usar factories para datos de prueba (`database/factories/`)

---

## Módulo de Pagos — Referencia completa

> Este módulo maneja dinero real. Cualquier cambio aquí debe respetar todas las reglas que siguen sin excepción.

### Arquitectura DDD del módulo

El módulo Pagos está completamente migrado a DDD. Su estructura es la referencia canónica para módulos que operan sobre tablas legado (`t_*`):

```text
Domain/Pagos/
  Contracts/
    PagoRepositoryInterface.php
    FechaPagoRepositoryInterface.php
    DevolucionRepositoryInterface.php
  Exceptions/
    PagoNotFoundException.php
    PagoDuplicadoException.php          ← lanza 422, no 404
    FechaPagoNotFoundException.php
    DevolucionNotFoundException.php

Application/Pagos/
  DTOs/
    PagoDTO.php                         ← monto_pagado: float (nunca string)
    FechaPagoDTO.php                    ← monto_a_pagar: float
    ResumenPagoDTO.php                  ← pendiente: ?float (null = sin plan)
    DevolucionDTO.php
  Commands/
    CreatePagoCommand.php
    UpdatePagoCommand.php
    AnularPagoCommand.php
    RegistrarAnticipoCommand.php
    CreateFechaPagoCommand.php
    UpdateFechaPagoCommand.php
    CreateDevolucionCommand.php
    ResolverDevolucionCommand.php
  Handlers/                             ← uno por Command
  Queries/
    GetPagosQuery.php
    GetPagoByIdQuery.php
    GetFechasPagoQuery.php
    GetDevolucionesQuery.php
  QueryHandlers/                        ← uno por Query
  Services/
    PagoCalculadorService.php           ← ÚNICA fuente de cálculo de pendiente

Infrastructure/Pagos/
  Models/
    Pago.php        → tabla t_pago
    FechaPago.php   → tabla t_fechapago
    PagoLog.php     → tabla t_pagolog
    Devolucion.php  → tabla web_devolucion
  Repositories/
    EloquentPagoRepository.php
    EloquentFechaPagoRepository.php
    EloquentDevolucionRepository.php

Http/
  Controllers/Api/
    PagoController.php          ← solo orquesta, cero SQL
    FechaPagoController.php
    DevolucionController.php
    InscripcionController.php   ← inyecta PagoCalculadorService + RegistrarAnticipoHandler
    VentaController.php         ← inyecta PagoCalculadorService
  Requests/Pagos/
    StorePagoRequest.php
    UpdatePagoRequest.php
    StoreFechaPagoRequest.php
    UpdateFechaPagoRequest.php
    StoreAnticipoRequest.php
    StoreDevolucionRequest.php
    ResolverDevolucionRequest.php
```

Bindings registrados en `DomainServiceProvider`:

```php
$this->app->bind(PagoRepositoryInterface::class,       EloquentPagoRepository::class);
$this->app->bind(FechaPagoRepositoryInterface::class,  EloquentFechaPagoRepository::class);
$this->app->bind(DevolucionRepositoryInterface::class, EloquentDevolucionRepository::class);
```

### Tablas involucradas y sus PKs

| Tabla | PK | Auto-increment | Notas |
|---|---|---|---|
| `t_pago` | `id_pago` | ✅ Sí (migración 2026_06_01_000001) | `monto_pagado` almacenado como VARCHAR legado — siempre castear a float |
| `t_fechapago` | `id_fechapago` | ✅ Sí (migración 2026_06_01_000003) | `monto_a_pagar` también VARCHAR — castear a float |
| `t_pagolog` | `id_pagolog` | ✅ Sí (migración 2026_06_01_000002) | Espejo de `t_pago` para auditoría |
| `web_devolucion` | `id` (bigint) | ✅ Sí (nativa Laravel) | Estado: `pendiente` / `aprobada` / `rechazada` / `cancelada` |

> **CRÍTICO:** Nunca generar IDs manualmente con `MAX(id) + 1`. Usar siempre `insertGetId()` o `Model::create()` que delega en AUTO_INCREMENT.

### Reglas irrompibles del módulo de Pagos

```php
// ❌ NUNCA: generar IDs manuales
$maxId  = DB::table('t_pago')->max('id_pago') ?? 0;
$idPago = $maxId + 1;

// ✅ SIEMPRE: dejar que MySQL genere el ID
$pago = Pago::create([...]);  // id_pago lo asigna AUTO_INCREMENT

// ❌ NUNCA: validar o almacenar monto como string
'monto_pagado' => ['nullable', 'string']

// ✅ SIEMPRE: validar monto como numeric
'monto_pagado' => ['required', 'numeric', 'min:0.01', 'max:999999.99']

// ❌ NUNCA: registrar un pago sin verificar duplicado de cuota
DB::table('t_pago')->insertGetId([...]);

// ✅ SIEMPRE: verificar antes de insertar si hay id_fechapago
if ($repo->existePagoActivo($idUs, $idFechapago)) {
    throw new PagoDuplicadoException();
}

// ❌ NUNCA: escribir en t_pago sin DB::transaction()
DB::table('t_pago')->insert([...]);
DB::table('t_pagolog')->insert([...]);  // si falla, inconsistencia

// ✅ SIEMPRE: envolver toda escritura de pago en transacción
DB::transaction(function () use ($command) {
    $model = $this->repository->create([...]);
    return PagoDTO::fromModel($model);
});

// ❌ NUNCA: calcular pendiente duplicando lógica en controllers
$pendiente = $totalPlan - $totalPagado;  // en VentaController
$pendiente = $cuotasPendientes - $anticipos;  // en InscripcionController (distinto!)

// ✅ SIEMPRE: usar PagoCalculadorService (única fuente de verdad)
$resumen = $this->calculador->calcular($planId, $pagosActivos);
```

### Lógica de cálculo de pendiente (`PagoCalculadorService`)

Esta es la regla de negocio central — no duplicar en ningún otro lugar:

1. **Cuotas cubiertas**: pagos con `id_fechapago` no nulo y `pago_extra = 0`.
2. **Anticipos** (`pago_extra = 1`, `id_fechapago = null`): reducen el monto pendiente de cuotas no cubiertas.
3. **Sin cuotas en el plan**: `pendiente = total_plan - total_pagado`.
4. **Sin plan asignado**: devuelve `plan_no_asignado = true` y `pendiente = null` (nunca 0 — null significa "indeterminado", no "pagado").

```php
// Uso correcto en cualquier controller que necesite el resumen
$resumen = $this->calculador->calcular(
    planId: $planId ? (int) $planId : null,
    pagosActivos: $pagos->where('estado', 1)
);
// $resumen es un ResumenPagoDTO con: total_pagado, pendiente, cuotas_pagadas, etc.
```

### Auditoría de pagos

Toda modificación o anulación de `t_pago` debe registrarse en `t_pagolog` **antes** de aplicar el cambio, dentro de la misma transacción. El `EloquentPagoRepository::auditarCambio()` lo hace automáticamente cuando se llama desde `UpdatePagoHandler` y `AnularPagoHandler`.

Tipos de log válidos en `t_pagolog.tipo_log`:

| Valor | Cuándo |
|---|---|
| `edicion` | `UpdatePagoHandler` — se modifica monto, boleta, fecha u observación |
| `anulacion` | `AnularPagoHandler` — se pone `estado = 0` |
| `verificacion` | `VerificarPagoHandler` — se pone `estado_verificacion = 'verificado'` |
| `observacion` | `ObservarPagoHandler` — se pone `estado_verificacion = 'observado'` con `nota_verificacion`; notifica al cajero (rol Admin marcó el comprobante como inválido/dudoso) |

Al registrar un pago o anticipo (`CreatePagoHandler`, `RegistrarAnticipoHandler`) se notifica automáticamente a los usuarios con permiso `pagos.editar` vía `NotificacionService::enviarAPermiso()` (tipo `pago_registrado`) para que revisen el comprobante y lo marquen como verificado u observado.

### Webhook de pago online — PagosYa (no Stripe)

La pasarela de pago online activa es **PagosYa**, no Stripe. El webhook real está en `POST /pagosya/webhook` (`routes/api.php`, sin `auth:sanctum`, ruta pública), manejado por `PagoOnlineSessionController::webhook()`. Flujo:

```text
PagosYa → POST /pagosya/webhook
  → PagoOnlineSessionController::webhook()
  → PagosYaService::verificarFirmaWebhook()  ← HMAC-SHA256 sobre el body, header X-PagosYa-Signature
  → evento 'checkout.completed'
      → resuelve la PagoOnlineSession por checkout_id
      → verifica idempotencia por transaction_id (nro_boleta_bancaria)
      → llama CreatePagoHandler con los datos del checkout
```

`config/pagosya.php` expone `PAGOSYA_API_KEY`/`PAGOSYA_WEBHOOK_SECRET` — si `webhook_secret` está vacío, `verificarFirmaWebhook()` rechaza la request (fail-closed), nunca firma con clave vacía.

> Nota histórica: el proyecto tuvo en algún momento planeado un flujo de Stripe (`stripe/stripe-php` sigue en `composer.json`), pero nunca se completó — no hay `StripeService` ni ruta de webhook registrada. `PagoController::webhook()` (el stub que quedó) fue eliminado; usar siempre PagosYa como referencia de pasarela de pago online.

### Endpoints del módulo

| Método | Ruta | Controller@método | Request |
|---|---|---|---|
| GET | `/api/v1/pagos-academicos` | `PagoController@index` | — |
| POST | `/api/v1/pagos-academicos` | `PagoController@store` | `StorePagoRequest` |
| GET | `/api/v1/pagos-academicos/{id}` | `PagoController@show` | — |
| PUT | `/api/v1/pagos-academicos/{id}` | `PagoController@update` | `UpdatePagoRequest` |
| DELETE | `/api/v1/pagos-academicos/{id}` | `PagoController@destroy` | — |
| POST | `/pagosya/webhook` | `PagoOnlineSessionController@webhook` | sin auth, firma HMAC |
| GET | `/api/v1/fechas-pago` | `FechaPagoController@index` | — |
| POST | `/api/v1/fechas-pago` | `FechaPagoController@store` | `StoreFechaPagoRequest` |
| PUT | `/api/v1/fechas-pago/{id}` | `FechaPagoController@update` | `UpdateFechaPagoRequest` |
| DELETE | `/api/v1/fechas-pago/{id}` | `FechaPagoController@destroy` | — |
| POST | `/api/v1/inscripciones/{id}/anticipo` | `InscripcionController@registrarAnticipo` | `StoreAnticipoRequest` |
| GET | `/api/v1/inscripciones/{id}/devoluciones` | `DevolucionController@index` | — |
| POST | `/api/v1/inscripciones/{id}/devoluciones` | `DevolucionController@store` | `StoreDevolucionRequest` |
| PATCH | `/api/v1/devoluciones/{id}/cancelar` | `DevolucionController@update` | `ResolverDevolucionRequest` |
| PATCH | `/api/v1/devoluciones/{id}/resolver` | `DevolucionController@update` | `ResolverDevolucionRequest` |

---

## Módulo de Patrocinados / Visitas — Referencia completa

> Bounded context nuevo, sin relación funcional con cursos/diplomados/pagos. Gestiona el seguimiento de visitas a niños patrocinados por una ONG de apadrinamiento (app móvil Flutter offline-first + este backend). Planificación completa en [docs/patrocinados/](docs/patrocinados/) (9 etapas + DDL + código PHP completo por módulo en `docs/patrocinados/codigo/`).

### Diferencias de arquitectura frente al resto del proyecto

| Aspecto | Resto del proyecto (mentabit) | Módulo Patrocinados |
| --- | --- | --- |
| Motor de BD | MySQL 8, conexión `mysql` | **PostgreSQL 16 + PostGIS**, conexión propia `pgsql_patrocinados` |
| PK | `bigIncrements` / compuesta (legado) | **UUID** (`HasUuids`) en todas las tablas salvo `registros_auditoria` (BIGINT autoincremental, insert-only) |
| Auth | Sanctum + `t_usuario` + middleware `permiso:` | Sanctum multi-modelo con modelo `Usuario` propio + middleware `permiso-patrocinados:` (**no comparte usuarios ni permisos con mentabit**) |
| Geolocalización | No aplica | `GEOGRAPHY(POINT,4326)` vía PostGIS, derivado siempre de `latitude`/`longitude` en el Repository (nunca aceptado directo del cliente) |
| Rutas | `routes/api/v1.php` | `routes/api/patrocinados.php`, prefijo `/api/v1/patrocinados/...` |

### Reglas irrompibles del módulo

```php
// ❌ NUNCA: un modelo de este módulo sin el trait de conexión
class Foo extends Model { use HasUuids; }

// ✅ SIEMPRE: UsaConexionPatrocinados en todo modelo de los 7 submódulos
class Foo extends Model {
    use HasUuids, UsaConexionPatrocinados;
}

// ❌ NUNCA: transacción sin fijar la conexión explícitamente
DB::transaction(fn () => ...);

// ✅ SIEMPRE
DB::connection('pgsql_patrocinados')->transaction(fn () => ...);

// ❌ NUNCA: actualizar patrocinados.comunidad_id/ubicacion_id fuera de su Handler dedicado
$patrocinado->update(['comunidad_id' => $x]); // en UpdatePatrocinadoHandler u otro lugar

// ✅ SIEMPRE: único camino autorizado (ver EloquentPatrocinadoRepository::moverUbicacion())
$this->cambiarUbicacionHandler->handle(new CambiarUbicacionPatrocinadoCommand(...));

// ❌ NUNCA: reasignar una visita tocando solo visitas.user_id
$visita->update(['user_id' => $nuevoTecnico]);

// ✅ SIEMPRE: pasa por ReasignarVisitaHandler (cierra asignación activa + abre la nueva + actualiza visitas.user_id, misma transacción)

// ❌ NUNCA: leer/inferir visitas.estado_revision desde la última fila de revisiones_visitas
$estado = $visita->revisiones()->latest()->first()->estado;

// ✅ SIEMPRE: RevisarVisitaHandler escribe revisiones_visitas y visitas.estado_revision en la misma transacción

// ❌ NUNCA: exponer punto_geografico crudo o aceptar GEOGRAPHY del cliente
'punto_geografico' => $request->punto,

// ✅ SIEMPRE: el Repository deriva el punto de latitude/longitude (ST_MakePoint(lng, lat))
```

### Conexión y comandos

```bash
# .env — ver .env.example para todas las variables PATROCINADOS_DB_*
PATROCINADOS_DB_HOST=127.0.0.1
PATROCINADOS_DB_PORT=5432
PATROCINADOS_DB_DATABASE=patrocinados

# Migrar solo este módulo (nunca con el comando de migración default, que apunta a MySQL)
php artisan migrate --path=database/migrations/patrocinados --database=pgsql_patrocinados

# Seed inicial (roles SUPERADMIN/TECNICO_CAMPO/SUPERVISOR, 29 permisos, primer superadmin)
php artisan db:seed --class="Database\Seeders\Patrocinados\AccesoPatrocinadosSeeder"
```

### Módulos DDD y submódulos

| Módulo | Tablas | Entidad raíz |
| --- | --- | --- |
| `AccesoPatrocinados` | `usuarios`, `roles`, `permisos`, `usuarios_roles`, `roles_permisos` | `Usuario` (auth propia, `HasApiTokens`) |
| `Geografia` | `departamento`, `municipios`, `comunidades`, `ubicaciones` | Catálogo territorial + PostGIS |
| `Dispositivos` | `dispositivos` | Ciclo de vida PENDIENTE→ACTIVO→REVOCADO |
| `Patrocinados` | `estados_patrocinados`, `patrocinados`, `tipos_parentescos`, `tutores`, `historial_ubicaciones` | `Patrocinado` (dato de menor de edad — ver DTO reducido) |
| `Visitas` | 10 tablas (`visitas`, `planes_visitas`, `asignaciones_visitas`, `habilitaciones_visitas`, `ubicaciones_visitas`, `observaciones_visitas`, `fotos_visitas`, `revisiones_visitas`, `motivos_visitas`, `categorias_observaciones`) | `Visita` — núcleo operativo |
| `Sincronizacion` | `lotes_sincronizacion`, `elementos_sincronizacion` | Sync offline Flutter, idempotente por lote/elemento |
| `Auditoria` | `registros_auditoria` | `AuditoriaService`, transversal a los 6 módulos anteriores |

### Datos sensibles (menor de edad)

`GetPatrocinadosQueryHandler` devuelve `PatrocinadoResumenDTO` (sin tutor, sin dirección, edad aproximada) salvo que el usuario tenga el permiso `patrocinados.ver-detalle` — la decisión se toma en el QueryHandler, nunca en el Controller.

### Endpoints principales

| Método | Ruta | Controller@método |
| --- | --- | --- |
| POST | `/api/v1/patrocinados/auth/login` | `AuthController@login` |
| GET/POST | `/api/v1/patrocinados/usuarios` | `UsuarioController@index/store` |
| GET/POST | `/api/v1/patrocinados/ninos` | `PatrocinadoController@index/store` |
| POST | `/api/v1/patrocinados/ninos/{id}/cambiar-ubicacion` | `PatrocinadoController@cambiarUbicacion` |
| GET/POST | `/api/v1/patrocinados/visitas` | `VisitaController@index/store` |
| POST | `/api/v1/patrocinados/visitas/{id}/reasignar` | `VisitaController@reasignar` |
| POST | `/api/v1/patrocinados/visitas/{id}/iniciar` | `VisitaController@iniciar` |
| POST | `/api/v1/patrocinados/visitas/{id}/finalizar` | `VisitaController@finalizar` |
| POST | `/api/v1/patrocinados/visitas/{id}/reprogramar` | `VisitaController@reprogramar` |
| POST | `/api/v1/patrocinados/visitas/{visitaId}/habilitaciones` | `HabilitacionVisitaController@store` |
| POST | `/api/v1/patrocinados/visitas/{visitaId}/ubicaciones` | `UbicacionVisitaController@store` |
| POST | `/api/v1/patrocinados/visitas/{visitaId}/fotos` | `FotoVisitaController@store` |
| GET/POST | `/api/v1/patrocinados/visitas/{visitaId}/revisiones` | `RevisionVisitaController@index/store` |
| POST | `/api/v1/patrocinados/sincronizacion/lotes` | `SincronizacionController@iniciarLote` |
| GET | `/api/v1/patrocinados/registros-auditoria` | `RegistroAuditoriaController@index` (solo lectura) |

Listado completo (~90 endpoints) en `routes/api/patrocinados.php`.

### Autenticación: guard propio, no `auth:sanctum`

El módulo usa el guard **`patrocinados`** (`->middleware('auth:patrocinados')`), no `auth:sanctum` directamente. Motivo verificado en runtime (no solo teórico): `Usuario::createToken()` guarda el token en `personal_access_tokens` de `pgsql_patrocinados` correctamente (Eloquent propaga la conexión del padre al crear vía la relación `morphMany`), pero `Laravel\Sanctum\PersonalAccessToken::findToken()` — el método **estático** que Sanctum usa para autenticar cada request — no hereda conexión de ningún padre y por default cae en la conexión `mysql` (la de mentabit), rompiendo la autenticación de todos los requests posteriores al login.

Solución implementada: `App\Infrastructure\AccesoPatrocinados\Models\PersonalAccessToken` (subclase de la de Sanctum con `UsaConexionPatrocinados`) + `App\Infrastructure\AccesoPatrocinados\Guards\PatrocinadosTokenGuard` (reimplementa la resolución de token de Sanctum contra esa subclase) + guard `patrocinados` en `config/auth.php`. **Nunca** usar `Sanctum::usePersonalAccessTokenModel()` para esto — es una config global que rompería los tokens de mentabit (`App\Models\User`, conexión mysql).

Tabla `personal_access_tokens` propia en `pgsql_patrocinados` (migración `2026_09_02_000001`), con `tokenable_id` UUID (`uuidMorphs`, no el `morphs` bigint por defecto del stub de Sanctum) — es independiente de la tabla homónima en mysql.

### Compatibilidad Postgres: evitar `latestOfMany()` en modelos con PK UUID

`Visita::revisionVigente()` usaba `->latestOfMany('fecha_revision')`; Eloquent genera un `MAX(id)` como tie-breaker interno, y Postgres no tiene función `MAX()` para `uuid` (sí para `bigint`, que es lo que Eloquent asume por defecto). Falla con `SQLSTATE[42883]: function max(uuid) does not exist`, solo en runtime — no lo detecta el linter ni una revisión de código. Se reemplazó por un `hasOne(...)->orderByDesc('fecha_revision')` simple, que resuelve igual en eager/lazy loading sin agregados. **Regla general: en cualquier modelo de este módulo, evitar `latestOfMany()`/`oldestOfMany()` — usar `hasOne()->orderBy()` en su lugar.**

### Pendientes conocidos (no bloquean uso normal)

- Mecanismo de cifrado de `fotos_visitas` (at-rest de disco vs. aplicativo) sin decidir con negocio — ver `FotoVisitaService`.
- Adapters concretos de `SincronizacionRouterService` (`VisitaSyncAdapter`, etc.) pendientes: hoy despachar un elemento de sync lanza `InvalidArgumentException` para cualquier `tipo_entidad`.
- `HabilitarDispositivoParaVisitaHandler` no captura aún la violación del índice único parcial `uq_habilitaciones_visitas_activa` como excepción de dominio (sí lo hace `ReasignarVisitaHandler` para asignaciones).
