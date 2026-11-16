# TaskShield

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
config.example.php        ← plantilla de config.php sin credenciales
conexion.php              ← conexión a la base de datos con PDO
index.php                 ← panel principal
nueva_incidencia.php      ← alta de incidencias
editar_incidencia.php     ← cambio de estado
eliminar_incidencia.php   ← borrado controlado
asignar_incidencia.php    ← asignación a un técnico (transacción)
css/styles.css            ← hoja de estilos de todas las páginas
doc/                      ← documentación técnica
```

## Instalación

Estos son los pasos que seguimos para poner en marcha el proyecto en otro ordenador con Windows y XAMPP. Usamos la ruta por defecto, `C:\xampp`; si XAMPP está instalado en otra carpeta, hay que cambiarla en los comandos.

### 1. Arrancar los servicios

Abrimos el panel de control de XAMPP y arrancamos **Apache** y **MySQL**.

### 2. Copiar el proyecto en `htdocs`

Apache solo sirve lo que está dentro de `C:\xampp\htdocs`. Clonamos el repositorio ahí desde PowerShell:

```powershell
git clone https://github.com/jramonnv0786-prog/Taskshield.git C:\xampp\htdocs\taskshield
```

También podemos descargar el ZIP desde GitHub y descomprimirlo en `C:\xampp\htdocs\taskshield`.

### 3. Crear la base de datos

El script [`doc/schema.sql`](doc/schema.sql) crea la base de datos `taskshield`, sus tablas y los datos de prueba. Podemos importarlo de dos formas:

- **Con phpMyAdmin:** abrimos `http://localhost/phpmyadmin`, vamos a la pestaña **Importar**, elegimos `doc/schema.sql`, comprobamos que el juego de caracteres es **utf-8** y pulsamos **Importar**.
- **Desde la terminal:** dentro de la carpeta del proyecto ejecutamos:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < doc\schema.sql"
```

Usamos `cmd /c` porque PowerShell no admite el operador `<` para redirigir un archivo. La opción `--default-character-set=utf8mb4` evita que se estropeen las tildes y la ñ.

> El script empieza borrando las tablas, así que podemos volver a importarlo cuando queramos recuperar los datos de prueba.

### 4. Crear `config.php`

Las credenciales no están en el repositorio. Copiamos la plantilla:

```powershell
Copy-Item config.example.php config.php
```

Después editamos `config.php` y ponemos nuestro usuario y contraseña de MariaDB. En una instalación de XAMPP sin cambios, el usuario es `root` y la contraseña está vacía (`''`).

### 5. Registro de errores

La aplicación guarda los errores con `error_log()` en el log de PHP, que en XAMPP es `C:\xampp\php\logs\php_error_log`. Si la carpeta `logs` no existe, la creamos; si no, PHP no puede escribir el log.

### 6. Abrir la aplicación

Entramos en `http://localhost/taskshield/`. Deben aparecer 10 incidencias activas. Para comprobar que todo funciona, seguimos los [casos de prueba](doc/casos_de_prueba.md).

## Documentación

La documentación técnica (script de la base de datos, arquitectura y casos de prueba) está en la carpeta [`doc/`](doc/).

## Autor

Jose Ramon Neira Vega
