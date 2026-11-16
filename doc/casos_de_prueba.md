# Casos de prueba

Hemos preparado estos casos para comprobar que la aplicación valida los datos, controla los errores y protege la integridad de la base de datos. Todos parten de la base de datos recién creada con [`schema.sql`](schema.sql), así que antes de cada prueba volvemos a importar el script para tener los mismos datos de partida.

Datos de prueba que usamos:

| Técnico | id | tareas_activas |
|---|---|---|
| Ana Ruiz | 1 | 0 |
| Luis Gómez | 2 | 2 |
| Marta Díaz | 3 | 5 (el máximo que permite el `CHECK`) |

Las incidencias 1, 2 y 3 están en estado Pendiente y sin técnico; la 11 está Resuelta y no aparece en el panel.

## Casos obligatorios del enunciado

| Nº | Caso | Pasos | Resultado esperado | Resultado obtenido |
|---|---|---|---|---|
| 1 | Email inválido en el alta | En `nueva_incidencia.php` rellenamos título, descripción y prioridad, y escribimos `cliente@` como email. Como el navegador ya bloquea ese valor por el `type="email"`, antes quitamos ese atributo con las herramientas de desarrollo (F12): así comprobamos que el servidor también valida. | `filter_input` con `FILTER_VALIDATE_EMAIL` devuelve `false`, aparece el mensaje "El email del cliente no es válido." y no se ejecuta el INSERT. | Se muestra el error y el formulario. El panel sigue con 10 incidencias: no se ha guardado nada. ✔ |
| 2 | Editar con un ID inexistente | Abrimos `editar_incidencia.php?id=999`. | El SELECT no devuelve ninguna fila, `fetch()` devuelve `false` y la página responde con código 404 y el mensaje "Incidencia no encontrada." | Código 404 y mensaje "Incidencia no encontrada.". No aparece ningún error de PHP ni ningún dato interno. ✔ |
| 3 | Fallo de la segunda parte de la transacción | Desde el panel pulsamos "Asignar" en la incidencia 2 y elegimos a Marta Díaz (ya tiene 5 tareas). | El primer UPDATE (incidencia a "En Proceso" con `tecnico_id = 3`) se ejecuta, pero el segundo (`tareas_activas + 1`) viola el `CHECK (tareas_activas <= 5)` y lanza una excepción. El `catch` hace `rollBack()`, guarda el error en el log y muestra un mensaje genérico. | Mensaje "No se ha podido asignar incidencia. Inténtelo más tarde.". En el panel la incidencia 2 sigue **Pendiente** y **sin técnico**, y Marta sigue con 5 tareas: el primer UPDATE se ha deshecho. En el log aparece el motivo (violación del `CHECK chk_tecnicos_tareas`). ✔ |

## Casos adicionales

| Nº | Caso | Pasos | Resultado esperado | Resultado obtenido |
|---|---|---|---|---|
| 4 | ID que no es un número | Abrimos `editar_incidencia.php?id=hola` (lo mismo con `eliminar_incidencia.php`). | `FILTER_VALIDATE_INT` con `min_range` devuelve `false` y la página responde con código 400 y "ID de incidencia inválido.". La consulta ni siquiera llega a ejecutarse. | Código 400 y mensaje de ID inválido. ✔ |
| 5 | Asignación correcta | Asignamos la incidencia 1 a Ana Ruiz. | Las dos consultas funcionan, se hace `commit()` y volvemos al panel con el aviso "Incidencia asignada con éxito". | La incidencia 1 pasa a "En Proceso" con Ana Ruiz y Ana pasa de 0 a 1 tarea. ✔ |
| 6 | Técnico inexistente en la asignación | En `asignar_incidencia.php?id=1` cambiamos con las herramientas de desarrollo el `value` de una opción del desplegable por `99` y enviamos. | Esta vez falla la **primera** consulta: la clave foránea `fk_incidencias_tecnico` no permite un técnico que no existe. Salta la excepción y se hace `rollBack()`. | Mensaje genérico de error y la incidencia sigue igual. ✔ |
| 7 | Borrado de una incidencia que ya no existe | Abrimos la confirmación de borrado de la misma incidencia en dos pestañas. Eliminamos en la primera y después pulsamos "Eliminar" en la segunda. | En la segunda pestaña el DELETE no afecta a ninguna fila, `rowCount()` devuelve 0 y se muestra "La incidencia ya no existe o ya fue eliminada.". | Se muestra el mensaje y queda registrado en el log. ✔ |
| 8 | Intento de inyección SQL | En el alta escribimos como título `'); DROP TABLE incidencias; --`. | Con sentencias preparadas el texto viaja como dato y no como código SQL, así que nunca llega a ejecutarse. Al mostrarlo, `htmlspecialchars` evita que el navegador lo interprete. | La incidencia se crea con ese título y la tabla sigue existiendo. ✔ |
| 9 | Intento de inyección de HTML/JavaScript | En el alta escribimos como título `<script>alert(1)</script>`. | `FILTER_SANITIZE_SPECIAL_CHARS` convierte `<` y `>` en entidades, así que el navegador lo muestra como texto. | Se ve el texto literal y no se ejecuta ningún script. ✔ |
