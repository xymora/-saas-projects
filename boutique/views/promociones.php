<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin', 'dueno']);
$pageTitle = 'Promociones — Mi Boutique';
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div class="app-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main-content">
  <header class="topbar">
    <span class="topbar-title">🎁 Promociones</span>
    <div class="topbar-actions">
      <button class="btn btn-primary" onclick="openModal()">+ Nueva promoción</button>
    </div>
  </header>
  <div class="page-body">
    <div class="card">
      <div class="table-wrap">
        <table>
          <thead><tr><th>Nombre</th><th>Tipo</th><th>Valor</th><th>Venta mínima</th><th>Vigencia</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody id="promo-tbody"><tr><td colspan="7" style="text-align:center;padding:24px;color:var(--gray-500)">Cargando…</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</div>

<!-- Modal Promo -->
<div class="modal-overlay" id="modal-promo">
  <div class="modal" style="max-width:500px">
    <div class="modal-header">
      <span class="modal-title" id="promo-modal-title">Nueva promoción</span>
      <button class="modal-close" onclick="Modal.close('modal-promo')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="promo-id">
      <div class="form-group"><label class="form-label">Nombre *</label><input type="text" id="promo-nombre" class="form-control" placeholder="Ej. Rebajas de verano"></div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Tipo</label>
          <select id="promo-tipo" class="form-control">
            <option value="porcentaje">Porcentaje (%)</option>
            <option value="monto_fijo">Monto fijo ($)</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Valor *</label><input type="number" id="promo-valor" class="form-control" step="0.01" min="0" placeholder="Ej. 10 o 50"></div>
      </div>
      <div class="form-group"><label class="form-label">Venta mínima ($)</label><input type="number" id="promo-minima" class="form-control" step="0.01" min="0" value="0"></div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Fecha inicio</label><input type="date" id="promo-inicio" class="form-control"></div>
        <div class="form-group"><label class="form-label">Fecha fin</label><input type="date" id="promo-fin" class="form-control"></div>
      </div>
      <div class="form-group"><label class="form-label">Descripción</label><textarea id="promo-desc" class="form-control" rows="2"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="Modal.close('modal-promo')">Cancelar</button>
      <button class="btn btn-primary" onclick="savePromo()">Guardar</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>


async function loadPromos() {
  const res  = await fetch(`${BASE}/api/promociones.php`, { credentials: 'same-origin' });
  const data = await res.json();
  if (!data.success) { Toast.error(data.error); return; }
  const today = new Date().toISOString().slice(0,10);
  document.getElementById('promo-tbody').innerHTML = data.promociones.map(p => {
    const activa = p.activa && p.fecha_inicio <= today && p.fecha_fin >= today;
    return `<tr>
      <td><strong>${p.nombre}</strong><br><span class="text-xs text-muted">${p.descripcion || ''}</span></td>
      <td>${p.tipo === 'porcentaje' ? '%' : '$'}</td>
      <td class="fw-bold">${p.tipo === 'porcentaje' ? p.valor + '%' : fmt.money(p.valor)}</td>
      <td>${fmt.money(p.venta_minima)}</td>
      <td style="font-size:12px">${p.fecha_inicio} → ${p.fecha_fin}</td>
      <td><span class="badge ${activa ? 'badge-success' : 'badge-gray'}">${activa ? 'Activa' : 'Inactiva'}</span></td>
      <td>
        <div class="d-flex gap-2">
          <button class="btn btn-ghost btn-sm" onclick='editPromo(${JSON.stringify(p).replace(/"/g,"&quot;")})'>✏️</button>
          <button class="btn btn-danger btn-sm" onclick="deletePromo(${p.id})">🗑</button>
        </div>
      </td>
    </tr>`;
  }).join('') || '<tr><td colspan="7" style="text-align:center;padding:24px;color:var(--gray-400)">Sin promociones</td></tr>';
}

function openModal() {
  ['promo-id','promo-nombre','promo-valor','promo-desc'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('promo-minima').value = '0';
  document.getElementById('promo-tipo').value   = 'porcentaje';
  document.getElementById('promo-inicio').value = new Date().toISOString().slice(0,10);
  document.getElementById('promo-fin').value    = new Date().toISOString().slice(0,10);
  document.getElementById('promo-modal-title').textContent = 'Nueva promoción';
  Modal.open('modal-promo');
}

function editPromo(p) {
  document.getElementById('promo-id').value     = p.id;
  document.getElementById('promo-nombre').value = p.nombre;
  document.getElementById('promo-tipo').value   = p.tipo;
  document.getElementById('promo-valor').value  = p.valor;
  document.getElementById('promo-minima').value = p.venta_minima;
  document.getElementById('promo-inicio').value = p.fecha_inicio;
  document.getElementById('promo-fin').value    = p.fecha_fin;
  document.getElementById('promo-desc').value   = p.descripcion || '';
  document.getElementById('promo-modal-title').textContent = 'Editar promoción';
  Modal.open('modal-promo');
}

async function savePromo() {
  const payload = {
    id:           parseInt(document.getElementById('promo-id').value) || 0,
    nombre:       document.getElementById('promo-nombre').value.trim(),
    tipo:         document.getElementById('promo-tipo').value,
    valor:        parseFloat(document.getElementById('promo-valor').value),
    venta_minima: parseFloat(document.getElementById('promo-minima').value) || 0,
    fecha_inicio: document.getElementById('promo-inicio').value,
    fecha_fin:    document.getElementById('promo-fin').value,
    descripcion:  document.getElementById('promo-desc').value.trim(),
  };
  try {
    const res  = await fetch(`${BASE}/api/promociones.php`, {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify(payload), credentials:'same-origin'
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    Modal.close('modal-promo');
    Toast.success(data.message);
    loadPromos();
  } catch(e) { Toast.error(e.message); }
}

async function deletePromo(id) {
  if (!await confirmAction('¿Desactivar esta promoción?')) return;
  try {
    const res  = await fetch(`${BASE}/api/promociones.php?id=${id}`, { method:'DELETE', credentials:'same-origin' });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    Toast.success('Promoción desactivada');
    loadPromos();
  } catch(e) { Toast.error(e.message); }
}

loadPromos();
</script>
</body>
</html>
