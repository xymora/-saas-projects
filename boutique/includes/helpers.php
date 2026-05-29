<?php
function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonError(string $message, int $status = 400): void {
    jsonResponse(['success' => false, 'error' => $message], $status);
}

function jsonSuccess(array $data = [], string $message = 'OK'): void {
    jsonResponse(array_merge(['success' => true, 'message' => $message], $data));
}

function sanitize(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

function requireApiLogin(): void {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../includes/auth.php';
    if (!isLoggedIn()) {
        jsonError('No autenticado', 401);
    }
}

function requireApiRole(array $roles): void {
    requireApiLogin();
    if (!in_array($_SESSION['user_rol'] ?? '', $roles, true)) {
        jsonError('Acceso denegado', 403);
    }
}

function generateFolio(): string {
    return 'L' . date('ymd') . strtoupper(substr(uniqid(), -5));
}

function formatMoney(float $amount): string {
    return '$' . number_format($amount, 2);
}

function generateSKU(string $marca, string $modelo, string $color, string $talla): string {
    $parts = [$marca, $modelo, $color, $talla];
    $slug  = implode('-', array_map(fn($p) => strtoupper(preg_replace('/[^A-Z0-9]/i', '', $p)), $parts));
    return substr($slug, 0, 38);
}

function getConfig(string $clave): string {
    static $cache = [];
    if (!isset($cache[$clave])) {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = ?");
            $stmt->execute([$clave]);
            $row  = $stmt->fetch();
            $cache[$clave] = $row ? (string)$row['valor'] : '';
        } catch (Exception) {
            $cache[$clave] = '';
        }
    }
    return $cache[$clave];
}

function sendTicketEmail(string $to, string $subject, string $htmlBody): bool {
    $from     = MAIL_FROM;
    $fromName = MAIL_FROM_NAME;
    $boundary = md5(uniqid());

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "X-Mailer: BOUTIQUE-POS/1.0\r\n";

    return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $htmlBody, $headers);
}

function clipComision(float $total): float {
    $fee = CLIP_FEE_PCT / 100;
    $iva = CLIP_IVA_PCT / 100;
    return round($total * $fee * (1 + $iva), 2);
}
