<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$pageTitle = 'Etiquetas QR — Mi Boutique';
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<!-- Barcode font -->
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<style>
@font-face {
  font-family: 'Libre Barcode 39';
  src: url('https://fonts.gstatic.com/s/librebarcode39/v20/-nFnOHM08vwC6h8Li1eQnP_AHzI2K_d709jy92k.woff2') format('woff2');
  font-display: swap;
}
</style>
<div class="app-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main-content">
  <header class="topbar no-print">
    <span class="topbar-title">🏷️ Etiquetas QR</span>
    <div class="topbar-actions">
      <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimir</button>
    </div>
  </header>

  <div class="page-body no-print">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
      <!-- Búsqueda y selección -->
      <div class="card">
        <div class="card-title">Buscar producto</div>
        <div class="form-group">
          <input type="search" id="label-search" class="form-control" placeholder="SKU, marca, modelo…">
        </div>
        <div id="label-results" style="max-height:340px;overflow-y:auto"></div>
      </div>

      <!-- Etiquetas seleccionadas -->
      <div class="card">
        <div class="d-flex justify-between align-center mb-4">
          <div class="card-title" style="margin:0">Etiquetas <span id="label-count" class="badge badge-gray">0</span></div>
          <button class="btn btn-ghost btn-sm" onclick="clearLabels()">Limpiar</button>
        </div>
        <div id="labels-preview"></div>
      </div>
    </div>
  </div>

  <!-- Zona de impresión -->
  <div id="print-zone" class="print-label-page">
    <!-- Labels se insertan aquí -->
  </div>
</div>
</div>

<div id="toast-container"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>

let selectedLabels = [];
let searchTimeout;

document.getElementById('label-search').addEventListener('input', function() {
  clearTimeout(searchTimeout);
  const q = this.value.trim();
  if (!q) { document.getElementById('label-results').innerHTML = ''; return; }
  searchTimeout = setTimeout(() => searchProducts(q), 350);
});

async function searchProducts(q) {
  try {
    const res  = await fetch(`${BASE}/api/productos.php?q=${encodeURIComponent(q)}&page=1`, { credentials: 'same-origin' });
    const data = await res.json();
    if (!data.success) return;

    const container = document.getElementById('label-results');
    if (!data.productos.length) { container.innerHTML = '<p style="color:var(--gray-500);font-size:13px">Sin resultados</p>'; return; }

    container.innerHTML = data.productos.map(p => `
      <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--gray-100)">
        <div>
          <div style="font-size:13px;font-weight:600">${p.marca} ${p.modelo}</div>
          <div style="font-size:11px;color:var(--gray-500)">${p.sku} · ${p.color} · T:${p.talla}</div>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
          <input type="number" value="1" min="1" max="50" style="width:60px" class="form-control" id="qty-${p.id}">
          <button class="btn btn-primary btn-sm" onclick="addLabel(${JSON.stringify(p).replace(/"/g,'&quot;')})">+ Add</button>
        </div>
      </div>
    `).join('');
  } catch(e) { Toast.error('Error al buscar'); }
}

function addLabel(p) {
  const qty = parseInt(document.getElementById(`qty-${p.id}`)?.value) || 1;
  for (let i = 0; i < qty; i++) {
    selectedLabels.push({ sku: p.sku, marca: p.marca, modelo: p.modelo, color: p.color, talla: p.talla, precio: p.precio_venta });
  }
  renderLabels();
  Toast.success(`${qty} etiqueta(s) añadida(s)`);
}

function removeLabel(idx) {
  selectedLabels.splice(idx, 1);
  renderLabels();
}

function clearLabels() {
  selectedLabels = [];
  renderLabels();
}

function renderLabels() {
  document.getElementById('label-count').textContent = selectedLabels.length;

  // Preview
  const preview = document.getElementById('labels-preview');
  if (!selectedLabels.length) {
    preview.innerHTML = '<p style="color:var(--gray-400);font-size:13px">Sin etiquetas seleccionadas</p>';
  } else {
    preview.innerHTML = selectedLabels.map((l, i) => `
      <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--gray-100)">
        <div>
          <div style="font-size:12px;font-weight:700;font-family:'Libre Barcode 39',monospace;font-size:28px;line-height:1">*${l.sku}*</div>
          <div style="font-size:11px;color:var(--gray-600)">${l.marca} ${l.modelo} · ${l.color} · T:${l.talla}</div>
        </div>
        <button onclick="removeLabel(${i})" style="background:none;border:none;cursor:pointer;color:var(--gray-400)">✕</button>
      </div>
    `).join('');
  }

  // Print zone
  const printZone = document.getElementById('print-zone');
  printZone.innerHTML = selectedLabels.map(l => `
    <div class="print-label">
      <div style="font-family:'Libre Barcode 39',monospace;font-size:30px;line-height:1;text-align:center">*${l.sku}*</div>
      <div class="label-text" style="font-size:7pt;text-align:center;line-height:1.3;margin-top:1mm">
        <strong>${l.marca} ${l.modelo}</strong><br>
        ${l.color} · T: ${l.talla}<br>
        <strong>$${parseFloat(l.precio).toFixed(2)}</strong>
      </div>
    </div>
  `).join('');
}

renderLabels();
</script>
</body>
</html>
