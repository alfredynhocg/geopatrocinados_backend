# Etapa 9 — Testing integral y hardening de despliegue

> Dependencias: todas las etapas anteriores (esta etapa consolida y verifica; algunos de sus puntos —CI, factories— conviene adelantarlos e ir aplicándolos desde la Etapa 2, no dejarlos literalmente para el final).
> Referencia: plan de revisión §7 (riesgos técnicos).

## 9.1 Estrategia de test (decidir antes de escribir el primer test del módulo)

- **Handlers** → unitarios con Mockery, mockeando el repositorio (igual que el resto del proyecto, `CLAUDE.md` §Tests).
- **Controllers/Endpoints** → Feature tests, pero **contra Postgres real**, no SQLite (SQLite no soporta `GEOGRAPHY`/PostGIS). Configurar una BD `pgsql_patrocinados_testing` separada de la de desarrollo.
- `phpunit.xml` / `.env.testing`: agregar las variables `PATROCINADOS_DB_*` apuntando a la BD de test, y usar `RefreshDatabase` con `$connection = 'pgsql_patrocinados'` en los Feature Tests de este módulo (Laravel permite especificar la conexión del trait).
- CI: agregar un servicio `postgis/postgis:16-3.4` al pipeline (GitHub Actions / GitLab CI / el que use el proyecto) junto al servicio MySQL que ya debe existir para el resto de la suite. Correr las migraciones de `database/migrations/patrocinados` contra ese servicio antes de la suite de Feature Tests del módulo.
- Comando sugerido para tests filtrados del módulo (análogo a `make test-filter`):
  ```bash
  php artisan test --filter=Patrocinados
  ```

## 9.2 Factories

Crear `database/factories/patrocinados/` con una Factory por entidad (no por tabla pivote pura). Casos especiales:
- `UsuarioFactory`: `password_hash` con `Hash::make('password')` fijo para tests reproducibles.
- `UbicacionFactory`: generar `latitude`/`longitude` dentro de un rango geográfico plausible del país/región real de la ONG (no lat/lng aleatorio global) para que los tests de distancia/PostGIS tengan sentido.
- `VisitaFactory`: estados por defecto `PLANIFICADA`, con `states()` para los demás (`finalizada()`, `noEncontrada()`, `reprogramada()`) — facilita escribir los tests de la regla de reprogramación (Etapa 6).

## 9.3 Checklist de cobertura mínima por módulo

- [ ] Etapa 2 (Acceso): login exitoso, login con credenciales inválidas, bloqueo tras N intentos, middleware de permisos deniega sin el permiso.
- [ ] Etapa 3 (Geografía): CRUD de las 4 entidades, derivación lat/lng ↔ `GEOGRAPHY` en `ubicaciones` (test de ida y vuelta).
- [ ] Etapa 4 (Dispositivos): alta, duplicado rechazado, aprobar, revocar.
- [ ] Etapa 5 (Patrocinados): CRUD, regla de sincronía `patrocinados`↔`historial_ubicaciones`, exposición de DTO reducido sin permiso de detalle.
- [ ] Etapa 6 (Visitas): las 6 pruebas listadas en el DoD de `06-visitas.md`, en particular el escenario completo de reprogramación → baja.
- [ ] Etapa 7 (Sincronización): lote con elementos mixtos éxito/error, idempotencia de reenvío.
- [ ] Etapa 8 (Auditoría): una fila de auditoría por cada operación de la tabla "qué se audita".

## 9.4 Hardening de seguridad antes de producción

- [ ] Confirmar mecanismo de cifrado de `fotos_visitas` (plan de revisión §5.7, pendiente de decisión con negocio): cifrado at-rest del bucket/disco vs cifrado aplicativo por archivo. Si es aplicativo, definir dónde vive la clave (KMS / variable de entorno / por-tenant) antes de subir la primera foto real.
- [ ] URLs de fotos siempre firmadas y de corta duración (`Storage::temporaryUrl()` si el driver lo soporta, o un endpoint propio que haga streaming autenticado) — nunca exponer `clave_almacenamiento` ni una URL pública permanente.
- [ ] Confirmar `PATROCINADOS_DB_SSLMODE=require` (no `prefer`) en producción si la BD Postgres es gestionada (RDS/Cloud SQL) y accesible por red pública.
- [ ] Revisar que el guard de auth propio del módulo (Etapa 2) no permite que un token de mentabit (`t_usuario`/Sanctum) autentique contra las rutas `/api/v1/patrocinados/*`, ni viceversa — test de Feature específico de aislamiento entre los dos sistemas de auth.
- [ ] Rate limiting en `/api/v1/patrocinados/auth/login` (`throttle:login`, mismo que ya usa mentabit) y en el endpoint de sync (`/api/v1/patrocinados/sincronizacion/lotes/*`) para evitar que un dispositivo comprometido inunde la API.

## 9.5 Runbook de migración a producción

1. Provisionar instancia Postgres con PostGIS habilitado (confirmar con el proveedor de hosting si es necesario un plan/addon específico — riesgo señalado en plan de revisión §7.1).
2. Ejecutar `CREATE EXTENSION postgis;` como paso de infraestructura (Terraform/script de provisión), no depender solo de la migración de Laravel si el usuario de BD de la app no tiene privilegios de superusuario para crear extensiones.
3. Correr `php artisan migrate --path=database/migrations/patrocinados --database=pgsql_patrocinados --force`.
4. Correr el seeder de `AccesoPatrocinadosSeeder` (Etapa 2) solo la primera vez, con credenciales del primer SUPERADMIN generadas de forma segura (no un password por defecto en el seeder de producción).
5. Smoke test post-deploy: login, crear un `departamento` de prueba, `GET /api/v1/patrocinados/ping`.

## 9.6 Documentación a actualizar al cerrar el módulo

- [ ] Agregar sección "Módulo de Patrocinados / Visitas — Referencia completa" a `CLAUDE.md`, con el mismo nivel de detalle que la sección existente "Módulo de Pagos", incluyendo la tabla de endpoints final y las reglas irrompibles (equivalentes a las de Pagos: nunca actualizar `comunidad_id` fuera de `CambiarUbicacionPatrocinadoHandler`, nunca leer `estado_revision` calculado en vivo, etc.).
- [ ] Marcar como resueltos, en `PLAN_INTEGRACION_PATROCINADOS.md`, los puntos de la sección 5 (ambigüedades) y 8 (hallazgos de diseño) a medida que cada uno se cierra con negocio — no dejar el documento de revisión desactualizado una vez empiece la implementación real.
