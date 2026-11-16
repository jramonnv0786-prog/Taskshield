<?php
// TaskShield - Panel principal
// Muestra en una tabla todas las incidencias activas (no resueltas).

// Tipado estricto
declare(strict_types=1);

// Conexión a la base de datos
require_once __DIR__ . '/conexion.php';

// Consulta de las incidencias activas
try {
    $consulta_incidencias = $pdo->prepare(
        'SELECT incidencias.id, incidencias.titulo, incidencias.prioridad, incidencias.estado,
                tecnicos.nombre AS tecnico
         FROM incidencias
         LEFT JOIN tecnicos ON incidencias.tecnico_id = tecnicos.id
         WHERE incidencias.estado <> :estado'
    );
    $consulta_incidencias->execute(['estado' => 'Resuelta']);
    $incidencias = $consulta_incidencias->fetchAll(PDO::FETCH_ASSOC); // Obtener los resultados como un array asociativo
} catch (PDOException $e) {
    error_log("Error al obtener las incidencias: " . $e->getMessage());
    die("Error al obtener las incidencias. Por favor, inténtalo más tarde.");
}

// Avisos de alta, edición, eliminación y asignación (ids recibidos por la URL)
$creada = filter_input(INPUT_GET, 'creada', FILTER_VALIDATE_INT);
$editada = filter_input(INPUT_GET, 'editada', FILTER_VALIDATE_INT);
$eliminada = filter_input(INPUT_GET, 'eliminada', FILTER_VALIDATE_INT);
$asignada = filter_input(INPUT_GET, 'asignada', FILTER_VALIDATE_INT);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskShield - Panel de Incidencias</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <header>
        <h1>Panel de Incidencias</h1>
    </header>
    <main>
        <!-- Mensajes de éxito -->
        <?php if ($creada): ?>
            <p class="aviso">Incidencia creada con éxito. ID: <?= htmlspecialchars((string) $creada, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($editada): ?>
            <p class="aviso">Incidencia actualizada con éxito. ID: <?= htmlspecialchars((string) $editada, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($eliminada): ?>
            <p class="aviso">Incidencia eliminada con éxito. ID: <?= htmlspecialchars((string) $eliminada, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($asignada): ?>
            <p class="aviso">Incidencia asignada con éxito. ID: <?= htmlspecialchars((string) $asignada, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <a href="nueva_incidencia.php">Crear nueva incidencia</a>
        <table>
            <!-- caption: título de la tabla (accesibilidad) -->
            <caption>Incidencias Activas</caption>
            <thead>
                <tr>
                    <!-- scope="col": cada th encabeza una columna (accesibilidad) -->
                    <th scope="col">ID</th>
                    <th scope="col">Título</th>
                    <th scope="col">Prioridad</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Técnico</th>
                    <th scope="col">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- Convertir a string y escapar caracteres especiales para prevenir XSS -->
                <?php foreach ($incidencias as $incidencia): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($incidencia['id']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($incidencia['titulo']), ENT_QUOTES, 'UTF-8', false) ?></td>
                        <td><?= htmlspecialchars((string) ($incidencia['prioridad']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($incidencia['estado']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($incidencia['tecnico'] ?? 'No asignado'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="editar_incidencia.php?id=<?= htmlspecialchars((string) $incidencia['id'], ENT_QUOTES, 'UTF-8') ?>">Editar</a>
                            <a href="eliminar_incidencia.php?id=<?= htmlspecialchars((string) $incidencia['id'], ENT_QUOTES, 'UTF-8') ?>">Eliminar</a>
                            <a href="asignar_incidencia.php?id=<?= htmlspecialchars((string) $incidencia['id'], ENT_QUOTES, 'UTF-8') ?>">Asignar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
    <footer>
    </footer>
</body>

</html>