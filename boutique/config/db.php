<?php
// ── Credenciales de base de datos ────────────────────────────
// En hosting compartido (cPanel):
//   DB_NAME → cpanel_usuario_nombrebd  (el que creaste en "MySQL Databases")
//   DB_USER → cpanel_usuario_mysqluser (el usuario MySQL que creaste)
//   DB_PASS → la contraseña de ese usuario MySQL
//   DB_HOST → generalmente 'localhost'
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'covenant_B0ut1qu3');   // <-- CAMBIA ESTO
define('DB_USER',    'covenant_B0ut1qu31aula');              // <-- CAMBIA ESTO
define('DB_PASS',    'B0#ut1qu3$1aula');                  // <-- CAMBIA ESTO
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT
             . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}