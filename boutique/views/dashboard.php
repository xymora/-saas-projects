<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin', 'dueno']);
$pageTitle = 'Dashboard — Mi Boutique';
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div class="app-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main-content">
  <header class="topbar">
    <button class="btn btn-icon sidebar-toggle no-print" style="display:none">☰</button>
    <span class="topbar-title">Dashboard</span>
    <div class="topbar-actions">
      <select id="periodo-select" class="form-control" style="width:auto">
        <option value="diario">Hoy</option>
        <option value="semanal">Esta semana</option>
        <option value="mensual" selected>Este mes</option>
        <option value="bimestral">Bimestral</option>
        <option value="semestral">Semestral</option>
        <option value="anual">Anual</option>
      </select>
    </div>
  </header>

  <div class="page-body">
    <!-- KPIs -->
    <div class="stats-grid" id="stats-grid">
      <div class="stat-card brand"><div class="stat-label">Ingresos</div><div class="stat-value" id="kpi-ingresos">—</div><div class="stat-sub" id="kpi-ingresos-prev">vs año anterior: —</div></div>
      <div class="stat-card success"><div class="stat-label">Ganancia real</div><div class="stat-value" id="kpi-ganancia">—</div><div class="stat-sub" id="kpi-ganancia-prev">vs año anterior: —</div></div>
      <div class="stat-card warning"><div class="stat-label">Ventas</div><div class="stat-value" id="kpi-ventas">—</div><div class="stat-sub" id="kpi-ventas-prev">vs año anterior: —</div></div>
      <div class="stat-card accent"><div class="stat-label">Comisiones Clip</div><div class="stat-value" id="kpi-clip">—</div><div class="stat-sub">3.6% + IVA por venta Clip</div></div>
      <div class="stat-card danger"><div class="stat-label">Descuentos</div><div class="stat-value" id="kpi-desc">—</div><div class="stat-sub">aplicados en el periodo</div></div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:24px">
      <!-- Ventas por día -->
      <div class="card">
        <div class="card-title">Ventas por día</div>
        <canvas id="chart-ventas" height="200"></canvas>
      </div>
      <!-- Métodos de pago -->
      <div class="card">
        <div class="card-title">Métodos de pago</div>
        <canvas id="chart-metodos" height="200"></canvas>
        <div id="metodos-list" style="margin-top:16px"></div>
      </div>
    </div>

    <!-- Ranking productos -->
    <div class="card">
      <div class="d-flex justify-between align-center mb-4">
        <div class="card-title" style="margin:0">Top productos</div>
        <a href="<?= BASE_URL ?>/views/reportes.php" class="btn btn-ghost btn-sm">Ver reporte completo</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Producto</th><th>Unidades</th><th>Ingresos</th><th>Ganancia</th></tr></thead>
          <tbody id="top-productos"><tr><td colspan="5" style="text-align:center;padding:24px;color:var(--gray-500)">Cargando…</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>
</div><!-- main-content -->
</div><!-- app-layout -->

<div id="toast-container"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>

let chartVentas, chartMetodos;

async function loadDashboard(periodo = 'mensual') {
  try {
    const [r, inv] = await Promise.all([
      fetch(`${BASE}/api/reportes.php?action=resumen&periodo=${periodo}`, {credentials:'same-origin'}).then(r=>r.json()),
      fetch(`${BASE}/api/reportes.php?action=inventario&periodo=${periodo}`, {credentials:'same-origin'}).then(r=>r.json()),
    ]);

    if (!r.success) throw new Error(r.error);
    const a = r.actual, p = r.anterior;

    document.getElementById('kpi-ingresos').textContent = fmt.money(a.ingresos);
    document.getElementById('kpi-ingresos-prev').textContent = `vs año anterior: ${fmt.money(p.ingresos)}`;
    document.getElementById('kpi-ganancia').textContent = fmt.money(a.ganancia_real);
    document.getElementById('kpi-ganancia-prev').textContent = `vs año anterior: ${fmt.money(p.ganancia_real)}`;
    document.getElementById('kpi-ventas').textContent = a.total_ventas;
    document.getElementById('kpi-ventas-prev').textContent = `vs año anterior: ${p.total_ventas}`;
    document.getElementById('kpi-clip').textContent = fmt.money(a.comisiones_clip);
    document.getElementById('kpi-desc').textContent = fmt.money(a.descuentos);

    // Chart ventas por día
    const dias = r.por_dia || [];
    const labels = dias.map(d => d.fecha);
    const totales = dias.map(d => parseFloat(d.total));
    if (chartVentas) chartVentas.destroy();
    chartVentas = new Chart(document.getElementById('chart-ventas'), {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Ventas ($)',
          data: totales,
          fill: true,
          borderColor: '#c8956c',
          backgroundColor: 'rgba(200,149,108,.12)',
          tension: .4,
          pointBackgroundColor: '#c8956c',
        }]
      },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    // Chart métodos de pago
    const metodos = r.metodos || [];
    const mLabels = metodos.map(m => m.metodo_pago === 'clip' ? 'Clip' : 'Efectivo');
    const mData   = metodos.map(m => parseFloat(m.monto));
    if (chartMetodos) chartMetodos.destroy();
    chartMetodos = new Chart(document.getElementById('chart-metodos'), {
      type: 'doughnut',
      data: { labels: mLabels, datasets: [{ data: mData, backgroundColor: ['#c8956c','#8b5cf6'], borderWidth: 0 }] },
      options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    const mList = metodos.map(m =>
      `<div class="d-flex justify-between" style="font-size:13px;padding:4px 0">
        <span>${m.metodo_pago === 'clip' ? '💳 Clip' : '💵 Efectivo'} (${m.num})</span>
        <strong>${fmt.money(m.monto)}</strong>
      </div>`).join('');
    document.getElementById('metodos-list').innerHTML = mList;

    // Top productos
    if (inv.success) {
      const ranking = (inv.ranking || []).slice(0, 10);
      const tbody = ranking.length
        ? ranking.map((p, i) => `<tr>
            <td><strong>${i+1}</strong></td>
            <td>
              <div style="font-size:13px;font-weight:600">${p.marca} ${p.modelo}</div>
              <div style="font-size:11px;color:var(--gray-500);margin-top:2px">
                <span style="background:var(--gray-100);padding:1px 7px;border-radius:4px;margin-right:4px">${p.color}</span>
                <span style="background:var(--gray-100);padding:1px 7px;border-radius:4px">T: ${p.talla}</span>
              </div>
              <div class="text-xs text-muted" style="margin-top:3px">${p.sku}</div>
            </td>
            <td>${p.unidades}</td>
            <td>${fmt.money(p.ingresos)}</td>
            <td class="text-success fw-bold">${fmt.money(p.ganancia_real)}</td>
          </tr>`).join('')
        : '<tr><td colspan="5" style="text-align:center;padding:24px;color:var(--gray-500)">Sin datos en el periodo</td></tr>';
      document.getElementById('top-productos').innerHTML = tbody;
    }

  } catch(e) {
    Toast.error('Error al cargar dashboard: ' + e.message);
  }
}

document.getElementById('periodo-select').addEventListener('change', function() {
  loadDashboard(this.value);
});

loadDashboard('mensual');
</script>
</body>
</html>
