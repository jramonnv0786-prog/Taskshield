# TaskShield - Documentación técnica

En esta carpeta recogemos la documentación técnica del proyecto:

- [`schema.sql`](schema.sql): script que crea la base de datos, las tablas y los datos de prueba.
- [`casos_de_prueba.md`](casos_de_prueba.md): tabla de casos de prueba con el resultado esperado y el obtenido.
- Este documento: arquitectura, funcionamiento de cada archivo, medidas de seguridad, lo que hemos usado fuera de los apuntes, limitaciones conocidas y bibliografía.

Los pasos de instalación están en el [README principal](../README.md#instalación).

## 1. Arquitectura

Hemos construido una aplicación web con arquitectura **cliente-servidor** de tres capas:

| Capa | Tecnología | Qué hace |
|---|---|---|
| Cliente | Navegador | Muestra el HTML y envía los formularios (GET para consultar, POST para modificar). |
| Servidor web | Apache + PHP 8 | Recibe la petición, ejecuta el archivo PHP correspondiente y devuelve HTML. |
| Datos | MariaDB (motor InnoDB) | Guarda técnicos e incidencias. PHP se comunica con ella mediante PDO. |

Usamos InnoDB porque es el motor que admite transacciones y claves foráneas. Con MyISAM, `rollBack()` no desharía nada.

### Archivos

| Archivo | Función |
|---|---|
| `config.php` | Define las constantes de conexión (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`, `DB_DSN`). No se sube al repositorio. |
| `config.example.php` | Plantilla de `config.php` sin credenciales reales, para poder instalar el proyecto en otro equipo. |
| `conexion.php` | Carga `config.php` y crea el objeto `$pdo` con las opciones de seguridad. Lo incluyen todas las páginas. |
| `index.php` | Panel principal: tabla con las incidencias activas y avisos tras crear, editar, eliminar o asignar. |
| `nueva_incidencia.php` | Formulario de alta con validación y saneamiento de datos. |
| `editar_incidencia.php` | Cambio de estado (Pendiente / En Proceso / Resuelta). |
| `eliminar_incidencia.php` | Borrado con página de confirmación y comprobación con `rowCount()`. |
| `asignar_incidencia.php` | Asignación de una incidencia a un técnico mediante una transacción. |

### Separación entre lógica y vista

En cada página hemos colocado primero el bloque PHP con la lógica (recoger datos, validar, consultar la base de datos y decidir si redirigir) y debajo el HTML. En la vista solo imprimimos variables ya preparadas con `<?= ?>` y recorremos los datos con la sintaxis alternativa `foreach: ... endforeach;`, para no mezclar la lógica con el HTML.

### Base de datos

| Tabla | Campos principales | Restricciones |
|---|---|---|
| `tecnicos` | `id`, `nombre`, `email`, `tareas_activas` | `email` único (`uq_tecnicos_email`); `CHECK (tareas_activas <= 5)` (`chk_tecnicos_tareas`). |
| `incidencias` | `id`, `titulo`, `descripcion`, `prioridad`, `email_cliente`, `estado`, `tecnico_id`, `fecha_creacion` | `prioridad` y `estado` son `ENUM`; `tecnico_id` es clave foránea hacia `tecnicos(id)` con `ON DELETE SET NULL` (`fk_incidencias_tecnico`). |

Consideramos **incidencias activas** las que no están en estado Resuelta (`estado <> 'Resuelta'`). Por eso, al marcar una incidencia como Resuelta, desaparece del panel.

El `CHECK` de `tareas_activas` cumple dos funciones: impide que un técnico tenga más de 5 tareas y nos permite provocar a propósito el fallo de la segunda consulta de la transacción (caso de prueba 3).

## 2. Funcionamiento de cada archivo

### `conexion.php`

1. Carga las credenciales con `require_once __DIR__ . '/config.php'`.
2. Crea el objeto PDO dentro de un `try` con tres opciones:
   - `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`: cualquier error de la base de datos lanza una excepción que podemos capturar.
   - `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`: los resultados llegan como arrays asociativos.
   - `PDO::ATTR_EMULATE_PREPARES => false`: las sentencias preparadas las prepara de verdad el servidor de base de datos, no PHP.
3. Si la conexión falla, el `catch (PDOException $e)` guarda el detalle con `error_log()` y muestra un mensaje genérico. Así nunca enseñamos al usuario datos internos como el host, el usuario o la consulta.

### `index.php` (lectura)

- Consulta las incidencias activas con una sentencia preparada y un marcador con nombre (`:estado`).
- Usa un `LEFT JOIN` con `tecnicos` para mostrar el nombre del técnico. Con `LEFT JOIN` también aparecen las incidencias sin técnico, que mostramos como "No asignado".
- Recoge todas las filas con `fetchAll()` y las recorre con `foreach` en la tabla HTML.
- Lee con `filter_input(INPUT_GET, ..., FILTER_VALIDATE_INT)` los parámetros `creada`, `editada`, `eliminada` y `asignada` para mostrar el aviso de la última operación.

### `nueva_incidencia.php` (alta)

Seguimos el ciclo de un formulario: recuperar, validar, sanear, procesar y dar feedback.

1. Solo procesa si la petición es POST (`$_SERVER['REQUEST_METHOD'] === 'POST'`).
2. Recoge los campos con `filter_input`:
   - Título, descripción y prioridad con `FILTER_SANITIZE_SPECIAL_CHARS`.
   - Email del cliente con `FILTER_VALIDATE_EMAIL`.
3. Valida que no haya campos vacíos, que la prioridad sea una de las permitidas (`in_array` en modo estricto) y que el email sea válido. Los errores se guardan en `$errores` y se muestran encima del formulario.
4. Si no hay errores, hace el INSERT con una sentencia preparada. No enviamos el estado: el `DEFAULT 'Pendiente'` de la tabla lo pone solo.
5. Redirige a `index.php?creada=` con el id obtenido con `$pdo->lastInsertId()` y termina con `exit`.

### `editar_incidencia.php` (actualización)

1. Valida el `id` de la URL con `FILTER_VALIDATE_INT` y `min_range => 1`. Si no es válido, responde con código 400.
2. Comprueba que la incidencia existe con un SELECT y `bindValue(..., PDO::PARAM_INT)`. Si `fetch()` devuelve `false`, responde con código 404.
3. Al enviar el formulario, comprueba que el estado sea uno de los tres permitidos y hace el UPDATE.
4. Redirige al panel con `?editada=`.

### `eliminar_incidencia.php` (borrado)

1. Valida el `id` y comprueba que la incidencia existe (400 o 404 si no).
2. Muestra una página de confirmación con el número y el título de la incidencia. El borrado solo se hace al enviar el formulario por POST, nunca desde un enlace GET: así un enlace o un buscador no pueden borrar datos por accidente.
3. Ejecuta el DELETE y comprueba `rowCount()`. Si es mayor que 0, redirige con `?eliminada=`; si es 0, la incidencia ya no existía y lo indicamos con un mensaje.

### `asignar_incidencia.php` (transacción)

Asignar una incidencia exige dos cambios que deben ir juntos: o se hacen los dos o no se hace ninguno.

1. Valida el id de la incidencia, comprueba que existe y carga la lista de técnicos para el desplegable.
2. Al enviar el formulario, valida el id del técnico y abre la transacción con `beginTransaction()`.
3. **Primera consulta:** UPDATE de la incidencia a "En Proceso" con el `tecnico_id`.
4. **Segunda consulta:** UPDATE que suma 1 a `tareas_activas` del técnico.
5. Después de cada UPDATE comprobamos `rowCount()`. Un UPDATE que no afecta a ninguna fila **no lanza excepción por sí solo**, así que, si devuelve 0, lanzamos nosotros una `Exception`.
6. Si todo va bien, `commit()` confirma los dos cambios. Si algo falla (una excepción de PDO o una de las nuestras), el `catch (Exception $e)` hace `rollBack()`, guarda el error en el log y muestra un mensaje genérico.

Capturamos `Exception` y no solo `PDOException` porque así cogemos tanto los errores de la base de datos como las excepciones que lanzamos nosotros con `rowCount()`.

## 3. Medidas de seguridad

| Riesgo | Medida |
|---|---|
| Inyección SQL | Todas las consultas que reciben datos usan `prepare()` y `execute()` con marcadores con nombre. Los ids se enlazan con `bindValue(..., PDO::PARAM_INT)`. La emulación de sentencias preparadas está desactivada. |
| Inyección de HTML/JavaScript (XSS) | El texto del usuario se sanea con `FILTER_SANITIZE_SPECIAL_CHARS` y todo lo que imprimimos pasa por `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`. |
| Datos no válidos | `filter_input` con `FILTER_VALIDATE_EMAIL` y `FILTER_VALIDATE_INT`; listas blancas con `in_array(..., true)` para prioridad y estado; restricciones `ENUM`, `CHECK` y clave foránea en la base de datos. |
| Revelar información sensible | Los errores se guardan con `error_log()` y al usuario solo le mostramos mensajes genéricos. Nunca imprimimos `$e->getMessage()`. |
| Credenciales en el repositorio | `config.php` está en `.gitignore` desde el primer commit. En el repositorio solo está `config.example.php`. |
| Datos a medias | La asignación usa una transacción con `rollBack()`. |
| Borrados accidentales | El borrado exige confirmación y una petición POST. |

## 4. Cómo probarlo

1. Instalamos el proyecto siguiendo el [README principal](../README.md#instalación).
2. Abrimos `http://localhost/taskshield/`. Deben aparecer 10 incidencias activas (la 11 está Resuelta).
3. Seguimos la tabla de [`casos_de_prueba.md`](casos_de_prueba.md). Antes de cada caso volvemos a importar `schema.sql` para partir de los mismos datos.
4. Para ver los errores registrados abrimos el log de PHP. En XAMPP está en `C:\xampp\php\logs\php_error_log` (si la carpeta `logs` no existe, hay que crearla). Desde PowerShell:

```powershell
Get-Content C:\xampp\php\logs\php_error_log -Tail 5 -Encoding UTF8
```

## 5. Fuera de apuntes

Los apuntes de ILERNA (DWS 1.1, 2.1, 3.1, 3.2 y 4.1) son nuestra fuente principal. Lo siguiente lo hemos usado porque lo pide el enunciado o porque es una buena práctica, pero no aparece en esos temas. Indicamos de dónde lo hemos sacado.

| Elemento | Dónde lo usamos | Fuente |
|---|---|---|
| `beginTransaction()`, `commit()`, `rollBack()` | `asignar_incidencia.php` | Manual de PHP (PDO). El concepto de transacción viene de Bases de Datos de 1º. |
| `rowCount()` | `eliminar_incidencia.php`, `asignar_incidencia.php` | Manual de PHP. |
| `lastInsertId()` | `nueva_incidencia.php` | Manual de PHP. |
| `filter_input()` | Todas las páginas que reciben datos | Manual de PHP. Los apuntes (3.2) usan `filter_var()`, que valida igual pero sobre una variable ya recogida. |
| `FILTER_SANITIZE_SPECIAL_CHARS` | `nueva_incidencia.php` | Manual de PHP. Sustituye a `FILTER_SANITIZE_STRING`, obsoleto desde PHP 8.1. |
| Cuarto parámetro de `htmlspecialchars()` (`double_encode = false`) | Vistas que muestran texto escrito por el usuario | Manual de PHP. Evita la doble codificación (`&amp;amp;`), porque el texto ya llega saneado. |
| Conversión de tipo `(string)` | Vistas | Manual de PHP. Con `strict_types`, `htmlspecialchars()` exige un string y los ids llegan como enteros. |
| `http_response_code()` | `editar_incidencia.php`, `eliminar_incidencia.php` | Manual de PHP. Devuelve el código HTTP correcto (400 o 404). |
| Omitir el cierre `?>` en archivos solo PHP | `config.php`, `conexion.php` | Estándar PSR-12. Evita que un espacio o salto de línea tras el cierre se envíe al navegador. |
| SQL de definición (`CREATE TABLE`, `ENUM`, `CHECK`, `FOREIGN KEY`) y `LEFT JOIN` | `schema.sql`, `index.php` | Bases de Datos de 1º y documentación de MariaDB. |

### Diferencias intencionadas con los apuntes

- **No mostramos `$e->getMessage()` en pantalla.** El ejemplo de login del tema 3.2 lo hace, pero eso puede revelar datos internos. Seguimos el criterio del tema 4.1: `error_log()` y mensaje genérico.
- **Desactivamos `PDO::ATTR_EMULATE_PREPARES`.** El ejemplo de `config.php` + `conexion.php` del tema 4.1 no lo incluye, pero el enunciado lo exige y el primer ejemplo del propio tema sí lo pone.
- **El DSN lleva `charset=utf8mb4`.** El ejemplo del tema 3.2 no indica juego de caracteres.
- **No aplicamos `htmlspecialchars()` antes de guardar.** El tema 3.2 lo hace al sanear, pero, combinado con `FILTER_SANITIZE_SPECIAL_CHARS` y con el escapado al mostrar, codificaría el texto dos veces.

## 6. Limitaciones conocidas

- **Resolver una incidencia no resta tareas al técnico.** `editar_incidencia.php` solo cambia el estado; `tareas_activas` no baja al pasar a Resuelta. Para hacerlo bien haría falta otra transacción en la edición.
- **Se puede reasignar una incidencia que ya está En Proceso.** La asignación no comprueba el estado actual, así que una misma incidencia puede sumar tareas a más de un técnico. Se evitaría añadiendo `AND estado = 'Pendiente'` al primer UPDATE.
- **No hay autenticación.** Cualquiera que acceda a la intranet puede usar todas las operaciones. El enunciado no lo pide.
- **Las incidencias resueltas no se pueden consultar desde la aplicación.** El panel solo muestra las activas.

## 7. Bibliografía

### Apuntes de ILERNA (fuente principal)

- ILERNA. *Desarrollo Web en Entorno Servidor*. Tema 1.1: Arquitecturas web.
- ILERNA. *Desarrollo Web en Entorno Servidor*. Tema 2.1: PHP. Fundamentos.
- ILERNA. *Desarrollo Web en Entorno Servidor*. Temas 3.1 y 3.2: PHP. Fundamentos II.
- ILERNA. *Desarrollo Web en Entorno Servidor*. Tema 4.1: Acceso a datos, parte I.

### Manual de PHP (php.net)

- Transacciones y autocommit: <https://www.php.net/manual/es/pdo.transactions.php>
- `PDO::beginTransaction`: <https://www.php.net/manual/es/pdo.begintransaction.php>
- `PDO::commit`: <https://www.php.net/manual/es/pdo.commit.php>
- `PDO::rollBack`: <https://www.php.net/manual/es/pdo.rollback.php>
- `PDO::prepare`: <https://www.php.net/manual/es/pdo.prepare.php>
- `PDO::lastInsertId`: <https://www.php.net/manual/es/pdo.lastinsertid.php>
- `PDOStatement::execute`: <https://www.php.net/manual/es/pdostatement.execute.php>
- `PDOStatement::bindValue`: <https://www.php.net/manual/es/pdostatement.bindvalue.php>
- `PDOStatement::fetch`: <https://www.php.net/manual/es/pdostatement.fetch.php>
- `PDOStatement::fetchAll`: <https://www.php.net/manual/es/pdostatement.fetchall.php>
- `PDOStatement::rowCount`: <https://www.php.net/manual/es/pdostatement.rowcount.php>
- `filter_input`: <https://www.php.net/manual/es/function.filter-input.php>
- Filtros de validación: <https://www.php.net/manual/es/filter.constants.validation.php>
- Filtros de saneamiento: <https://www.php.net/manual/es/filter.constants.sanitization.php>
- `htmlspecialchars`: <https://www.php.net/manual/es/function.htmlspecialchars.php>
- `http_response_code`: <https://www.php.net/manual/es/function.http-response-code.php>
- Manipulación de tipos: <https://www.php.net/manual/es/language.types.type-juggling.php>

### Estándares

- PHP-FIG. *PSR-12: Extended Coding Style*: <https://www.php-fig.org/psr/psr-12/>

### Documentación de MariaDB

- CONSTRAINT (incluye `CHECK`): <https://mariadb.com/docs/server/reference/sql-statements/data-definition/constraint>
- Foreign Key Constraints: <https://mariadb.com/docs/server/architecture/server-constraints/foreign-key-constraints>
- ENUM: <https://mariadb.com/docs/server/reference/data-types/string-data-types/enum>
- START TRANSACTION: <https://mariadb.com/docs/server/reference/sql-statements/transactions/start-transaction>
- JOIN Syntax: <https://mariadb.com/docs/server/reference/sql-statements/data-manipulation/selecting-data/joins/join-syntax>
