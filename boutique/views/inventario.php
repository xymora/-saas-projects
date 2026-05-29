<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$pageTitle = 'Inventario — Mi Boutique';
$isOwner   = isOwnerOrAdmin();
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div class="app-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main-content">
  <header class="topbar">
    <span class="topbar-title">📦 Inventario</span>
    <div class="topbar-actions">
      <input type="search" id="search-inv" class="form-control" style="width:220px" placeholder="Buscar producto…">
      <?php if ($isOwner): ?>
      <button class="btn btn-primary" onclick="openModal()">+ Nuevo producto</button>
      <?php endif; ?>
    </div>
  </header>

  <div class="page-body">
    <!-- Stock bajo alert -->
    <div id="stock-alert" style="display:none"></div>

    <div class="card">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>SKU</th><th>Marca</th><th>Modelo</th><th>Color</th><th>Talla</th>
              <th>Precio</th><th>Costo</th><th>Stock</th><th>Acciones</th>
            </tr>
          </thead>
          <tbody id="inv-tbody">
            <tr><td colspan="9" style="text-align:center;padding:32px;color:var(--gray-500)">Cargando…</td></tr>
          </tbody>
        </table>
      </div>
      <div id="pagination" class="d-flex justify-between align-center mt-4" style="font-size:13px;color:var(--gray-500)"></div>
    </div>
  </div>
</div>
</div>

<!-- Modal: Producto -->
<div class="modal-overlay" id="modal-producto">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="modal-prod-title">Nuevo producto</span>
      <button class="modal-close" onclick="Modal.close('modal-producto')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="prod-id">
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Marca *</label><input type="text" id="prod-marca" class="form-control" placeholder="Ej. Zara"></div>
        <div class="form-group"><label class="form-label">Modelo *</label><input type="text" id="prod-modelo" class="form-control" placeholder="Ej. Vestido Floral"></div>
      </div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Color *</label><input type="text" id="prod-color" class="form-control" placeholder="Ej. Rojo"></div>
        <div class="form-group"><label class="form-label">Talla *</label><input type="text" id="prod-talla" class="form-control" placeholder="Ej. M / 32 / Única"></div>
      </div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Precio de venta *</label><input type="number" id="prod-precio" class="form-control" step="0.01" min="0"></div>
        <div class="form-group"><label class="form-label">Costo</label><input type="number" id="prod-costo" class="form-control" step="0.01" min="0"></div>
      </div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Stock inicial</label><input type="number" id="prod-stock" class="form-control" min="0" value="0"></div>
        <div class="form-group"><label class="form-label">Stock mínimo</label><input type="number" id="prod-stockmin" class="form-control" min="0" value="2"></div>
      </div>
      <div class="form-group"><label class="form-label">Descripción</label><textarea id="prod-desc" class="form-control" rows="2" placeholder="Notas adicionales…"></textarea></div>
      <div id="sku-preview" style="display:none" class="alert alert-info">SKU generado: <strong id="sku-val">—</strong></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="Modal.close('modal-producto')">Cancelar</button>
      <button class="btn btn-primary" onclick="saveProd()">Guardar</button>
    </div>
  </div>
</div>

<!-- Modal: Ajuste stock -->
<div class="modal-overlay" id="modal-ajuste">
  <div class="modal" style="max-width:400px">
    <div class="modal-header">
      <span class="modal-title">Ajuste de stock</span>
      <button class="modal-close" onclick="Modal.close('modal-ajuste')">✕</button>
    </div>
    <div class="modal-body">
      <p style="font-size:14px;font-weight:600;margin-bottom:16px" id="ajuste-prod-name">—</p>
      <input type="hidden" id="ajuste-prod-id">
      <div class="form-group">
        <label class="form-label">Tipo</label>
        <select id="ajuste-tipo" class="form-control">
          <option value="entrada">Entrada (suma stock)</option>
          <option value="salida">Salida (resta stock)</option>
          <option value="ajuste">Ajuste (establece total)</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Cantidad</label><input type="number" id="ajuste-cantidad" class="form-control" min="1" value="1"></div>
      <div class="form-group"><label class="form-label">Motivo</label><input type="text" id="ajuste-motivo" class="form-control" placeholder="Compra, devolución, inventario…"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="Modal.close('modal-ajuste')">Cancelar</button>
      <button class="btn btn-primary" onclick="saveAjuste()">Aplicar ajuste</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>

const isOwner = <?= $isOwner ? 'true' : 'false' ?>;
let currentPage = 1;
let searchQ     = '';
let searchDelay;

