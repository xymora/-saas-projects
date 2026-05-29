<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);
$pageTitle = 'Panel Admin — Mi Boutique';
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div class="app-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main-content">
  <header class="topbar">
    <span class="topbar-title">🔧 Panel Administrador del Sistema</span>
  </header>
  <div class="page-body">

    <!-- Accesos rápidos -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-bottom:28px">
      <a href="<?= BASE_URL ?>/views/usuarios.php" class="stat-card brand" style="text-decoration:none;cursor:pointer">
        <div class="stat-icon">👥</div>
        <div class="stat-label">Usuarios</div>
        <div class="stat-value" id="admin-total-users">—</div>
        <div class="stat-sub">admins · dueños · empleados</div>
      </a>
      <a href="<?= BASE_URL ?>/views/inventario.php" class="stat-card success" style="text-decoration:none">
        <div class="stat-icon">📦</div>
        <div class="stat-label">Productos activos</div>
        <div class="stat-value" id="admin-total-prods">—</div>
        <div class="stat-sub">en catálogo</div>
      </a>
      <a href="<?= BASE_URL ?>/views/reportes.php" class="stat-card warning" style="text-decoration:none">
        <div class="stat-icon">📈</div>
        <div class="stat-label">Ventas totales</div>
        <div class="stat-value" id="admin-total-ventas">—</div>
        <div class="stat-sub">históricas</div>
      </a>
      <a href="<?= BASE_URL ?>/views/configuracion.php" class="stat-card accent" style="text-decoration:none">
        <div class="stat-icon">⚙️</div>
        <div class="stat-label">Configuración</div>
        <div class="stat-value" style="font-size:18px">Negocio</div>
        <div class="stat-sub">datos, logo, correo</div>
      </a>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
      <!-- Usuarios por rol -->
      <div class="card">
        <div class="card-title">Usuarios del sistema</div>
        <div id="admin-users-list">Cargando…</div>
      </div>

      <!-- Estado del sistema -->
      <div class="card">
        <div class="card-title">Estado del sistema</div>
        <div id="sys-info">Cargando…</div>
      </div>
    </div>

    <!-- Actividad reciente (ventas últimas 24h) -->
    <div class="card mt-4">
      <div class="card-title">Actividad reciente (últimas 24 horas)</div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Folio</th><th>Cajero</th><th>Total</th><th>Método</th><th>Hora</th></tr></thead>
          <tbody id="admin-recent"></tbody>
        </table>
      </div>
    </div>

  </div>
</div>
</div>

<div id="toast-container"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>


async function loadAdminPanel() {
  try {
    const [users, ventas] = await Promise.all([
      fetch(`${BASE}/api/usuarios.php`, { credentials: 'same-origin' }).then(r => r.json()),
      fetch(`${BASE}/api/ventas.php?desde=${new Date().toISOString().slice(0,10)}&hasta=${new Date().toISOString().slice(0,10)}`, { credentials: 'same-origin' }).then(r => r.json()),
    ]);

    if (users.success) {
      const us = users.usuarios;
      document.getElementById('admin-total-users').textContent = us.length;
      const byRol = { admin: [], dueno: [], empleado: [] };
      us.forEach(u => byRol[u.rol]?.push(u));

      document.getElementById('admin-users-list').innerHTML = ['admin','dueno','empleado'].map(rol => {
        const items = byRol[rol];
        const labels = { admin: '🔧 Administradores', dueno: '👑 Dueños', empleado: '👷 Empleados' };
        return `<div style="margin-bottom:16px">
          <div style="font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px">${labels[rol]} (${items.length})</div>
          ${items.map(u => `
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--gray-100);font-size:13px">
              <div>
                <div style="font-weight:600">${u.nombre}</div>
                <div style="color:var(--gray-500);font-size:11px">${u.email}</div>
              </div>
              <span class="badge ${u.activo ? 'badge-success' : 'badge-gray'}">${u.activo ? 'Activo' : 'Inactivo'}</span>
            </div>
          `).join('')}
        </div>`;
      }).join('');
    }

    // Info del sistema
    document.getElementById('sys-info').innerHTML = `
      <div style="display:flex;flex-direction:column;gap:10px;font-size:13px">
        <div class="d-flex justify-between"><span>PHP Version</span><strong><?= PHP_VERSION ?></strong></div>
        <div class="d-flex justify-between"><span>Servidor</span><strong><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></strong></div>
        <div class="d-flex justify-between"><span>Zona horaria</span><strong>America/Mexico_City</strong></div>
        <div class="d-flex justify-between"><span>Charset</span><strong>UTF-8 / utf8mb4</strong></div>
        <div class="d-flex justify-between"><span>Fecha/hora servidor</span><strong><?= date('d/m/Y H:i:s') ?></strong></div>
      </div>
    `;

    if (ventas.success) {
      document.getElementById('admin-total-ventas').textContent = ventas.ventas.length;
      document.getElementById('admin-recent').innerHTML = ventas.ventas.slice(0, 10).map(v => `
        <tr>
          <td><code>${v.folio}</code></td>
          <td>${v.cajero}</td>
          <td class="fw-bold text-brand">${fmt.money(v.total)}</td>
          <td><span class="badge ${v.metodo_pago === 'clip' ? 'badge-success' : 'badge-gray'}">${v.metodo_pago}</span></td>
          <td class="text-xs text-muted">${fmt.datetime(v.created_at)}</td>
        </tr>
      `).join('') || '<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--gray-400)">Sin ventas hoy</td></tr>';
    }

  } catch(e) { Toast.error('Error al cargar panel: ' + e.message); }
}

loadAdminPanel();
</script>
</body>
</html>
