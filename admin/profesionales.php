<?php
require_once '../includes/db.php';
require_once '../includes/funciones.php';

iniciar_sesion();
requerir_admin();

$titulo_pagina = 'Profesionales';
$pagina_activa = 'profesionales';

// ----------------------------------------------------------------
// POST
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $id_prof = (int) ($_POST['profesional_id'] ?? 0);
    $accion  = $_POST['accion'] ?? 'cambiar_estado';

    // ---- ACCIONES MASIVAS ----
    if ($accion === 'bulk') {
        $ids       = array_unique(array_filter(array_map('intval', $_POST['sel'] ?? []), fn($v) => $v > 0));
        $bulk_tipo = $_POST['bulk_tipo'] ?? '';

        if (!empty($ids)) {
            if ($bulk_tipo === 'activar') {
                $stmt = $pdo->prepare("UPDATE profesionales SET estado = 'activo' WHERE id_profesional = ?");
                foreach ($ids as $bid) $stmt->execute([$bid]);
                $_SESSION['flash_admin']      = count($ids) . ' profesional(es) activado(s).';
                $_SESSION['flash_admin_tipo'] = 'exito';

            } elseif ($bulk_tipo === 'inactivar') {
                $stmt = $pdo->prepare("UPDATE profesionales SET estado = 'inactivo' WHERE id_profesional = ?");
                foreach ($ids as $bid) $stmt->execute([$bid]);
                $_SESSION['flash_admin']      = count($ids) . ' profesional(es) inactivado(s).';
                $_SESSION['flash_admin_tipo'] = 'info';

            } elseif ($bulk_tipo === 'eliminar') {
                $eliminados = 0;
                $omitidos   = 0;
                $stmt_c = $pdo->prepare(
                    "SELECT estado, foto_perfil, foto_dni FROM profesionales WHERE id_profesional = ?"
                );
                $stmt_d = $pdo->prepare(
                    "DELETE FROM profesionales WHERE id_profesional = ? AND estado = 'inactivo'"
                );
                foreach ($ids as $bid) {
                    $stmt_c->execute([$bid]);
                    $pd = $stmt_c->fetch();
                    if (!$pd || $pd['estado'] !== 'inactivo') { $omitidos++; continue; }
                    if (!empty($pd['foto_perfil']) && foto_nombre_valido($pd['foto_perfil'])) {
                        @unlink(__DIR__ . '/../uploads/fotos/' . $pd['foto_perfil']);
                    }
                    if (!empty($pd['foto_dni']) && foto_nombre_valido($pd['foto_dni'])) {
                        @unlink(__DIR__ . '/../uploads/dni/' . $pd['foto_dni']);
                    }
                    $stmt_d->execute([$bid]);
                    if ($stmt_d->rowCount() > 0) $eliminados++;
                }
                $msg = "$eliminados profesional(es) eliminado(s).";
                if ($omitidos > 0) $msg .= " $omitidos omitido(s) porque no estaban inactivos.";
                $_SESSION['flash_admin']      = $msg;
                $_SESSION['flash_admin_tipo'] = 'info';
            }
        }

        $qs = http_build_query(array_filter([
            'filtro' => $_POST['redir_filtro'] ?? 'todos',
            'q'      => $_POST['redir_q']      ?? '',
            'cat'    => $_POST['redir_cat']     ?? '',
        ], fn($v) => $v !== '' && $v !== '0' && $v !== 0));
        redirigir('profesionales.php' . ($qs ? '?' . $qs : ''));
    }

    // ---- RECHAZAR (pendiente / en_revision → inactivo) ----
    if ($accion === 'rechazar' && $id_prof > 0) {
        $stmt_p = $pdo->prepare(
            "SELECT nombre, email, estado FROM profesionales WHERE id_profesional = ?"
        );
        $stmt_p->execute([$id_prof]);
        $p_data = $stmt_p->fetch();

        if ($p_data && in_array($p_data['estado'], ['pendiente', 'en_revision'], true)) {
            $pdo->prepare("UPDATE profesionales SET estado = 'inactivo' WHERE id_profesional = ?")
                ->execute([$id_prof]);

            // Notificar si ya había completado verificación de email (en_revision)
            if ($p_data['estado'] === 'en_revision') {
                require_once '../includes/mailer.php';
                $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>
                  <body style="font-family:sans-serif;padding:24px;">
                  <h2 style="color:#C41E3A;">OficioRed</h2>
                  <p>Hola <strong>' . htmlspecialchars($p_data['nombre'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>
                  <p>Revisamos tu solicitud de perfil y en este momento no podemos aprobarlo. Si tenés dudas, contactanos.</p>
                  <hr><p style="color:#999;font-size:0.75rem;">OficioRed · El Colorado, Formosa</p>
                  </body></html>';
                enviar_email(
                    $p_data['email'],
                    $p_data['nombre'],
                    'Tu solicitud en OficioRed',
                    $html
                );
            }

            $_SESSION['flash_admin']      = 'Solicitud de '
                . htmlspecialchars($p_data['nombre'], ENT_QUOTES, 'UTF-8')
                . ' rechazada.';
            $_SESSION['flash_admin_tipo'] = 'info';
        } else {
            $_SESSION['flash_admin']      = 'No se pudo rechazar (estado no válido).';
            $_SESSION['flash_admin_tipo'] = 'error';
        }

        $filtro_redirect = isset($_POST['filtro']) ? '?filtro=' . urlencode($_POST['filtro']) : '';
        redirigir('profesionales.php' . $filtro_redirect);
    }

    // ---- ELIMINAR INDIVIDUAL ----
    if ($accion === 'eliminar' && $id_prof > 0) {
        $stmt_p = $pdo->prepare(
            "SELECT nombre, apellido, estado, foto_perfil, foto_dni FROM profesionales WHERE id_profesional = ?"
        );
        $stmt_p->execute([$id_prof]);
        $p_data = $stmt_p->fetch();

        if (!$p_data) {
            $_SESSION['flash_admin']      = 'Profesional no encontrado.';
            $_SESSION['flash_admin_tipo'] = 'error';
        } elseif ($p_data['estado'] !== 'inactivo') {
            $_SESSION['flash_admin']      = 'Solo se pueden eliminar profesionales inactivos.';
            $_SESSION['flash_admin_tipo'] = 'error';
        } else {
            if (!empty($p_data['foto_perfil']) && foto_nombre_valido($p_data['foto_perfil'])) {
                @unlink(__DIR__ . '/../uploads/fotos/' . $p_data['foto_perfil']);
            }
            if (!empty($p_data['foto_dni']) && foto_nombre_valido($p_data['foto_dni'])) {
                @unlink(__DIR__ . '/../uploads/dni/' . $p_data['foto_dni']);
            }
            $pdo->prepare("DELETE FROM profesionales WHERE id_profesional = ?")->execute([$id_prof]);
            $nombre = htmlspecialchars($p_data['nombre'] . ' ' . $p_data['apellido'], ENT_QUOTES, 'UTF-8');
            $_SESSION['flash_admin']      = "Profesional $nombre eliminado permanentemente.";
            $_SESSION['flash_admin_tipo'] = 'info';
        }

        redirigir('profesionales.php?filtro=inactivo');
    }

    // ---- CAMBIAR ESTADO INDIVIDUAL ----
    $estado_raw   = $_POST['nuevo_estado'] ?? '';
    $nuevo_estado = in_array($estado_raw, ['activo', 'inactivo', 'en_revision'], true)
                    ? $estado_raw : null;

    if ($id_prof > 0 && $nuevo_estado) {
        if ($nuevo_estado === 'activo') {
            require_once '../includes/mailer.php';
            $stmt_p = $pdo->prepare("SELECT nombre, email, estado FROM profesionales WHERE id_profesional = ?");
            $stmt_p->execute([$id_prof]);
            $p_data = $stmt_p->fetch();

            if ($p_data && $p_data['estado'] === 'en_revision') {
                $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>
                  <body style="font-family:sans-serif;padding:24px;">
                  <h2 style="color:#C41E3A;">OficioRed</h2>
                  <p>Hola <strong>' . htmlspecialchars($p_data['nombre'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>
                  <p>¡Buenas noticias! Tu perfil fue <strong>aprobado</strong> y ya aparece en las búsquedas de OficioRed.</p>
                  <p><a href="http://localhost/oficiored/login.php" style="color:#C41E3A;">Ingresá a tu panel</a></p>
                  <hr><p style="color:#999;font-size:0.75rem;">OficioRed · El Colorado, Formosa</p>
                  </body></html>';
                enviar_email($p_data['email'], $p_data['nombre'], '¡Tu perfil fue aprobado! – OficioRed', $html);
            }
        }

        $stmt = $pdo->prepare("UPDATE profesionales SET estado = ? WHERE id_profesional = ?");
        $stmt->execute([$nuevo_estado, $id_prof]);

        $labels = ['activo' => 'aprobado', 'inactivo' => 'inactivado', 'en_revision' => 'devuelto a revisión'];
        $_SESSION['flash_admin']      = 'Profesional ' . ($labels[$nuevo_estado] ?? 'actualizado') . ' exitosamente.';
        $_SESSION['flash_admin_tipo'] = $nuevo_estado === 'activo' ? 'exito' : 'info';
    }

    $filtro_redirect = isset($_POST['filtro']) ? '?filtro=' . urlencode($_POST['filtro']) : '';
    redirigir('profesionales.php' . $filtro_redirect);
}

