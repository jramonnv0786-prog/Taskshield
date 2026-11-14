<?php
// TaskShield - Alta de incidencias
// Formulario para registrar una incidencia nueva y guardarla en la base de datos.

// Tipado estricto
declare(strict_types=1);

// Conexión a la base de datos
require_once __DIR__ . '/conexion.php';

// Valores iniciales del formulario y lista de errores
$errores = [];

// Procesar el formulario (solo si se ha enviado por POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recuperar y validar los datos del formulario
    $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_SPECIAL_CHARS);
    $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_SPECIAL_CHARS);
    $prioridad = filter_input(INPUT_POST, 'prioridad', FILTER_SANITIZE_SPECIAL_CHARS);
    $email_cliente = filter_input(INPUT_POST, 'email_cliente', FILTER_VALIDATE_EMAIL);

    // Validación de los campos
    if (!$titulo) {
        $errores[] = "El título es obligatorio.";
    }
    if (!$descripcion) {
        $errores[] = "La descripción es obligatoria.";
    }
    if (!in_array($prioridad, ['Baja', 'Media', 'Alta'], true)) {
        $errores[] = "La prioridad seleccionada no es válida.";
    }
    if (!$email_cliente) {
        $errores[] = "El email del cliente no es válido.";
    }

    // Si no hay errores, guardar la incidencia en la base de datos.
    if (empty($errores)) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO incidencias (titulo, descripcion, prioridad, email_cliente)
                 VALUES (:titulo, :descripcion, :prioridad, :email_cliente)'
            );
            $stmt->execute([
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'prioridad' => $prioridad,
                'email_cliente' => $email_cliente
            ]);

            // Redirigir al panel con el id de la incidencia creada
            header('Location: index.php?creada=' . $pdo->lastInsertId());
            exit;
        } catch (PDOException $e) {
            error_log("Error al guardar la incidencia: " . $e->getMessage());
            die("Error al guardar la incidencia. Por favor, inténtalo más tarde.");
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskShield - Nueva incidencia</title>
</head>

<body>
    <header>
        <h1>Nueva incidencia</h1>
    </header>
    <main>
        <!-- Errores de validación -->
        <?php if (!empty($errores)): ?>
            <ul>
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <!-- Formulario de alta -->
        <form action="" method="POST">
            <div>
                <label for="titulo">Título:</label>
                <input type="text" id="titulo" name="titulo" maxlength="150" required>
            </div>

            <div>
                <label for="descripcion">Descripción:</label>
                <textarea id="descripcion" name="descripcion" required></textarea>
            </div>

            <div>
                <label for="prioridad">Prioridad:</label>
                <select id="prioridad" name="prioridad" required>
                    <option value="Baja">Baja</option>
                    <option value="Media" selected>Media</option>
                    <option value="Alta">Alta</option>
                </select>
            </div>

            <div>
                <label for="email_cliente">Email del cliente:</label>
                <input type="email" id="email_cliente" name="email_cliente" maxlength="150" required>
            </div>

            <button type="submit">Crear incidencia</button>
        </form>
    </main>
</body>

</html>