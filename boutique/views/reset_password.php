<?php
require_once __DIR__ . '/../config/config.php';
$token = htmlspecialchars($_GET['token'] ?? '');
if (!$token) { header('Location: ' . BASE_URL . '/views/login.php'); exit; }
$pageTitle = 'Restablecer contraseña — Mi Boutique';
?>
<!DOCTYPE html><html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
<script>const BASE = '<?= BASE_URL ?>';</script>
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <div class="brand">Mi Boutique</div>
      <div class="sub">Nueva contraseña</div>
    </div>
    <div id="alert-box" style="display:none"></div>
    <div class="form-group">
      <label class="form-label">Nueva contraseña</label>
      <input type="password" id="pass1" class="form-control" placeholder="Mínimo 8 caracteres">
    </div>
    <div class="form-group">
      <label class="form-label">Confirmar contraseña</label>
      <input type="password" id="pass2" class="form-control" placeholder="Repite la contraseña">
    </div>
    <button class="btn btn-primary w-100" onclick="doReset()">Guardar contraseña</button>
    <p style="text-align:center;margin-top:12px">
      <a href="<?= BASE_URL ?>/views/login.php" style="color:var(--gray-500);font-size:13px;text-decoration:none">← Volver al login</a>
    </p>
  </div>
</div>
<script>
const TOKEN = '<?= $token ?>';
async function doReset() {
  const p1 = document.getElementById('pass1').value;
  const p2 = document.getElementById('pass2').value;
  const box = document.getElementById('alert-box');
  box.style.display = 'none';
  if (p1.length < 8) { box.innerHTML='<div class="alert alert-danger">Mínimo 8 caracteres</div>'; box.style.display='block'; return; }
  if (p1 !== p2) { box.innerHTML='<div class="alert alert-danger">Las contraseñas no coinciden</div>'; box.style.display='block'; return; }
  try {
    const res = await fetch(BASE + '/api/auth.php?action=reset', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ token: TOKEN, password: p1 }), credentials:'same-origin'
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    box.innerHTML=`<div class="alert alert-success">Contraseña actualizada. <a href="${BASE}/views/login.php">Inicia sesión</a></div>`;
    box.style.display='block';
  } catch(e) {
    box.innerHTML=`<div class="alert alert-danger">${e.message}</div>`; box.style.display='block';
  }
}
</script>
</body></html>
