# Patrocinados API (`geopatrocinados_backend`)

API REST construida con **Laravel 12** (PHP). Este backend aloja, bajo un mismo código, dos sistemas independientes que conforman la plataforma **Patrocinados**:

- **Módulo Cursos**: gestión de cursos, diplomados y programas de formación continua — estudiantes, inscripciones, notas, pagos, docentes, estructura académica, contenido web institucional y certificados con QR. Usa **MySQL**.
- **Módulo Visitas** (`Patrocinados / Visitas`): seguimiento de visitas a niños patrocinados de una ONG de apadrinamiento, consumido por la app móvil [`geopatrocinados_app`](../geopatrocinados_app). Usa **PostgreSQL + PostGIS**, con su propia base de datos, autenticación y reglas de negocio, completamente aislado del módulo Cursos.


> **Nota de nomenclatura**: internamente, algunos archivos de configuración (`.env`, `composer.json`) todavía usan el nombre histórico `mentabit` (ej. `APP_NAME=mentabit-api`, base de datos `cenefco_api`) porque así se llamaba el proyecto originalmente. A nivel de documentación y de negocio, este backend se referencia como **Patrocinados** — este README usa esa nomenclatura en todo momento.

> **Este README está escrito para alguien que no maneja mucho de programación.** Cada tecnología se explica con su propósito, de dónde se descarga, y los pasos exactos de instalación en Windows, macOS y Linux. Si ya sos desarrollador backend, andá directo a [Instalación rápida](#instalación-rápida-para-quien-ya-tiene-todo-instalado).

---

## Índice

1. [¿Qué es este proyecto?](#qué-es-este-proyecto)
2. [Tecnologías utilizadas (resumen)](#tecnologías-utilizadas-resumen)
3. [Detalle de cada tecnología](#detalle-de-cada-tecnología)
4. [Requisitos del sistema](#requisitos-del-sistema)
5. [Instalación paso a paso desde cero](#instalación-paso-a-paso-desde-cero)
6. [Instalación rápida (para quien ya tiene todo instalado)](#instalación-rápida-para-quien-ya-tiene-todo-instalado)
7. [Variables de entorno importantes](#variables-de-entorno-importantes)
8. [Cómo levantar el proyecto día a día](#cómo-levantar-el-proyecto-día-a-día)
9. [Comandos disponibles (Makefile)](#comandos-disponibles-makefile)
10. [Arquitectura del proyecto](#arquitectura-del-proyecto)
11. [Tests](#tests)
12. [Docker](#docker)
13. [Documentación adicional](#documentación-adicional)
14. [Problemas comunes (Troubleshooting)](#problemas-comunes-troubleshooting)
15. [Glosario de términos](#glosario-de-términos)

---

## ¿Qué es este proyecto?

Es el **servidor** (backend) que guarda toda la información y expone una **API** (un "menú" de operaciones vía internet) para que otras aplicaciones la consuman. En este repositorio conviven dos módulos, ambos parte de la plataforma **Patrocinados**:

1. **Módulo Cursos**: cursos/diplomados — estudiantes, inscripciones, pagos, certificados con QR, contenido web, notificaciones (WhatsApp/Telegram), etc. Usa **MySQL**.
2. **Módulo Visitas** (`Patrocinados / Visitas`): técnicos de campo de una ONG registran visitas a niños patrocinados (con ubicación geográfica). Usa **PostgreSQL + PostGIS** (una base de datos separada) y es consumido por la app móvil [`geopatrocinados_app`](../geopatrocinados_app).

Ambos módulos comparten el mismo código Laravel pero tienen bases de datos, autenticación y reglas de negocio completamente aisladas entre sí.

---

## Tecnologías utilizadas (resumen)

| Tecnología | Para qué sirve en este proyecto | Versión usada aquí |
|---|---|---|
| [PHP](https://www.php.net) | Lenguaje en el que está escrito todo el backend | Requerido `^8.2` — instalado en este entorno: `8.3.6` |
| [Laravel](https://laravel.com) | Framework PHP sobre el que está construida toda la API (rutas, base de datos, autenticación, colas, etc.) | `^12.0` (resuelto: `v12.58.0`) |
| [Composer](https://getcomposer.org) | Gestor de dependencias/paquetes de PHP — descarga Laravel y todas las librerías del proyecto | 2.x (instalado en este entorno: `2.7.1`) |
| [MySQL](https://www.mysql.com) | Base de datos del **módulo Cursos** (cursos, estudiantes, pagos, etc.) | 8.x |
| [PostgreSQL](https://www.postgresql.org) + [PostGIS](https://postgis.net) | Base de datos separada del **módulo Visitas**, con soporte de datos geográficos (ubicación de visitas) | PostgreSQL 16 + PostGIS (imagen recomendada `postgis/postgis:16-3.4`; cliente instalado en este entorno: `16.15`) |
| [Node.js](https://nodejs.org) + [npm](https://www.npmjs.com) | Necesarios para compilar los assets del frontend (CSS/JS) con Vite | Node 18+ (instalado en este entorno: `v22.23.2`) / npm (instalado: `10.9.8`) |
| [Laravel Sanctum](https://laravel.com/docs/sanctum) | Autenticación por tokens de la API — cada módulo (Cursos, Visitas) usa su propio "guard" independiente | `^4.3` (resuelto: `v4.3.1`) |
| [Vite](https://vitejs.dev) | Empaqueta y sirve los assets del frontend (CSS/JS) en desarrollo y producción | `^7.0.7` (resuelto: `7.3.1`) |
| [Tailwind CSS](https://tailwindcss.com) | Framework de CSS utilitario para las vistas web incluidas en el proyecto | `^4.0.0` (resuelto: `4.2.1`) |
| [L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger) | Genera documentación interactiva de la API (OpenAPI/Swagger) a partir de anotaciones en el código | `^10.1` (resuelto: `10.1.0`) |
| [Spatie Laravel Settings](https://github.com/spatie/laravel-settings) | Configuración dinámica que se puede cambiar sin editar código ni redeployar | `^3.7` (resuelto: `3.8.0`) |
| [DomPDF](https://github.com/barryvdh/laravel-dompdf) | Generación de documentos PDF (certificados, reportes) | `^3.1` (resuelto: `3.1.2`) |
| [simplesoftwareio/simple-qrcode](https://github.com/SimpleSoftwareIO/simple-qrcode) + [endroid/qr-code](https://github.com/endroid/qr-code) + [chillerlan/php-qrcode](https://github.com/chillerlan/php-qrcode) | Generación de códigos QR (certificados, pagos) | `^4.2` / `^5.0` / `^6.0` |
| [PhpSpreadsheet](https://phpspreadsheet.readthedocs.io) | Lectura/escritura de archivos Excel (reportes, importaciones) | `^5.8` (resuelto: `5.8.0`) |
| [Stripe PHP SDK](https://github.com/stripe/stripe-php) | Integración con Stripe para pagos online | `^19.4` (resuelto: `19.4.1`) |
| [Prism PHP](https://prismphp.com) | Integración con modelos de lenguaje (LLM) para funcionalidades de IA (ej. el bot) | `^0.100.1` |
| [Google API Client](https://github.com/googleapis/google-api-php-client) | Integraciones con servicios de Google (ej. login con Google) | `^2.19` (resuelto: `2.19.4`) |
| [Laravel Pint](https://laravel.com/docs/pint) | Formateador/linter de código PHP (solo desarrollo) | `^1.24` (resuelto: `1.29.1`) |
| [PHPUnit](https://phpunit.de) | Motor que corre los tests automáticos (solo desarrollo) | `^11.5.3` (resuelto: `11.5.55`) |
| [Mockery](https://github.com/mockery/mockery) | Librería para simular ("mockear") dependencias en los tests (solo desarrollo) | `^1.6` (resuelto: `1.6.12`) |
| [Faker](https://fakerphp.github.io) | Genera datos de prueba realistas para seeders/tests (solo desarrollo) | `^1.23` (resuelto: `1.24.1`) |
| [Laravel Sail](https://laravel.com/docs/sail) | Entorno de desarrollo con Docker, opcional | `^1.41` (resuelto: `1.57.0`) |
| [axios](https://axios-http.com) | Cliente HTTP usado por el JavaScript del frontend | `^1.11.0` (resuelto: `1.13.6`) |
| [SweetAlert2](https://sweetalert2.github.io) | Ventanas de alerta/confirmación estilizadas en las vistas web | `^11.26.22` |
| [concurrently](https://github.com/open-cli-tools/concurrently) | Corre varios procesos a la vez (servidor + colas + logs + Vite) con un solo comando (`make dev`) | `^9.0.1` (resuelto: `9.2.1`) |

Los paquetes PHP se descargan desde [Packagist](https://packagist.org) (el repositorio oficial de paquetes PHP/Composer) al correr `composer install`. Los paquetes de JavaScript se descargan desde [npmjs.com](https://www.npmjs.com) al correr `npm install`. **No hay que bajar ninguno a mano.**

---

## Detalle de cada tecnología

### PHP

**Qué es**: el lenguaje de programación en el que está escrito todo el código del backend (carpeta `app/`).

**De dónde se descarga**:
- Windows: https://windows.php.net/download (o mejor, usando [Laragon](https://laragon.org) o [XAMPP](https://www.apachefriends.org), que instalan PHP + extensiones + un servidor todo junto).
- macOS: `brew install php@8.3` (requiere [Homebrew](https://brew.sh)).
- Linux (Ubuntu/Debian): vía el PPA de [ondrej/php](https://launchpad.net/~ondrej/+archive/ubuntu/php) o el gestor de paquetes de tu distribución.

**Versión requerida**: `^8.2` (8.2 en adelante). En este entorno hay instalado PHP `8.3.6`.

**Extensiones necesarias** (vienen incluidas en la mayoría de las instalaciones modernas, pero hay que confirmarlas): `pdo_mysql`, `pdo_pgsql`, `gd`, `zip`, `intl`, `mbstring`, `bcmath`, `ctype`, `fileinfo`, `curl`, `xml`.

### Composer

**Qué es**: el gestor de paquetes de PHP — es al backend lo que `flutter pub get` es a la app móvil, o lo que una tienda de aplicaciones es a un celular: descarga Laravel y todas las librerías que el proyecto necesita, en las versiones correctas.

**De dónde se descarga**: https://getcomposer.org/download (oficial).

**Versión usada**: Composer 2.x (en este entorno: `2.7.1`).

### Laravel

**Qué es**: el framework PHP sobre el cual está construido todo el backend. Provee el sistema de rutas (qué URL ejecuta qué código), conexión a bases de datos, autenticación, colas de trabajos en segundo plano, envío de correos, y mucho más — evita reinventar todo eso desde cero.

**Cómo se obtiene**: no se descarga aparte — es una dependencia más declarada en `composer.json` (`laravel/framework`), que Composer instala junto con el resto.

**Versión**: `^12.0` (resuelta en este proyecto: `v12.58.0`).

### MySQL

**Qué es**: el motor de base de datos donde vive toda la información del **módulo Cursos** (cursos, estudiantes, inscripciones, pagos, etc.).

**De dónde se descarga**: https://dev.mysql.com/downloads (oficial), o instalando un paquete todo-en-uno como [XAMPP](https://www.apachefriends.org)/[Laragon](https://laragon.org) en Windows, `brew install mysql` en macOS, o `apt install mysql-server` en Linux. También se puede usar vía Docker (ver sección [Docker](#docker)).

**Versión recomendada**: MySQL 8.

### PostgreSQL + PostGIS

**Qué es**: PostgreSQL es otro motor de base de datos, usado exclusivamente por el **módulo Visitas** (separado de MySQL). **PostGIS** es una extensión de PostgreSQL que agrega soporte para datos geográficos (guardar y consultar coordenadas de ubicación) — imprescindible porque las visitas registran la ubicación donde ocurrieron.

**De dónde se descarga**: https://www.postgresql.org/download (PostgreSQL oficial) + https://postgis.net/install (PostGIS). La forma más simple de tener ambos juntos y ya configurados es con Docker, usando la imagen oficial `postgis/postgis:16-3.4` (ver el paso a paso más abajo).

**Versión usada**: PostgreSQL 16 + PostGIS (imagen `postgis/postgis:16-3.4`).

### Node.js y npm

**Qué es**: Node.js es un entorno para ejecutar JavaScript fuera del navegador; npm es su gestor de paquetes (el equivalente a Composer, pero para JavaScript). Acá se usan únicamente para **compilar los assets del frontend** (CSS de Tailwind, JavaScript) con Vite — no corren la API en sí.

**De dónde se descarga**: https://nodejs.org (elegir la versión LTS — "Long Term Support"). Instalar Node.js instala npm automáticamente.

**Versión requerida**: Node 18+ (en este entorno: `v22.23.2`, npm `10.9.8`).

### Vite + Tailwind CSS

**Qué son**: Vite es la herramienta que toma los archivos fuente de CSS/JS y los empaqueta/optimiza para el navegador (y recarga automáticamente los cambios durante el desarrollo). Tailwind CSS es un framework de CSS basado en clases utilitarias (`class="flex p-4 text-white"`) usado para armar las vistas web incluidas en el proyecto (paneles, páginas de pago, etc.).

**Cómo se obtienen**: son dependencias declaradas en `package.json`, se instalan con `npm install`.

### Laravel Sanctum

**Qué es**: el sistema de autenticación de la API — genera y valida los "tokens de sesión" que cada app cliente (panel admin, app móvil Flutter) usa para demostrar que el usuario ya inició sesión. Este proyecto usa **guards independientes** por módulo: un token del módulo Cursos no sirve para el módulo Visitas, y viceversa.

### L5-Swagger

**Qué es**: genera automáticamente una página de documentación interactiva de la API (formato OpenAPI/Swagger) a partir de comentarios especiales en el código de los controllers. Se regenera con `make swagger` y queda disponible en `/api/documentation` una vez generada.

### Otras librerías PHP relevantes

- **DomPDF**: genera archivos PDF (por ejemplo certificados de cursos).
- **simple-qrcode / endroid/qr-code / chillerlan/php-qrcode**: generan códigos QR (para certificados, comprobantes de pago).
- **PhpSpreadsheet**: lee y escribe archivos Excel (`.xlsx`), usado para reportes o importaciones masivas de datos.
- **Stripe PHP SDK**: conecta con [Stripe](https://stripe.com) para procesar pagos con tarjeta.
- **Prism PHP**: conecta con modelos de inteligencia artificial (LLM) para funcionalidades del bot/asistente.
- **Google API Client**: permite integraciones con servicios de Google (por ejemplo, "iniciar sesión con Google").
- **Spatie Laravel Settings**: guarda configuraciones del sistema que se pueden cambiar en caliente (sin tocar el código ni reiniciar el servidor).

### Herramientas solo de desarrollo

- **Laravel Pint**: revisa y corrige automáticamente el estilo del código PHP para que todo el equipo escriba de forma consistente.
- **PHPUnit + Mockery**: motor y utilidades para correr los tests automáticos.
- **Faker**: genera datos falsos pero realistas (nombres, emails, fechas) para poblar la base de datos de prueba.
- **Laravel Sail**: una forma alternativa de levantar todo el entorno (PHP, base de datos, etc.) usando Docker, sin instalar nada directamente en tu computadora.

---

## Requisitos del sistema

| Sistema operativo | Requisito |
|---|---|
| Windows | Windows 10/11 (64-bit), 8 GB de RAM recomendados, ~5 GB libres en disco |
| macOS | macOS 13+ recomendado, 8 GB de RAM, ~5 GB libres en disco |
| Linux | Distribución 64-bit reciente, 8 GB de RAM, ~5 GB libres en disco |

Software necesario (detallado paso a paso más abajo):

- PHP 8.2+ con extensiones `pdo_mysql`, `pdo_pgsql`, `gd`, `zip`, `intl`
- Composer 2
- Node.js 18+ / npm
- MySQL 8 (para el módulo Cursos)
- PostgreSQL 16 + extensión PostGIS (para el módulo Visitas)
- Git

**Alternativa que evita instalar MySQL/PostgreSQL manualmente**: usar Docker para las bases de datos (recomendado si no querés instalar motores de base de datos directamente en tu computadora). Ver el paso a paso más abajo.

---

## Instalación paso a paso desde cero

### Paso 1 — Instalar Git

Descargar de https://git-scm.com/downloads e instalar con las opciones por defecto. Verificar con:
```bash
git --version
```

### Paso 2 — Instalar PHP + Composer

**Windows** (opción recomendada para principiantes: usar [Laragon](https://laragon.org), que instala PHP, MySQL, y un servidor web todo junto con un instalador gráfico único)
1. Descargar Laragon desde https://laragon.org/download e instalar.
2. Laragon ya incluye Composer, PHP y MySQL — abrí "Laragon" y usá su terminal integrada ("Terminal" en el menú), o agregá `php` y `composer` al `PATH` de Windows si preferís usar la terminal normal.
3. Verificar: `php -v` y `composer --version`.

**macOS**
```bash
brew install php@8.3 composer
```
Verificar con `php -v` y `composer --version`.

**Linux (Ubuntu/Debian)**
```bash
sudo apt update
sudo apt install php8.3 php8.3-cli php8.3-mysql php8.3-pgsql php8.3-gd php8.3-zip php8.3-intl php8.3-mbstring php8.3-bcmath php8.3-curl php8.3-xml
```
Instalar Composer:
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```
Verificar con `php -v` y `composer --version`.

### Paso 3 — Instalar Node.js y npm

Descargar el instalador LTS desde https://nodejs.org para tu sistema operativo, e instalarlo con las opciones por defecto (esto instala `node` y `npm` juntos). Verificar:
```bash
node -v
npm -v
```

### Paso 4 — Instalar las bases de datos

**Opción A — Con Docker (recomendada, evita instalar MySQL/PostgreSQL directamente)**

Instalar Docker Desktop (Windows/Mac) o Docker Engine (Linux) desde https://www.docker.com/get-started.

Levantar MySQL (módulo Cursos):
```bash
docker run -d --name patrocinados_mysql \
  -e MYSQL_ROOT_PASSWORD=root \
  -e MYSQL_DATABASE=cenefco_api \
  -p 3306:3306 mysql:8
```

Levantar PostgreSQL + PostGIS (módulo Visitas):
```bash
docker run -d --name patrocinados_pg \
  -e POSTGRES_USER=postgres -e POSTGRES_PASSWORD=postgres -e POSTGRES_DB=patrocinados \
  -p 5432:5432 postgis/postgis:16-3.4
```

**Opción B — Instalación directa**
- MySQL: https://dev.mysql.com/downloads/mysql/ (o incluido en Laragon/XAMPP en Windows).
- PostgreSQL: https://www.postgresql.org/download, y luego instalar PostGIS siguiendo https://postgis.net/install (en Linux suele alcanzar con `sudo apt install postgresql-16-postgis-3`).

### Paso 5 — Clonar el proyecto

```bash
git clone <URL-del-repositorio>
cd geopatrocinados_backend
```

### Paso 6 — Configurar el archivo `.env`

Este archivo guarda toda la configuración sensible del proyecto (credenciales de base de datos, claves de servicios externos) y **nunca se sube a Git** (está en `.gitignore`). Se crea a partir de una plantilla:

```bash
cp .env.example .env
```

Editar `.env` con un editor de texto y, como mínimo, revisar/completar:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cenefco_api
DB_USERNAME=<tu usuario de MySQL>
DB_PASSWORD=<tu contraseña de MySQL>

PATROCINADOS_DB_HOST=127.0.0.1
PATROCINADOS_DB_PORT=5432
PATROCINADOS_DB_DATABASE=patrocinados
PATROCINADOS_DB_USERNAME=postgres
PATROCINADOS_DB_PASSWORD=<tu contraseña de Postgres>
```
(Si usaste los comandos Docker del Paso 4 tal cual, esos valores ya coinciden. `DB_DATABASE=cenefco_api` y `APP_NAME=mentabit-api` son nombres internos heredados del módulo Cursos — no hace falta cambiarlos.)

Las demás variables (WhatsApp, Zoom, Stripe, Google, etc.) son integraciones opcionales — se pueden dejar vacías para levantar el proyecto en local; solo son necesarias si vas a probar esas funcionalidades específicas.

### Paso 7 — Instalar dependencias, generar la clave de la app y migrar

Todo esto lo automatiza un solo comando:
```bash
make setup
```

Que internamente corre:
```bash
composer install       # descarga las librerías PHP (Laravel, etc.)
npm install             # descarga las librerías de JavaScript
php artisan key:generate   # genera la clave de encriptación única de la app (APP_KEY)
php artisan storage:link   # crea un enlace para servir archivos subidos (imágenes, PDFs)
php artisan migrate         # crea las tablas en la base de datos MySQL (módulo Cursos)
php artisan db:seed --class=RoleSeeder   # crea los roles base
```

Si preferís hacerlo manualmente paso a paso (o sin `make`), corré esos mismos comandos en orden.

### Paso 8 — Configurar y migrar el módulo Visitas (base de datos separada)

Este módulo usa PostgreSQL y **no** se migra con `make migrate` (que apunta solo a MySQL, módulo Cursos). Con la conexión Postgres ya configurada en `.env` (Paso 6):

```bash
php artisan migrate --path=database/migrations/patrocinados --database=pgsql_patrocinados
php artisan db:seed --class="Database\Seeders\Patrocinados\PatrocinadosDatabaseSeeder"
```

El seeder crea: los 9 departamentos de Bolivia, comunidades/ubicaciones demo en Cochabamba, 2 niños patrocinados con tutores, catálogos de visitas, y dos usuarios de prueba (ver tabla abajo).

### Paso 9 — Compilar los assets del frontend

```bash
npm run build
```
(o `npm run dev` para modo desarrollo con recarga automática — ver siguiente sección).

### Paso 10 — Levantar el servidor

```bash
make serve
```

Por defecto queda accesible en `http://localhost:8000`. Probar que responde:
```bash
curl http://localhost:8000/api/ping
```
Debería devolver `{"status":"ok","message":"API funcionando"}`.

---

## Instalación rápida (para quien ya tiene todo instalado)

```bash
git clone <URL-del-repositorio>
cd geopatrocinados_backend
make setup

# Módulo Visitas (Postgres, aparte):
php artisan migrate --path=database/migrations/patrocinados --database=pgsql_patrocinados
php artisan db:seed --class="Database\Seeders\Patrocinados\PatrocinadosDatabaseSeeder"

make dev   # servidor + colas + logs + Vite, todo en paralelo
```

**Usuarios de prueba** (creados por el seeder del módulo Visitas, `PatrocinadosDatabaseSeeder`):

| Usuario | Password | Rol |
|---|---|---|
| `superadmin` | `changeme123` | SUPERADMIN |
| `tecnico1` | `changeme123` | TECNICO_CAMPO |

Login: `POST /api/v1/patrocinados/auth/login` con `{"login": "...", "password": "..."}`.

---

## Variables de entorno importantes

El archivo `.env` (no versionado) controla toda la configuración. Las más relevantes para levantar el proyecto en local:

| Variable | Para qué es |
|---|---|
| `APP_KEY` | Clave de encriptación única de la app — se genera automáticamente con `php artisan key:generate`, nunca se escribe a mano |
| `APP_URL` | URL base donde corre el backend (por defecto `http://localhost:8000`) |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Conexión a MySQL, base de datos del **módulo Cursos** |
| `DB_TIMEZONE` | Zona horaria usada por la conexión Postgres — importante para que las fechas/horas de visitas no queden desfasadas |
| `PATROCINADOS_DB_HOST`, `PATROCINADOS_DB_PORT`, `PATROCINADOS_DB_DATABASE`, `PATROCINADOS_DB_USERNAME`, `PATROCINADOS_DB_PASSWORD`, `PATROCINADOS_DB_SSLMODE` | Conexión a PostgreSQL, base de datos separada del **módulo Visitas** |
| `SANCTUM_TOKEN_EXPIRATION_MINUTES` | Minutos hasta que expira un token de sesión del panel admin |
| `SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN` | Dominios permitidos para autenticación por sesión (paneles web) |
| `CACHE_STORE`, `QUEUE_CONNECTION`, `SESSION_DRIVER` | Dónde se guardan caché, colas de trabajos y sesiones (por defecto, en la propia base de datos) |
| `MAIL_MAILER` | Motor de envío de correos (`log` en desarrollo = los correos solo quedan escritos en el log, no se envían de verdad) |

El resto de las variables (`WHATSAPP_*`, `TELEGRAM_*`, `ZOOM_*`, `STRIPE_*`/`PAGOSYA_*`, `GOOGLE_CLIENT_ID`, `OLLAMA_*`) configuran integraciones externas opcionales — no son necesarias para levantar el backend en un entorno de desarrollo local, solo para probar esas funcionalidades puntuales. **Nunca compartas tu archivo `.env` real ni subas sus valores a Git** — contiene credenciales.

---

## Cómo levantar el proyecto día a día

Una vez instalado todo (secciones anteriores), para trabajar normalmente:

```bash
make dev
```

Esto levanta **en paralelo**, en una sola terminal:
- El servidor de la API (`php artisan serve`)
- Un worker de colas (procesa trabajos en segundo plano, como envío de notificaciones)
- El visor de logs en tiempo real (`pail`)
- El scheduler de tareas programadas
- Vite en modo desarrollo (recompila CSS/JS automáticamente al guardar cambios)

Para exponer el servidor en la red local (por ejemplo, para que la app móvil Flutter en un celular físico se conecte, ver el README de `geopatrocinados_app`):
```bash
make serve-public
```
Queda accesible en `http://<tu-IP-de-LAN>:8000`.

---

## Comandos disponibles (Makefile)

Ver la lista completa en cualquier momento con `make help`. Resumen:

| Categoría | Comando | Qué hace |
|---|---|---|
| Setup | `make setup` | Instalación completa desde cero (composer, npm, `.env`, key, migrate, seed) |
| Setup | `make install` | `composer install` + `key:generate` |
| Setup | `make update` | Actualiza dependencias PHP y Node |
| Setup | `make clean` | Elimina `vendor/`, `node_modules/` y caches |
| Setup | `make reset-hard` | Limpia, instala, migra y siembra todo desde cero |
| Setup | `make info` | Muestra versiones instaladas (PHP, Node, Composer, Laravel) |
| Desarrollo | `make dev` | Servidor + colas + logs + Vite, en paralelo |
| Desarrollo | `make serve` | Servidor local (`php artisan serve`), subida de archivos hasta 20MB |
| Desarrollo | `make serve-public` | Servidor accesible en la red local (`0.0.0.0:8000`) |
| Desarrollo | `make queue` / `make queue-work` | Worker de colas |
| Desarrollo | `make logs` | Logs en tiempo real |
| Desarrollo | `make tinker` | Consola interactiva (REPL) para probar código Laravel |
| Desarrollo | `make routes` | Lista todas las rutas registradas de la API |
| Base de datos | `make migrate` | Migraciones pendientes (MySQL, módulo Cursos) |
| Base de datos | `make fresh` | `migrate:fresh` + seed completo |
| Base de datos | `make migrate-status` | Ver qué migraciones ya corrieron |
| Base de datos | `make seed` | Corre todos los seeders |
| Calidad | `make test` | Corre la suite de tests |
| Calidad | `make test-filter f=NombreTest` | Corre un test específico |
| Calidad | `make lint` / `make format` | Revisar / aplicar formato de código (Pint) |
| Docker | `make sail-up` / `make sail-down` | Levantar/bajar el entorno con Laravel Sail (Docker) |
| Documentación | `make swagger` | Regenera la documentación OpenAPI de la API |

> **Nota**: las migraciones y seeders del **módulo Visitas** no pasan por `make migrate`/`make seed` (usan una conexión de base de datos distinta) — se corren manualmente como se detalla en el [Paso 8](#paso-8--configurar-y-migrar-el-módulo-visitas-base-de-datos-separada).

---

## Arquitectura del proyecto

Cada módulo se organiza en 4 capas, de afuera hacia adentro:

```text
app/
├── Domain/          Contratos (interfaces), excepciones — sin dependencias externas
├── Application/      Commands, Queries, Handlers, DTOs — orquesta el dominio
├── Infrastructure/   Modelos Eloquent, Repositorios — implementaciones concretas
└── Http/             Controllers, FormRequests, Middleware — solo entrada/salida HTTP
```


- Los Controllers nunca acceden a Eloquent directamente — solo inyectan Handlers.
- Toda respuesta HTTP sale como DTO, nunca como modelo Eloquent crudo.
- Escrituras que tocan más de una tabla van siempre en `DB::transaction()`.
- Los repositorios implementan una interfaz declarada en `Domain/{Modulo}/Contracts`.

**Estructura de carpetas relevante:**

```text
app/
  Domain/            Contratos y excepciones por módulo
  Application/        Commands, Queries, Handlers, DTOs por módulo
  Infrastructure/      Modelos Eloquent y Repositorios por módulo
  Http/                Controllers, FormRequests, Middleware
  Providers/           Service Providers (bindings de dominio)
database/
  migrations/          Migraciones del módulo Cursos (MySQL) + legado cenefco/SIASEC
  migrations/patrocinados/   Migraciones del módulo Visitas (PostgreSQL)
  seeders/             Seeders por módulo
routes/
  api.php              Punto de entrada de rutas API
  api/v1.php           Rutas del módulo Cursos v1
  api/patrocinados.php Rutas del módulo Visitas
docs/
  patrocinados/        Planificación e implementación del módulo Visitas
```

---

## Tests

```bash
make test                              # todos los tests (módulo Cursos)
make test-filter f=CreateNoticiaTest   # test específico
php artisan test --coverage            # con cobertura
```

- Tests de **Handlers** → unitarios con Mockery (mockeando el repositorio).
- Tests de **Controllers/Endpoints** → Feature tests con `RefreshDatabase`.
- El **módulo Visitas** requiere **Postgres real** en los Feature Tests (SQLite no soporta PostGIS) — ver `docs/patrocinados/09-testing-y-qa.md`.

---

## Docker

El proyecto incluye un `Dockerfile` (basado en `php:8.2-fpm-alpine`) para construir una imagen de producción, y soporte para [Laravel Sail](https://laravel.com/docs/sail) como entorno de desarrollo alternativo.

```bash
make sail-up      # levantar contenedores (requiere Docker instalado)
make sail-down    # detener contenedores

# Build/gestión manual de la imagen de producción:
docker build -t patrocinados-api:latest .
docker run -d --name patrocinados_api --env-file ../.env.docker -p 8000:8000 patrocinados-api:latest
docker logs -f patrocinados_api
```

---

## Documentación adicional

- **`docs/patrocinados/`** — planificación por etapas del módulo Visitas: estructura DDD, endpoints, criterios de aceptación y código completo por módulo (`docs/patrocinados/codigo/`).
- **`PLAN_INTEGRACION_PATROCINADOS.md`** — revisión técnica original del diseño de base de datos del módulo Visitas.
- **Swagger / OpenAPI** — `make swagger` regenera la especificación; queda servida en `/api/documentation` una vez generada.

---

## Problemas comunes (Troubleshooting)

**`composer: command not found`**
Composer no quedó en el `PATH`. Revisar el Paso 2 de la instalación, o reinstalar siguiendo exactamente https://getcomposer.org/download.

**`SQLSTATE[HY000] [2002] Connection refused` al migrar**
El servidor de base de datos (MySQL o PostgreSQL) no está corriendo, o los datos de `.env` (host/puerto/usuario/contraseña) no coinciden con los reales. Confirmar que el contenedor Docker o el servicio local esté activo.

**`php artisan migrate` no crea las tablas del módulo Visitas**
Es esperado — ese comando solo migra la conexión MySQL (módulo Cursos). Las migraciones del módulo Visitas se corren aparte, apuntando explícitamente a `--database=pgsql_patrocinados` (ver [Paso 8](#paso-8--configurar-y-migrar-el-módulo-visitas-base-de-datos-separada)).

**Error de PostGIS ("function st_... does not exist" o similar)**
La base de datos Postgres no tiene la extensión PostGIS habilitada, o se está usando una imagen/instalación de Postgres sin PostGIS. Usar la imagen `postgis/postgis:16-3.4` (Docker) o instalar el paquete PostGIS correspondiente a tu versión de PostgreSQL.

**Error al correr `npm run build` o `npm run dev`**
Confirmar la versión de Node con `node -v` (debe ser 18+). Si el error persiste, borrar `node_modules/` y volver a correr `npm install`.

**"No application encryption key has been specified"**
Falta correr `php artisan key:generate` (ya incluido en `make setup`).

**Quiero empezar de cero porque algo quedó en un estado raro**
```bash
make reset-hard   # limpia, instala, migra y siembra todo desde cero (solo módulo Cursos/MySQL)
```
Recordá que esto **no** toca la base de datos del módulo Visitas (Postgres) — si también querés reiniciarla, hay que borrar/recrear esa base manualmente y volver a correr el Paso 8.

---

## Glosario de términos

- **API (Application Programming Interface)**: el "menú" de operaciones que este backend ofrece por internet (ej. "iniciar sesión", "listar visitas") para que otras aplicaciones (como la app móvil Flutter) las usen.
- **Framework**: conjunto de código y herramientas reutilizables sobre el que se construye una aplicación (Laravel es un framework PHP).
- **ORM / Eloquent**: la capa de Laravel que permite trabajar con la base de datos usando código PHP (clases y objetos) en vez de escribir SQL a mano directamente.
- **Migración**: un archivo de código que describe un cambio en la estructura de la base de datos (crear una tabla, agregar una columna) de forma versionada y repetible.
- **Seeder**: un archivo de código que llena la base de datos con datos iniciales o de prueba (usuarios demo, catálogos).
- **DDD (Domain-Driven Design)**: forma de organizar el código separando las reglas de negocio ("dominio") de los detalles técnicos (base de datos, HTTP), para que el sistema sea más fácil de mantener a medida que crece.
- **CQRS (Command Query Responsibility Segregation)**: patrón que separa las operaciones que **cambian** datos (Commands) de las que solo **leen** datos (Queries), cada una con su propio flujo.
- **DTO (Data Transfer Object)**: un objeto simple que solo transporta datos entre capas (por ejemplo, entre el backend y la respuesta JSON que recibe la app), sin lógica de negocio.
- **Token de sesión**: una especie de "credencial temporal" que el backend entrega al iniciar sesión, y que el cliente (app o panel web) reenvía en cada pedido posterior para demostrar que ya inició sesión.
- **Guard de autenticación**: en Laravel/Sanctum, un mecanismo de login independiente — este proyecto tiene guards separados para el módulo Cursos y para el módulo Visitas, de forma que un token de uno no sirve para el otro.
- **Cola de trabajos (queue)**: mecanismo para ejecutar tareas "en segundo plano" (como enviar una notificación) sin hacer esperar al usuario a que termine.
- **Seed / sembrar datos**: acción de ejecutar un seeder para poblar la base de datos con información inicial.
- **PostGIS**: extensión de PostgreSQL que agrega tipos de datos y funciones para trabajar con información geográfica (coordenadas, distancias, áreas).
- **Bounded Context**: en DDD, una parte del sistema con sus propias reglas, modelos y (en este proyecto) hasta su propia base de datos, aislada del resto — el módulo Visitas es un bounded context aparte del módulo Cursos, ambos dentro de la plataforma Patrocinados.
