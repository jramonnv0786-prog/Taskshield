ñ# TaskShield

Intranet para el departamento de soporte informático de una empresa. Permite registrar averías o tareas, asignarlas a técnicos, actualizarlas y eliminarlas, garantizando la seguridad frente a inyecciones SQL y la integridad de los datos.

Miniproyecto del módulo **Desarrollo Web en Entorno Servidor** (2º DAW, ILERNA).

## Funcionalidades

- Panel principal con todas las incidencias activas.
- Alta de incidencias con validación y saneamiento de los datos del formulario.
- Edición del estado de una incidencia (Pendiente / En Proceso / Resuelta).
- Eliminación controlada de incidencias, con confirmación.
- Asignación de una incidencia a un técnico mediante una transacción.

## Tecnologías y requisitos

- XAMPP (Apache + MariaDB + PHP 8).
- PHP con la extensión PDO (`pdo_mysql`) activada.
- Navegador web.

## Estructura del proyecto

```
config.php                ← credenciales (no se incluye en el repositorio)
conexion.php              ← conexión a la base de datos con PDO
index.php                 ← panel principal
nueva_incidencia.php      ← alta de incidencias
editar_incidencia.php     ← cambio de estado
eliminar_incidencia.php   ← borrado controlado
asignar_incidencia.php    ← asignación a un técnico (transacción)
doc/                      ← documentación técnica
```

## Instalación

> Pendiente: se completará al final del proyecto con el paso a paso para instalar la base de datos y poner en marcha la aplicación en otro ordenador.

## Documentación

La documentación técnica (script de la base de datos, arquitectura y casos de prueba) está en la carpeta [`doc/`](doc/).

## Autor

Jose Ramon Neira Vega
