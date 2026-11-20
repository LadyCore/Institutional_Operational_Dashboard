# Institutional Operational Dashboard

Prototipo funcional de seguimiento operativo para un organismo central ficticio (CENTRAL) y nueve dependencias. Sirve para evaluar la interfaz, los permisos y el modelo de datos antes de construir la aplicación definitiva.

**Estado del proyecto:** prototipo funcional demostrativo. No es apto todavía para producción.

## Objetivo

Centralizar, en una aplicación navegable, la coordinación y la supervisión de actividades operativas compartidas entre una autoridad central y varios institutos. Una misma actividad puede involucrar a varios institutos, cada uno con tareas, fechas y requerimientos propios.

Los organismos, las personas y las operaciones son inventados. No hay datos institucionales reales.

## Tecnologías

- PHP 8.1 o superior, sin framework y sin dependencias externas.
- HTML, CSS y un JavaScript breve. No usa una CDN.
- Servidor embebido de PHP para el desarrollo. Apache con PHP-FPM puede publicar la carpeta `public` más adelante.
- Datos ficticios en `RepositorioFicticio`, detrás del contrato `RepositorioOperativo`.
- Esquema previsto, no conectado, en `database/esquema.sql` para MySQL 8 o MariaDB 10.11.

No usa PostgreSQL, Supabase, Firebase ni autenticación externa. No incluye servicios de IA. Esta versión no tiene base de datos ni autenticación real.

## Estructura de archivos

```text
index.php                      Aviso: el sitio se sirve desde public
public/index.php               Controlador y rutas
public/.htaccess               Opciones básicas para Apache
public/assets                  CSS, JavaScript e ícono
src/bootstrap.php              Arranque y sesión
src/funciones.php              Fechas, semáforo, filtros y utilidades
src/Politica.php               Permisos de los tres perfiles
src/RepositorioOperativo.php   Contrato de datos y datos ficticios
src/ServicioOperativo.php      Indicadores, cambios de sesión y auditoría
views/                         Páginas y fragmentos de la interfaz
database/esquema.sql           Esquema futuro, no ejecutado
scripts/comprobar.php          Comprobación de plazo, orden y permisos
scripts/servir.ps1             Arranque local si PHP está en el PATH o en .tools
```

La carpeta `.tools/` es solo de este equipo de desarrollo. El `.gitignore` la excluye.

## Requisitos y ejecución local

Hace falta PHP 8.1 o superior. Desde la raíz del proyecto:

```text
php -S localhost:8080 -t public
```

Abra http://localhost:8080/

Para comprobar plazo, orden y permisos sin el navegador:

```text
php scripts/comprobar.php
```

En Windows, si `php` no está en el PATH, use la ruta de su `php.exe`:

```text
& "C:\ruta\php.exe" -S localhost:8080 -t public
```

`scripts/servir.ps1` hace lo mismo y también busca `.tools\php\php.exe` si esa copia local existe. Esa copia no forma parte del repositorio.

Cuando exista el servidor definitivo, el documento de Apache debe apuntar a `public`. Las direcciones son `index.php?r=panel` y no requieren reescritura.

La zona horaria es `America/Argentina/Buenos_Aires`. Las fechas de ejemplo se calculan respecto del día del servidor para que el semáforo siga mostrando vencidas, próximas y en plazo.

## Funcionalidades implementadas

- Panel con totales, próximas a vencer, vencidas, finalizadas, requerimientos pendientes, distribución por instituto y avance.
- Tabla general ordenada con las actividades vencidas primero y el resto por fecha de realización.
- Actividades de los siete tipos previstos, con ficha, institutos participantes y tareas desplegables.
- Tareas con instituto, responsable, fecha límite propia, estado, avance, observaciones y requerimientos.
- Semáforo por días calendario, separado del estado de ejecución. Una tarea finalizada no queda como vencida pendiente.
- Requerimientos de recursos, solicitudes a la autoridad central, pedidos a otro instituto e impedimentos, con estado de resolución y responsable de atenderlos.
- Tres perfiles de demostración, elegidos con **Ver como** y **Aplicar**:
  - Mariana Quelvo, administradora central.
  - Responsables de IFAL, ISEL, CCTA, IGAN y EEOP.
  - Elena Duarte, consulta de solo lectura.
- El alcance se calcula en el servidor. Un responsable no recibe una actividad ajena aunque escriba su identificador en la dirección.
- Auditoría de ejemplo y registro de los cambios hechos durante la sesión.
- Filtros, navegación entre secciones e interfaz en español adaptable a computadora y tablet.

## Limitaciones actuales

Los cambios de tareas, requerimientos y auditoría de la visita se guardan en la sesión PHP. No hay base de datos. Se pierden al cerrar la sesión o al usar **Descartar cambios de la sesión**, que vuelve a los datos ficticios precargados.

Otras limitaciones de esta versión:

- El selector de perfil no es autenticación.
- La auditoría precargada es de demostración y la de la sesión se puede descartar. En la aplicación definitiva el historial debe escribirse solo y un usuario común no debe poder alterarlo.
- No se crean actividades nuevas, no hay adjuntos, no hay correo y no hay alta de usuarios.
- Los permisos ya se deciden en el servidor, pero todavía no hay cuentas ni una base que los imponga en cada consulta.

## Qué faltaría para producción

- Apache y PHP-FPM, con el sitio servido desde `public`.
- MySQL 8 o MariaDB 10.11 y una implementación PDO de `RepositorioOperativo`.
- Cuentas locales, contraseñas y sesión real.
- Usuario de base con `INSERT` en auditoría y sin `UPDATE` ni `DELETE` sobre esa tabla.
- HTTPS, respaldos y, si más adelante hace falta, el alta administrativa de actividades.