async function loadInventario(page = 1, q = '') {
  currentPage = page;
  const url = `${BASE}/api/productos.php?page=${page}&q=${encodeURIComponent(q)}`;
  const res  = await fetch(url, { credentials: 'same-origin' });
  const data = await res.json();
  if (!data.success) { Toast.error(data.error); return; }

  const tbody = document.getElementById('inv-tbody');
  const prods = data.productos || [];
  if (!prods.length) {
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:32px;color:var(--gray-500)">Sin resultados</td></tr>';
    return;
  }

  tbody.innerHTML = prods.map(p => {
    const stockBadge = p.stock <= p.stock_minimo
      ? `<span class="badge badge-danger">${p.stock}</span>`
      : `<span class="badge badge-success">${p.stock}</span>`;
    const actions = `
      <div class="d-flex gap-2">
        <button class="btn btn-ghost btn-sm" onclick="openAjuste(${p.id}, '${escHtml(p.marca + ' ' + p.modelo)}')">📋 Stock</button>
        ${isOwner ? `<button class="btn btn-ghost btn-sm" onclick="editProd(${JSON.stringify(p).replace(/"/g,'&quot;')})">✏️</button>` : ''}
        ${isOwner ? `<button class="btn btn-danger btn-sm" onclick="deleteProd(${p.id})">🗑</button>` : ''}
      </div>`;
    return `<tr>
      <td><code style="font-size:11px">${p.sku}</code></td>
      <td>${p.marca}</td>
      <td>${p.modelo}</td>
      <td>${p.color}</td>
      <td>${p.talla}</td>
      <td style="font-weight:600;color:var(--brand)">${fmt.money(p.precio_venta)}</td>
      <td style="color:var(--gray-500)">${fmt.money(p.costo)}</td>
      <td>${stockBadge}</td>
      <td>${actions}</td>
    </tr>`;
  }).join('');

  document.getElementById('pagination').textContent = `Página ${page} · Mostrando ${prods.length} productos`;
}

function escHtml(s) { return s.replace(/&/g,'&amp;').replace(/"/g,'&quot;'); }

document.getElementById('search-inv').addEventListener('input', function() {
  clearTimeout(searchDelay);
  searchQ = this.value.trim();
  searchDelay = setTimeout(() => loadInventario(1, searchQ), 400);
});

function openModal(clear = true) {
  if (clear) {
    ['prod-id','prod-marca','prod-modelo','prod-color','prod-talla','prod-desc'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('prod-precio').value  = '';
    document.getElementById('prod-costo').value   = '0';
    document.getElementById('prod-stock').value   = '0';
    document.getElementById('prod-stockmin').value = '2';
    document.getElementById('modal-prod-title').textContent = 'Nuevo producto';
    document.getElementById('sku-preview').style.display = 'none';
  }
  Modal.open('modal-producto');
}

function editProd(p) {
  document.getElementById('prod-id').value      = p.id;
  document.getElementById('prod-marca').value   = p.marca;
  document.getElementById('prod-modelo').value  = p.modelo;
  document.getElementById('prod-color').value   = p.color;
  document.getElementById('prod-talla').value   = p.talla;
  document.getElementById('prod-precio').value  = p.precio_venta;
  document.getElementById('prod-costo').value   = p.costo;
  document.getElementById('prod-stock').value   = p.stock;
  document.getElementById('prod-stockmin').value = p.stock_minimo;
  document.getElementById('prod-desc').value    = p.descripcion || '';
  document.getElementById('modal-prod-title').textContent = 'Editar producto';
  document.getElementById('sku-preview').style.display = 'block';
  document.getElementById('sku-val').textContent = p.sku;
  openModal(false);
}

async function saveProd() {
  const payload = {
    id:           parseInt(document.getElementById('prod-id').value) || 0,
    marca:        document.getElementById('prod-marca').value.trim(),
    modelo:       document.getElementById('prod-modelo').value.trim(),
    color:        document.getElementById('prod-color').value.trim(),
    talla:        document.getElementById('prod-talla').value.trim(),
    precio_venta: parseFloat(document.getElementById('prod-precio').value) || 0,
    costo:        parseFloat(document.getElementById('prod-costo').value) || 0,
    stock:        parseInt(document.getElementById('prod-stock').value) || 0,
    stock_minimo: parseInt(document.getElementById('prod-stockmin').value) || 2,
    descripcion:  document.getElementById('prod-desc').value.trim(),
  };
  try {
    const res  = await fetch(`${BASE}/api/productos.php`, {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify(payload), credentials: 'same-origin'
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    Modal.close('modal-producto');
    Toast.success(data.message);
    loadInventario(currentPage, searchQ);
  } catch(e) { Toast.error(e.message); }
}

async function deleteProd(id) {
  if (!await confirmAction('¿Desactivar este producto?')) return;
  try {
    const res  = await fetch(`${BASE}/api/productos.php?id=${id}`, { method: 'DELETE', credentials: 'same-origin' });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    Toast.success('Producto desactivado');
    loadInventario(currentPage, searchQ);
  } catch(e) { Toast.error(e.message); }
}

function openAjuste(id, nombre) {
  document.getElementById('ajuste-prod-id').value   = id;
  document.getElementById('ajuste-prod-name').textContent = nombre;
  document.getElementById('ajuste-cantidad').value  = 1;
  document.getElementById('ajuste-motivo').value    = '';
  Modal.open('modal-ajuste');
}

async function saveAjuste() {
  const payload = {
    producto_id: parseInt(document.getElementById('ajuste-prod-id').value),
    tipo:        document.getElementById('ajuste-tipo').value,
    cantidad:    parseInt(document.getElementById('ajuste-cantidad').value),
    motivo:      document.getElementById('ajuste-motivo').value.trim(),
  };
  try {
    const res  = await fetch(`${BASE}/api/productos.php?action=ajuste_stock`, {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify(payload), credentials:'same-origin'
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    Modal.close('modal-ajuste');
    Toast.success(`Stock actualizado → ${data.stock_nuevo} pzs`);
    loadInventario(currentPage, searchQ);
  } catch(e) { Toast.error(e.message); }
}

loadInventario();
</script>
</body>
</html>
