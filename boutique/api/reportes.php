<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

requireApiRole(['admin', 'dueno']);

$pdo    = getDB();
$action = $_GET['action'] ?? 'resumen';

// Rango de fechas según periodo
function dateRange(string $periodo): array {
    return match($periodo) {
        'diario'    => [date('Y-m-d'), date('Y-m-d')],
        'semanal'   => [date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week'))],
        'mensual'   => [date('Y-m-01'), date('Y-m-t')],
        'bimestral' => [date('Y-m-01', strtotime('-1 month')), date('Y-m-t')],
        'semestral' => [date('Y-m-01', strtotime('-5 months')), date('Y-m-t')],
        'anual'     => [date('Y-01-01'), date('Y-12-31')],
        default     => [date('Y-m-01'), date('Y-m-t')],
    };
}

function prevYearRange(array $range): array {
    return [
        date('Y-m-d', strtotime($range[0] . ' -1 year')),
        date('Y-m-d', strtotime($range[1] . ' -1 year')),
    ];
}

function queryResumen(PDO $pdo, string $desde, string $hasta): array {
    $stmt = $pdo->prepare("
        SELECT
            COUNT(DISTINCT v.id)                                            AS total_ventas,
            COALESCE(SUM(v.total), 0)                                       AS ingresos,
            COALESCE(SUM(dv.cantidad * dv.costo_unitario), 0)              AS costo_total,
            COALESCE(SUM(v.total) - SUM(dv.cantidad * dv.costo_unitario), 0) AS ganancia_real,
            COALESCE(SUM(v.clip_comision), 0)                               AS comisiones_clip,
            COALESCE(SUM(v.descuento), 0)                                   AS descuentos
        FROM ventas v
        JOIN detalle_ventas dv ON dv.venta_id = v.id
        WHERE v.estado = 'completada'
          AND DATE(v.created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$desde, $hasta]);
    return $stmt->fetch() ?: [];
}

// ── Resumen financiero ────────────────────────────────────────
if ($action === 'resumen') {
    $periodo = $_GET['periodo'] ?? 'mensual';
    [$desde, $hasta]    = dateRange($periodo);
    [$pdesde, $phasta]  = prevYearRange([$desde, $hasta]);

    $actual   = queryResumen($pdo, $desde, $hasta);
    $anterior = queryResumen($pdo, $pdesde, $phasta);

    // Ventas por día (para gráfica)
    $stmt = $pdo->prepare("
        SELECT DATE(v.created_at) AS fecha, COUNT(*) AS num_ventas, SUM(v.total) AS total
        FROM ventas v
        WHERE v.estado = 'completada' AND DATE(v.created_at) BETWEEN ? AND ?
        GROUP BY DATE(v.created_at)
        ORDER BY fecha
    ");
    $stmt->execute([$desde, $hasta]);
    $porDia = $stmt->fetchAll();

    // Métodos de pago
    $stmt = $pdo->prepare("
        SELECT metodo_pago, COUNT(*) AS num, SUM(total) AS monto
        FROM ventas WHERE estado = 'completada' AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY metodo_pago
    ");
    $stmt->execute([$desde, $hasta]);
    $metodos = $stmt->fetchAll();

    jsonSuccess([
        'periodo'   => $periodo,
        'desde'     => $desde,
        'hasta'     => $hasta,
        'actual'    => $actual,
        'anterior'  => $anterior,
        'por_dia'   => $porDia,
        'metodos'   => $metodos,
    ]);
}

// ── Rendimiento de inventario ─────────────────────────────────
if ($action === 'inventario') {
    $periodo = $_GET['periodo'] ?? 'mensual';
    [$desde, $hasta] = dateRange($periodo);

    // Más y menos vendidos
    $stmt = $pdo->prepare("
        SELECT
            dv.sku,
            dv.nombre_producto,
            p.marca,
            p.modelo,
            p.color,
            p.talla,
            SUM(dv.cantidad)                                                  AS unidades,
            SUM(dv.precio_unitario * dv.cantidad)                             AS ingresos,
            SUM((dv.precio_unitario - dv.costo_unitario) * dv.cantidad)       AS ganancia_real,
            p.stock AS stock_actual
        FROM detalle_ventas dv
        JOIN ventas v  ON v.id  = dv.venta_id
        JOIN productos p ON p.id = dv.producto_id
        WHERE v.estado = 'completada' AND DATE(v.created_at) BETWEEN ? AND ?
        GROUP BY dv.sku, dv.nombre_producto, p.marca, p.modelo, p.color, p.talla, p.stock
        ORDER BY unidades DESC
    ");
    $stmt->execute([$desde, $hasta]);
    $ranking = $stmt->fetchAll();

    // Stock bajo
    $stmt = $pdo->prepare("
        SELECT id, sku, marca, modelo, color, talla, stock, stock_minimo
        FROM productos WHERE activo = 1 AND stock <= stock_minimo ORDER BY stock ASC
    ");
    $stmt->execute();
    $stockBajo = $stmt->fetchAll();

    jsonSuccess(['ranking' => $ranking, 'stock_bajo' => $stockBajo, 'periodo' => $periodo, 'desde' => $desde, 'hasta' => $hasta]);
}

// ── Clip breakdown ────────────────────────────────────────────
if ($action === 'clip') {
    $periodo = $_GET['periodo'] ?? 'mensual';
    [$desde, $hasta] = dateRange($periodo);
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS num_clip, SUM(total) AS monto_clip, SUM(clip_comision) AS comision_total
        FROM ventas WHERE metodo_pago = 'clip' AND estado = 'completada'
          AND DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$desde, $hasta]);
    jsonSuccess(['clip' => $stmt->fetch(), 'desde' => $desde, 'hasta' => $hasta]);
}

jsonError('Acción no válida', 404);
