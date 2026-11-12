
# Mario DAW World

Aplicación web de intranet de Mario DAW World para que el personal del Gaming Center consulte las salas, ocupe un módulo cuando llega un grupo, lo libere cuando se marcha y consulte el historial de uso.

## Requisitos

- WAMP o entorno equivalente con PHP 8.0 o posterior.
- MySQL 8 y las extensiones PHP `PDO` y `pdo_mysql`.
- Navegador moderno con JavaScript habilitado.
- La validación en JavaScript muestra mensajes accesibles en castellano; el servidor vuelve a validar los datos antes de guardar, incluso si JavaScript está desactivado o se manipula la solicitud.
- SweetAlert2 se carga desde jsDelivr para confirmar acciones. Si el CDN no está disponible, se usa una confirmación básica del navegador.

## Puesta en marcha

1. Inicia los servicios de Apache y MySQL.
2. Para una instalación nueva, importa `sql/db_pj5_gamingCenter.sql` en phpMyAdmin o desde la consola MySQL.
3. **La importación elimina y recrea la base `gamingCenter` y sus datos.** No la importes sobre una base con datos que quieras conservar.
4. Si ya tienes una base existente que necesita actualizarse, haz una copia de seguridad y consulta `sql/migracion.sql`. Ejecuta solo la sección que corresponda a tu esquema actual: la migración desde `tipos_sala` o la actualización de las instantáneas del gamemaster. No ejecutes estas migraciones sobre una instalación nueva: el SQL de instalación ya contiene todos los cambios.
5. Comprueba los parámetros en `config/conexion.php`. Se pueden sobrescribir con `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS` y `DB_NAME`.
6. Abre `http://localhost/Sintesis%200616/PJ/`.

## Cuentas de prueba

| Rol | Usuario | Contraseña |
|---|---|---|
| Administración | `admin` | `AdminGaming2026!` |
| Gamemaster | `mariano` | `PlayGaming2026!` |

Son cuentas locales para probar el proyecto. Cámbialas antes de desplegarlo fuera del entorno de clase.

## Funcionamiento

1. El usuario inicia sesión con su nombre de usuario y contraseña. La aplicación comprueba el hash con `password_verify`, inicia la sesión y dirige al usuario al panel de su rol.
2. El panel del gamemaster agrupa las salas por las zonas registradas en la base de datos, con resúmenes de capacidad y disponibilidad. Se puede filtrar por zona, sala y estado.
3. Cuando llega un grupo, el gamemaster elige el número de jugadores y ocupa el módulo. El servidor comprueba de nuevo la capacidad y bloquea la fila del módulo durante la operación para evitar ocupaciones simultáneas.
4. La ocupación registra el gamemaster y la hora de inicio. Solo ese mismo gamemaster puede liberar el recurso; la hora de salida cierra el registro sin borrar el histórico.
5. El historial permite filtrar por zona, sala, módulo y fechas, identifica la zona de cada ocupación y muestra hasta 50 registros por página.
6. Ambos roles pueden administrar la estructura del local desde «Gestión del local»: crear y editar zonas, salas, tipos de módulo, capacidades y módulos. La gestión sincroniza los puestos de juego con la capacidad configurada.
7. El rol de administración dispone de un resumen agrupado por zona y sala, una vista de las ocupaciones abiertas y el panel de gamemasters para crear, editar, restablecer contraseñas, reactivar o eliminar cuentas.

Las zonas, salas y módulos se archivan y dejan de aparecer en la vista operativa; no se permite archivarlos mientras tengan ocupaciones abiertas. Después del archivado se pueden eliminar definitivamente junto con sus puestos e historial, incluidas las ocupaciones cerradas y abiertas. Esta acción es irreversible. Las zonas, características del módulo y nombre del gamemaster de cada ocupación se guardan como instantáneas. Las cuentas gamemaster se desactivan antes de poder eliminarlas definitivamente. La eliminación de la cuenta borra también su historial, pero se bloquea mientras tenga ocupaciones abiertas. La aplicación vuelve a comprobar que la cuenta siga activa en las páginas privadas y en las acciones de escritura.

Los formularios que modifican datos usan POST y validación en servidor. Las consultas que reciben valores del usuario usan sentencias preparadas; los datos mostrados se escapan para HTML.

## Pruebas manuales

