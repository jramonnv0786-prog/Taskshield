<?php
// TaskShield - Editar incidencia
// Cambia el estado de una incidencia a partir de su id (recibido por GET).

// Tipado estricto
declare(strict_types=1);

// Conexión a la base de datos
require_once __DIR__ . '/conexion.php';

// Recuperar y validar el id de la URL (solo enteros desde 1)
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    http_response_code(400);
    die('ID de incidencia inválido.');
}

// Comprobar que la incidencia existe
try {
    $stmt = $pdo->prepare('SELECT id, titulo, estado FROM incidencias WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $incidencia = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener la incidencia: " . $e->getMessage());
    die("Error al obtener la incidencia. Por favor, inténtalo más tarde.");
}

// Si no se encuentra la incidencia, mostrar un error 404
if (!$incidencia) {
    http_response_code(404);
    die('Incidencia no encontrada.');
}

// Procesar el formulario (solo si se ha enviado por POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar el estado elegido
    $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_SPECIAL_CHARS);
    if (!in_array($estado, ['Pendiente', 'En Proceso', 'Resuelta'], true)) {
        die('Estado inválido.');
    }

    // Actualizar la incidencia con una sentencia preparada
    try {
        $stmt = $pdo->prepare('UPDATE incidencias SET estado = :estado WHERE id = :id');
        $stmt->bindValue(':estado', $estado);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error al actualizar la incidencia: " . $e->getMessage());
        die("Error al actualizar la incidencia. Por favor, inténtalo más tarde.");
    }

    // Redirigir al panel con el id de la incidencia editada
    header('Location: index.php?editada=' . $id);
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskShield - Editar incidencia</title>
</head>

<body>
    <header>
        <h1>Editar incidencia</h1>
    </header>
    <main>
        <!-- Datos de la incidencia (el título se escapa sin doble codificación) -->
        <p>
            Incidencia nº <?= htmlspecialchars((string) $incidencia['id'], ENT_QUOTES, 'UTF-8') ?>:
            <?= htmlspecialchars($incidencia['titulo'], ENT_QUOTES, 'UTF-8', false) ?>
        </p>

        <!-- Formulario de cambio de estado (marca el estado actual) -->
        <form method="POST">
            <label for="estado">Estado:</label>
            <select name="estado" id="estado" required>
                <option value="Pendiente" <?= ($incidencia['estado'] === 'Pendiente') ? 'selected' : '' ?>>Pendiente</option>
                <option value="En Proceso" <?= ($incidencia['estado'] === 'En Proceso') ? 'selected' : '' ?>>En Proceso</option>
                <option value="Resuelta" <?= ($incidencia['estado'] === 'Resuelta') ? 'selected' : '' ?>>Resuelta</option>
            </select>
            <button type="submit">Actualizar estado</button>
        </form>

        <a href="index.php">Volver al panel</a>
    </main>
</body>

</html>
