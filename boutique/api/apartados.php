<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

requireApiLogin();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$pdo    = getDB();

// ── GET: listar apartados activos ─────────────────────────────
if ($method === 'GET' && $action === 'list') {
    $stmt = $pdo->query("
        SELECT
            a.id, a.folio, a.nombre_cliente, a.telefono_cliente,
            a.monto_apartado, a.monto_total,
            a.fecha_apartado, a.fecha_vigencia,
            CASE
                WHEN a.estado = 'activo' AND a.fecha_vigencia < CURDATE() THEN 'vencido'
                ELSE a.estado
            END AS estado,
            a.notas, a.created_at,
            u.nombre AS registrado_por,
            (SELECT GROUP_CONCAT(ap2.sku ORDER BY ap2.id SEPARATOR ', ')
             FROM apartado_prendas ap2
             WHERE ap2.apartado_id = a.id) AS prendas_desc,
            (SELECT COUNT(*) FROM apartado_prendas ap2 WHERE ap2.apartado_id = a.id) AS num_prendas
        FROM apartados a
        JOIN usuarios u ON u.id = a.usuario_id
        WHERE a.estado = 'activo'
        ORDER BY a.fecha_vigencia ASC, a.created_at DESC
        LIMIT 100
    ");
    jsonSuccess(['apartados' => $stmt->fetchAll()]);
}

// ── GET: obtener apartado con prendas ─────────────────────────
if ($method === 'GET' && $action === 'get') {
    $id   = (int)($_GET['id'] ?? 0);
    if (!$id) jsonError('ID requerido');

    $stmt = $pdo->prepare("
        SELECT a.*,
            CASE
                WHEN a.estado = 'activo' AND a.fecha_vigencia < CURDATE() THEN 'vencido'
                ELSE a.estado
            END AS estado,
            u.nombre AS registrado_por
        FROM apartados a
        JOIN usuarios u ON u.id = a.usuario_id
        WHERE a.id = ?
    ");
    $stmt->execute([$id]);
    $apt = $stmt->fetch();
    if (!$apt) jsonError('Apartado no encontrado', 404);

    $det = $pdo->prepare("SELECT * FROM apartado_prendas WHERE apartado_id = ? ORDER BY id");
    $det->execute([$id]);
    $apt['prendas'] = $det->fetchAll();

    jsonSuccess(['apartado' => $apt]);
}

// ── POST: crear apartado ──────────────────────────────────────
if ($method === 'POST' && $action === 'crear') {
    $body          = json_decode(file_get_contents('php://input'), true) ?? [];
    $nombre        = sanitize($body['nombre_cliente']  ?? '');
    $telefono      = sanitize($body['telefono_cliente'] ?? '');
    $montoApartado = round((float)($body['monto_apartado'] ?? 0), 2);
    $vigenciaDias  = max(1, (int)($body['vigencia_dias'] ?? 30));
    $notas         = sanitize($body['notas'] ?? '');
    $carrito       = $body['carrito'] ?? [];

    if (!$nombre)            jsonError('El nombre del cliente es obligatorio');
    if ($montoApartado <= 0) jsonError('El monto de apartado debe ser mayor a cero');
    if (empty($carrito))     jsonError('Debe seleccionar al menos una prenda');

    $pdo->beginTransaction();
    try {
        $montoTotal = 0.0;
        $items      = [];

        foreach ($carrito as $item) {
            $prodId = (int)($item['producto_id'] ?? 0);
            $cant   = (int)($item['cantidad']    ?? 0);
            if (!$prodId || $cant < 1) throw new Exception('Item inválido');

            $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ? AND activo = 1");
            $stmt->execute([$prodId]);
            $prod = $stmt->fetch();
            if (!$prod) throw new Exception("Producto {$prodId} no encontrado");

            $items[]     = ['prod' => $prod, 'cantidad' => $cant];
            $montoTotal += round((float)$prod['precio_venta'] * $cant, 2);
        }

        if ($montoApartado > $montoTotal) {
            throw new Exception('El anticipo no puede ser mayor al total');
        }

        $folio         = 'APT-' . date('ymd') . strtoupper(substr(uniqid(), -4));
        $fechaApartado = date('Y-m-d');
        $fechaVigencia = date('Y-m-d', strtotime("+{$vigenciaDias} days"));

        $stmt = $pdo->prepare("
            INSERT INTO apartados
                (folio, nombre_cliente, telefono_cliente, usuario_id,
                 monto_apartado, monto_total, fecha_apartado, fecha_vigencia, notas)
            VALUES (?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $folio, $nombre, $telefono ?: null, $_SESSION['user_id'],
            $montoApartado, $montoTotal, $fechaApartado, $fechaVigencia,
            $notas ?: null,
        ]);
        $aptId = (int)$pdo->lastInsertId();

        $detStmt = $pdo->prepare("
            INSERT INTO apartado_prendas
                (apartado_id, producto_id, sku, nombre_producto, cantidad, precio_unitario, subtotal)
            VALUES (?,?,?,?,?,?,?)
        ");

        foreach ($items as $i) {
            $p    = $i['prod'];
            $cant = $i['cantidad'];
            $sub  = round((float)$p['precio_venta'] * $cant, 2);
            $nombreProd = "{$p['marca']} {$p['modelo']} — {$p['color']} T:{$p['talla']}";
            $detStmt->execute([
                $aptId, $p['id'], $p['sku'], $nombreProd,
                $cant, $p['precio_venta'], $sub,
            ]);
        }

        $pdo->commit();
        jsonSuccess([
            'apartado_id'   => $aptId,
            'folio'         => $folio,
            'fecha_vigencia' => $fechaVigencia,
            'monto_total'   => $montoTotal,
        ], 'Apartado registrado');

    } catch (Exception $e) {
        $pdo->rollBack();
        jsonError($e->getMessage());
    }
}

// ── POST: completar apartado ──────────────────────────────────
if ($method === 'POST' && $action === 'completar') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int)($body['apartado_id'] ?? 0);
    if (!$id) jsonError('ID requerido');

    $stmt = $pdo->prepare("SELECT estado FROM apartados WHERE id = ?");
    $stmt->execute([$id]);
    $apt = $stmt->fetch();
    if (!$apt || $apt['estado'] !== 'activo') jsonError('Apartado no válido o ya procesado');

    $pdo->prepare("UPDATE apartados SET estado = 'completado' WHERE id = ?")->execute([$id]);
    jsonSuccess([], 'Apartado marcado como completado');
}

// ── POST: cancelar apartado ───────────────────────────────────
if ($method === 'POST' && $action === 'cancelar') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int)($body['apartado_id'] ?? 0);
    if (!$id) jsonError('ID requerido');

    $stmt = $pdo->prepare("SELECT estado FROM apartados WHERE id = ?");
    $stmt->execute([$id]);
    $apt = $stmt->fetch();
    if (!$apt || $apt['estado'] !== 'activo') jsonError('Apartado no válido para cancelar');

    $pdo->prepare("UPDATE apartados SET estado = 'cancelado' WHERE id = ?")->execute([$id]);
    jsonSuccess([], 'Apartado cancelado');
}

jsonError('Acción no válida', 404);
