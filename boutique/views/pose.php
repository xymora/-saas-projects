<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$isLoggedIn = isLoggedIn();
$user       = $isLoggedIn ? currentUser() : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<title>POS Empleados — Mi Boutique</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script>const BASE = '<?= BASE_URL ?>';</script>
<style>
/* ── Variables ─────────────────────────────────────────── */
:root {
  --brand:       #c8956c;
  --brand-dark:  #a8754c;
  --brand-light: #f3e5d8;
  --brand-xl:    #fdf8f4;
  --success:     #10b981;
  --danger:      #ef4444;
  --warning:     #f59e0b;
  --dark:        #1a1a2e;
  --gray-900:    #111827;
  --gray-700:    #374151;
  --gray-500:    #6b7280;
  --gray-300:    #d1d5db;
  --gray-100:    #f3f4f6;
  --white:       #ffffff;
  --radius:      14px;
  --radius-sm:   10px;
  --shadow:      0 2px 16px rgba(0,0,0,.08);
  --shadow-lg:   0 8px 40px rgba(0,0,0,.16);
  --font:        'Inter', system-ui, sans-serif;
  --header-h:    60px;
  --tabbar-h:    64px;
  --safe-top:    env(safe-area-inset-top,    0px);
  --safe-bottom: env(safe-area-inset-bottom, 0px);
}
*,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }
html { font-size:16px; }
body {
  font-family: var(--font);
  background: var(--gray-100);
  color: var(--gray-900);
  line-height: 1.5;
  min-height: 100vh;
  -webkit-tap-highlight-color: transparent;
}

/* ══════════════════════════════════════════════════════════
   LOGIN
══════════════════════════════════════════════════════════ */
#screen-login {
  min-height: 100vh;
  background: linear-gradient(160deg, var(--dark) 0%, #2d1b69 55%, #1a1a2e 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: calc(24px + env(safe-area-inset-top, 0px)) 20px
           calc(24px + env(safe-area-inset-bottom, 0px));
}
.login-card {
  background: var(--white);
  border-radius: 22px;
  padding: 40px 32px;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 24px 80px rgba(0,0,0,.45);
}
.login-logo {
  text-align: center;
  margin-bottom: 32px;
}
.login-logo .brand {
  font-size: 30px;
  font-weight: 800;
  color: var(--brand);
  letter-spacing: .5px;
}
.login-logo .sub {
  font-size: 11px;
  color: var(--gray-500);
  text-transform: uppercase;
  letter-spacing: 2px;
  margin-top: 4px;
}
.login-divider {
  height: 1px;
  background: var(--gray-100);
  margin: 0 -32px 28px;
}

/* ══════════════════════════════════════════════════════════
   POS APP SHELL
══════════════════════════════════════════════════════════ */
#screen-pos {
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow: hidden;
}

/* ── Header ─────────────────────────────────────────────── */
.pos-header {
  height: calc(var(--header-h) + var(--safe-top));
  padding-top: var(--safe-top);
  background: var(--dark);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-left: 16px;
  padding-right: 16px;
  flex-shrink: 0;
  box-shadow: 0 2px 12px rgba(0,0,0,.3);
  position: relative;
  z-index: 10;
}
.pos-header-brand {
  display: flex;
  flex-direction: column;
}
.pos-header-brand .name {
  font-size: 17px;
  font-weight: 800;
  color: var(--brand);
  letter-spacing: .4px;
  line-height: 1.1;
}
.pos-header-brand .role {
  font-size: 10px;
  color: rgba(255,255,255,.4);
  text-transform: uppercase;
  letter-spacing: 1.5px;
}
.pos-header-right {
  display: flex;
  align-items: center;
  gap: 10px;
}
.cart-pill {
  background: var(--brand);
  color: var(--white);
  font-size: 12px;
  font-weight: 700;
  padding: 5px 12px;
  border-radius: 99px;
  cursor: pointer;
  transition: background .2s;
  border: none;
  display: flex;
  align-items: center;
  gap: 6px;
}
.cart-pill:active { background: var(--brand-dark); }
.cart-pill .count {
  background: var(--white);
  color: var(--brand);
  font-size: 11px;
  font-weight: 800;
  min-width: 18px;
  height: 18px;
  border-radius: 99px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
}
.logout-btn {
  background: rgba(255,255,255,.1);
  border: none;
  color: rgba(255,255,255,.7);
  font-size: 11px;
  font-weight: 600;
  padding: 6px 12px;
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: background .2s;
  text-transform: uppercase;
  letter-spacing: .5px;
}
.logout-btn:active { background: rgba(255,255,255,.2); }

/* ── Content area ───────────────────────────────────────── */
.pos-content {
  flex: 1;
  overflow: hidden;
  position: relative;
}

/* ── Panels ─────────────────────────────────────────────── */
.panel {
  position: absolute;
  inset: 0;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  padding: 16px 16px calc(var(--tabbar-h) + var(--safe-bottom) + 16px);
  display: none;
  flex-direction: column;
  gap: 12px;
}
.panel.active { display: flex; }

/* ── Tab bar ────────────────────────────────────────────── */
.pos-tabbar {
  height: calc(var(--tabbar-h) + var(--safe-bottom));
  padding-bottom: var(--safe-bottom);
  background: var(--white);
  border-top: 1px solid var(--gray-100);
  display: flex;
  flex-shrink: 0;
  box-shadow: 0 -2px 16px rgba(0,0,0,.06);
}
.tab-btn {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 3px;
  border: none;
  background: none;
  cursor: pointer;
  color: var(--gray-500);
  font-family: var(--font);
  transition: color .2s;
  position: relative;
  padding: 8px 4px 10px;
}
.tab-btn.active { color: var(--brand); }
.tab-btn .tab-icon { font-size: 20px; line-height: 1; }
.tab-btn .tab-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
.tab-btn.active::after {
  content: '';
  position: absolute;
  top: 0;
  left: 20%;
  right: 20%;
  height: 3px;
  background: var(--brand);
  border-radius: 0 0 4px 4px;
}
.tab-badge {
  position: absolute;
  top: 6px;
  right: calc(50% - 22px);
  background: var(--danger);
  color: var(--white);
  font-size: 10px;
  font-weight: 800;
  min-width: 17px;
  height: 17px;
  border-radius: 99px;
  display: none;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
}
.tab-badge.visible { display: inline-flex; }

/* ══════════════════════════════════════════════════════════
   COMPONENTES REUTILIZABLES
══════════════════════════════════════════════════════════ */
.card {
  background: var(--white);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 16px;
}

/* Formularios */
.form-group { margin-bottom: 16px; }
.form-label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: var(--gray-700);
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: .5px;
}
.form-control {
  width: 100%;
  padding: 12px 14px;
  border: 1.5px solid var(--gray-300);
  border-radius: var(--radius-sm);
  font-size: 16px;
  color: var(--gray-900);
  background: var(--white);
  transition: border-color .2s, box-shadow .2s;
  font-family: var(--font);
  -webkit-appearance: none;
}
.form-control:focus {
  outline: none;
  border-color: var(--brand);
  box-shadow: 0 0 0 3px rgba(200,149,108,.18);
}
.form-hint { font-size: 12px; color: var(--gray-500); margin-top: 5px; }
.pass-wrap { position: relative; }
.pass-wrap .form-control { padding-right: 44px; }
.pass-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: var(--gray-500);
  font-size: 18px;
  padding: 4px;
  line-height: 1;
}

