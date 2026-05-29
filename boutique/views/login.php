<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/views/pos.php');
    exit;
}
$pageTitle = 'Iniciar sesión — Mi Boutique';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
<script>const BASE = '<?= BASE_URL ?>';</script>
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <div class="brand">Mi Boutique</div>
      <div class="sub">Boutique &middot; Sistema POS</div>
    </div>

    <div id="alert-box" style="display:none"></div>

    <!-- Login form -->
    <div id="form-login">
      <div class="form-group">
        <label class="form-label">Correo electrónico</label>
        <input type="email" id="login-email" class="form-control" placeholder="tu@correo.com" autocomplete="email">
      </div>
      <div class="form-group">
        <label class="form-label">Contraseña</label>
        <div style="position:relative">
          <input type="password" id="login-pass" class="form-control" placeholder="••••••••" autocomplete="current-password">
          <button type="button" onclick="togglePass('login-pass')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--gray-500)">👁</button>
        </div>
      </div>
      <button class="btn btn-primary w-100 btn-lg" id="btn-login" onclick="doLogin()">Entrar</button>
      <p style="text-align:center;margin-top:16px">
        <a href="#" onclick="showForgot()" style="color:var(--brand);font-size:13px;text-decoration:none">¿Olvidaste tu contraseña?</a>
      </p>
    </div>

    <!-- Forgot form -->
    <div id="form-forgot" style="display:none">
      <p style="font-size:14px;color:var(--gray-500);margin-bottom:18px">Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.</p>
      <div class="form-group">
        <label class="form-label">Correo electrónico</label>
        <input type="email" id="forgot-email" class="form-control" placeholder="tu@correo.com">
      </div>
      <button class="btn btn-primary w-100" onclick="doForgot()">Enviar enlace</button>
      <p style="text-align:center;margin-top:12px">
        <a href="#" onclick="showLogin()" style="color:var(--gray-500);font-size:13px;text-decoration:none">← Volver al login</a>
      </p>
    </div>
  </div>
</div>

<script>
function showAlert(msg, type = 'danger') {
  const box = document.getElementById('alert-box');
  box.innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
  box.style.display = 'block';
}
function hideAlert() {
  document.getElementById('alert-box').style.display = 'none';
}
function togglePass(id) {
  const el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
}
function showForgot() {
  document.getElementById('form-login').style.display  = 'none';
  document.getElementById('form-forgot').style.display = 'block';
  hideAlert();
}
function showLogin() {
  document.getElementById('form-login').style.display  = 'block';
  document.getElementById('form-forgot').style.display = 'none';
  hideAlert();
}

async function doLogin() {
  hideAlert();
  const email = document.getElementById('login-email').value.trim();
  const pass  = document.getElementById('login-pass').value;
  if (!email || !pass) return showAlert('Por favor completa todos los campos');

  const btn = document.getElementById('btn-login');
  btn.disabled = true; btn.textContent = 'Entrando…';

  try {
    const res = await fetch(BASE + '/api/auth.php?action=login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password: pass }),
      credentials: 'same-origin',
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error || 'Error al iniciar sesión');

    const rol = data.user.rol;
    if (rol === 'admin' || rol === 'dueno') window.location.href = BASE + '/views/dashboard.php';
    else window.location.href = BASE + '/views/pos.php';
  } catch (e) {
    showAlert(e.message);
    btn.disabled = false; btn.textContent = 'Entrar';
  }
}

async function doForgot() {
  const email = document.getElementById('forgot-email').value.trim();
  if (!email) return showAlert('Ingresa tu correo');

  try {
    await fetch(BASE + '/api/auth.php?action=forgot', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email }),
      credentials: 'same-origin',
    });
    showAlert('Si el correo existe, recibirás un enlace en los próximos minutos.', 'success');
  } catch {
    showAlert('Error al enviar. Intenta de nuevo.');
  }
}

document.addEventListener('keydown', e => {
  if (e.key === 'Enter') {
    if (document.getElementById('form-login').style.display !== 'none') doLogin();
    else doForgot();
  }
});
</script>
</body>
</html>
