<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

requireApiLogin();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$pdo    = getDB();

// ── GET: listar / buscar / obtener uno ───────────────────────
if ($method === 'GET') {
    if ($action === 'get') {
        $id   = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if (!$p) jsonError('Producto no encontrado', 404);
        jsonSuccess(['producto' => $p]);
    }

    if ($action === 'by_sku') {
        $sku  = trim($_GET['sku'] ?? '');
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE sku = ? AND activo = 1");
        $stmt->execute([$sku]);
        $p = $stmt->fetch();
        if (!$p) jsonError('SKU no encontrado', 404);
        jsonSuccess(['producto' => $p]);
    }

    // Búsqueda general (para POS)
    $q     = trim($_GET['q'] ?? '');
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;

    if ($q) {
        $like = "%{$q}%";
        $stmt = $pdo->prepare("
            SELECT * FROM productos
            WHERE activo = 1
              AND (sku LIKE ? OR marca LIKE ? OR modelo LIKE ? OR color LIKE ? OR talla LIKE ?)
            ORDER BY marca, modelo
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$like, $like, $like, $like, $like, $limit, $offset]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE activo = 1 ORDER BY marca, modelo LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
    }

    $rows = $stmt->fetchAll();
    $total = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
    jsonSuccess(['productos' => $rows, 'page' => $page, 'limit' => $limit]);
}

// ── POST: crear / actualizar / ajuste stock ───────────────────
requireApiRole(['admin', 'dueno', 'empleado']);

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    // Ajuste de stock
    if ($action === 'ajuste_stock') {
        requireApiRole(['admin', 'dueno', 'empleado']);
        $id       = (int)($body['producto_id'] ?? 0);
        $cantidad = (int)($body['cantidad'] ?? 0);
        $tipo     = $body['tipo'] ?? '';
        $motivo   = sanitize($body['motivo'] ?? '');

        if (!in_array($tipo, ['entrada', 'salida', 'ajuste'], true)) jsonError('Tipo inválido');
        if (!$id || $cantidad <= 0) jsonError('Datos inválidos');

        $stmt = $pdo->prepare("SELECT id, stock FROM productos WHERE id = ? AND activo = 1");
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if (!$p) jsonError('Producto no encontrado', 404);

        $anterior = (int)$p['stock'];
        if ($tipo === 'entrada') $nuevo = $anterior + $cantidad;
        elseif ($tipo === 'salida') {
            if ($anterior < $cantidad) jsonError('Stock insuficiente');
            $nuevo = $anterior - $cantidad;
        } else {
            $nuevo = $cantidad;
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE productos SET stock = ? WHERE id = ?")->execute([$nuevo, $id]);
            $pdo->prepare("INSERT INTO movimientos_inventario (producto_id, usuario_id, tipo, cantidad, stock_anterior, stock_nuevo, motivo)
                           VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute([$id, $_SESSION['user_id'], $tipo, $cantidad, $anterior, $nuevo, $motivo]);
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonError('Error al ajustar stock');
        }
        jsonSuccess(['stock_nuevo' => $nuevo], 'Stock actualizado');
    }

    // Crear / actualizar producto
    requireApiRole(['admin', 'dueno']);

    $id     = (int)($body['id'] ?? 0);
    $marca  = sanitize($body['marca'] ?? '');
    $modelo = sanitize($body['modelo'] ?? '');
    $color  = sanitize($body['color'] ?? '');
    $talla  = sanitize($body['talla'] ?? '');
    $precio = (float)($body['precio_venta'] ?? 0);
    $costo  = (float)($body['costo'] ?? 0);
    $stock  = (int)($body['stock'] ?? 0);
    $stockMin = (int)($body['stock_minimo'] ?? 2);
    $desc   = sanitize($body['descripcion'] ?? '');

    if (!$marca || !$modelo || !$color || !$talla) jsonError('Marca, modelo, color y talla son obligatorios');
    if ($precio <= 0) jsonError('El precio de venta debe ser mayor a 0');

    $sku = generateSKU($marca, $modelo, $color, $talla);

    if ($id > 0) {
        // Actualizar
        $stmt = $pdo->prepare("
            UPDATE productos SET marca=?, modelo=?, color=?, talla=?, sku=?,
            descripcion=?, precio_venta=?, costo=?, stock=?, stock_minimo=?
            WHERE id=?
        ");
        $stmt->execute([$marca, $modelo, $color, $talla, $sku, $desc, $precio, $costo, $stock, $stockMin, $id]);
        jsonSuccess(['id' => $id, 'sku' => $sku], 'Producto actualizado');
    } else {
        // Crear — si ya existe el SKU, sólo suma stock
        $existing = $pdo->prepare("SELECT id, stock FROM productos WHERE sku = ?");
        $existing->execute([$sku]);
        $ex = $existing->fetch();
        if ($ex) {
            $nuevo = $ex['stock'] + $stock;
            $pdo->prepare("UPDATE productos SET stock = ? WHERE id = ?")->execute([$nuevo, $ex['id']]);
            jsonSuccess(['id' => $ex['id'], 'sku' => $sku, 'stock' => $nuevo], 'Stock sumado a producto existente');
        }
        $stmt = $pdo->prepare("
            INSERT INTO productos (sku, marca, modelo, color, talla, descripcion, precio_venta, costo, stock, stock_minimo)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([$sku, $marca, $modelo, $color, $talla, $desc, $precio, $costo, $stock, $stockMin]);
        jsonSuccess(['id' => $pdo->lastInsertId(), 'sku' => $sku], 'Producto creado');
    }
}

// ── DELETE: desactivar ────────────────────────────────────────
if ($method === 'DELETE') {
    requireApiRole(['admin', 'dueno']);
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonError('ID requerido');
    $pdo->prepare("UPDATE productos SET activo = 0 WHERE id = ?")->execute([$id]);
    jsonSuccess([], 'Producto eliminado');
}

jsonError('Método no permitido', 405);