// ----------------------------------------------------------------
// GET: parámetros de filtro
// ----------------------------------------------------------------
$filtro = $_GET['filtro'] ?? 'todos';
$filtros_validos = ['todos', 'activo', 'inactivo', 'en_revision', 'pendiente'];
if (!in_array($filtro, $filtros_validos, true)) $filtro = 'todos';

$q_busq     = trim($_GET['q'] ?? '');
$cat_filtro = filter_input(INPUT_GET, 'cat', FILTER_VALIDATE_INT) ?: 0;

// Categorías para el select de filtro
$categorias_lista = $pdo->query(
    "SELECT id_categoria, nombre FROM categorias ORDER BY nombre"
)->fetchAll();

// ----------------------------------------------------------------
// Construir la consulta con filtros
// ----------------------------------------------------------------
$where_parts = [];
$params_q    = [];

if ($filtro !== 'todos') {
    $where_parts[] = "p.estado = ?";
    $params_q[]    = $filtro;
}

if ($q_busq !== '') {
    $like          = '%' . escapar_like($q_busq) . '%';
    $where_parts[] = "(p.nombre LIKE ? ESCAPE '#' OR p.apellido LIKE ? ESCAPE '#' OR p.email LIKE ? ESCAPE '#')";
    $params_q[]    = $like;
    $params_q[]    = $like;
    $params_q[]    = $like;
}