- Accede como `mariano` y verifica que llega al panel de salas.
- Filtra por zona, sala y disponibilidad libre/ocupado; comprueba que las salas se agrupen en PC, simuladores/consolas y VIP.
- Intenta superar la capacidad de un módulo; el formulario y el servidor deben rechazarlo.
- Ocupa un módulo libre y comprueba que cambia a ocupado y aparece en el historial.
- Intenta liberarlo desde otra cuenta: la acción debe rechazarse.
- Libéralo desde la cuenta responsable y comprueba que vuelve a estar disponible y que el registro conserva ambas horas.
- Consulta el historial por zona, sala, módulo y fechas.
- Accede como `admin` y comprueba los resúmenes separados por zona y las ocupaciones abiertas.
- Accede al panel «Gamemasters» como `admin`; crea y edita una cuenta, comprueba el cambio de contraseña y desactiva/reactiva una cuenta de prueba. Comprueba que la eliminación definitiva de una cuenta desactivada borra su historial y se bloquea si quedan ocupaciones abiertas.
- Accede con cualquiera de los dos roles, crea una zona, sala, tipo y módulo, y confirma que aparecen en el panel.
- Archiva una zona, sala y módulo de prueba; comprueba que aparezca la opción de eliminación definitiva y que quite también ocupaciones y puestos relacionados. Esta acción es irreversible.
- Desactiva una cuenta gamemaster de prueba y elimínala definitivamente; la operación debe bloquearse mientras tenga ocupaciones abiertas.
- Cambia la capacidad de un tipo y comprueba que se actualiza el número de puestos; intenta dejar una ocupación abierta por encima del nuevo límite.
- Archiva y restaura un módulo con historial; verifica que el historial conserva los nombres y la capacidad originales.

## Estructura principal

- `index/`: páginas de acceso, salas, administración, historial y gestión de la estructura del local.
- `index/header.php`: cabecera común reutilizada por las páginas autenticadas.
- `index/filtros/`: filtros independientes por sección. `filtro_zonas.php` y `filtro_salas.php` se comparten entre la gestión del local y el resumen administrativo; `filtro_tipos_modulo.php` y `filtro_modulos_local.php` filtran sus secciones.
- `index/filtros/filtro_gamemasters.php`: búsqueda y filtro de estado para las cuentas del panel admin.
- `procesos/`: controladores POST de sesión, ocupaciones, estructura del local y cuentas gamemaster. Las operaciones de estado de zonas, salas, módulos y tipos de módulo tienen controladores separados y comparten la validación de sesión, rol y cuenta activa de `config/validar_solicitud_estructura_local.php`.
- `consultas/`: una función de lectura por fichero, con nombres descriptivos. Incluye consultas de estructura local, paneles, gamemasters e historial; `obtener_zonas_del_local.php` y `obtener_resumen_salas_activas.php` se reutilizan entre layouts.
- `config/`: conexión PDO, arranque de sesión y funciones comunes de autenticación.
- `estilos/css/estilos.css`: hoja de estilos común.
- `estilos/js/app.js`: filtros dinámicos, validación accesible y confirmaciones.
- `logo/logo.png`: logotipo utilizado en las páginas y como icono del sitio.
- `sql/db_pj5_gamingCenter.sql`: estructura completa y datos de prueba.
- `sql/migracion.sql`: migraciones para los dos esquemas existentes admitidos; ejecuta solo la sección correspondiente.

## Equipo, roles y seguimiento

Completar con información real del grupo antes de la entrega; no se han inventado integrantes ni tareas.

| Integrante | Rol | Tareas concretas realizadas |
|---|---|---|
| [Nombre y apellidos] | Coordinación | [Completar] |
| [Nombre y apellidos] | Impulso | [Completar] |
| [Nombre y apellidos] | Finalización | [Completar] |
| [Nombre y apellidos] | [Rol del equipo] | [Completar] |
| [Nombre y apellidos] | [Rol del equipo] | [Completar] |

- Repositorio GitHub compartido con el profesorado: [Añadir enlace].
- Planificación Gantt: [Añadir enlace o ubicación].
- Seguimiento de reuniones diarias: [Añadir ubicación de las actas].

## Explicación del código

Antes de entregar, cada integrante debe revisar, probar y explicar con sus propias palabras las rutas, consultas, validaciones y decisiones que haya trabajado, tal como pide la ficha de actividad.

