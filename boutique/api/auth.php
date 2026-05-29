<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── Login ────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'login') {
    $body  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $email = trim($body['email'] ?? '');
    $pass  = $body['password'] ?? '';

    if (!$email || !$pass) jsonError('Credenciales incompletas');

    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT id, nombre, email, password_hash, rol, activo FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !$user['activo'] || !password_verify($pass, $user['password_hash'])) {
        jsonError('Correo o contraseña incorrectos', 401);
    }

    $_SESSION['user_id']     = $user['id'];
    $_SESSION['user_nombre'] = $user['nombre'];
    $_SESSION['user_email']  = $user['email'];
    $_SESSION['user_rol']    = $user['rol'];

    jsonSuccess([
        'user' => [
            'id'     => $user['id'],
            'nombre' => $user['nombre'],
            'email'  => $user['email'],
            'rol'    => $user['rol'],
        ]
    ], 'Bienvenido');
}

// ── Logout ───────────────────────────────────────────────────
if ($method === 'POST' && $action === 'logout') {
    $_SESSION = [];
    session_destroy();
    jsonSuccess([], 'Sesión cerrada');
}

// ── Me ───────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'me') {
    if (!isset($_SESSION['user_id'])) jsonError('No autenticado', 401);
    jsonSuccess(['user' => [
        'id'     => $_SESSION['user_id'],
        'nombre' => $_SESSION['user_nombre'],
        'email'  => $_SESSION['user_email'],
        'rol'    => $_SESSION['user_rol'],
    ]]);
}

// ── Forgot password ──────────────────────────────────────────
if ($method === 'POST' && $action === 'forgot') {
    $body  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $email = trim($body['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Email inválido');

    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE email = ? AND activo = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_expires = ? WHERE id = ?")
            ->execute([$token, $expires, $user['id']]);

        $link    = BASE_URL . '/views/reset_password.php?token=' . $token;
        $subject = 'Recuperar contraseña — Mi Boutique';
        $html    = "
        <div style='font-family:sans-serif;max-width:480px;margin:auto;padding:32px;background:#f9f5f0;border-radius:12px'>
          <h2 style='color:#c8956c'>Mi Boutique</h2>
          <p>Hola <strong>{$user['nombre']}</strong>,</p>
          <p>Recibimos una solicitud para restablecer tu contraseña. Haz clic en el botón:</p>
          <a href='{$link}' style='display:inline-block;background:#c8956c;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold'>Restablecer contraseña</a>
          <p style='color:#888;font-size:12px;margin-top:24px'>El enlace expira en 1 hora. Si no solicitaste esto, ignora este correo.</p>
        </div>";
        sendTicketEmail($email, $subject, $html);
    }
    // Siempre responder igual (evita user enumeration)
    jsonSuccess([], 'Si el correo existe, recibirás instrucciones.');
}

// ── Reset password ───────────────────────────────────────────
if ($method === 'POST' && $action === 'reset') {
    $body     = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $token    = trim($body['token'] ?? '');
    $password = $body['password'] ?? '';

    if (strlen($token) !== 64) jsonError('Token inválido');
    if (strlen($password) < 8)  jsonError('La contraseña debe tener al menos 8 caracteres');

    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND reset_expires > NOW() AND activo = 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) jsonError('Token inválido o expirado');

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $pdo->prepare("UPDATE usuarios SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?")
        ->execute([$hash, $user['id']]);

    jsonSuccess([], 'Contraseña actualizada');
}

jsonError('Acción no válida', 404);
