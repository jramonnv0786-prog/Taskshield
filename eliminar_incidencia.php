<?php
// TaskShield - Eliminar incidencia
// Pide confirmación y borra una incidencia a partir de su id (recibido por GET).

// Tipado estricto
declare(strict_types=1);

// Conexión a la base de datos
require_once __DIR__ . '/conexion.php';

// Recuperar y validar el id de la URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id <= 0) {
    http_response_code(400);
    die("ID de incidencia inválido.");
}

// Comprobar que la incidencia existe.
try {
    $consulta_incidencia = $pdo->prepare('SELECT * FROM incidencias WHERE id = :id');
    $consulta_incidencia->bindValue(':id', $id, PDO::PARAM_INT);
    $consulta_incidencia->execute();
    // Obtener la incidencia como un array asociativo.
    $incidencia = $consulta_incidencia->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener la incidencia: " . $e->getMessage());
    die("Error al obtener la incidencia. Por favor, inténtalo más tarde.");
}

// Si no se encuentra la incidencia, mostrar un error 404
if (!$incidencia) {
    http_response_code(404);
    die('Incidencia no encontrada.');
}

// Procesar la confirmación (solo si se ha enviado por POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Borrar la incidencia con una sentencia preparada
    try {
        $borrado = $pdo->prepare('DELETE FROM incidencias WHERE id = :id');
        $borrado->bindValue(':id', $id, PDO::PARAM_INT);
        $borrado->execute();
    } catch (PDOException $e) {
        error_log("Error al eliminar la incidencia: " . $e->getMessage());
        die("Error al eliminar la incidencia. Por favor, inténtalo más tarde.");
    }

    // Verificar con rowCount() que se ha borrado
    if ($borrado->rowCount() > 0) {
        // Redirigir al panel con un mensaje de éxito
        header('Location: index.php?eliminada=' . $id);
        exit;
    } else {
        error_log("No se ha eliminado la incidencia {$id}: ya no existía.");
        die("La incidencia ya no existe o ya fue eliminada.");
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskShield - Eliminar incidencia</title>
</head>

<body>
    <header>
        <h1>Eliminar incidencia</h1>
    </header>
    <main>
        <!-- Mensaje de confirmación -->
        <p>¿Estás seguro de que quieres eliminar la incidencia nº
            <strong><?= htmlspecialchars((string) $incidencia['id'], ENT_QUOTES, 'UTF-8') ?></strong>:
            <strong><?= htmlspecialchars((string) $incidencia['titulo'], ENT_QUOTES, 'UTF-8', false) ?></strong>
        </p>

        <!-- Formulario de confirmación -->
        <form method="POST">
            <input type="hidden" name="id" value="<?= htmlspecialchars((string) $incidencia['id'], ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Eliminar</button>
        </form>
        <a href="index.php">Cancelar y volver al panel</a>
    </main>
</body>

</html>