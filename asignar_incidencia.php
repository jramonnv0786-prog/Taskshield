<?php
// TaskShield - Asignar incidencia
// Asigna una incidencia a un técnico con una transacción: o se hacen los dos cambios, o ninguno.

// Tipado estricto
declare(strict_types=1);

// Conexión a la base de datos
require_once __DIR__ . '/conexion.php';

// Recuperar y validar el id de la URL
$id_incidencia = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_incidencia) {
    die("ID de incidencia no válido.");
}

// Comprobar que la incidencia existe
try {
    $consulta_incidencia = $pdo->prepare('SELECT * FROM incidencias WHERE id = :id');
    $consulta_incidencia->execute(['id' => $id_incidencia]);
    $incidencia = $consulta_incidencia->fetch(PDO::FETCH_ASSOC);
    if (!$incidencia) {
        die("La incidencia no existe.");
    }
} catch (PDOException $e) {
    error_log("Error al comprobar la incidencia: " . $e->getMessage());
    die("Error al comprobar la incidencia. Por favor, inténtalo más tarde.");
}

// Cargar la lista de técnicos para el desplegable
try {
    $consulta_tecnicos = $pdo->prepare('SELECT id, nombre FROM tecnicos');
    $consulta_tecnicos->execute();
    $tecnicos = $consulta_tecnicos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al cargar los técnicos: " . $e->getMessage());
    die("Error al cargar los técnicos. Por favor, inténtalo más tarde.");
}

// Procesar la asignación (solo si se ha enviado por POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar el técnico elegido
    $tecnico_id = filter_input(INPUT_POST, 'tecnico_id', FILTER_VALIDATE_INT);
    if (!$tecnico_id) {
        die("Técnico no válido.");
    }
    // Transacción:
    // 1) UPDATE de la incidencia: estado 'En Proceso' y técnico asignado
    try {
        $pdo->beginTransaction();
        $actualizacion_incidencia = $pdo->prepare(
            'UPDATE incidencias SET estado = :estado, tecnico_id = :tecnico_id WHERE id = :id'
        );
        $actualizacion_incidencia->execute([
            'estado' => 'En Proceso',
            'tecnico_id' => $tecnico_id,
            'id' => $id_incidencia
        ]);
        if ($actualizacion_incidencia->rowCount() === 0) {
            throw new Exception("No se ha podido actualizar la incidencia " . $id_incidencia);
        }

        // 2) UPDATE del técnico: tareas_activas + 1
        $actualizacion_tecnico = $pdo->prepare(
            'UPDATE tecnicos SET tareas_activas = tareas_activas + 1 WHERE id = :tecnico_id'
        );
        $actualizacion_tecnico->execute([
            'tecnico_id' => $tecnico_id
        ]);
        if ($actualizacion_tecnico->rowCount() === 0) {
            throw new Exception("No se ha podido actualizar el técnico " . $tecnico_id);
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error al asignar la incidencia: " . $e->getMessage());
        die("No se ha podido asignar incidencia. Inténtelo más tarde.");
    }

    // Redirigir al panel con el id de la incidencia asignada
    header('Location: index.php?asignada=' . $id_incidencia);
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskShield - Asignar incidencia</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <header>
        <h1>Asignar incidencia</h1>
    </header>
    <main>
        <!-- Datos de la incidencia -->
        <p>
            Incidencia nº <?= htmlspecialchars((string) $incidencia['id'], ENT_QUOTES, 'UTF-8') ?>:
            <?= htmlspecialchars($incidencia['titulo'], ENT_QUOTES, 'UTF-8', false) ?>
        </p>
        <p>
            Estado actual: <?= htmlspecialchars($incidencia['estado'], ENT_QUOTES, 'UTF-8') ?>
        </p>

        <!-- Formulario de asignación -->
        <form method="POST">
            <label for="tecnico_id">Técnico:</label>
            <select name="tecnico_id" id="tecnico_id" required>
                <?php foreach ($tecnicos as $tecnico): ?>
                    <option value="<?= htmlspecialchars((string) $tecnico['id'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($tecnico['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Asignar</button>
        </form>

        <a href="index.php">Volver al panel</a>
    </main>
</body>

</html>