if ($cat_filtro > 0) {
    $where_parts[] = "p.categoria_id = ?";
    $params_q[]    = $cat_filtro;
}

$where_sql = empty($where_parts) ? '' : 'WHERE ' . implode(' AND ', $where_parts);

$stmt = $pdo->prepare(
    "SELECT p.id_profesional, p.nombre, p.apellido, p.email, p.oficio,
            p.estado, p.fecha_registro, p.foto_perfil,
            c.nombre AS categoria_nombre
     FROM profesionales p
     LEFT JOIN categorias c ON c.id_categoria = p.categoria_id
     $where_sql
     ORDER BY
       CASE p.estado WHEN 'en_revision' THEN 0 WHEN 'pendiente' THEN 1 WHEN 'activo' THEN 2 ELSE 3 END,
       p.fecha_registro DESC"
);
$stmt->execute($params_q);
$profesionales = $stmt->fetchAll();

$etiquetas_estado = [
    'activo'      => 'Activo',
    'inactivo'    => 'Inactivo',
    'en_revision' => 'En revisión',
    'pendiente'   => 'Pendiente OTP',
];

include 'includes/header-admin.php';
?>

<div class="admin-page-header">
  <h1>Profesionales</h1>
  <a href="index.php">← Volver al inicio</a>
</div>

