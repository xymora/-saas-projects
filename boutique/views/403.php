<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
http_response_code(403);
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<title>Acceso denegado — Mi Boutique</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
</head><body>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;flex-direction:column;text-align:center;padding:40px">
  <div style="font-size:72px;margin-bottom:24px">🚫</div>
  <h1 style="color:var(--brand);margin-bottom:8px">Acceso denegado</h1>
  <p style="color:var(--gray-500);margin-bottom:24px">No tienes permiso para ver esta página.</p>
  <?php if (isLoggedIn()): ?>
    <a href="<?= BASE_URL ?>/views/pos.php" class="btn btn-primary">Ir al POS</a>
  <?php else: ?>
    <a href="<?= BASE_URL ?>/views/login.php" class="btn btn-primary">Iniciar sesión</a>
  <?php endif; ?>
</div>
</body></html>
