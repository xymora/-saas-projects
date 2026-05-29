<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

requireApiLogin();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$pdo    = getDB();

if ($method === 'GET') {
    if ($action === 'activas') {
        $stmt = $pdo->prepare("
            SELECT * FROM promociones
            WHERE activa = 1 AND CURDATE() BETWEEN fecha_inicio AND fecha_fin
            ORDER BY valor DESC
        ");
        $stmt->execute();
        jsonSuccess(['promociones' => $stmt->fetchAll()]);
    }
    requireApiRole(['admin', 'dueno']);
    $stmt = $pdo->query("SELECT * FROM promociones ORDER BY fecha_fin DESC");
    jsonSuccess(['promociones' => $stmt->fetchAll()]);
}

requireApiRole(['admin', 'dueno']);

if ($method === 'POST') {
    $body       = json_decode(file_get_contents('php://input'), true) ?? [];
    $id         = (int)($body['id'] ?? 0);
    $nombre     = sanitize($body['nombre'] ?? '');
    $tipo       = $body['tipo'] ?? 'porcentaje';
    $valor      = (float)($body['valor'] ?? 0);
    $ventaMin   = (float)($body['venta_minima'] ?? 0);
    $inicio     = $body['fecha_inicio'] ?? '';
    $fin        = $body['fecha_fin'] ?? '';
    $desc       = sanitize($body['descripcion'] ?? '');

    if (!$nombre || !in_array($tipo, ['porcentaje', 'monto_fijo'], true)) jsonError('Datos inválidos');
    if ($valor <= 0) jsonError('El valor debe ser mayor a 0');

    if ($id > 0) {
        $pdo->prepare("UPDATE promociones SET nombre=?,descripcion=?,tipo=?,valor=?,venta_minima=?,fecha_inicio=?,fecha_fin=? WHERE id=?")
            ->execute([$nombre,$desc,$tipo,$valor,$ventaMin,$inicio,$fin,$id]);
        jsonSuccess(['id' => $id], 'Promoción actualizada');
    } else {
        $stmt = $pdo->prepare("INSERT INTO promociones (nombre,descripcion,tipo,valor,venta_minima,fecha_inicio,fecha_fin) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$nombre,$desc,$tipo,$valor,$ventaMin,$inicio,$fin]);
        jsonSuccess(['id' => $pdo->lastInsertId()], 'Promoción creada');
    }
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonError('ID requerido');
    $pdo->prepare("UPDATE promociones SET activa = 0 WHERE id = ?")->execute([$id]);
    jsonSuccess([], 'Promoción desactivada');
}

jsonError('Método no permitido', 405);
