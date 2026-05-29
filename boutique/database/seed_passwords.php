<?php
/**
 * Ejecutar UNA SOLA VEZ para generar los hashes bcrypt correctos.
 * php seed_passwords.php
 */
require_once __DIR__ . '/../config/db.php';

$users = [
    ['email' => 'admin@miboutique.com',     'password' => 'Admin123!'],
    ['email' => 'dueno1@miboutique.com',    'password' => 'Dueno123!'],
    ['email' => 'dueno2@miboutique.com',    'password' => 'Duena123!'],
    ['email' => 'empleado1@miboutique.com', 'password' => 'Emp1Pass!'],
    ['email' => 'empleado2@miboutique.com', 'password' => 'Emp2Pass!'],
];

$pdo = getDB();
$stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE email = ?");

foreach ($users as $u) {
    $hash = password_hash($u['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt->execute([$hash, $u['email']]);
    echo "✓ {$u['email']} actualizado\n";
}
echo "\nListo. Elimina este archivo después de ejecutarlo.\n";