<!-- Barra de búsqueda y filtros avanzados -->
<form method="get" action="profesionales.php" class="prof-filtros-bar">
  <input type="hidden" name="filtro" value="<?= sanitizar($filtro) ?>">

  <div class="prof-filtros-inputs">
    <div class="prof-filtros-campo">
      <input type="text" name="q" value="<?= sanitizar($q_busq) ?>"
             placeholder="Buscar por nombre o email…"
             class="prof-filtros-input">
    </div>
    <div class="prof-filtros-campo">
      <select name="cat" class="prof-filtros-input">
        <option value="">Todas las categorías</option>
        <?php foreach ($categorias_lista as $cl): ?>
          <option value="<?= (int)$cl['id_categoria'] ?>"
            <?= $cat_filtro === (int)$cl['id_categoria'] ? 'selected' : '' ?>>
            <?= sanitizar($cl['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-sm btn-primary">
      <i data-lucide="search"></i> Buscar
    </button>
    <?php if ($q_busq !== '' || $cat_filtro > 0): ?>
      <a href="profesionales.php?filtro=<?= $filtro ?>" class="btn btn-sm btn-secundario">
        <i data-lucide="x"></i> Limpiar
      </a>
    <?php endif; ?>
  </div>
</form>

<!-- Filtros de estado (tabs) -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
  <?php
  $tab_labels = ['todos' => 'Todos', 'en_revision' => 'En revisión', 'activo' => 'Activos', 'inactivo' => 'Inactivos', 'pendiente' => 'Pendientes OTP'];
  foreach ($tab_labels as $k => $label):
      $tab_params = array_filter(['filtro' => $k, 'q' => $q_busq, 'cat' => $cat_filtro ?: ''], fn($v) => $v !== '' && $v !== '0' && $v !== 0);
      $tab_url    = 'profesionales.php?' . http_build_query($tab_params);
  ?>
    <a href="<?= sanitizar($tab_url) ?>"
       class="btn btn-sm <?= $filtro === $k ? 'btn-primary' : 'btn-secundario' ?>">
      <?= $label ?>
    </a>
  <?php endforeach; ?>
</div>

<!-- Form bulk oculto (fuera de la tabla para evitar forms anidados) -->
<form method="post" action="profesionales.php" id="form-tabla-profs" style="display:none;">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
  <input type="hidden" name="accion" value="bulk">
  <input type="hidden" name="bulk_tipo" id="bulk-tipo-input" value="">
  <input type="hidden" name="redir_filtro" value="<?= sanitizar($filtro) ?>">
  <input type="hidden" name="redir_q"      value="<?= sanitizar($q_busq) ?>">
  <input type="hidden" name="redir_cat"    value="<?= (int)$cat_filtro ?>">
</form>

<div class="admin-card">
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead>
        <tr>
          <th style="width:36px;">
            <input type="checkbox" id="sel-all" title="Seleccionar todos" form="form-tabla-profs">
          </th>
          <th>Profesional</th>
          <th>Categoría / Oficio</th>
          <th>Estado</th>
          <th>Registro</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($profesionales as $p): ?>
        <tr class="fila-seleccionable">
          <td>
            <input type="checkbox" name="sel[]" value="<?= $p['id_profesional'] ?>"
                   class="sel-prof" form="form-tabla-profs">
          </td>
          <td>
            <div class="admin-prof-celda">
              <?php if (!empty($p['foto_perfil']) && foto_nombre_valido($p['foto_perfil'])): ?>
                <img src="../uploads/fotos/<?= sanitizar($p['foto_perfil']) ?>"
                     alt="" class="admin-prof-mini-foto">
              <?php else: ?>
                <span class="admin-prof-mini-avatar">
                  <?= strtoupper(substr($p['nombre'], 0, 1) . substr($p['apellido'], 0, 1)) ?>
                </span>
              <?php endif; ?>
              <div>
                <a href="profesional-detalle.php?id=<?= $p['id_profesional'] ?>"
                   class="admin-prof-nombre">
                  <?= sanitizar($p['nombre']) ?> <?= sanitizar($p['apellido']) ?>
                </a>
                <span class="admin-prof-email"><?= sanitizar($p['email']) ?></span>
              </div>
            </div>
          </td>
          <td><?= sanitizar($p['categoria_nombre'] ?? $p['oficio'] ?? '—') ?></td>
          <td>
            <span class="badge badge-<?= $p['estado'] ?>">
              <?= $etiquetas_estado[$p['estado']] ?? $p['estado'] ?>
            </span>
          </td>
          <td style="white-space:nowrap;font-size:0.82rem;color:var(--color-text-light);">
            <?= htmlspecialchars(formatear_fecha($p['fecha_registro']), ENT_QUOTES, 'UTF-8') ?>
          </td>
          <td>
            <div class="admin-acciones">
              <a href="profesional-detalle.php?id=<?= $p['id_profesional'] ?>"
                 class="btn btn-sm btn-secundario">Ver</a>
              <?php if ($p['estado'] === 'en_revision'): ?>
                <form method="post" action="profesionales.php">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="profesional_id" value="<?= $p['id_profesional'] ?>">
                  <input type="hidden" name="nuevo_estado" value="activo">
                  <input type="hidden" name="filtro" value="<?= sanitizar($filtro) ?>">
                  <button type="submit" class="btn btn-sm btn-primary">Aprobar</button>
                </form>
                <form method="post" action="profesionales.php"
                      onsubmit="return confirm('¿Rechazar la solicitud de <?= addslashes(sanitizar($p['nombre']) . ' ' . sanitizar($p['apellido'])) ?>? El perfil quedará inactivo.');">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="profesional_id" value="<?= $p['id_profesional'] ?>">
                  <input type="hidden" name="accion" value="rechazar">
                  <input type="hidden" name="filtro" value="<?= sanitizar($filtro) ?>">
                  <button type="submit" class="btn btn-sm btn-secundario" style="color:var(--color-primary);">Rechazar</button>
                </form>
              <?php elseif ($p['estado'] === 'pendiente'): ?>
                <form method="post" action="profesionales.php"
                      onsubmit="return confirm('¿Rechazar y descartar la solicitud pendiente de <?= addslashes(sanitizar($p['nombre']) . ' ' . sanitizar($p['apellido'])) ?>?');">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="profesional_id" value="<?= $p['id_profesional'] ?>">
                  <input type="hidden" name="accion" value="rechazar">
                  <input type="hidden" name="filtro" value="<?= sanitizar($filtro) ?>">
                  <button type="submit" class="btn btn-sm btn-secundario" style="color:var(--color-primary);">Descartar</button>
                </form>
              <?php elseif ($p['estado'] === 'activo'): ?>
                <form method="post" action="profesionales.php"
                      onsubmit="return confirm('¿Inactivar a <?= addslashes(sanitizar($p['nombre']) . ' ' . sanitizar($p['apellido'])) ?>? Su perfil dejará de aparecer públicamente.');">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="profesional_id" value="<?= $p['id_profesional'] ?>">
                  <input type="hidden" name="nuevo_estado" value="inactivo">
                  <input type="hidden" name="filtro" value="<?= sanitizar($filtro) ?>">
                  <button type="submit" class="btn btn-sm btn-secundario" style="color:var(--color-accent);">Inactivar</button>
                </form>
              <?php elseif ($p['estado'] === 'inactivo'): ?>
                <form method="post" action="profesionales.php">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="profesional_id" value="<?= $p['id_profesional'] ?>">
                  <input type="hidden" name="nuevo_estado" value="activo">
                  <input type="hidden" name="filtro" value="<?= sanitizar($filtro) ?>">
                  <button type="submit" class="btn btn-sm btn-secundario" style="color:var(--color-success);">Activar</button>
                </form>
                <form method="post" action="profesionales.php"
                      onsubmit="return confirm('⚠️ ELIMINAR A <?= addslashes(sanitizar($p['nombre']) . ' ' . sanitizar($p['apellido'])) ?>\n\nEsta acción es permanente e irreversible. ¿Confirmás?');">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="profesional_id" value="<?= $p['id_profesional'] ?>">
                  <input type="hidden" name="accion" value="eliminar">
                  <button type="submit" class="btn btn-sm btn-secundario" style="color:var(--color-primary);">Eliminar</button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($profesionales)): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--color-text-light);padding:2rem;">
          No hay profesionales con estos filtros.
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Barra de acciones masivas (aparece cuando hay seleccionados) -->
<div id="bulk-bar" class="bulk-bar" style="display:none;">
  <span class="bulk-bar__info">
    <strong id="bulk-count">0</strong> seleccionado(s)
  </span>
  <div class="bulk-bar__acciones">
    <button type="button" class="btn btn-sm btn-secundario"
            style="color:var(--color-success);"
            onclick="ejecutarBulk('activar')">
      <i data-lucide="user-check"></i> Activar todos
    </button>
    <button type="button" class="btn btn-sm btn-secundario"
            style="color:var(--color-accent);"
            onclick="ejecutarBulk('inactivar')">
      <i data-lucide="user-x"></i> Inactivar todos
    </button>
    <button type="button" class="btn btn-sm btn-secundario"
            style="color:var(--color-primary);"
            onclick="ejecutarBulk('eliminar')">
      <i data-lucide="trash-2"></i> Eliminar inactivos
    </button>
  </div>
  <button type="button" class="bulk-bar__cerrar" onclick="deseleccionarTodos()"
          title="Cancelar selección">✕</button>
