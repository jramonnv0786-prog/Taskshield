<?php
// TaskShield - Conexión a la base de datos con PDO
// Las páginas que usan la base de datos cargan este archivo.

// Tipado estricto
declare(strict_types=1);

// Cargar el archivo de configuración.
require_once __DIR__ . '/config.php';

// Opciones de PDO.
$opciones = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// Crear la conexión y gestionar el error.
try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, $opciones);
} catch (PDOException $e) {
    // Registrar el error en el log del servidor y mostrar un mensaje genérico al usuario.
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    die("Error de conexión. Por favor, inténtalo más tarde.");
}
