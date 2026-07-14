# SaiQ-Laravel

SaiQ-Laravel es la aplicación Laravel migrada desde un sistema PHP legacy de homologación académica. Incluye flujos completos para homologación de programas, homologación de asignaturas, reconocimiento de título, administración de catálogos y trazabilidad de auditoría.

## Estado actual

- Laravel 9.19
- PHP 8.0+ (XAMPP local)
- Base de datos de producción: `pcaedu_homologa`
- Base de datos de testing: `pcaedu_homologa_test`
- Test suite actual: `99 tests, 281 assertions`
- Middleware administrativo: `saiq.admin`
- Sesión legacy: `session('x')` = usuario, `session('tus')` = rol

## Características principales

- Autenticación legacy con compatibilidad MD5 y rehash automático
- Middleware `saiq.auth` y `saiq.admin`
- CRUD administrativo para instituciones, programas, asignaturas, equivalencias y usuarios
- Homologación por programa y por asignatura
- Borradores y trazabilidad de homologaciones
- Auditoría de acciones en `audit_log`
- Validación de datos y constraints de base de datos para evitar duplicados

## Requisitos

- PHP 8.0 o superior
- MySQL
- Composer
- XAMPP en Windows (entorno local)
- Opcional: Node.js / npm si se quiere regenerar assets

## Instalación

1. Instalar dependencias:

```bash
composer install
```

2. Copiar el entorno:

```bash
cp .env.example .env
```

3. Ajustar la conexión de base de datos en `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pcaedu_homologa
DB_USERNAME=root
DB_PASSWORD=
```

4. Generar clave de aplicación:

```bash
php artisan key:generate
```

5. Ejecutar migraciones:

```bash
php artisan migrate
```

6. Iniciar servidor local:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

7. Acceder a la aplicación:

```txt
http://127.0.0.1:8000/login
```

## Ejecutar tests

```bash
vendor/bin/phpunit
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --testsuite Feature
vendor/bin/phpunit --testsuite Integration
```

## Documentación

- `TESTING_GUIDE.md` — Guía de pruebas
- `TESTING_IMPLEMENTATION.md` — Resumen de implementación de pruebas
- `MIGRACION_LARAVEL.md` — Estado de la migración Laravel
- `README_MEJORAS.md` — Resumen ejecutivo de mejoras
- `ANALISIS_MEJORAS.md` — Detalle de análisis y soluciones
- `PLAN_IMPLEMENTACION.md` — Plan de trabajo estructurado

## Notas importantes

- El sistema usa sesiones personalizadas para control de acceso.
- Las rutas administrativas están protegidas por `saiq.admin`.
- El estándar de sesiones legacy mantiene compatibilidad con los datos actuales.
- El conjunto de pruebas actual está verde en este estado.
- Las migraciones incluyen constraints e índices para evitar duplicados.

## Licencia

Este proyecto utiliza la licencia MIT.