</div>

<script>
(function () {
  var selAll   = document.getElementById('sel-all');
  var form     = document.getElementById('form-tabla-profs');
  var bulkBar  = document.getElementById('bulk-bar');
  var countEl  = document.getElementById('bulk-count');
  var tipoInp  = document.getElementById('bulk-tipo-input');

  function getChecked() {
    return Array.from(document.querySelectorAll('.sel-prof:checked'));
  }

  function actualizarBarra() {
    var checked = getChecked();
    if (checked.length > 0) {
      countEl.textContent = checked.length;
      bulkBar.style.display = 'flex';
    } else {
      bulkBar.style.display = 'none';
    }
    var todos = document.querySelectorAll('.sel-prof');
    selAll.indeterminate = checked.length > 0 && checked.length < todos.length;
    selAll.checked = todos.length > 0 && checked.length === todos.length;
    // No llamar lucide.createIcons() aquí: los íconos ya están renderizados
  }

  selAll.addEventListener('change', function () {
    document.querySelectorAll('.sel-prof').forEach(function (cb) {
      cb.checked = selAll.checked;
    });
    actualizarBarra();
  });

  document.querySelectorAll('.sel-prof').forEach(function (cb) {
    cb.addEventListener('change', actualizarBarra);
  });

  window.ejecutarBulk = function (tipo) {
    var checked = getChecked();
    if (checked.length === 0) return;

    var mensajes = {
      activar:   '¿Activar ' + checked.length + ' profesional(es) seleccionado(s)?',
      inactivar: '¿Inactivar ' + checked.length + ' profesional(es) seleccionado(s)?',
      eliminar:  '⚠️ Eliminar los inactivos seleccionados (' + checked.length + ' marcado(s)).\n\nSolo se borrarán los que tengan estado "Inactivo". Esta acción es permanente. ¿Confirmás?'
    };

    if (!confirm(mensajes[tipo])) return;

    tipoInp.value = tipo;

    // Quitar IDs dinámicos anteriores y agregar los actuales
    form.querySelectorAll('input[name="sel[]"]').forEach(function (el) { el.remove(); });
    checked.forEach(function (cb) {
      var hidden = document.createElement('input');
      hidden.type  = 'hidden';
      hidden.name  = 'sel[]';
      hidden.value = cb.value;
      form.appendChild(hidden);
    });

    form.submit();
  };

  window.deseleccionarTodos = function () {
    document.querySelectorAll('.sel-prof').forEach(function (cb) { cb.checked = false; });
    selAll.checked = false;
    actualizarBarra();
  };

  // Clic en cualquier parte de la fila = toggle checkbox
  // (excepto si el clic fue directamente sobre un enlace, botón o input)
  document.querySelectorAll('tr.fila-seleccionable').forEach(function (tr) {
    tr.addEventListener('click', function (e) {
      var tag = (e.target.tagName || '').toLowerCase();
      // Si el clic fue sobre un elemento interactivo → no hacer nada
      if (tag === 'a' || tag === 'button' || tag === 'input' || tag === 'select' || tag === 'label') return;
      // Si hay un elemento interactivo en el ancestro (pero no form — queremos ignorar solo el elemento en sí)
      if (e.target.closest('a, button, input, select, label')) return;
      var cb = tr.querySelector('.sel-prof');
      if (!cb) return;
      cb.checked = !cb.checked;
      actualizarBarra();
    });
  });
})();
</script>

<?php include 'includes/footer-admin.php'; ?>
