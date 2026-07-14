# Migracion Laravel - SaiQ

## Estado actual

Esta carpeta ya contiene una aplicacion Laravel funcional. La copia PHP inicial se conserva en:

```txt
legacy_php/
```

La app Laravel esta en la raiz de `SaiQ-Lavarel` y usa los assets visuales heredados:

```txt
public/css/
public/img/
public/js/
public/plugins/
```

## Migrado en esta fase

- Layout institucional en `resources/views/layouts/institucional.blade.php`.
- Vista inicial `/inicio` con menu de acceso a flujos por tipo de usuario.
- Login, logout y cambio de clave.
- Seguridad de contrasenas migrada a hash seguro de Laravel:
  - las claves MD5 heredadas se aceptan solo como compatibilidad temporal
  - al iniciar sesion correctamente con una clave MD5, se actualiza automaticamente a hash seguro
  - cambios de clave y usuarios nuevos ya se guardan con hash seguro
- Middleware de sesion `saiq.auth`.
- Homologacion por programa:
  - formulario
  - consulta de periodo, programas, planes y equivalencias
  - generacion de vista de resultado/PDF con `html2pdf.js`
  - guardado compatible con `homologacion` y `homologacion_detalle` si existen
  - opcion visible para guardar borrador antes de generar PDF
- Homologacion por asignatura:
  - formulario equivalente a `indexc.php`
  - pantalla de captura equivalente a `guardar2.php`
  - formato final equivalente a `single.php`
  - opcion visible para guardar borrador y trazabilidad hasta completar el estudio
- Borradores y trazabilidad:
  - listado por estado: borrador, completado o todos
  - detalle de homologacion con asignaturas registradas
  - auditoria de acciones por homologacion
- Tabla `audit_log` creada por migracion Laravel para registrar eventos de trazabilidad.
- Reconocimiento de titulo:
  - formulario equivalente a `completo.php`
  - carta equivalente a `reconoce.php`
- Listado administrativo de instituciones.
- Catalogos administrativos heredados:
  - instituciones: listado y creacion
  - programas: listado y creacion
  - asignaturas: listado y creacion
  - equivalencias: listado y creacion
  - usuarios: listado y creacion con firma
  - relaciones director-programa: listado y creacion
- Modelos base: `Usuario`, `Programa`, `Asignatura`, `Institucion`, `Homologacion`.
- Pruebas basicas de login, sesion y formulario.

## Rutas principales

```txt
GET  /login
POST /login
POST /logout
GET  /inicio
GET  /homologaciones/borradores
GET  /homologaciones/{homologacion}/trazabilidad
GET  /homologaciones/programa
POST /homologaciones/programa
GET  /homologaciones/asignatura
POST /homologaciones/asignatura/revision
POST /homologaciones/asignatura/resultado
GET  /reconocimiento-titulo
POST /reconocimiento-titulo
GET  /clave
POST /clave
GET  /admin/instituciones
POST /admin/instituciones
GET  /admin/programas
POST /admin/programas
GET  /admin/asignaturas
POST /admin/asignaturas
GET  /admin/equivalencias
POST /admin/equivalencias
GET  /admin/usuarios
POST /admin/usuarios
GET  /admin/relaciones
POST /admin/relaciones
```

## Base de datos

Configurada en `.env`:

```txt
DB_DATABASE=pcaedu_homologa
DB_USERNAME=root
DB_PASSWORD=
```

No se agregaron migraciones Laravel default para evitar tablas ajenas al sistema actual.
Se agrego una migracion propia para `audit_log`, necesaria para la trazabilidad solicitada.

## Ejecutar

```bat
c:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
```

URL:

```txt
http://127.0.0.1:8000/login
```

## Verificar

```bat
c:\xampp\php\php.exe artisan test
c:\xampp\php\php.exe artisan route:list
```

## Nota de version

Este XAMPP trae PHP 8.0.30. Por compatibilidad local se instalo Laravel 9. Para produccion moderna se recomienda actualizar a PHP 8.2+ y subir a Laravel actual antes del despliegue final.

## Pendiente por migrar

- Edicion/eliminacion de catalogos si la institucion decide agregarlas. En el PHP heredado revisado predomina listado + creacion.
- PDF del lado servidor con Dompdf/Browsershot si se quiere eliminar dependencia de navegador.
- Prueba manual con usuarios reales para confirmar permisos, rutas y flujos completos.
