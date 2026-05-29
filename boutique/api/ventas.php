<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

requireApiLogin();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$pdo    = getDB();

// ── GET: listar ventas ────────────────────────────────────────
if ($method === 'GET') {
    if ($action === 'get') {
        $id   = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("
            SELECT v.*, u.nombre AS cajero
            FROM ventas v
            JOIN usuarios u ON u.id = v.usuario_id
            WHERE v.id = ?
        ");
        $stmt->execute([$id]);
        $v = $stmt->fetch();
        if (!$v) jsonError('Venta no encontrada', 404);

        $det = $pdo->prepare("SELECT * FROM detalle_ventas WHERE venta_id = ?");
        $det->execute([$id]);
        $v['detalle'] = $det->fetchAll();
        jsonSuccess(['venta' => $v]);
    }

    requireApiRole(['admin', 'dueno']);
    $desde  = $_GET['desde']  ?? date('Y-m-01');
    $hasta  = $_GET['hasta']  ?? date('Y-m-t');
    $stmt   = $pdo->prepare("
        SELECT v.id, v.folio, v.total, v.descuento, v.metodo_pago, v.estado,
               v.created_at, u.nombre AS cajero
        FROM ventas v
        JOIN usuarios u ON u.id = v.usuario_id
        WHERE DATE(v.created_at) BETWEEN ? AND ?
        ORDER BY v.created_at DESC
    ");
    $stmt->execute([$desde, $hasta]);
    jsonSuccess(['ventas' => $stmt->fetchAll()]);
}

// ── POST: confirmar venta ─────────────────────────────────────
if ($method === 'POST' && $action === 'confirmar') {
    $body        = json_decode(file_get_contents('php://input'), true) ?? [];
    $carrito     = $body['carrito']      ?? [];
    $metodoPago  = $body['metodo_pago']  ?? 'efectivo';
    $folioClip   = sanitize($body['folio_clip'] ?? '');
    $clienteEmail= filter_var(trim($body['cliente_email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: null;
    $promoId     = (int)($body['promocion_id'] ?? 0);

    if (empty($carrito)) jsonError('El carrito está vacío');
    if (!in_array($metodoPago, ['efectivo', 'clip'], true)) jsonError('Método de pago inválido');
    if ($metodoPago === 'clip' && !$folioClip) jsonError('El folio Clip es obligatorio');

    // Calcular subtotal y validar stock
    $subtotal = 0.0;
    $items    = [];

    $pdo->beginTransaction();
    try {
        foreach ($carrito as $item) {
            $prodId  = (int)($item['producto_id'] ?? 0);
            $cant    = (int)($item['cantidad'] ?? 0);
            if (!$prodId || $cant < 1) throw new Exception('Item inválido');

            $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ? AND activo = 1 FOR UPDATE");
            $stmt->execute([$prodId]);
            $prod = $stmt->fetch();
            if (!$prod) throw new Exception("Producto {$prodId} no encontrado");
            if ($prod['stock'] < $cant) throw new Exception("Stock insuficiente para {$prod['sku']}");

            $items[]   = ['prod' => $prod, 'cantidad' => $cant];
            $subtotal += (float)$prod['precio_venta'] * $cant;
        }

        // Aplicar promoción
        $descuento = 0.0;
        $promoApp  = null;
        if ($promoId > 0) {
            $stmt = $pdo->prepare("
                SELECT * FROM promociones
                WHERE id = ? AND activa = 1
                  AND CURDATE() BETWEEN fecha_inicio AND fecha_fin
                  AND ? >= venta_minima
            ");
            $stmt->execute([$promoId, $subtotal]);
            $promo = $stmt->fetch();
            if ($promo) {
                $descuento = $promo['tipo'] === 'porcentaje'
                    ? round($subtotal * ((float)$promo['valor'] / 100), 2)
                    : min((float)$promo['valor'], $subtotal);
                $promoApp = $promo['id'];
            }
        }

        $total        = max(0, $subtotal - $descuento);
        $clipComision = ($metodoPago === 'clip') ? clipComision($total) : 0.0;
        $folio        = generateFolio();

        // Insertar venta
        $stmt = $pdo->prepare("
            INSERT INTO ventas (folio, usuario_id, cliente_email, subtotal, descuento, total,
                                metodo_pago, folio_clip, clip_comision, promocion_id)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $folio, $_SESSION['user_id'], $clienteEmail,
            $subtotal, $descuento, $total,
            $metodoPago, $folioClip ?: null, $clipComision,
            $promoApp
        ]);
        $ventaId = (int)$pdo->lastInsertId();

        // Insertar detalles y descontar stock
        $detStmt = $pdo->prepare("
            INSERT INTO detalle_ventas (venta_id, producto_id, sku, nombre_producto, cantidad,
                                        costo_unitario, precio_unitario, subtotal)
            VALUES (?,?,?,?,?,?,?,?)
        ");
        $movStmt = $pdo->prepare("
            INSERT INTO movimientos_inventario (producto_id, usuario_id, tipo, cantidad,
                                                stock_anterior, stock_nuevo, motivo)
            VALUES (?,?,'salida',?,?,?,?)
        ");
        $updStmt = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");

        foreach ($items as $i) {
            $p    = $i['prod'];
            $cant = $i['cantidad'];
            $sub  = round((float)$p['precio_venta'] * $cant, 2);
            $nombre = "{$p['marca']} {$p['modelo']} — {$p['color']} T:{$p['talla']}";

            $detStmt->execute([$ventaId, $p['id'], $p['sku'], $nombre, $cant,
                               $p['costo'], $p['precio_venta'], $sub]);
            $updStmt->execute([$cant, $p['id']]);
            $movStmt->execute([$p['id'], $_SESSION['user_id'], $cant,
                               $p['stock'], $p['stock'] - $cant, "Venta {$folio}"]);
        }

        $pdo->commit();

        // Intentar enviar ticket por correo
        $ticketEnviado = false;
        if ($clienteEmail) {
            $ticketHtml   = buildTicketHtml($ventaId, $folio, $items, $subtotal, $descuento, $total, $metodoPago, $clipComision);
            $ticketEnviado = sendTicketEmail($clienteEmail, "Tu ticket Mi Boutique — {$folio}", $ticketHtml);
            if ($ticketEnviado) {
                $pdo->prepare("UPDATE ventas SET ticket_enviado = 1 WHERE id = ?")->execute([$ventaId]);
            }
        }

        jsonSuccess([
            'venta_id'       => $ventaId,
            'folio'          => $folio,
            'total'          => $total,
            'descuento'      => $descuento,
            'clip_comision'  => $clipComision,
            'ticket_enviado' => $ticketEnviado,
        ], 'Venta registrada');

    } catch (Exception $e) {
        $pdo->rollBack();
        jsonError($e->getMessage());
    }
}

// ── POST: reenviar ticket ─────────────────────────────────────
if ($method === 'POST' && $action === 'reenviar_ticket') {
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $id    = (int)($body['venta_id'] ?? 0);
    $email = filter_var(trim($body['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    if (!$id || !$email) jsonError('Datos inválidos');

    $stmt = $pdo->prepare("SELECT * FROM ventas WHERE id = ?");
    $stmt->execute([$id]);
    $v = $stmt->fetch();
    if (!$v) jsonError('Venta no encontrada', 404);

    $det = $pdo->prepare("SELECT * FROM detalle_ventas WHERE venta_id = ?");
    $det->execute([$id]);
    $items = array_map(fn($r) => ['prod' => [
        'marca'         => explode(' ', $r['nombre_producto'])[0],
        'modelo'        => '',
        'color'         => '',
        'talla'         => '',
        'precio_venta'  => $r['precio_unitario'],
        'costo'         => $r['costo_unitario'],
        'sku'           => $r['sku'],
    ], 'cantidad' => $r['cantidad']], $det->fetchAll());

    $html = buildTicketHtml($id, $v['folio'], $items,
        $v['subtotal'], $v['descuento'], $v['total'], $v['metodo_pago'], $v['clip_comision'],
        $v['nombre_producto'] ?? '');
    $ok = sendTicketEmail($email, "Tu ticket Mi Boutique — {$v['folio']}", $html);
    jsonSuccess(['enviado' => $ok]);
}

// ── POST: cancelar venta ──────────────────────────────────────
if ($method === 'POST' && $action === 'cancelar') {
    requireApiRole(['admin', 'dueno']);
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int)($body['venta_id'] ?? 0);
    if (!$id) jsonError('ID requerido');

    $stmt = $pdo->prepare("SELECT estado FROM ventas WHERE id = ?");
    $stmt->execute([$id]);
    $v = $stmt->fetch();
    if (!$v || $v['estado'] === 'cancelada') jsonError('Venta no válida para cancelar');

    $pdo->beginTransaction();
    try {
        $det = $pdo->prepare("SELECT producto_id, cantidad FROM detalle_ventas WHERE venta_id = ?");
        $det->execute([$id]);
        $rows = $det->fetchAll();
        foreach ($rows as $r) {
            $pdo->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?")
                ->execute([$r['cantidad'], $r['producto_id']]);
        }
        $pdo->prepare("UPDATE ventas SET estado = 'cancelada' WHERE id = ?")->execute([$id]);
        $pdo->commit();
        jsonSuccess([], 'Venta cancelada y stock revertido');
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonError('Error al cancelar');
    }
}

jsonError('Acción no válida', 404);

// ── Helper: construir HTML del ticket ────────────────────────
function buildTicketHtml(int $ventaId, string $folio, array $items, float $subtotal,
                          float $descuento, float $total, string $metodo, float $clipComision): string {
    $negocio  = getConfig('nombre_negocio');
    $dir      = getConfig('direccion');
    $leyenda  = getConfig('leyenda_ticket');
    $fecha    = date('d/m/Y H:i');
    $rows     = '';

    foreach ($items as $i) {
        $p    = $i['prod'];
        $cant = $i['cantidad'];
        $sub  = number_format((float)$p['precio_venta'] * $cant, 2);
        $nombre = "{$p['marca']} {$p['modelo']}";
        if (!empty($p['color'])) $nombre .= " · {$p['color']}";
        if (!empty($p['talla'])) $nombre .= " T:{$p['talla']}";
        $rows .= "<tr>
            <td style='padding:4px 0'>{$nombre}<br><small style='color:#888'>{$p['sku']}</small></td>
            <td style='text-align:center;padding:4px 8px'>{$cant}</td>
            <td style='text-align:right;padding:4px 0'>\${$sub}</td>
        </tr>";
    }

    $descRow = $descuento > 0
        ? "<tr><td colspan='2' style='color:#e05'>Descuento</td><td style='text-align:right;color:#e05'>-\$" . number_format($descuento, 2) . "</td></tr>"
        : '';
    $clipRow = $clipComision > 0
        ? "<tr><td colspan='2' style='color:#888;font-size:11px'>Comisión Clip</td><td style='text-align:right;color:#888;font-size:11px'>\$" . number_format($clipComision, 2) . "</td></tr>"
        : '';
    $metodoLabel = $metodo === 'clip' ? 'Clip (tarjeta)' : 'Efectivo';

    return "<!DOCTYPE html><html><head><meta charset='UTF-8'>
    <style>body{font-family:Arial,sans-serif;font-size:13px;color:#333;max-width:360px;margin:auto;padding:20px}
    h2{color:#c8956c;margin:0}hr{border:none;border-top:1px dashed #ccc}
    table{width:100%;border-collapse:collapse}.total-row td{font-weight:bold;font-size:15px;border-top:1px dashed #ccc}</style>
    </head><body>
    <div style='text-align:center'><h2>{$negocio}</h2>
    <small>{$dir}</small><br><small>{$fecha}</small></div>
    <hr>
    <p><strong>Folio:</strong> {$folio} &nbsp;|&nbsp; <strong>Pago:</strong> {$metodoPago}</p>
    <table>{$rows}{$descRow}
    <tr class='total-row'><td colspan='2'>TOTAL</td><td style='text-align:right'>\$" . number_format($total, 2) . "</td></tr>
    {$clipRow}</table>
    <hr><p style='text-align:center;color:#c8956c'>{$leyenda}</p>
    </body></html>";
}
