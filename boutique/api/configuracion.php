<?php
ob_start(); // captura cualquier output inesperado (warnings, notices)

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Garantiza que siempre salga JSON aunque haya un error fatal
set_exception_handler(function (Throwable $e) {
    ob_clean();
    jsonError('Error interno: ' . $e->getMessage(), 500);
});
set_error_handler(function (int $errno, string $errstr) {
    ob_clean();
    jsonError('Error PHP: ' . $errstr, 500);
});

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDB();
} catch (Throwable $e) {
    ob_clean();
    jsonError('No se pudo conectar a la base de datos: ' . $e->getMessage(), 500);
}

// ── GET: leer configuración ───────────────────────────────────
if ($method === 'GET') {
    requireApiLogin();
    $stmt = $pdo->query("SELECT clave, valor FROM configuracion ORDER BY clave");
    $rows = $stmt->fetchAll();
    $cfg  = [];
    foreach ($rows as $r) $cfg[$r['clave']] = $r['valor'];
    ob_clean();
    jsonSuccess(['configuracion' => $cfg]);
}

requireApiRole(['admin', 'dueno']);

// ── POST ──────────────────────────────────────────────────────
if ($method === 'POST') {

    // ── Subida de logo ────────────────────────────────────────
    if (!empty($_FILES['logo'])) {
        $file = $_FILES['logo'];

        // Error de subida del lado de PHP
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $phpErrors = [
                UPLOAD_ERR_INI_SIZE   => 'El archivo supera upload_max_filesize del servidor',
                UPLOAD_ERR_FORM_SIZE  => 'El archivo supera MAX_FILE_SIZE del formulario',
                UPLOAD_ERR_PARTIAL    => 'El archivo se subió parcialmente',
                UPLOAD_ERR_NO_FILE    => 'No se seleccionó archivo',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor',
                UPLOAD_ERR_CANT_WRITE => 'Error al escribir en disco',
                UPLOAD_ERR_EXTENSION  => 'Una extensión PHP detuvo la subida',
            ];
            ob_clean();
            jsonError($phpErrors[$file['error']] ?? 'Error desconocido al subir (#' . $file['error'] . ')');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'], true)) {
            ob_clean();
            jsonError('Formato no permitido. Usa PNG, JPG, SVG o WEBP.');
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            ob_clean();
            jsonError('El logo no debe superar 2 MB.');
        }

        // Crear carpeta si no existe
        if (!is_dir(UPLOAD_PATH)) {
            if (!mkdir(UPLOAD_PATH, 0755, true)) {
                ob_clean();
                jsonError('No se pudo crear el directorio de uploads. Verifica permisos en el servidor.');
            }
        }

        if (!is_writable(UPLOAD_PATH)) {
            ob_clean();
            jsonError('La carpeta uploads/logos/ no tiene permisos de escritura (chmod 755).');
        }

        $name   = 'logo_' . time() . '.' . $ext;
        $target = UPLOAD_PATH . $name;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            ob_clean();
            jsonError('move_uploaded_file falló. Ruta destino: ' . $target);
        }

        $relativePath = 'uploads/logos/' . $name;

        try {
            $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'logo_path'")
                ->execute([$relativePath]);
        } catch (Throwable $e) {
            ob_clean();
            jsonError('Logo subido pero no se pudo guardar en BD: ' . $e->getMessage());
        }

        ob_clean();
        jsonSuccess(['logo_path' => $relativePath], 'Logo actualizado');
    }

    // ── Guardar campos de configuración ───────────────────────
    $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $allowed = [
        'nombre_negocio', 'direccion', 'telefono', 'email_negocio',
        'rfc', 'moneda', 'leyenda_ticket', 'logo_width_mm', 'logo_height_mm'
    ];

    try {
        $stmt = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");
        foreach ($allowed as $clave) {
            if (isset($body[$clave])) {
                $stmt->execute([sanitize((string)$body[$clave]), $clave]);
            }
        }
    } catch (Throwable $e) {
        ob_clean();
        jsonError('Error al guardar configuración: ' . $e->getMessage());
    }

    ob_clean();
    jsonSuccess([], 'Configuración guardada');
}

ob_clean();
jsonError('Método no permitido', 405);
