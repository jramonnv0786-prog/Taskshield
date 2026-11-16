<?php
// TaskShield - Plantilla de configuración de la base de datos
// Cómo usarla: copiamos este archivo con el nombre config.php y rellenamos nuestros datos.
// config.php está en .gitignore: las credenciales reales nunca se suben al repositorio.

// 1. Datos de acceso al servidor de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'taskshield');
define('DB_USER', 'usuario');
define('DB_PASS', 'contraseña');
define('DB_CHARSET', 'utf8mb4');
// 2. DSN (Data Source Name) construido con los datos anteriores
define('DB_DSN', 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET);
