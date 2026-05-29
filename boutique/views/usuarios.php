<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin', 'dueno']);
$isAdmin   = isAdmin();
$pageTitle = 'Usuarios — Mi Boutique';
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<div class="app-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main-content">
  <header class="topbar">
    <span class="topbar-title">👥 Usuarios</span>
    <div class="topbar-actions">
      <button class="btn btn-primary" onclick="openModal()">+ Nuevo usuario</button>
    </div>
  </header>
  <div class="page-body">
    <div class="card">
      <div class="table-wrap">
        <table>
          <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Alta</th><th>Acciones</th></tr></thead>
          <tbody id="users-tbody"><tr><td colspan="6" style="text-align:center;padding:24px;color:var(--gray-500)">Cargando…</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</div>

<!-- Modal usuario -->
<div class="modal-overlay" id="modal-user">
  <div class="modal" style="max-width:480px">
    <div class="modal-header">
      <span class="modal-title" id="user-modal-title">Nuevo usuario</span>
      <button class="modal-close" onclick="Modal.close('modal-user')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="user-id">
      <div class="form-group"><label class="form-label">Nombre *</label><input type="text" id="user-nombre" class="form-control"></div>
      <div class="form-group"><label class="form-label">Correo *</label><input type="email" id="user-email" class="form-control"></div>
      <div class="form-group">
        <label class="form-label">Rol</label>
        <select id="user-rol" class="form-control">
          <?php if ($isAdmin): ?><option value="admin">Admin del sistema</option><?php endif; ?>
          <option value="dueno">Dueño</option>
          <option value="empleado" selected>Empleado</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Contraseña <span id="pass-hint" style="color:var(--gray-400);font-weight:400">(dejar vacío para no cambiar)</span></label>
        <input type="password" id="user-pass" class="form-control" placeholder="Mínimo 8 caracteres">
      </div>
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px">
          <input type="checkbox" id="user-activo" checked> Usuario activo
        </label>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="Modal.close('modal-user')">Cancelar</button>
      <button class="btn btn-primary" onclick="saveUser()">Guardar</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>

const isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
const rolLabels = { admin: '🔧 Admin', dueno: '👑 Dueño', empleado: '👷 Empleado' };

async function loadUsers() {
  const res  = await fetch(`${BASE}/api/usuarios.php`, { credentials: 'same-origin' });
  const data = await res.json();
  if (!data.success) { Toast.error(data.error); return; }
  document.getElementById('users-tbody').innerHTML = data.usuarios.map(u => `
    <tr>
      <td><strong>${u.nombre}</strong></td>
      <td>${u.email}</td>
      <td><span class="badge badge-brand">${rolLabels[u.rol] || u.rol}</span></td>
      <td><span class="badge ${u.activo ? 'badge-success' : 'badge-gray'}">${u.activo ? 'Activo' : 'Inactivo'}</span></td>
      <td class="text-xs text-muted">${fmt.date(u.created_at)}</td>
      <td>
        <div class="d-flex gap-2">
          <button class="btn btn-ghost btn-sm" onclick='editUser(${JSON.stringify(u).replace(/"/g,"&quot;")})'>✏️ Editar</button>
          ${isAdmin ? `<button class="btn btn-danger btn-sm" onclick="deleteUser(${u.id})">🗑</button>` : ''}
        </div>
      </td>
    </tr>
  `).join('');
}

function openModal() {
  ['user-id','user-nombre','user-email','user-pass'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('user-rol').value = 'empleado';
  document.getElementById('user-activo').checked = true;
  document.getElementById('user-modal-title').textContent = 'Nuevo usuario';
  document.getElementById('pass-hint').style.display = 'none';
  Modal.open('modal-user');
}

function editUser(u) {
  document.getElementById('user-id').value      = u.id;
  document.getElementById('user-nombre').value  = u.nombre;
  document.getElementById('user-email').value   = u.email;
  document.getElementById('user-rol').value     = u.rol;
  document.getElementById('user-activo').checked = !!u.activo;
  document.getElementById('user-pass').value    = '';
  document.getElementById('user-modal-title').textContent = 'Editar usuario';
  document.getElementById('pass-hint').style.display = 'inline';
  Modal.open('modal-user');
}

async function saveUser() {
  const payload = {
    id:       parseInt(document.getElementById('user-id').value) || 0,
    nombre:   document.getElementById('user-nombre').value.trim(),
    email:    document.getElementById('user-email').value.trim(),
    rol:      document.getElementById('user-rol').value,
    activo:   document.getElementById('user-activo').checked,
    password: document.getElementById('user-pass').value,
  };
  try {
    const res  = await fetch(`${BASE}/api/usuarios.php`, {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload), credentials: 'same-origin'
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    Modal.close('modal-user');
    Toast.success(data.message);
    loadUsers();
  } catch(e) { Toast.error(e.message); }
}

async function deleteUser(id) {
  if (!await confirmAction('¿Desactivar este usuario?')) return;
  try {
    const res  = await fetch(`${BASE}/api/usuarios.php?id=${id}`, { method: 'DELETE', credentials: 'same-origin' });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    Toast.success('Usuario desactivado');
    loadUsers();
  } catch(e) { Toast.error(e.message); }
}

loadUsers();
</script>
</body>
</html>