/* Botones */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 13px 20px;
  border-radius: var(--radius-sm);
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  border: none;
  transition: all .2s;
  font-family: var(--font);
  white-space: nowrap;
  -webkit-appearance: none;
}
.btn:disabled { opacity: .5; cursor: not-allowed; }
.btn-primary  { background: var(--brand);   color: var(--white); }
.btn-primary:active:not(:disabled)  { background: var(--brand-dark); }
.btn-success  { background: var(--success); color: var(--white); }
.btn-success:active:not(:disabled)  { background: #059669; }
.btn-ghost    { background: var(--gray-100); color: var(--gray-700); }
.btn-ghost:active:not(:disabled)    { background: var(--gray-300); }
.btn-danger   { background: var(--danger);  color: var(--white); }
.btn-danger:active:not(:disabled)   { background: #dc2626; }
.btn-lg       { padding: 16px 20px; font-size: 16px; }
.btn-sm       { padding: 7px 14px; font-size: 13px; }
.btn-block    { width: 100%; }

/* Alertas */
.alert {
  padding: 12px 14px;
  border-radius: var(--radius-sm);
  font-size: 14px;
  border-left: 4px solid;
  line-height: 1.4;
}
.alert-danger   { background:#fee2e2; border-color:var(--danger);  color:#991b1b; }
.alert-success  { background:#d1fae5; border-color:var(--success); color:#065f46; }
.alert-info     { background:#e0f2fe; border-color:#0ea5e9;        color:#0c4a6e; }
.alert-warning  { background:#fef3c7; border-color:var(--warning); color:#92400e; }

/* Badges */
.badge {
  display: inline-flex;
  align-items: center;
  padding: 3px 8px;
  border-radius: 99px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .4px;
}
.badge-success { background:#d1fae5; color:#065f46; }
.badge-danger  { background:#fee2e2; color:#991b1b; }
.badge-brand   { background:var(--brand-light); color:var(--brand-dark); }
.badge-gray    { background:var(--gray-100);    color:var(--gray-700); }

/* ══════════════════════════════════════════════════════════
   PANEL BUSCAR
══════════════════════════════════════════════════════════ */
/* Scanner bar */
.scanner-bar {
  background: var(--white);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 12px 14px;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}
.scanner-icon { font-size: 22px; flex-shrink: 0; }
.scanner-input {
  flex: 1;
  min-width: 120px;
  border: none;
  outline: none;
  font-size: 16px;
  font-family: var(--font);
  color: var(--gray-900);
  background: transparent;
}
.scanner-input::placeholder { color: var(--gray-300); }
.scanner-cam-btn {
  flex-shrink: 0;
  padding: 8px 14px;
  border-radius: var(--radius-sm);
  background: var(--dark);
  color: var(--white);
  border: none;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  font-family: var(--font);
  display: flex;
  align-items: center;
  gap: 6px;
  transition: background .2s;
}
.scanner-cam-btn:active { background: #2d1b69; }

/* Promo bar */
.promo-bar {
  background: var(--white);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 12px 14px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.promo-label {
  font-size: 12px;
  font-weight: 700;
  color: var(--gray-700);
  text-transform: uppercase;
  letter-spacing: .5px;
  white-space: nowrap;
  flex-shrink: 0;
}
.promo-select {
  flex: 1;
  padding: 8px 32px 8px 10px;
  border: 1.5px solid var(--gray-300);
  border-radius: var(--radius-sm);
  font-size: 13px;
  color: var(--gray-900);
  background: var(--white);
  font-family: var(--font);
  -webkit-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 8px center;
  background-size: 16px;
}
.promo-select:focus { outline: none; border-color: var(--brand); }

/* Producto preview */
.product-card {
  background: var(--white);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 14px;
}
.product-card-icon { font-size: 38px; flex-shrink: 0; }
.product-card-info { flex: 1; min-width: 0; }
.product-card-name {
  font-size: 14px;
  font-weight: 700;
  color: var(--gray-900);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.product-card-sku  { font-size: 11px; color: var(--gray-500); margin-top: 2px; }
.product-card-meta { display: flex; align-items: center; gap: 8px; margin-top: 6px; }
.product-card-price { font-weight: 800; color: var(--brand); font-size: 15px; }

/* Lista de resultados */
.search-results {
  background: var(--white);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  overflow: hidden;
  display: none;
}
.search-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid var(--gray-100);
  cursor: pointer;
  transition: background .15s;
  -webkit-tap-highlight-color: transparent;
}
.search-item:last-child { border-bottom: none; }
.search-item:active { background: var(--brand-xl); }
.search-item-info { flex: 1; min-width: 0; }
.search-item-name  { font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.search-item-sku   { font-size: 11px; color: var(--gray-500); margin-top: 2px; }
.search-item-right { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0; }
.search-item-price { font-size: 14px; font-weight: 800; color: var(--brand-dark); }

/* Cámara QR */
.cam-card {
  background: var(--white);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 16px;
  display: none;
}
.cam-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
.cam-title { font-size: 14px; font-weight: 700; }
#qr-video {
  width: 100%;
  border-radius: var(--radius-sm);
  max-height: 280px;
  object-fit: cover;
  display: block;
}
#cam-status {
  text-align: center;
  margin-top: 10px;
  font-size: 12px;
  color: var(--gray-500);
}

/* ══════════════════════════════════════════════════════════
   PANEL CARRITO
══════════════════════════════════════════════════════════ */
.cart-panel-inner {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-height: 100%;
}

/* Items */
.cart-empty-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 24px;
  color: var(--gray-500);
  text-align: center;
  gap: 10px;
}
.cart-empty-state .icon { font-size: 52px; }
.cart-empty-state .msg  { font-size: 15px; font-weight: 600; }
.cart-empty-state .sub  { font-size: 13px; color: var(--gray-300); }

.cart-list {
  background: var(--white);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  overflow: hidden;
}
.cart-list-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  border-bottom: 1px solid var(--gray-100);
}
.cart-list-item:last-child { border-bottom: none; }
.cli-info { flex: 1; min-width: 0; }
.cli-name { font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cli-sku  { font-size: 11px; color: var(--gray-500); margin-top: 2px; }
.cli-qty  { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.qty-btn {
  width: 28px; height: 28px;
  border-radius: 8px;
  border: 1.5px solid var(--gray-300);
  background: none;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 16px;
  color: var(--gray-700);
  transition: all .15s;
  -webkit-tap-highlight-color: transparent;
}
.qty-btn:active { background: var(--brand); border-color: var(--brand); color: var(--white); }
.cli-qty-num { font-size: 14px; font-weight: 700; min-width: 22px; text-align: center; }
.cli-price { font-size: 13px; font-weight: 700; color: var(--brand-dark); white-space: nowrap; flex-shrink: 0; }
.cli-remove {
  background: none; border: none;
  color: var(--gray-300);
  font-size: 18px;
  cursor: pointer;
  padding: 4px;
  line-height: 1;
  flex-shrink: 0;
  transition: color .15s;
}
.cli-remove:active { color: var(--danger); }

/* Totales */
.cart-totals {
  background: var(--white);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 16px;
}
.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 14px;
  color: var(--gray-700);
  padding: 4px 0;
}
.total-row.grand {
  font-size: 20px;
  font-weight: 800;
  color: var(--gray-900);
  border-top: 1.5px dashed var(--gray-300);
  margin-top: 10px;
  padding-top: 12px;
}
.total-row.descuento { color: var(--danger); }

/* Botones cobrar */
.cobrar-btns {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

/* ══════════════════════════════════════════════════════════
   MODALS
══════════════════════════════════════════════════════════ */
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.55);
  backdrop-filter: blur(4px);
  z-index: 300;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  opacity: 0;
  pointer-events: none;
  transition: opacity .25s;
}
.modal-overlay.open { opacity: 1; pointer-events: all; }
.modal-sheet {
  background: var(--white);
  border-radius: 20px 20px 0 0;
  width: 100%;
  max-width: 560px;
  max-height: 92vh;
  overflow-y: auto;
  transform: translateY(100%);
  transition: transform .3s cubic-bezier(.4,0,.2,1);
  padding-bottom: env(safe-area-inset-bottom, 12px);
}
.modal-overlay.open .modal-sheet { transform: translateY(0); }
.modal-handle {
  width: 40px; height: 4px;
  background: var(--gray-300);
  border-radius: 99px;
  margin: 12px auto 0;
}
.modal-head {
  padding: 16px 20px;
  border-bottom: 1px solid var(--gray-100);
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.modal-title { font-size: 17px; font-weight: 700; }
.modal-close {
  background: none; border: none;
  font-size: 22px;
  cursor: pointer;
  color: var(--gray-500);
  line-height: 1;
  padding: 2px 6px;
}
.modal-body { padding: 20px; }
.modal-foot {
  padding: 16px 20px;
  border-top: 1px solid var(--gray-100);
  display: flex;
  gap: 10px;
}
.modal-foot .btn { flex: 1; }

/* Summary box */
.summary-box {
  background: var(--brand-xl);
  border-radius: var(--radius-sm);
  padding: 14px;
  margin-top: 8px;
}
.summary-row {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  color: var(--gray-700);
  padding: 3px 0;
}
.summary-total {
  font-size: 22px;
  font-weight: 800;
  color: var(--brand);
  border-top: 1px dashed var(--gray-300);
  margin-top: 10px;
  padding-top: 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

/* ══════════════════════════════════════════════════════════
   TOAST
══════════════════════════════════════════════════════════ */
#toast-container {
  position: fixed;
  top: calc(var(--header-h) + 10px);
  left: 50%;
  transform: translateX(-50%);
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  pointer-events: none;
  width: calc(100% - 32px);
  max-width: 380px;
}
.toast {
  background: var(--gray-900);
  color: var(--white);
  padding: 11px 16px;
  border-radius: var(--radius-sm);
  font-size: 14px;
  font-weight: 500;
  box-shadow: var(--shadow-lg);
  width: 100%;
  text-align: center;
  animation: toastIn .3s ease;
}
.toast.success { background: var(--success); }
.toast.error   { background: var(--danger); }
.toast.warning { background: var(--warning); color: var(--gray-900); }
@keyframes toastIn { from { transform: translateY(-16px); opacity:0; } to { transform: translateY(0); opacity:1; } }

/* ══════════════════════════════════════════════════════════
   UTILIDADES
══════════════════════════════════════════════════════════ */
.d-none   { display: none !important; }
.text-center { text-align: center; }
.mt-1 { margin-top: 4px; }
.mt-2 { margin-top: 8px; }
.mt-3 { margin-top: 14px; }
.fw-bold { font-weight: 700; }
.text-muted { color: var(--gray-500); }
.text-sm { font-size: 13px; }

/* ══════════════════════════════════════════════════════════
   BOTÓN APARTAR
══════════════════════════════════════════════════════════ */
.btn-warning { background: var(--warning); color: var(--gray-900); }
.btn-warning:active:not(:disabled) { background: #d97706; }

/* ══════════════════════════════════════════════════════════
   PANEL APARTADOS
══════════════════════════════════════════════════════════ */
.apt-header { display:flex; align-items:center; justify-content:space-between; }
.apt-header-title { font-size:15px; font-weight:700; color:var(--gray-900); }
.apt-card {
  background: var(--white); border-radius: var(--radius);
  box-shadow: var(--shadow); padding: 14px 16px;
  cursor: pointer; -webkit-tap-highlight-color: transparent; transition: box-shadow .15s;
}
.apt-card:active { box-shadow: var(--shadow-lg); }
.apt-card-top { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:6px; }
.apt-card-name { font-size:15px; font-weight:700; color:var(--gray-900); flex:1; min-width:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.apt-card-folio { font-size:11px; color:var(--gray-500); margin-top:2px; }
.apt-card-prendas { font-size:12px; color:var(--gray-700); margin-bottom:10px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.apt-money-row { display:flex; gap:8px; margin-bottom:10px; }
.apt-money-box { flex:1; background:var(--gray-100); border-radius:var(--radius-sm); padding:8px; text-align:center; }
.apt-money-label { font-size:10px; font-weight:600; color:var(--gray-500); text-transform:uppercase; letter-spacing:.5px; display:block; margin-bottom:2px; }
.apt-money-value { font-size:14px; font-weight:800; color:var(--gray-900); }
.apt-money-value.anticipo { color:var(--success); }
.apt-money-value.restante { color:var(--warning); }
.apt-card-footer { display:flex; align-items:center; justify-content:space-between; }
.apt-vigencia { font-size:12px; color:var(--gray-500); }
.badge-activo     { background:#d1fae5; color:#065f46; }
.badge-vencido    { background:#fee2e2; color:#991b1b; }
.badge-completado { background:#e0f2fe; color:#0c4a6e; }
.badge-cancelado  { background:var(--gray-100); color:var(--gray-500); }
.apt-empty { text-align:center; padding:48px 24px; color:var(--gray-500); }
.apt-empty .icon { font-size:52px; display:block; margin-bottom:12px; }
.apt-empty .msg  { font-size:15px; font-weight:600; margin-bottom:6px; }
.apt-empty .sub  { font-size:13px; color:var(--gray-300); }
.apt-detail-prendas { background:var(--gray-100); border-radius:var(--radius-sm); overflow:hidden; margin-bottom:14px; }
.apt-prenda-row { display:flex; align-items:center; padding:10px 14px; border-bottom:1px solid var(--gray-300); gap:8px; }
.apt-prenda-row:last-child { border-bottom:none; }
.apt-prenda-info { flex:1; min-width:0; }
.apt-prenda-name { font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.apt-prenda-sku  { font-size:11px; color:var(--gray-500); margin-top:2px; }
.apt-prenda-price { font-size:13px; font-weight:700; color:var(--brand-dark); flex-shrink:0; }
</style>
</head>
<body>

<div id="screen-login" <?= $isLoggedIn ? 'style="display:none"' : '' ?>>
  <div class="login-card">

    <div class="login-logo">
      <div class="brand">Mi Boutique</div>
      <div class="sub">Boutique &middot; Empleados</div>
    </div>

    <div class="login-divider"></div>

    <div id="login-alert" class="d-none"></div>

    <div id="form-login">
      <div class="form-group">
        <label class="form-label">Correo electrónico</label>
        <input type="email" id="login-email" class="form-control"
               placeholder="tu@correo.com"
               autocomplete="email"
               inputmode="email">
      </div>

      <div class="form-group">
        <label class="form-label">Contraseña</label>
        <div class="pass-wrap">
          <input type="password" id="login-pass" class="form-control"
                 placeholder="••••••••"
                 autocomplete="current-password">
          <button type="button" class="pass-toggle" onclick="togglePass('login-pass')" aria-label="Mostrar contraseña">👁</button>
        </div>
      </div>

      <button class="btn btn-primary btn-lg btn-block" id="btn-login" onclick="doLogin()">
        Iniciar sesión
      </button>

      <p class="text-center mt-3">
        <a href="#" onclick="showForgot(); return false;"
           style="color:var(--brand);font-size:13px;text-decoration:none;font-weight:600">
          ¿Olvidaste tu contraseña?
        </a>
      </p>
    </div>

    <div id="form-forgot" class="d-none">
      <p class="text-sm text-muted mt-1" style="margin-bottom:18px;line-height:1.6">
        Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.
      </p>

      <div class="form-group">
        <label class="form-label">Correo electrónico</label>
        <input type="email" id="forgot-email" class="form-control"
               placeholder="tu@correo.com"
               inputmode="email">
      </div>

      <button class="btn btn-primary btn-block" onclick="doForgot()">
        Enviar enlace
      </button>

      <p class="text-center mt-3">
        <a href="#" onclick="showLogin(); return false;"
           style="color:var(--gray-500);font-size:13px;text-decoration:none">
          ← Volver al login
        </a>
      </p>
    </div>

  </div>
</div>

<div id="screen-pos" <?= !$isLoggedIn ? 'style="display:none"' : '' ?>>

  <header class="pos-header">
    <div class="pos-header-brand">
      <span class="name">Mi Boutique</span>
      <span class="role">Punto de Venta</span>
    </div>
    <div class="pos-header-right">
      <button class="cart-pill" id="header-cart-pill" onclick="switchTab('carrito')">
        🛒 <span class="count" id="header-cart-count">0</span>
      </button>
      <button class="logout-btn" onclick="doLogout()">Salir</button>
    </div>
  </header>

  <div class="pos-content">

    <div class="panel active" id="panel-buscar">

      <div class="scanner-bar">
        <span class="scanner-icon">📷</span>
        <input type="text" id="sku-input" class="scanner-input"
               placeholder="SKU o nombre del producto…"
               autocomplete="off"
               autocorrect="off"
               autocapitalize="none"
               spellcheck="false"
               autofocus>
        <button class="scanner-cam-btn" onclick="openCamera()">
          📹 Cámara
        </button>
      </div>

      <div class="cam-card" id="cam-area">
        <div class="cam-header">
          <span class="cam-title">Escanear código QR</span>
          <button class="btn btn-ghost btn-sm" onclick="closeCamera()">✕ Cerrar</button>
        </div>
        <video id="qr-video" autoplay muted playsinline></video>
        <canvas id="qr-canvas" style="display:none"></canvas>
        <p id="cam-status">Apunta la cámara al código QR…</p>
      </div>

      <div class="promo-bar" id="promo-area" style="display:none">
        <span class="promo-label">Promo</span>
        <select id="promo-select" class="promo-select" onchange="applyPromo()">
          <option value="0">Sin promoción</option>
        </select>
      </div>

      <div class="product-card" id="product-preview" style="display:none">
        <span class="product-card-icon">👗</span>
        <div class="product-card-info">
          <div class="product-card-name" id="pp-name">—</div>
          <div class="product-card-sku"  id="pp-sku">—</div>
          <div class="product-card-meta">
            <span class="badge badge-brand" id="pp-stock">—</span>
            <span class="product-card-price" id="pp-price">—</span>
          </div>
        </div>
        <button class="btn btn-primary btn-sm" onclick="addToCart(currentProduct)">
          + Agregar
        </button>
      </div>

      <div class="search-results" id="search-results">
        <div id="search-list"></div>
      </div>

    </div><div class="panel" id="panel-carrito">
      <div class="cart-panel-inner">

        <div class="cart-empty-state" id="cart-empty">
          <div class="icon">🛍️</div>
          <div class="msg">El carrito está vacío</div>
          <div class="sub">Busca productos en la pestaña anterior</div>
        </div>

        <div class="cart-list d-none" id="cart-list">
          <div id="cart-items-container"></div>
        </div>

        <div class="cart-totals d-none" id="cart-totals">
          <div class="total-row">
            <span>Subtotal</span>
            <strong id="sub-total">$0.00</strong>
          </div>
          <div class="total-row descuento d-none" id="row-desc">
            <span>Descuento</span>
            <strong id="desc-total">-$0.00</strong>
          </div>
          <div class="total-row grand">
            <span>TOTAL</span>
            <strong id="grand-total">$0.00</strong>
          </div>
        </div>

        <div class="cobrar-btns d-none" id="cobrar-btns">
          <button class="btn btn-primary btn-lg btn-block" onclick="openCheckout('efectivo')">
            💵 Cobrar en efectivo
          </button>
          <button class="btn btn-success btn-lg btn-block" onclick="openCheckout('clip')">
            💳 Cobrar con Clip
          </button>
          <button class="btn btn-warning btn-lg btn-block" onclick="openApartado()">
            📦 Apartar prendas
          </button>
          <button class="btn btn-ghost btn-sm btn-block" onclick="clearCart()">
            🗑 Limpiar carrito
          </button>
        </div>

      </div>
    </div><div class="panel" id="panel-apartados">

      <div class="apt-header">
        <span class="apt-header-title">Apartados activos</span>
        <button class="btn btn-ghost btn-sm" onclick="loadApartados()">↻ Actualizar</button>
      </div>

      <div id="apt-list-container">
        <div class="apt-empty">
          <span class="icon">📦</span>
          <div class="msg">Sin apartados activos</div>
          <div class="sub">Agrega prendas al carrito y toca «Apartar»</div>
        </div>
      </div>

    </div></div><nav class="pos-tabbar">
    <button class="tab-btn active" id="tab-buscar" onclick="switchTab('buscar')">
      <span class="tab-icon">🔍</span>
      <span class="tab-label">Buscar</span>
    </button>
    <button class="tab-btn" id="tab-carrito" onclick="switchTab('carrito')">
      <span class="tab-icon">🛒</span>
      <span class="tab-label">Carrito</span>
      <span class="tab-badge" id="cart-badge"></span>
    </button>
    <button class="tab-btn" id="tab-apartados" onclick="switchTab('apartados')">
      <span class="tab-icon">📦</span>
      <span class="tab-label">Apartados</span>
      <span class="tab-badge" id="apt-badge"></span>
    </button>
  </nav>

</div><div class="modal-overlay" id="modal-checkout">
  <div class="modal-sheet">
    <div class="modal-handle"></div>

    <div class="modal-head">
      <span class="modal-title" id="checkout-title">Confirmar cobro</span>
      <button class="modal-close" onclick="Modal.close('modal-checkout')">✕</button>
    </div>

    <div class="modal-body">
      <div id="clip-fields" class="d-none">
        <div class="alert alert-info" style="margin-bottom:14px">
          Cobra en la terminal Clip y luego ingresa el folio del voucher.
        </div>
        <div class="form-group">
          <label class="form-label">Folio Clip *</label>
          <input type="text" id="folio-clip" class="form-control"
                 placeholder="Ej. CLP-2024-XXXXX"
                 autocomplete="off">
        </div>
        <div class="form-group">
          <label class="form-label">Comisión Clip (3.6% + IVA)</label>
          <p class="text-sm" style="padding:8px 0">
            <strong id="clip-fee-display">$0.00</strong>
          </p>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Correo del cliente <span class="text-muted fw-bold">(opcional)</span></label>
        <input type="email" id="client-email" class="form-control"
               placeholder="cliente@email.com"
               inputmode="email"
               autocomplete="off">
        <p class="form-hint">Si se ingresa, se enviará el ticket por correo.</p>
      </div>

      <div class="summary-box">
        <div class="summary-row">
          <span>Subtotal</span>
          <strong id="confirm-subtotal">$0.00</strong>
        </div>
        <div class="summary-row d-none" id="confirm-desc-row" style="color:var(--danger)">
          <span>Descuento</span>
          <strong id="confirm-desc">$0.00</strong>
        </div>
        <div class="summary-total">
          <span>TOTAL</span>
          <span id="confirm-total">$0.00</span>
        </div>
      </div>
    </div>

    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="Modal.close('modal-checkout')">Cancelar</button>
      <button class="btn btn-success" id="btn-confirm" onclick="confirmSale()">✓ Confirmar venta</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modal-success">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-body text-center" style="padding:40px 28px">
      <div style="font-size:64px;margin-bottom:16px">✅</div>
      <h2 style="color:var(--success);margin-bottom:8px;font-size:22px">¡Venta registrada!</h2>
      <div style="font-size:14px;color:var(--gray-700);margin-bottom:4px">
        Folio: <strong id="success-folio">—</strong>
      </div>
      <div style="font-size:28px;font-weight:800;color:var(--brand);margin:12px 0" id="success-total">$0.00</div>
      <div id="success-ticket-msg" style="font-size:13px;color:var(--success);margin-bottom:24px"></div>
      <button class="btn btn-primary btn-lg btn-block" onclick="newSale()">
        Nueva venta
      </button>
    </div>
  </div>
</div>

<!-- Modal: Nuevo Apartado ─────────────────────────────── -->
<div class="modal-overlay" id="modal-apartado">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-head">
      <span class="modal-title">📦 Nuevo Apartado</span>
      <button class="modal-close" onclick="Modal.close('modal-apartado')">✕</button>
    </div>
    <div class="modal-body">

      <div class="form-group">
        <label class="form-label">Nombre del cliente *</label>
        <input type="text" id="apt-nombre" class="form-control"
               placeholder="Nombre completo" autocomplete="off">
      </div>

      <div class="form-group">
        <label class="form-label">Teléfono <span class="text-muted">(opcional)</span></label>
        <input type="tel" id="apt-telefono" class="form-control"
               placeholder="55 1234 5678" inputmode="tel">
      </div>

      <div style="display:flex;gap:10px">
        <div class="form-group" style="flex:1">
          <label class="form-label">Anticipo *</label>
          <input type="number" id="apt-monto" class="form-control"
                 placeholder="0.00" min="0" step="0.01" inputmode="decimal">
        </div>
        <div class="form-group" style="flex:1">
          <label class="form-label">Vigencia (días)</label>
          <input type="number" id="apt-vigencia" class="form-control"
                 value="30" min="1" max="365" inputmode="numeric">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Notas <span class="text-muted">(opcional)</span></label>
        <input type="text" id="apt-notas" class="form-control"
               placeholder="Observaciones..." autocomplete="off">
      </div>

      <div class="form-label" style="margin-bottom:8px">Prendas a apartar</div>
      <div class="apt-detail-prendas" id="apt-prendas-preview"></div>

      <div class="summary-box">
        <div class="summary-row">
          <span>Total de prendas</span>
          <strong id="apt-confirm-total">$0.00</strong>
        </div>
        <div class="summary-row" style="color:var(--success)">
          <span>Anticipo</span>
          <strong id="apt-confirm-anticipo">$0.00</strong>
        </div>
        <div class="summary-total" style="color:var(--warning);font-size:18px">
          <span>Resta por pagar</span>
          <span id="apt-confirm-restante">$0.00</span>
        </div>
      </div>

    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="Modal.close('modal-apartado')">Cancelar</button>
      <button class="btn btn-primary" id="btn-confirm-apt" onclick="confirmarApartado()">✓ Guardar apartado</button>
    </div>
  </div>
</div>

<!-- Modal: Apartado registrado ────────────────────────── -->
<div class="modal-overlay" id="modal-apt-success">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-body text-center" style="padding:40px 28px">
      <div style="font-size:64px;margin-bottom:16px">📦</div>
      <h2 style="color:var(--brand);margin-bottom:8px;font-size:22px">¡Apartado registrado!</h2>
      <div style="font-size:14px;color:var(--gray-700);margin-bottom:2px">
        Folio: <strong id="apt-success-folio">—</strong>
      </div>
      <div style="font-size:14px;color:var(--gray-700);margin-bottom:2px">
        Cliente: <strong id="apt-success-nombre">—</strong>
      </div>
      <div style="font-size:24px;font-weight:800;color:var(--success);margin:14px 0" id="apt-success-anticipo"></div>
      <div style="font-size:13px;color:var(--gray-500);margin-bottom:24px" id="apt-success-vigencia"></div>
      <button class="btn btn-primary btn-lg btn-block" onclick="newSaleAfterApt()">
        Nueva venta
      </button>
      <button class="btn btn-ghost btn-block" style="margin-top:10px" onclick="goToApartados()">
        Ver apartados
      </button>
    </div>
  </div>
</div>

<!-- Modal: Detalle de apartado ────────────────────────── -->
<div class="modal-overlay" id="modal-apt-detail">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-head">
      <span class="modal-title" id="apt-detail-title">Apartado</span>
      <button class="modal-close" onclick="Modal.close('modal-apt-detail')">✕</button>
    </div>
    <div class="modal-body" id="apt-detail-body"></div>
    <div class="modal-foot" id="apt-detail-foot"></div>
  </div>
</div>

<div id="toast-container"></div>

<script>
/* ── Helpers globales ──────────────────────────────────── */
const fmt = {
  money: v => '$' + parseFloat(v).toLocaleString('es-MX', { minimumFractionDigits:2, maximumFractionDigits:2 })
};

const Toast = {
  show(msg, type = '', ms = 3000) {
    const t = document.createElement('div');
    t.className = 'toast' + (type ? ' ' + type : '');
    t.textContent = msg;
    document.getElementById('toast-container').appendChild(t);
    setTimeout(() => t.remove(), ms);
  },
  success(m) { this.show(m, 'success'); },
  error(m)   { this.show(m, 'error'); },
  warning(m) { this.show(m, 'warning'); },
};

const Modal = {
  open(id)  { document.getElementById(id).classList.add('open'); },
  close(id) { document.getElementById(id).classList.remove('open'); },
};

/* ── Navegación de pantallas ───────────────────────────── */
function showScreen(name) {
  document.getElementById('screen-login').style.display = name === 'login' ? 'flex' : 'none';
  document.getElementById('screen-pos').style.display   = name === 'pos'   ? 'flex' : 'none';
  if (name === 'pos') {
    loadPromos();
    loadApartados();
    document.getElementById('sku-input').focus();
  }
}

/* ── Pestañas ──────────────────────────────────────────── */
function switchTab(name) {
  document.querySelectorAll('.tab-btn').forEach(b  => b.classList.remove('active'));
  document.querySelectorAll('.panel').forEach(p    => p.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  document.getElementById('panel-' + name).classList.add('active');
  if (name === 'buscar') {
    setTimeout(() => document.getElementById('sku-input').focus(), 100);
  }
  if (name === 'apartados') loadApartados();
}

/* ══════════════════════════════════════════════════════════
   LOGIN / AUTH
══════════════════════════════════════════════════════════ */
function showLoginAlert(msg, type = 'danger') {
  const box = document.getElementById('login-alert');
  box.innerHTML = `<div class="alert alert-${type}" style="margin-bottom:16px">${msg}</div>`;
  box.classList.remove('d-none');
}
function hideLoginAlert() {
  document.getElementById('login-alert').classList.add('d-none');
}
function togglePass(id) {
  const el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
}
function showForgot() {
  document.getElementById('form-login').classList.add('d-none');
  document.getElementById('form-forgot').classList.remove('d-none');
  hideLoginAlert();
}
function showLogin() {
  document.getElementById('form-forgot').classList.add('d-none');
  document.getElementById('form-login').classList.remove('d-none');
  hideLoginAlert();
}

async function doLogin() {
  hideLoginAlert();
  const email = document.getElementById('login-email').value.trim();
  const pass  = document.getElementById('login-pass').value;
  if (!email || !pass) { showLoginAlert('Por favor completa todos los campos.'); return; }

  const btn = document.getElementById('btn-login');
  btn.disabled = true;
  btn.textContent = 'Entrando…';

  try {
    const res  = await fetch(BASE + '/api/auth.php?action=login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password: pass }),
      credentials: 'same-origin',
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error || 'Credenciales incorrectas.');
    showScreen('pos');
  } catch (e) {
    showLoginAlert(e.message);
    btn.disabled = false;
    btn.textContent = 'Iniciar sesión';
  }
}

async function doForgot() {
  const email = document.getElementById('forgot-email').value.trim();
  if (!email) { showLoginAlert('Ingresa tu correo electrónico.'); return; }
  try {
    await fetch(BASE + '/api/auth.php?action=forgot', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email }),
      credentials: 'same-origin',
    });
    showLoginAlert('Si el correo existe, recibirás un enlace en los próximos minutos.', 'success');
  } catch {
    showLoginAlert('Error al enviar. Intenta de nuevo.');
  }
}

async function doLogout() {
  try {
    await fetch(BASE + '/api/auth.php?action=logout', { credentials: 'same-origin' });
  } catch {}
  showScreen('login');
}

document.addEventListener('keydown', e => {
  if (e.key !== 'Enter') return;
  if (document.getElementById('screen-login').style.display !== 'none') {
    const forgotVisible = !document.getElementById('form-forgot').classList.contains('d-none');
    forgotVisible ? doForgot() : doLogin();
  }
});

/* ══════════════════════════════════════════════════════════
   POS — Estado
══════════════════════════════════════════════════════════ */
let cart          = [];
let promoId       = 0;
let promoPct      = 0;
let promoFijo     = 0;
let payMethod     = 'efectivo';
let currentProduct = null;
let searchTimeout;
let qrStream      = null;
let scanning      = false;

/* ══════════════════════════════════════════════════════════
   PROMOCIONES
══════════════════════════════════════════════════════════ */
async function loadPromos() {
  try {
    const res  = await fetch(BASE + '/api/promociones.php?action=activas', { credentials: 'same-origin' });
    const data = await res.json();
    if (!data.success || !data.promociones.length) return;
    const sel = document.getElementById('promo-select');
    data.promociones.forEach(p => {
      const opt = document.createElement('option');
      opt.value = p.id;
      opt.textContent = `${p.nombre} (${p.tipo === 'porcentaje' ? p.valor + '%' : fmt.money(p.valor)}) ≥ ${fmt.money(p.venta_minima)}`;
      opt.dataset.tipo  = p.tipo;
      opt.dataset.valor = p.valor;
      sel.appendChild(opt);
    });
    document.getElementById('promo-area').style.display = 'flex';
  } catch {}
}

function applyPromo() {
  const sel = document.getElementById('promo-select');
  const opt = sel.options[sel.selectedIndex];
  promoId = parseInt(sel.value) || 0;
  if (promoId && opt.dataset.tipo === 'porcentaje')  { promoPct = parseFloat(opt.dataset.valor)||0; promoFijo = 0; }
  else if (promoId && opt.dataset.tipo === 'monto_fijo') { promoFijo = parseFloat(opt.dataset.valor)||0; promoPct = 0; }
  else { promoPct = 0; promoFijo = 0; }
  updateTotals();
}

/* ══════════════════════════════════════════════════════════
   BÚSQUEDA
══════════════════════════════════════════════════════════ */
const skuInput = document.getElementById('sku-input');
skuInput.addEventListener('input', function () {
  clearTimeout(searchTimeout);
  const q = this.value.trim();
  if (!q) { hideSearchResults(); return; }
  searchTimeout = setTimeout(() => searchProduct(q), 350);
});
skuInput.addEventListener('keydown', function (e) {
  if (e.key === 'Enter') {
    clearTimeout(searchTimeout);
    searchProduct(this.value.trim(), true);
  }
});

async function searchProduct(q, exact = false) {
  if (!q) return;
  try {
    const res  = await fetch(BASE + '/api/productos.php?action=by_sku&sku=' + encodeURIComponent(q), { credentials:'same-origin' });
    const data = await res.json();
    if (data.success) { showProductPreview(data.producto); hideSearchResults(); return; }
  } catch {}
  if (!exact) {
    try {
      const res  = await fetch(BASE + '/api/productos.php?q=' + encodeURIComponent(q), { credentials:'same-origin' });
      const data = await res.json();
      if (data.success && data.productos.length) { showSearchResults(data.productos); return; }
    } catch {}
  }
  hideSearchResults();
}

function showSearchResults(productos) {
  const list = document.getElementById('search-list');
  list.innerHTML = productos.map(p => `
    <div class="search-item" onclick='addToCart(${JSON.stringify(p)})'>
      <div class="search-item-info">
        <div class="search-item-name">${p.marca} ${p.modelo} · ${p.color} T:${p.talla}</div>
        <div class="search-item-sku">${p.sku}</div>
      </div>
      <div class="search-item-right">
        <span class="badge ${p.stock > 0 ? 'badge-success' : 'badge-danger'}">${p.stock} pzs</span>
        <span class="search-item-price">${fmt.money(p.precio_venta)}</span>
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
  document.getElementById('product-preview').style.display = 'flex';
}

/* ══════════════════════════════════════════════════════════
   CARRITO
══════════════════════════════════════════════════════════ */
function addToCart(p) {
  if (p.stock <= 0) { Toast.error('Sin stock disponible'); return; }
  const ex = cart.find(i => i.producto_id === p.id);
  if (ex) {
    if (ex.cantidad >= p.stock) { Toast.warning('Stock máximo alcanzado'); return; }
    ex.cantidad++;
  } else {
    cart.push({
      producto_id: p.id,
      sku:      p.sku,
      nombre:   `${p.marca} ${p.modelo} · ${p.color} T:${p.talla}`,
      precio:   parseFloat(p.precio_venta),
      stock:    parseInt(p.stock),
      cantidad: 1,
    });
  }
  hideSearchResults();
  document.getElementById('product-preview').style.display = 'none';
  skuInput.value = '';
  skuInput.focus();
  renderCart();
  Toast.success('Producto agregado ✓');
}

function removeFromCart(idx) { cart.splice(idx, 1); renderCart(); }

function changeQty(idx, delta) {
  cart[idx].cantidad += delta;
  if (cart[idx].cantidad <= 0) removeFromCart(idx);
  else if (cart[idx].cantidad > cart[idx].stock) {
    cart[idx].cantidad = cart[idx].stock;
    Toast.warning('Stock máximo');
  }
  renderCart();
}

function clearCart() {
  cart = []; promoId = 0; promoPct = 0; promoFijo = 0;
  document.getElementById('promo-select').value = '0';
  renderCart();
}

function renderCart() {
  const empty   = document.getElementById('cart-empty');
  const list    = document.getElementById('cart-list');
  const totals  = document.getElementById('cart-totals');
  const cobrar  = document.getElementById('cobrar-btns');
  const badge   = document.getElementById('cart-badge');
  const hdrCount = document.getElementById('header-cart-count');
  const totalItems = cart.reduce((a, i) => a + i.cantidad, 0);

  hdrCount.textContent = totalItems;
  if (totalItems > 0) {
    badge.textContent = totalItems;
    badge.classList.add('visible');
  } else {
    badge.classList.remove('visible');
  }

  if (!cart.length) {
    empty.classList.remove('d-none');
    list.classList.add('d-none');
    totals.classList.add('d-none');
    cobrar.classList.add('d-none');
    updateTotals();
    return;
  }

  empty.classList.add('d-none');
  list.classList.remove('d-none');
  totals.classList.remove('d-none');
  cobrar.classList.remove('d-none');

  document.getElementById('cart-items-container').innerHTML = cart.map((item, idx) => `
    <div class="cart-list-item">
      <div class="cli-info">
        <div class="cli-name">${item.nombre}</div>
        <div class="cli-sku">${item.sku}</div>
      </div>
      <div class="cli-qty">
        <button class="qty-btn" onclick="changeQty(${idx}, -1)">−</button>
        <span class="cli-qty-num">${item.cantidad}</span>
        <button class="qty-btn" onclick="changeQty(${idx}, 1)">+</button>
      </div>
      <span class="cli-price">${fmt.money(item.precio * item.cantidad)}</span>
      <button class="cli-remove" onclick="removeFromCart(${idx})" aria-label="Eliminar">✕</button>
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
  const rowDesc = document.getElementById('row-desc');
  if (desc > 0) {
    rowDesc.classList.remove('d-none');
    document.getElementById('desc-total').textContent = '-' + fmt.money(desc);
  } else {
    rowDesc.classList.add('d-none');
  }
  return { sub, desc, total };
}

/* ══════════════════════════════════════════════════════════
   CHECKOUT
══════════════════════════════════════════════════════════ */
function openCheckout(method) {
  if (!cart.length) { Toast.error('El carrito está vacío'); return; }
  payMethod = method;
  const { sub, desc, total } = updateTotals();
  document.getElementById('checkout-title').textContent =
    method === 'clip' ? '💳 Cobrar con Clip' : '💵 Cobrar en efectivo';
  const clipFields = document.getElementById('clip-fields');
  if (method === 'clip') {
    clipFields.classList.remove('d-none');
    const fee = Math.round(total * 0.036 * 1.16 * 100) / 100;
    document.getElementById('clip-fee-display').textContent = fmt.money(fee);
  } else {
    clipFields.classList.add('d-none');
  }
  document.getElementById('confirm-subtotal').textContent = fmt.money(sub);
  document.getElementById('confirm-total').textContent    = fmt.money(total);
  const confDesc = document.getElementById('confirm-desc-row');
  if (desc > 0) {
    confDesc.classList.remove('d-none');
    document.getElementById('confirm-desc').textContent = fmt.money(desc);
  } else {
    confDesc.classList.add('d-none');
  }
  Modal.open('modal-checkout');
}

async function confirmSale() {
  const btn       = document.getElementById('btn-confirm');
  const folioClip = document.getElementById('folio-clip').value.trim();
  const email     = document.getElementById('client-email').value.trim();
  if (payMethod === 'clip' && !folioClip) { Toast.error('El folio Clip es obligatorio'); return; }
  btn.disabled = true; btn.textContent = 'Procesando…';
  try {
    const res  = await fetch(BASE + '/api/ventas.php?action=confirmar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        carrito:      cart.map(i => ({ producto_id: i.producto_id, cantidad: i.cantidad })),
        metodo_pago:  payMethod,
        folio_clip:   folioClip,
        cliente_email: email || null,
        promocion_id:  promoId,
      }),
      credentials: 'same-origin',
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    Modal.close('modal-checkout');
    document.getElementById('success-folio').textContent      = data.folio;
    document.getElementById('success-total').textContent      = fmt.money(data.total);
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
  switchTab('buscar');
}

/* ══════════════════════════════════════════════════════════
   CÁMARA QR
══════════════════════════════════════════════════════════ */
async function openCamera() {
  // Modificación: Verifica si estamos en la app de Android para abrir el escáner nativo
  if (typeof AndroidApp !== "undefined") {
    AndroidApp.abrirCamaraNativa();
    return; // Detiene la ejecución aquí, evitando el error de cámara web
  }

  // Comportamiento web original (por si entran desde la computadora)
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
    canvas.getContext('2d').drawImage(video, 0, 0);
    if ('BarcodeDetector' in window) {
      try {
        const detector = new BarcodeDetector({ formats: ['qr_code', 'code_39'] });
        const codes = await detector.detect(canvas);
        if (codes.length) {
          const sku = codes[0].rawValue;
          document.getElementById('cam-status').textContent = '✓ Código: ' + sku;
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

/* ══════════════════════════════════════════════════════════
   APARTADOS
══════════════════════════════════════════════════════════ */

function openApartado() {
  if (!cart.length) { Toast.error('El carrito está vacío'); return; }
  const { total } = updateTotals();

  document.getElementById('apt-prendas-preview').innerHTML = cart.map(i => `
    <div class="apt-prenda-row">
      <div class="apt-prenda-info">
        <div class="apt-prenda-name">${i.nombre}</div>
        <div class="apt-prenda-sku">${i.sku} × ${i.cantidad}</div>
      </div>
      <span class="apt-prenda-price">${fmt.money(i.precio * i.cantidad)}</span>
    </div>
  `).join('');

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
  const restante  = Math.max(0, total - anticipo);
  document.getElementById('apt-confirm-anticipo').textContent = fmt.money(anticipo);
  document.getElementById('apt-confirm-restante').textContent = fmt.money(restante);
});

async function confirmarApartado() {
  const nombre   = document.getElementById('apt-nombre').value.trim();
  const telefono = document.getElementById('apt-telefono').value.trim();
  const monto    = parseFloat(document.getElementById('apt-monto').value) || 0;
  const vigencia = parseInt(document.getElementById('apt-vigencia').value) || 30;
  const notas    = document.getElementById('apt-notas').value.trim();
  const { total } = updateTotals();

  if (!nombre)        { Toast.error('El nombre del cliente es obligatorio'); return; }
  if (monto <= 0)     { Toast.error('El anticipo debe ser mayor a cero'); return; }
  if (monto > total)  { Toast.error('El anticipo no puede ser mayor al total'); return; }

  const btn = document.getElementById('btn-confirm-apt');
  btn.disabled = true; btn.textContent = 'Guardando…';

  try {
    const res  = await fetch(BASE + '/api/apartados.php?action=crear', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        nombre_cliente:   nombre,
        telefono_cliente: telefono || null,
        monto_apartado:   monto,
        vigencia_dias:    vigencia,
        notas:            notas || null,
        carrito: cart.map(i => ({ producto_id: i.producto_id, cantidad: i.cantidad })),
      }),
      credentials: 'same-origin',
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);

    Modal.close('modal-apartado');
    document.getElementById('apt-success-folio').textContent    = data.folio;
    document.getElementById('apt-success-nombre').textContent   = nombre;
    document.getElementById('apt-success-anticipo').textContent = fmt.money(monto) + ' anticipo';
    document.getElementById('apt-success-vigencia').textContent = 'Vigente hasta: ' + data.fecha_vigencia;
    Modal.open('modal-apt-success');
    clearCart();
    loadApartados();
  } catch (e) {
    Toast.error('Error: ' + e.message);
  } finally {
    btn.disabled = false; btn.textContent = '✓ Guardar apartado';
  }
}

function newSaleAfterApt() {
  Modal.close('modal-apt-success');
  switchTab('buscar');
}

function goToApartados() {
  Modal.close('modal-apt-success');
  switchTab('apartados');
}

function estadoBadge(estado) {
  const map = {
    activo:     ['badge-activo',     'Activo'],
    vencido:    ['badge-vencido',    'Vencido'],
    completado: ['badge-completado', 'Completado'],
    cancelado:  ['badge-cancelado',  'Cancelado'],
  };
  const [cls, label] = map[estado] || ['badge-gray', estado];
  return `<span class="badge ${cls}">${label}</span>`;
}

async function loadApartados() {
  try {
    const res  = await fetch(BASE + '/api/apartados.php?action=list', { credentials: 'same-origin' });
    const data = await res.json();
    if (!data.success) return;
    renderApartados(data.apartados);
    const count = data.apartados.length;
    const badge = document.getElementById('apt-badge');
    if (count > 0) { badge.textContent = count; badge.classList.add('visible'); }
    else           { badge.classList.remove('visible'); }
  } catch {}
}

function renderApartados(apartados) {
  const container = document.getElementById('apt-list-container');
  if (!apartados.length) {
    container.innerHTML = `
      <div class="apt-empty">
        <span class="icon">📦</span>
        <div class="msg">Sin apartados activos</div>
        <div class="sub">Agrega prendas al carrito y toca «Apartar»</div>
      </div>`;
    return;
  }
  container.innerHTML = apartados.map(a => {
    const restante = Math.max(0, parseFloat(a.monto_total) - parseFloat(a.monto_apartado));
    return `
      <div class="apt-card" onclick="viewApartado(${a.id})">
        <div class="apt-card-top">
          <div>
            <div class="apt-card-name">${a.nombre_cliente}</div>
            <div class="apt-card-folio">${a.folio} · ${a.fecha_apartado}</div>
          </div>
          ${estadoBadge(a.estado)}
        </div>
        <div class="apt-card-prendas">🧥 ${a.prendas_desc || '—'}</div>
        <div class="apt-money-row">
          <div class="apt-money-box">
            <span class="apt-money-label">Total</span>
            <span class="apt-money-value">${fmt.money(a.monto_total)}</span>
          </div>
          <div class="apt-money-box">
            <span class="apt-money-label">Anticipo</span>
            <span class="apt-money-value anticipo">${fmt.money(a.monto_apartado)}</span>
          </div>
          <div class="apt-money-box">
            <span class="apt-money-label">Resta</span>
            <span class="apt-money-value restante">${fmt.money(restante)}</span>
          </div>
        </div>
        <div class="apt-card-footer">
          <span class="apt-vigencia">📅 Vence: ${a.fecha_vigencia}</span>
          <span class="text-sm text-muted">${a.num_prendas} prenda(s)</span>
        </div>
      </div>`;
  }).join('');
}

async function viewApartado(id) {
  try {
    const res  = await fetch(BASE + '/api/apartados.php?action=get&id=' + id, { credentials: 'same-origin' });
    const data = await res.json();
    if (!data.success) { Toast.error('Error al cargar apartado'); return; }
    const a = data.apartado;

    const restante = Math.max(0, parseFloat(a.monto_total) - parseFloat(a.monto_apartado));
    document.getElementById('apt-detail-title').textContent = a.folio;

    const prendas = (a.prendas || []).map(p => `
      <div class="apt-prenda-row">
        <div class="apt-prenda-info">
          <div class="apt-prenda-name">${p.nombre_producto}</div>
          <div class="apt-prenda-sku">${p.sku} × ${p.cantidad}</div>
        </div>
        <span class="apt-prenda-price">${fmt.money(p.subtotal)}</span>
      </div>`).join('');

    document.getElementById('apt-detail-body').innerHTML = `
      <div style="margin-bottom:16px">
        <div style="font-size:18px;font-weight:800;margin-bottom:4px">${a.nombre_cliente}</div>
        ${a.telefono_cliente ? `<div style="font-size:13px;color:var(--gray-500)">📞 ${a.telefono_cliente}</div>` : ''}
        <div style="font-size:12px;color:var(--gray-500);margin-top:6px">
          Registrado: ${a.fecha_apartado} &nbsp; ${estadoBadge(a.estado)}
        </div>
      </div>
      <div class="form-label" style="margin-bottom:8px">Prendas apartadas</div>
      <div class="apt-detail-prendas">${prendas}</div>
      <div class="summary-box">
        <div class="summary-row">
          <span>Total</span><strong>${fmt.money(a.monto_total)}</strong>
        </div>
        <div class="summary-row" style="color:var(--success)">
          <span>Anticipo pagado</span><strong>${fmt.money(a.monto_apartado)}</strong>
        </div>
        <div class="summary-total" style="color:var(--warning);font-size:18px">
          <span>Resta por pagar</span><span>${fmt.money(restante)}</span>
        </div>
      </div>
      ${a.notas ? `<div style="margin-top:12px;font-size:13px;color:var(--gray-500)">📝 ${a.notas}</div>` : ''}
      <div style="margin-top:10px;font-size:13px;color:var(--gray-500)">
        📅 Vigencia hasta: <strong>${a.fecha_vigencia}</strong>
      </div>`;

    const footEl = document.getElementById('apt-detail-foot');
    if (a.estado === 'activo' || a.estado === 'vencido') {
      footEl.innerHTML = `
        <button class="btn btn-danger btn-sm" onclick="cancelarApartado(${a.id})">✕ Cancelar</button>
        <button class="btn btn-success" style="flex:1" onclick="completarApartado(${a.id})">✓ Completado</button>`;
    } else {
      footEl.innerHTML = `<button class="btn btn-ghost btn-block" onclick="Modal.close('modal-apt-detail')">Cerrar</button>`;
    }
    Modal.open('modal-apt-detail');
  } catch { Toast.error('Error al cargar apartado'); }
}

async function completarApartado(id) {
  if (!confirm('¿Marcar este apartado como completado?')) return;
  try {
    const res  = await fetch(BASE + '/api/apartados.php?action=completar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ apartado_id: id }),
      credentials: 'same-origin',
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    Modal.close('modal-apt-detail');
    Toast.success('Apartado completado ✓');
    loadApartados();
  } catch (e) { Toast.error('Error: ' + e.message); }
}

async function cancelarApartado(id) {
  if (!confirm('¿Cancelar este apartado?')) return;
  try {
    const res  = await fetch(BASE + '/api/apartados.php?action=cancelar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ apartado_id: id }),
      credentials: 'same-origin',
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    Modal.close('modal-apt-detail');
    Toast.success('Apartado cancelado');
    loadApartados();
  } catch (e) { Toast.error('Error: ' + e.message); }
}

/* ── Init ──────────────────────────────────────────────── */
<?php if ($isLoggedIn): ?>
loadPromos();
<?php endif; ?>
</script>
</body>
</html>