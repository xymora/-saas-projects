<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

requireApiRole(['admin', 'dueno']);

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT id, nombre, email, rol, activo, created_at FROM usuarios ORDER BY rol, nombre");
    jsonSuccess(['usuarios' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $id       = (int)($body['id'] ?? 0);
    $nombre   = sanitize($body['nombre'] ?? '');
    $email    = filter_var(trim($body['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $rol      = $body['rol'] ?? 'empleado';
    $activo   = isset($body['activo']) ? (int)(bool)$body['activo'] : 1;
    $password = $body['password'] ?? '';

    if (!$nombre || !$email) jsonError('Nombre y email son obligatorios');
    if (!in_array($rol, ['admin', 'dueno', 'empleado'], true)) jsonError('Rol inválido');

    // Solo admin puede crear/editar admins
    if ($rol === 'admin' && !isAdmin()) jsonError('Solo el administrador puede gestionar admins', 403);

    if ($id > 0) {
        if ($password) {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE usuarios SET nombre=?,email=?,rol=?,activo=?,password_hash=? WHERE id=?")
                ->execute([$nombre,$email,$rol,$activo,$hash,$id]);
        } else {
            $pdo->prepare("UPDATE usuarios SET nombre=?,email=?,rol=?,activo=? WHERE id=?")
                ->execute([$nombre,$email,$rol,$activo,$id]);
        }
        jsonSuccess(['id' => $id], 'Usuario actualizado');
    } else {
        if (!$password || strlen($password) < 8) jsonError('Contraseña mínima 8 caracteres');
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre,email,password_hash,rol,activo) VALUES (?,?,?,?,?)");
        $stmt->execute([$nombre,$email,$hash,$rol,$activo]);
        jsonSuccess(['id' => $pdo->lastInsertId()], 'Usuario creado');
    }
}

if ($method === 'DELETE') {
    requireApiRole(['admin']);
    $id = (int)($_GET['id'] ?? 0);
    if (!$id || $id === (int)$_SESSION['user_id']) jsonError('No puedes eliminar tu propio usuario');
    $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?")->execute([$id]);
    jsonSuccess([], 'Usuario desactivado');
}

jsonError('Método no permitido', 405);
