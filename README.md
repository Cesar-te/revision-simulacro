# Revision de Simulacros UNPRG

Sistema interno para administradores que importa claves y respuestas desde Excel, calcula puntajes ponderados por grupo academico y exporta resultados en Excel/PDF.

## Funciones principales

- Login administrativo sin registro publico.
- Importacion de claves por grupo: `A`, `BCD`, `EF` o general `ALL`.
- Importacion de respuestas por grupo sin borrar resultados de otros grupos.
- Calculo de puntaje con penalidad por incorrecta, blancos y preguntas anuladas.
- Ranking general, por grupo academico y por carrera.
- Filtros por grupo, carrera, nombre y DNI.
- Exportacion Excel con hojas General, Biomedicas, Letras e Ingenierias.
- Exportacion PDF horizontal para publicacion o impresion.

## Requisitos

- PHP 8.3+
- Composer
- Node.js y npm
- SQLite para desarrollo local, o la base configurada en `.env`

## Instalacion local

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

En PowerShell, si `npm run build` esta bloqueado por la politica de ejecucion, usa:

```bash
npm.cmd run build
```

## Acceso inicial

El seeder crea un administrador con estos valores por defecto:

- Correo: `admin@example.com`
- Contrasena: `password`

Puedes cambiarlos antes de ejecutar `php artisan migrate --seed` usando:

```dotenv
ADMIN_NAME="Administrador"
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=password
```

## Formato de archivos Excel

### Claves

El importador detecta columnas por encabezados. Los nombres recomendados son:

- `N.`, `Numero`, `Pregunta` o similar para el numero de pregunta.
- `Area`, `Asignatura`, `Materia` o `Curso` para la materia.
- `Clave`, `Respuesta` o `Resp` para la alternativa correcta.
- `Justificacion` o `Explicacion` opcional.

Si no encuentra encabezados, asume:

- Columna `A`: numero de pregunta.
- Columna `B`: area/asignatura.
- Columna `D`: clave.

### Respuestas

El importador busca:

- `DNI` o documento.
- `Nombre`, `Apellidos`, `Postulante` o `Alumno`.
- `Correo` o `Email`.
- `Carrera`, `Especialidad` u `Opcion`.
- Preguntas como `[PREGUNTA 1]`, `PREGUNTA 1`, `P1` o `1`.

Si no detecta columnas de preguntas, asume que empiezan desde la columna `G`.

## Reglas de datos

- Si se importa un grupo especifico, solo se reemplazan resultados de ese grupo.
- Si el mismo DNI aparece con nombres distintos, se conserva como filas separadas.
- Si coinciden examen, grupo, DNI y nombre, se actualiza la fila existente.
- Si se fuerza un grupo al importar, ese grupo manda sobre la carrera detectada.

## Comandos utiles

```bash
php artisan test
.\vendor\bin\phpunit --do-not-cache-result
npm.cmd run build
```

## Archivos fuera del repo

Los Excel (`*.xls`, `*.xlsx`) estan ignorados por Git. Si necesitas probar con archivos reales, dejalos localmente o en una carpeta privada, pero no los versionees.
