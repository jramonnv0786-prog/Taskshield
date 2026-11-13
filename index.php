<?php
// TaskShield - Panel principal
// Muestra en una tabla todas las incidencias activas (no resueltas).

// ===================== LÓGICA =====================

// Tipado estricto
declare(strict_types=1);

// Conexión a la base de datos
require_once __DIR__ . '/conexion.php';

// Consulta de las incidencias activas
try {
    $stmt = $pdo->prepare('SELECT id, titulo, prioridad, estado, tecnico_id FROM incidencias WHERE estado <> :estado');
    $stmt->execute(['estado' => 'Resuelta']);
    $incidencias = $stmt->fetchAll(PDO::FETCH_ASSOC); // Obtener los resultados como un array asociativo
} catch (PDOException $e) {
    error_log("Error al obtener las incidencias: " . $e->getMessage());
    die("Error al obtener las incidencias. Por favor, inténtalo más tarde.");
}


// ===================== VISTA =====================
?>

<!-- Estructura de la página -->
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskShield - Panel de Incidencias</title>

</head>


<body>
    <header>
        <h1>Panel de Incidencias</h1>
    </header>
    <main>
        <table>
            <caption>Incidencias Activas</caption>
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Título</th>
                    <th scope="col">Prioridad</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Técnico</th>
                </tr>
            </thead>
            <tbody>
                <!-- Convertir a string y escapar caracteres especiales para prevenir XSS -->
                <?php foreach ($incidencias as $incidencia): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($incidencia['id']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($incidencia['titulo']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($incidencia['prioridad']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($incidencia['estado']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($incidencia['tecnico_id'] ?? 'No asignado'), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
    <footer>
    </footer>
</body>

</html>
