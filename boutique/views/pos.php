<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$pageTitle = 'Punto de Venta — Mi Boutique';
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<style>
.btn-warning { background:#f59e0b; color:#111827; }
.btn-warning:hover:not(:disabled) { background:#d97706; }
.apt-summary { background:#fdf8f4; border-radius:8px; padding:14px; margin-top:10px; }
.apt-summary-row { display:flex; justify-content:space-between; font-size:14px; color:#374151; padding:3px 0; }
.apt-summary-total { display:flex; justify-content:space-between; font-size:18px; font-weight:800; border-top:1px dashed #d1d5db; margin-top:8px; padding-top:8px; }
.apt-prenda-table { width:100%; border-collapse:collapse; }
.apt-prenda-table th,
.apt-prenda-table td { padding:8px 12px; border-bottom:1px solid #e5e7eb; }
.apt-prenda-table tr:last-child td { border-bottom:none; }
.apt-card-desk { border:1px solid #e5e7eb; border-radius:10px; padding:14px; margin-bottom:10px; cursor:pointer; transition:border-color .15s; }
.apt-card-desk:hover { border-color:#c8956c; }
.apt-card-desk-top { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px; }
.apt-card-desk-money { display:flex; gap:20px; flex-wrap:wrap; }
.apt-money-item { display:flex; flex-direction:column; }
.apt-money-item span:first-child { font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:.5px; }
.apt-money-item span:last-child { font-size:14px; font-weight:700; }
</style>
<div class="app-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main-content">
  <header class="topbar">
    <span class="topbar-title">🛒 Punto de Venta</span>
    <div class="topbar-actions">
      <span id="promo-badge" style="display:none" class="badge badge-success">Promo activa</span>
      <button class="btn btn-ghost btn-sm" onclick="openAptList()">
        📦 Apartados <span id="apt-count-badge" class="badge badge-brand" style="display:none">0</span>
      </button>
      <button class="btn btn-ghost btn-sm" onclick="clearCart()">Limpiar carrito</button>
    </div>
  </header>

  <div class="page-body" style="padding:20px 24px">
    <div class="pos-layout">

      <!-- Izquierda: búsqueda + resultado -->
      <div class="pos-left">
        <!-- Scanner bar -->
        <div class="scanner-bar">
          <span style="font-size:20px">📷</span>
          <input type="text" id="sku-input" class="scanner-input"
                 placeholder="Escanea QR o escribe SKU / nombre del producto…"
                 autocomplete="off" autofocus>
          <button class="scanner-cam-btn" id="btn-cam" onclick="openCamera()">
            Cámara QR
          </button>
        </div>

        <!-- Resultados búsqueda -->
        <div class="card" id="search-results" style="display:none;max-height:260px;overflow-y:auto;padding:0">
          <div id="search-list"></div>
        </div>

        <!-- Cámara QR -->
        <div class="card" id="cam-area" style="display:none">
          <div class="d-flex justify-between align-center mb-4">
            <strong>Escanear QR con cámara</strong>
            <button class="btn btn-ghost btn-sm" onclick="closeCamera()">✕ Cerrar</button>
          </div>
          <video id="qr-video" style="width:100%;border-radius:8px;max-height:300px;object-fit:cover" autoplay muted playsinline></video>
          <canvas id="qr-canvas" style="display:none"></canvas>
          <div id="cam-status" style="text-align:center;margin-top:8px;font-size:13px;color:var(--gray-500)">Apuntando cámara al código QR…</div>
        </div>

        <!-- Info producto escaneado -->
        <div id="product-preview" style="display:none">
          <div class="card d-flex align-center gap-3">
            <div style="font-size:40px">👗</div>
            <div style="flex:1">
              <div style="font-weight:700;font-size:15px" id="pp-name">—</div>
              <div style="font-size:12px;color:var(--gray-500)" id="pp-sku">—</div>
              <div style="font-size:13px;margin-top:4px">
                <span class="badge badge-brand" id="pp-stock">—</span>
                <span style="font-weight:700;color:var(--brand);margin-left:8px" id="pp-price">—</span>
              </div>
            </div>
            <button class="btn btn-primary" onclick="addToCart(currentProduct)">+ Agregar</button>
          </div>
        </div>

        <!-- Promo selector -->
        <div class="card" id="promo-area">
          <div class="d-flex justify-between align-center">
            <span style="font-size:13px;font-weight:600">Promoción</span>
            <select id="promo-select" class="form-control" style="width:auto" onchange="applyPromo()">
              <option value="0">Sin promoción</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Derecha: carrito -->
      <div class="pos-right">
        <div class="cart-header">
          <div style="font-weight:700;font-size:16px">Carrito <span id="cart-count" class="badge badge-gray">0</span></div>
        </div>

        <div class="cart-items" id="cart-items">
          <div class="cart-empty">
            <div class="icon">🛍️</div>
            <div>El carrito está vacío</div>
            <div style="font-size:12px;margin-top:6px;color:var(--gray-400)">Escanea o busca un producto</div>
          </div>
        </div>

        <div class="cart-footer">
          <div class="cart-total-row"><span>Subtotal</span><strong id="sub-total">$0.00</strong></div>
          <div class="cart-total-row" id="row-descuento" style="display:none"><span style="color:var(--danger)">Descuento</span><strong id="desc-total" style="color:var(--danger)">-$0.00</strong></div>
          <div class="cart-total-row total"><span>TOTAL</span><strong id="grand-total">$0.00</strong></div>
          <div style="margin-top:16px;display:flex;flex-direction:column;gap:10px">
            <button class="btn btn-primary btn-lg w-100" onclick="openCheckout('efectivo')">💵 Cobrar en efectivo</button>
            <button class="btn btn-success btn-lg w-100" onclick="openCheckout('clip')">💳 Cobrar con Clip</button>
            <button class="btn btn-warning btn-lg w-100" onclick="openApartado()">📦 Apartar prendas</button>
          </div>
        </div>
      </div>

    </div><!-- pos-layout -->
  </div>
</div><!-- main-content -->
</div><!-- app-layout -->

<!-- Modal: Checkout -->
<div class="modal-overlay" id="modal-checkout">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <span class="modal-title" id="checkout-title">Confirmar cobro</span>
      <button class="modal-close" onclick="Modal.close('modal-checkout')">✕</button>
    </div>
    <div class="modal-body">
      <div id="clip-fields" style="display:none">
        <div class="alert alert-info">
          Cobra en la terminal Clip, luego ingresa el folio del voucher.
        </div>
        <div class="form-group">
          <label class="form-label">Folio Clip *</label>
          <input type="text" id="folio-clip" class="form-control" placeholder="Ej. CLP-2024-XXXXX">
        </div>
        <div class="form-group">
          <label class="form-label">Total a cobrar con Clip</label>
          <div style="font-size:22px;font-weight:800;color:var(--brand)" id="clip-total-display">$0.00</div>
          <div style="font-size:12px;color:var(--gray-500);margin-top:4px">
            Comisión Clip (3.6% + IVA): <strong id="clip-fee-display">$0.00</strong>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Correo del cliente (opcional)</label>
        <input type="email" id="client-email" class="form-control" placeholder="cliente@email.com">
        <div class="form-hint">Si se ingresa, se enviará el ticket por correo.</div>
      </div>
      <div style="background:var(--brand-xlight);border-radius:var(--radius-sm);padding:16px;margin-top:8px">
        <div class="d-flex justify-between" style="font-size:14px;margin-bottom:6px">
          <span>Subtotal</span><strong id="confirm-subtotal">$0.00</strong>
        </div>
        <div class="d-flex justify-between" id="confirm-desc-row" style="font-size:14px;color:var(--danger);display:none!important">
          <span>Descuento</span><strong id="confirm-desc">$0.00</strong>
        </div>
        <div class="d-flex justify-between" style="font-size:18px;font-weight:800;border-top:1px dashed #ccc;margin-top:8px;padding-top:8px">
          <span>TOTAL</span><span id="confirm-total" style="color:var(--brand)">$0.00</span>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="Modal.close('modal-checkout')">Cancelar</button>
      <button class="btn btn-success btn-lg" id="btn-confirm" onclick="confirmSale()">✓ Confirmar venta</button>
    </div>
  </div>
</div>

<!-- Modal: Venta exitosa -->
<div class="modal-overlay" id="modal-success">
  <div class="modal" style="max-width:380px;text-align:center">
    <div class="modal-body" style="padding:40px 32px">
      <div style="font-size:64px;margin-bottom:16px">✅</div>
      <h2 style="color:var(--success);margin-bottom:8px">¡Venta registrada!</h2>
      <div style="font-size:15px;color:var(--gray-700);margin-bottom:4px">Folio: <strong id="success-folio">—</strong></div>
      <div style="font-size:22px;font-weight:800;color:var(--brand);margin-bottom:4px" id="success-total">$0.00</div>
      <div id="success-ticket-msg" style="font-size:13px;color:var(--success);margin-bottom:24px"></div>
      <button class="btn btn-primary w-100" onclick="newSale()">Nueva venta</button>
    </div>
  </div>
</div>

<!-- Modal: Nuevo Apartado ───────────────────────────────── -->
<div class="modal-overlay" id="modal-apartado">
  <div class="modal" style="max-width:520px">
    <div class="modal-header">
      <span class="modal-title">📦 Nuevo Apartado</span>
      <button class="modal-close" onclick="Modal.close('modal-apartado')">✕</button>
    </div>
    <div class="modal-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group">
          <label class="form-label">Nombre del cliente *</label>
          <input type="text" id="apt-nombre" class="form-control" placeholder="Nombre completo" autocomplete="off">
        </div>
        <div class="form-group">
          <label class="form-label">Teléfono (opcional)</label>
          <input type="tel" id="apt-telefono" class="form-control" placeholder="55 1234 5678">
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group">
          <label class="form-label">Anticipo *</label>
          <input type="number" id="apt-monto" class="form-control" placeholder="0.00" min="0" step="0.01">
        </div>
        <div class="form-group">
          <label class="form-label">Vigencia (días)</label>
          <input type="number" id="apt-vigencia" class="form-control" value="30" min="1" max="365">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Notas (opcional)</label>
        <input type="text" id="apt-notas" class="form-control" placeholder="Observaciones..." autocomplete="off">
      </div>
      <label class="form-label">Prendas a apartar</label>
      <div id="apt-prendas-preview" style="background:#f3f4f6;border-radius:8px;overflow:hidden;margin-bottom:12px"></div>
      <div class="apt-summary">
        <div class="apt-summary-row"><span>Total de prendas</span><strong id="apt-confirm-total">$0.00</strong></div>
        <div class="apt-summary-row" style="color:#10b981"><span>Anticipo</span><strong id="apt-confirm-anticipo">$0.00</strong></div>
        <div class="apt-summary-total" style="color:#f59e0b"><span>Resta por pagar</span><span id="apt-confirm-restante">$0.00</span></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="Modal.close('modal-apartado')">Cancelar</button>
      <button class="btn btn-primary" id="btn-confirm-apt" onclick="confirmarApartado()">✓ Guardar apartado</button>
    </div>
  </div>
</div>

<!-- Modal: Apartado exitoso ─────────────────────────────── -->
<div class="modal-overlay" id="modal-apt-success">
  <div class="modal" style="max-width:380px;text-align:center">
    <div class="modal-body" style="padding:40px 32px">
      <div style="font-size:64px;margin-bottom:16px">📦</div>
      <h2 style="color:#c8956c;margin-bottom:8px">¡Apartado registrado!</h2>
      <div style="font-size:15px;color:#374151;margin-bottom:4px">Folio: <strong id="apt-success-folio">—</strong></div>
      <div style="font-size:15px;color:#374151;margin-bottom:4px">Cliente: <strong id="apt-success-nombre">—</strong></div>
      <div style="font-size:22px;font-weight:800;color:#10b981;margin:12px 0" id="apt-success-anticipo"></div>
      <div style="font-size:13px;color:#6b7280;margin-bottom:24px" id="apt-success-vigencia"></div>
      <div style="display:flex;flex-direction:column;gap:10px">
        <button class="btn btn-primary w-100" onclick="Modal.close('modal-apt-success')">Nueva venta</button>
        <button class="btn btn-ghost w-100" onclick="openAptList()">Ver apartados</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Detalle apartado ─────────────────────────────── -->
<div class="modal-overlay" id="modal-apt-detail">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <span class="modal-title" id="apt-detail-title">Apartado</span>
      <button class="modal-close" onclick="Modal.close('modal-apt-detail')">✕</button>
    </div>
    <div class="modal-body" style="max-height:70vh;overflow-y:auto" id="apt-detail-body"></div>
    <div class="modal-footer" id="apt-detail-foot"></div>
  </div>
</div>

<!-- Modal: Lista de apartados ───────────────────────────── -->
<div class="modal-overlay" id="modal-apt-list">
  <div class="modal" style="max-width:720px">
    <div class="modal-header">
      <span class="modal-title">📦 Apartados activos</span>
      <div style="display:flex;align-items:center;gap:10px">
        <button class="btn btn-ghost btn-sm" onclick="loadApartadosList()">↻ Actualizar</button>
        <button class="modal-close" onclick="Modal.close('modal-apt-list')">✕</button>
      </div>
    </div>
    <div class="modal-body" style="max-height:65vh;overflow-y:auto" id="apt-list-body"></div>
  </div>
</div>

<div id="toast-container"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>


// ── State ────────────────────────────────────────────────────
let cart      = [];
let promoId   = 0;
let promoPct  = 0;
let promoFijo = 0;
let payMethod = 'efectivo';
let currentProduct = null;
let searchTimeout;
let qrStream = null;
let scanning = false;

// ── Cargar promociones activas ───────────────────────────────
async function loadPromos() {
  try {
    const res = await fetch(`${BASE}/api/promociones.php?action=activas`, {credentials:'same-origin'});
    const data = await res.json();
    const sel  = document.getElementById('promo-select');
    if (data.success && data.promociones.length) {
      data.promociones.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = `${p.nombre} (${p.tipo === 'porcentaje' ? p.valor + '%' : fmt.money(p.valor)}) ≥ ${fmt.money(p.venta_minima)}`;
        opt.dataset.tipo  = p.tipo;
        opt.dataset.valor = p.valor;
        sel.appendChild(opt);
      });
      document.getElementById('promo-badge').style.display = 'inline-flex';
    } else {
      document.getElementById('promo-area').style.display = 'none';
    }
  } catch {}
}

function applyPromo() {
  const sel = document.getElementById('promo-select');
  const opt = sel.options[sel.selectedIndex];
  promoId = parseInt(sel.value) || 0;
  if (promoId && opt.dataset.tipo === 'porcentaje') {
    promoPct = parseFloat(opt.dataset.valor) || 0;
    promoFijo = 0;
  } else if (promoId && opt.dataset.tipo === 'monto_fijo') {
    promoFijo = parseFloat(opt.dataset.valor) || 0;
    promoPct = 0;
  } else {
    promoPct = 0; promoFijo = 0;
  }
  updateTotals();
}

// ── Búsqueda ─────────────────────────────────────────────────
const skuInput = document.getElementById('sku-input');
skuInput.addEventListener('input', function() {
  clearTimeout(searchTimeout);
  const q = this.value.trim();
  if (!q) { hideSearchResults(); return; }
  searchTimeout = setTimeout(() => searchProduct(q), 350);
});
skuInput.addEventListener('keydown', function(e) {
  if (e.key === 'Enter') {
    clearTimeout(searchTimeout);
    searchProduct(this.value.trim(), true);
  }
});

async function searchProduct(q, exact = false) {
  if (!q) return;
  try {
    const res  = await fetch(`${BASE}/api/productos.php?action=by_sku&sku=${encodeURIComponent(q)}`, {credentials:'same-origin'});
    const data = await res.json();
    if (data.success) {
      showProductPreview(data.producto);
      hideSearchResults();
      return;
    }
  } catch {}

  if (!exact) {
    try {
      const res  = await fetch(`${BASE}/api/productos.php?q=${encodeURIComponent(q)}`, {credentials:'same-origin'});
      const data = await res.json();
      if (data.success && data.productos.length) {
        showSearchResults(data.productos);
        return;
      }
    } catch {}
  }
  hideSearchResults();
}

function showSearchResults(productos) {
  const list = document.getElementById('search-list');
  list.innerHTML = productos.map(p => `
    <div class="cart-item" onclick='addToCart(${JSON.stringify(p)})' style="cursor:pointer">
      <div class="cart-item-info">
        <div class="cart-item-name">${p.marca} ${p.modelo} · ${p.color} T:${p.talla}</div>
        <div class="cart-item-sku">${p.sku}</div>
      </div>
      <div class="d-flex align-center gap-2">
        <span class="badge ${p.stock > 0 ? 'badge-success' : 'badge-danger'}">${p.stock} pzs</span>
        <span style="font-weight:700;color:var(--brand)">${fmt.money(p.precio_venta)}</span>
      </div>
    </div>
  `).join('');
  document.getElementById('search-results').style.display = 'block';
}
function hideSearchResults() { document.getElementById('search-results').style.display = 'none'; }

function showProductPreview(p) {
  currentProduct = p;
  document.getElementById('pp-name').textContent  = `${p.marca} ${p.modelo} · ${p.color} T:${p.talla}`;
  document.getElementById('pp-sku').textContent   = p.sku;
  document.getElementById('pp-stock').textContent = `${p.stock} en stock`;
  document.getElementById('pp-price').textContent = fmt.money(p.precio_venta);
  document.getElementById('product-preview').style.display = 'block';
}

// ── Carrito ──────────────────────────────────────────────────
function addToCart(p) {
  if (p.stock <= 0) { Toast.error('Sin stock disponible'); return; }
  const existing = cart.find(i => i.producto_id === p.id);
  if (existing) {
    if (existing.cantidad >= p.stock) { Toast.warning('Stock máximo alcanzado'); return; }
    existing.cantidad++;
  } else {
    cart.push({ producto_id: p.id, sku: p.sku, nombre: `${p.marca} ${p.modelo} · ${p.color} T:${p.talla}`, precio: parseFloat(p.precio_venta), stock: parseInt(p.stock), cantidad: 1 });
  }
  hideSearchResults();
  document.getElementById('product-preview').style.display = 'none';
  skuInput.value = '';
  skuInput.focus();
  renderCart();
  Toast.success('Producto agregado');
}

function removeFromCart(idx) { cart.splice(idx, 1); renderCart(); }
function changeQty(idx, delta) {
  cart[idx].cantidad += delta;
  if (cart[idx].cantidad <= 0) removeFromCart(idx);
  else if (cart[idx].cantidad > cart[idx].stock) { cart[idx].cantidad = cart[idx].stock; Toast.warning('Stock máximo'); }
  else renderCart();
}

function clearCart() { cart = []; promoId = 0; promoPct = 0; promoFijo = 0; document.getElementById('promo-select').value = '0'; renderCart(); }

function renderCart() {
  const container = document.getElementById('cart-items');
  if (!cart.length) {
    container.innerHTML = `<div class="cart-empty"><div class="icon">🛍️</div><div>El carrito está vacío</div></div>`;
    document.getElementById('cart-count').textContent = '0';
    updateTotals();
    return;
  }
  document.getElementById('cart-count').textContent = cart.reduce((a,i) => a + i.cantidad, 0);
  container.innerHTML = cart.map((item, idx) => `
    <div class="cart-item">
      <div class="cart-item-info">
        <div class="cart-item-name">${item.nombre}</div>
        <div class="cart-item-sku">${item.sku}</div>
      </div>
      <div class="cart-item-qty">
        <button class="qty-btn" onclick="changeQty(${idx}, -1)">−</button>
        <span style="font-weight:600;min-width:20px;text-align:center">${item.cantidad}</span>
        <button class="qty-btn" onclick="changeQty(${idx}, 1)">+</button>
      </div>
      <div class="cart-item-price">${fmt.money(item.precio * item.cantidad)}</div>
      <button onclick="removeFromCart(${idx})" style="background:none;border:none;cursor:pointer;color:var(--gray-400);font-size:18px;padding:4px">✕</button>
    </div>
  `).join('');
  updateTotals();
}

function updateTotals() {
  const sub  = cart.reduce((a, i) => a + i.precio * i.cantidad, 0);
  let desc   = 0;
  if (promoId) {
    if (promoPct)  desc = Math.round(sub * (promoPct / 100) * 100) / 100;
    if (promoFijo) desc = Math.min(promoFijo, sub);
  }
  const total = Math.max(0, sub - desc);
  document.getElementById('sub-total').textContent   = fmt.money(sub);
  document.getElementById('grand-total').textContent = fmt.money(total);
  const rowDesc = document.getElementById('row-descuento');
  if (desc > 0) { rowDesc.style.display = 'flex'; document.getElementById('desc-total').textContent = '-' + fmt.money(desc); }
  else rowDesc.style.display = 'none';
  return { sub, desc, total };
}

// ── Checkout ─────────────────────────────────────────────────
function openCheckout(method) {
  if (!cart.length) { Toast.error('El carrito está vacío'); return; }
  payMethod = method;
  const { sub, desc, total } = updateTotals();
  document.getElementById('checkout-title').textContent = method === 'clip' ? '💳 Cobrar con Clip' : '💵 Cobrar en efectivo';
  document.getElementById('clip-fields').style.display = method === 'clip' ? 'block' : 'none';
  document.getElementById('confirm-subtotal').textContent = fmt.money(sub);
  document.getElementById('confirm-total').textContent    = fmt.money(total);
  const clipFee = method === 'clip' ? Math.round(total * 0.036 * 1.16 * 100) / 100 : 0;
  document.getElementById('clip-total-display').textContent = fmt.money(total);
  document.getElementById('clip-fee-display').textContent   = fmt.money(clipFee);
  const confDesc = document.getElementById('confirm-desc-row');
  if (desc > 0) { confDesc.style.cssText = 'display:flex'; document.getElementById('confirm-desc').textContent = fmt.money(desc); }
  else confDesc.style.cssText = 'display:none!important';
  Modal.open('modal-checkout');
}

async function confirmSale() {
  const btn       = document.getElementById('btn-confirm');
  const folioClip = document.getElementById('folio-clip').value.trim();
  const email     = document.getElementById('client-email').value.trim();

  if (payMethod === 'clip' && !folioClip) { Toast.error('El folio Clip es obligatorio'); return; }

  btn.disabled = true; btn.textContent = 'Procesando…';
  try {
    const payload = {
      carrito:      cart.map(i => ({ producto_id: i.producto_id, cantidad: i.cantidad })),
      metodo_pago:  payMethod,
      folio_clip:   folioClip,
      cliente_email: email || null,
      promocion_id: promoId,
    };
    const res  = await fetch(`${BASE}/api/ventas.php?action=confirmar`, {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload), credentials: 'same-origin',
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);

    Modal.close('modal-checkout');
    document.getElementById('success-folio').textContent = data.folio;
    document.getElementById('success-total').textContent = fmt.money(data.total);
    document.getElementById('success-ticket-msg').textContent = data.ticket_enviado ? '📧 Ticket enviado por correo' : '';
    Modal.open('modal-success');
  } catch(e) {
    Toast.error('Error: ' + e.message);
  } finally {
    btn.disabled = false; btn.textContent = '✓ Confirmar venta';
  }
}

function newSale() {
  Modal.close('modal-success');
  clearCart();
  document.getElementById('client-email').value = '';
  document.getElementById('folio-clip').value   = '';
  skuInput.focus();
}

// ── Cámara QR ────────────────────────────────────────────────
async function openCamera() {
  try {
    qrStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
    document.getElementById('qr-video').srcObject = qrStream;
    document.getElementById('cam-area').style.display = 'block';
    scanning = true;
    requestAnimationFrame(scanFrame);
  } catch(e) {
    Toast.error('No se pudo acceder a la cámara: ' + e.message);
  }
}

function closeCamera() {
  scanning = false;
  if (qrStream) { qrStream.getTracks().forEach(t => t.stop()); qrStream = null; }
  document.getElementById('cam-area').style.display = 'none';
}

async function scanFrame() {
  if (!scanning) return;
  const video  = document.getElementById('qr-video');
  const canvas = document.getElementById('qr-canvas');
  if (video.readyState === video.HAVE_ENOUGH_DATA) {
    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    if ('BarcodeDetector' in window) {
      try {
        const detector = new BarcodeDetector({ formats: ['qr_code', 'code_39'] });
        const codes = await detector.detect(canvas);
        if (codes.length) {
          const sku = codes[0].rawValue;
          document.getElementById('cam-status').textContent = '✓ Código detectado: ' + sku;
          closeCamera();
          skuInput.value = sku;
          searchProduct(sku, true);
          return;
        }
      } catch {}
    }
  }
  requestAnimationFrame(scanFrame);
}

// ── Apartados ────────────────────────────────────────────────
function openApartado() {
  if (!cart.length) { Toast.error('El carrito está vacío'); return; }
  const { total } = updateTotals();
  document.getElementById('apt-prendas-preview').innerHTML = cart.map(i => `
    <div style="display:flex;align-items:center;padding:10px 14px;border-bottom:1px solid #d1d5db;gap:10px">
      <div style="flex:1;min-width:0">
        <div style="font-size:13px;font-weight:600">${i.nombre}</div>
        <div style="font-size:11px;color:#6b7280">${i.sku} × ${i.cantidad}</div>
      </div>
      <span style="font-size:13px;font-weight:700;color:#a8754c">${fmt.money(i.precio * i.cantidad)}</span>
    </div>`).join('');
  document.getElementById('apt-confirm-total').textContent    = fmt.money(total);
  document.getElementById('apt-confirm-anticipo').textContent = fmt.money(0);
  document.getElementById('apt-confirm-restante').textContent = fmt.money(total);
  document.getElementById('apt-monto').value    = '';
  document.getElementById('apt-nombre').value   = '';
  document.getElementById('apt-telefono').value = '';
  document.getElementById('apt-notas').value    = '';
  document.getElementById('apt-vigencia').value = '30';
  Modal.open('modal-apartado');
}

document.getElementById('apt-monto').addEventListener('input', function () {
  const { total } = updateTotals();
  const anticipo  = parseFloat(this.value) || 0;
  document.getElementById('apt-confirm-anticipo').textContent = fmt.money(anticipo);
  document.getElementById('apt-confirm-restante').textContent = fmt.money(Math.max(0, total - anticipo));
});

async function confirmarApartado() {
  const nombre   = document.getElementById('apt-nombre').value.trim();
  const telefono = document.getElementById('apt-telefono').value.trim();
  const monto    = parseFloat(document.getElementById('apt-monto').value) || 0;
  const vigencia = parseInt(document.getElementById('apt-vigencia').value) || 30;
  const notas    = document.getElementById('apt-notas').value.trim();
  const { total } = updateTotals();
  if (!nombre)       { Toast.error('El nombre del cliente es obligatorio'); return; }
  if (monto <= 0)    { Toast.error('El anticipo debe ser mayor a cero'); return; }
  if (monto > total) { Toast.error('El anticipo no puede ser mayor al total'); return; }
  const btn = document.getElementById('btn-confirm-apt');
  btn.disabled = true; btn.textContent = 'Guardando…';
  try {
    const data = await API.post(BASE + '/api/apartados.php?action=crear', {
      nombre_cliente: nombre, telefono_cliente: telefono || null,
      monto_apartado: monto, vigencia_dias: vigencia, notas: notas || null,
      carrito: cart.map(i => ({ producto_id: i.producto_id, cantidad: i.cantidad })),
    });
    Modal.close('modal-apartado');
    document.getElementById('apt-success-folio').textContent    = data.folio;
    document.getElementById('apt-success-nombre').textContent   = nombre;
    document.getElementById('apt-success-anticipo').textContent = fmt.money(monto) + ' anticipo';
    document.getElementById('apt-success-vigencia').textContent = 'Vigente hasta: ' + data.fecha_vigencia;
    Modal.open('modal-apt-success');
    clearCart();
    updateAptBadge();
  } catch (e) {
    Toast.error('Error: ' + e.message);
  } finally {
    btn.disabled = false; btn.textContent = '✓ Guardar apartado';
  }
}

function aptEstadoBadge(estado) {
  const map = { activo:['#d1fae5','#065f46','Activo'], vencido:['#fee2e2','#991b1b','Vencido'],
                completado:['#e0f2fe','#0c4a6e','Completado'], cancelado:['#f3f4f6','#374151','Cancelado'] };
  const [bg,color,label] = map[estado] || ['#f3f4f6','#374151',estado];
  return `<span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:700;background:${bg};color:${color}">${label}</span>`;
}

async function updateAptBadge() {
  try {
    const data  = await API.get(BASE + '/api/apartados.php?action=list');
    const count = data.apartados.length;
    const badge = document.getElementById('apt-count-badge');
    badge.textContent = count;
    badge.style.display = count > 0 ? 'inline-flex' : 'none';
  } catch {}
}

async function openAptList() {
  Modal.close('modal-apt-success');
  Modal.open('modal-apt-list');
  loadApartadosList();
}

async function loadApartadosList() {
  const body = document.getElementById('apt-list-body');
  body.innerHTML = '<div style="text-align:center;padding:32px;color:#6b7280">Cargando…</div>';
  try {
    const data = await API.get(BASE + '/api/apartados.php?action=list');
    updateAptBadge();
    if (!data.apartados.length) {
      body.innerHTML = '<div style="text-align:center;padding:48px;color:#6b7280"><div style="font-size:48px;margin-bottom:12px">📦</div><div style="font-weight:600;font-size:15px">Sin apartados activos</div></div>';
      return;
    }
    body.innerHTML = data.apartados.map(a => {
      const restante = Math.max(0, parseFloat(a.monto_total) - parseFloat(a.monto_apartado));
      return `<div class="apt-card-desk" onclick="viewApartado(${a.id})">
        <div class="apt-card-desk-top">
          <div>
            <div style="font-size:15px;font-weight:700">${a.nombre_cliente}</div>
            <div style="font-size:12px;color:#6b7280;margin-top:2px">${a.folio} &nbsp;·&nbsp; Registrado: ${a.fecha_apartado}</div>
            <div style="font-size:12px;color:#6b7280;margin-top:2px">🧥 ${a.prendas_desc || '—'}</div>
          </div>
          ${aptEstadoBadge(a.estado)}
        </div>
        <div class="apt-card-desk-money">
          <div class="apt-money-item"><span>Total</span><span>${fmt.money(a.monto_total)}</span></div>
          <div class="apt-money-item"><span>Anticipo</span><span style="color:#10b981">${fmt.money(a.monto_apartado)}</span></div>
          <div class="apt-money-item"><span>Resta</span><span style="color:#f59e0b">${fmt.money(restante)}</span></div>
          <div class="apt-money-item"><span>Vence</span><span>📅 ${a.fecha_vigencia}</span></div>
        </div>
      </div>`;
    }).join('');
  } catch { body.innerHTML = '<div class="alert alert-danger">Error al cargar apartados</div>'; }
}

async function viewApartado(id) {
  try {
    const data = await API.get(BASE + '/api/apartados.php?action=get&id=' + id);
    const a    = data.apartado;
    const restante = Math.max(0, parseFloat(a.monto_total) - parseFloat(a.monto_apartado));
    document.getElementById('apt-detail-title').textContent = a.folio;
    const prendas = (a.prendas || []).map(p => `<tr>
      <td><div style="font-weight:600">${p.nombre_producto}</div><div style="font-size:11px;color:#6b7280">${p.sku}</div></td>
      <td style="text-align:center">${p.cantidad}</td>
      <td style="text-align:right">${fmt.money(p.precio_unitario)}</td>
      <td style="text-align:right;font-weight:700">${fmt.money(p.subtotal)}</td>
    </tr>`).join('');
    document.getElementById('apt-detail-body').innerHTML = `
      <div style="margin-bottom:16px">
        <div style="font-size:18px;font-weight:800">${a.nombre_cliente} &nbsp; ${aptEstadoBadge(a.estado)}</div>
        ${a.telefono_cliente ? `<div style="font-size:13px;color:#6b7280;margin-top:4px">📞 ${a.telefono_cliente}</div>` : ''}
        <div style="font-size:12px;color:#6b7280;margin-top:4px">Registrado: ${a.fecha_apartado} por ${a.registrado_por}</div>
      </div>
      <table class="apt-prenda-table" style="margin-bottom:12px">
        <thead><tr style="font-size:11px;color:#6b7280;text-transform:uppercase;background:#f9fafb">
          <th style="text-align:left">Prenda</th><th style="text-align:center">Cant.</th>
          <th style="text-align:right">Precio</th><th style="text-align:right">Subtotal</th>
        </tr></thead>
        <tbody>${prendas}</tbody>
      </table>
      <div class="apt-summary">
        <div class="apt-summary-row"><span>Total</span><strong>${fmt.money(a.monto_total)}</strong></div>
        <div class="apt-summary-row" style="color:#10b981"><span>Anticipo pagado</span><strong>${fmt.money(a.monto_apartado)}</strong></div>
        <div class="apt-summary-total" style="color:#f59e0b"><span>Resta por pagar</span><span>${fmt.money(restante)}</span></div>
      </div>
      ${a.notas ? `<div style="margin-top:12px;font-size:13px;color:#6b7280">📝 ${a.notas}</div>` : ''}
      <div style="margin-top:10px;font-size:13px;color:#6b7280">📅 Vigencia hasta: <strong>${a.fecha_vigencia}</strong></div>`;
    const foot = document.getElementById('apt-detail-foot');
    if (a.estado === 'activo' || a.estado === 'vencido') {
      foot.innerHTML = `
        <button class="btn btn-danger btn-sm" onclick="cancelarApartado(${a.id})">✕ Cancelar apartado</button>
        <button class="btn btn-success" style="margin-left:auto" onclick="completarApartado(${a.id})">✓ Marcar completado</button>`;
    } else {
      foot.innerHTML = `<button class="btn btn-ghost" onclick="Modal.close('modal-apt-detail')">Cerrar</button>`;
    }
    Modal.open('modal-apt-detail');
  } catch { Toast.error('Error al cargar el apartado'); }
}

async function completarApartado(id) {
  if (!confirm('¿Marcar este apartado como completado?')) return;
  try {
    await API.post(BASE + '/api/apartados.php?action=completar', { apartado_id: id });
    Modal.close('modal-apt-detail');
    Toast.success('Apartado completado ✓');
    loadApartadosList();
  } catch (e) { Toast.error('Error: ' + e.message); }
}

async function cancelarApartado(id) {
  if (!confirm('¿Cancelar este apartado?')) return;
  try {
    await API.post(BASE + '/api/apartados.php?action=cancelar', { apartado_id: id });
    Modal.close('modal-apt-detail');
    Toast.success('Apartado cancelado');
    loadApartadosList();
  } catch (e) { Toast.error('Error: ' + e.message); }
}

loadPromos();
updateAptBadge();
</script>
</body>
</html>
