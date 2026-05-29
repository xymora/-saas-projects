<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin', 'dueno']);
$pageTitle = 'Configuración — Mi Boutique';
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div class="app-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main-content">
  <header class="topbar">
    <span class="topbar-title">⚙️ Configuración del negocio</span>
  </header>
  <div class="page-body" style="max-width:760px">
    <div id="cfg-alert" style="display:none"></div>

    <!-- Datos del negocio -->
    <div class="card mb-4">
      <div class="card-title">Datos del negocio</div>
      <div class="form-group"><label class="form-label">Nombre del negocio</label><input type="text" id="cfg-nombre" class="form-control"></div>
      <div class="form-group"><label class="form-label">Dirección</label><textarea id="cfg-dir" class="form-control" rows="2"></textarea></div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Teléfono</label><input type="tel" id="cfg-tel" class="form-control"></div>
        <div class="form-group"><label class="form-label">Correo del negocio</label><input type="email" id="cfg-email" class="form-control"></div>
      </div>
      <div class="form-group"><label class="form-label">RFC</label><input type="text" id="cfg-rfc" class="form-control"></div>
      <div class="form-group"><label class="form-label">Leyenda del ticket</label><input type="text" id="cfg-leyenda" class="form-control"></div>
      <button class="btn btn-primary" onclick="saveCfg()">Guardar cambios</button>
    </div>

    <!-- Logo -->
    <div class="card mb-4">
      <div class="card-title">Logo del negocio</div>
      <div id="logo-preview" style="margin-bottom:16px"></div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Ancho (mm, para ticket)</label><input type="number" id="cfg-logo-w" class="form-control" value="40"></div>
        <div class="form-group"><label class="form-label">Alto (mm, para ticket)</label><input type="number" id="cfg-logo-h" class="form-control" value="20"></div>
      </div>
      <div class="form-group">
        <label class="form-label">Subir logo (PNG/JPG/SVG, máx 2MB)</label>
        <input type="file" id="cfg-logo-file" class="form-control" accept=".png,.jpg,.jpeg,.svg">
      </div>
      <button class="btn btn-primary" onclick="uploadLogo()">Subir logo</button>
    </div>

    <!-- Info Clip -->
    <div class="card">
      <div class="card-title">💳 Información de comisiones Clip</div>
      <div class="alert alert-info">
        El sistema aplica automáticamente <strong>3.6% + IVA (16%)</strong> como comisión por venta con Clip.<br>
        Esto equivale a <strong>4.176%</strong> del total cobrado. Esta comisión se muestra en reportes y tickets.
      </div>
      <div style="font-size:13px;color:var(--gray-600)">Ejemplo: venta de $1,000 → comisión Clip: <strong>$41.76</strong></div>
    </div>
  </div>
</div>
</div>

<div id="toast-container"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>


async function loadCfg() {
  const res  = await fetch(`${BASE}/api/configuracion.php`, { credentials: 'same-origin' });
  const data = await res.json();
  if (!data.success) return;
  const c = data.configuracion;
  document.getElementById('cfg-nombre').value  = c.nombre_negocio  || '';
  document.getElementById('cfg-dir').value     = c.direccion       || '';
  document.getElementById('cfg-tel').value     = c.telefono        || '';
  document.getElementById('cfg-email').value   = c.email_negocio   || '';
  document.getElementById('cfg-rfc').value     = c.rfc             || '';
  document.getElementById('cfg-leyenda').value = c.leyenda_ticket  || '';
  document.getElementById('cfg-logo-w').value  = c.logo_width_mm   || '40';
  document.getElementById('cfg-logo-h').value  = c.logo_height_mm  || '20';
  if (c.logo_path) {
    document.getElementById('logo-preview').innerHTML =
      `<img src="${BASE}/${c.logo_path}" alt="Logo" style="max-height:80px;border-radius:8px;border:1px solid var(--gray-200);padding:8px">`;
  }
}

async function saveCfg() {
  const payload = {
    nombre_negocio:  document.getElementById('cfg-nombre').value.trim(),
    direccion:       document.getElementById('cfg-dir').value.trim(),
    telefono:        document.getElementById('cfg-tel').value.trim(),
    email_negocio:   document.getElementById('cfg-email').value.trim(),
    rfc:             document.getElementById('cfg-rfc').value.trim(),
    leyenda_ticket:  document.getElementById('cfg-leyenda').value.trim(),
    logo_width_mm:   document.getElementById('cfg-logo-w').value,
    logo_height_mm:  document.getElementById('cfg-logo-h').value,
  };
  try {
    const res  = await fetch(`${BASE}/api/configuracion.php`, {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload), credentials: 'same-origin'
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    Toast.success('Configuración guardada');
  } catch(e) { Toast.error(e.message); }
}

async function uploadLogo() {
  const file = document.getElementById('cfg-logo-file').files[0];
  if (!file) { Toast.warning('Selecciona un archivo'); return; }
  const form = new FormData();
  form.append('logo', file);
  try {
    const res  = await fetch(`${BASE}/api/configuracion.php`, {
      method: 'POST', body: form, credentials: 'same-origin'
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    Toast.success('Logo actualizado');
    document.getElementById('logo-preview').innerHTML =
      `<img src="${BASE}/${data.logo_path}" alt="Logo" style="max-height:80px;border-radius:8px;border:1px solid var(--gray-200);padding:8px">`;
  } catch(e) { Toast.error(e.message); }
}

loadCfg();
</script>
</body>
</html>
