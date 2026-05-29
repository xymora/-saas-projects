<?php
require_once __DIR__ . '/../../includes/auth.php';
$u    = currentUser();
$rol  = $u['rol'];
$init = strtoupper(substr($u['nombre'], 0, 1));
$b    = BASE_URL;
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-text">Mi Boutique</div>
    <div class="logo-sub">Boutique POS</div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-label">Principal</div>

    <?php if (in_array($rol, ['admin', 'dueno'])): ?>
    <a href="<?= $b ?>/views/dashboard.php" class="nav-item">
      <span class="nav-icon">📊</span> Dashboard
    </a>
    <?php endif; ?>

    <?php if ($rol === 'admin'): ?>
    <a href="<?= $b ?>/views/admin.php" class="nav-item">
      <span class="nav-icon">🔧</span> Panel Admin
    </a>
    <?php endif; ?>

    <a href="<?= $b ?>/views/pos.php" class="nav-item">
      <span class="nav-icon">🛒</span> Punto de Venta
    </a>

    <a href="<?= $b ?>/views/inventario.php" class="nav-item">
      <span class="nav-icon">📦</span> Inventario
    </a>

    <a href="<?= $b ?>/views/etiquetas.php" class="nav-item">
      <span class="nav-icon">🏷️</span> Etiquetas QR
    </a>

    <?php if (in_array($rol, ['admin', 'dueno'])): ?>
    <div class="nav-label">Gestión</div>

    <a href="<?= $b ?>/views/reportes.php" class="nav-item">
      <span class="nav-icon">📈</span> Reportes
    </a>

    <a href="<?= $b ?>/views/promociones.php" class="nav-item">
      <span class="nav-icon">🎁</span> Promociones
    </a>

    <a href="<?= $b ?>/views/usuarios.php" class="nav-item">
      <span class="nav-icon">👥</span> Usuarios
    </a>

    <a href="<?= $b ?>/views/configuracion.php" class="nav-item">
      <span class="nav-icon">⚙️</span> Configuración
    </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar"><?= $init ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($u['nombre']) ?></div>
        <div class="user-role"><?= $rol ?></div>
      </div>
    </div>
    <button onclick="logout()" class="btn btn-ghost w-100 mt-2" style="color:rgba(255,255,255,.5);border-color:rgba(255,255,255,.1)">
      <span>🚪</span> Cerrar sesión
    </button>
  </div>
</aside>

<!-- Mobile overlay -->
<div class="sidebar-overlay" onclick="document.getElementById('sidebar').classList.remove('open')"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99"></div>
