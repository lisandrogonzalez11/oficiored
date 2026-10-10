<?php
require_once '../includes/db.php';
require_once '../includes/funciones.php';

iniciar_sesion();
requerir_admin();

$titulo_pagina = 'Zonas de cobertura';
$pagina_activa = 'zonas';

$error = '';

// ----------------------------------------------------------------
// POST
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $accion = $_POST['accion'] ?? '';

    // ---- CREAR ----
    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $orden  = max(0, (int) ($_POST['orden'] ?? 0));

        if ($nombre === '') {
            $error = 'El nombre de la zona es obligatorio.';
        } elseif (mb_strlen($nombre) > 80) {
            $error = 'El nombre no puede superar 80 caracteres.';
        } else {
            try {
                $pdo->prepare(
                    "INSERT INTO zonas (nombre, orden, activa, es_todo) VALUES (?, ?, 1, 0)"
                )->execute([$nombre, $orden]);
                $_SESSION['flash_admin']      = "Zona «$nombre» creada exitosamente.";
                $_SESSION['flash_admin_tipo'] = 'exito';
                redirigir('zonas.php');
            } catch (PDOException $e) {
                $error = $e->getCode() === '23000'
                    ? 'Ya existe una zona con ese nombre.'
                    : 'Error al crear la zona.';
            }
        }
    }

    // ---- EDITAR ----
    elseif ($accion === 'editar') {
        $id_zona = (int) ($_POST['id_zona'] ?? 0);
        $nombre  = trim($_POST['nombre']   ?? '');
        $orden   = max(0, (int) ($_POST['orden'] ?? 0));

        if ($id_zona <= 0) {
            $error = 'Zona inválida.';
        } elseif ($nombre === '') {
            $error = 'El nombre es obligatorio.';
        } else {
            $zona_act = $pdo->prepare("SELECT es_todo FROM zonas WHERE id_zona = ?")->execute([$id_zona]);
            try {
                $pdo->prepare(
                    "UPDATE zonas SET nombre = ?, orden = ? WHERE id_zona = ? AND es_todo = 0"
                )->execute([$nombre, $orden, $id_zona]);
                $_SESSION['flash_admin']      = 'Zona actualizada.';
                $_SESSION['flash_admin_tipo'] = 'exito';
                redirigir('zonas.php');
            } catch (PDOException $e) {
                $error = $e->getCode() === '23000'
                    ? 'Ya existe una zona con ese nombre.'
                    : 'Error al actualizar la zona.';
            }
        }
    }

    // ---- TOGGLE ACTIVA ----
    elseif ($accion === 'toggle') {
        $id_zona = (int) ($_POST['id_zona'] ?? 0);
        if ($id_zona > 0) {
            $pdo->prepare(
                "UPDATE zonas SET activa = 1 - activa WHERE id_zona = ? AND es_todo = 0"
            )->execute([$id_zona]);
            $_SESSION['flash_admin']      = 'Estado de la zona actualizado.';
            $_SESSION['flash_admin_tipo'] = 'info';
        }
        redirigir('zonas.php');
    }

    // ---- ELIMINAR ----
    elseif ($accion === 'eliminar') {
        $id_zona = (int) ($_POST['id_zona'] ?? 0);
        if ($id_zona <= 0) {
            $error = 'Zona inválida.';
        } else {
            $stmt = $pdo->prepare("SELECT nombre, es_todo FROM zonas WHERE id_zona = ?");
            $stmt->execute([$id_zona]);
            $zona = $stmt->fetch();

            if (!$zona) {
                $error = 'Zona no encontrada.';
            } elseif ($zona['es_todo']) {
                $error = 'La zona "Todo El Colorado" no puede eliminarse.';
            } else {
                $en_uso = (int) $pdo->prepare(
                    "SELECT COUNT(*) FROM profesional_zonas WHERE zona_id = ?"
                )->execute([$id_zona]) ? $pdo->query("SELECT FOUND_ROWS()")->fetchColumn() : 0;

                // Requery correctly
                $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM profesional_zonas WHERE zona_id = ?");
                $stmt2->execute([$id_zona]);
                $en_uso = (int) $stmt2->fetchColumn();

                if ($en_uso > 0) {
                    $error = 'No se puede eliminar una zona que tiene profesionales asociados.';
                } else {
                    $pdo->prepare("DELETE FROM zonas WHERE id_zona = ?")->execute([$id_zona]);
                    $_SESSION['flash_admin']      = 'Zona eliminada.';
                    $_SESSION['flash_admin_tipo'] = 'info';
                    redirigir('zonas.php');
                }
            }
        }
    }
}

// ----------------------------------------------------------------
// GET: listar zonas con conteo de profesionales
// ----------------------------------------------------------------
$zonas = $pdo->query(
    "SELECT z.id_zona, z.nombre, z.orden, z.activa, z.es_todo,
            COUNT(pz.profesional_id) AS total_profesionales
     FROM zonas z
     LEFT JOIN profesional_zonas pz ON pz.zona_id = z.id_zona
     GROUP BY z.id_zona
     ORDER BY z.orden ASC, z.nombre ASC"
)->fetchAll();

include 'includes/header-admin.php';
?>

<div class="admin-page-header">
  <h1>Zonas de cobertura</h1>
  <a href="index.php">← Volver al inicio</a>
</div>

<?php if ($error !== ''): ?>
  <div class="alerta alerta-error" style="margin-bottom:1rem;">
    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<!-- Formulario crear -->
<form method="post" action="zonas.php" class="admin-form-inline">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
  <input type="hidden" name="accion" value="crear">
  <h3 style="margin:0 0 0.5rem;font-size:0.95rem;">Nueva zona</h3>
  <div class="fg">
    <label for="cn-nombre">Nombre</label>
    <input type="text" id="cn-nombre" name="nombre" maxlength="80" required
           placeholder="Ej: Barrio Los Álamos">
  </div>
  <div class="fg" style="flex:0 0 120px;">
    <label for="cn-orden">Orden</label>
    <input type="number" id="cn-orden" name="orden" min="0" max="99" value="10"
           style="width:100%;">
  </div>
  <div style="padding-top:1.2rem;">
    <button type="submit" class="btn btn-primary btn-sm">
      <i data-lucide="plus"></i> Crear
    </button>
  </div>
</form>

<!-- Tabla de zonas -->
<div class="admin-card">
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead>
        <tr>
          <th>#</th>
          <th>Nombre</th>
          <th>Orden</th>
          <th>Estado</th>
          <th>Profesionales</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($zonas as $z): ?>
        <tr>
          <td><?= $z['id_zona'] ?></td>
          <td>
            <strong><?= sanitizar($z['nombre']) ?></strong>
            <?php if ($z['es_todo']): ?>
              <span style="font-size:0.72rem;color:var(--color-text-light);margin-left:0.4rem;">(especial)</span>
            <?php endif; ?>
          </td>
          <td><?= (int)$z['orden'] ?></td>
          <td>
            <?php if ($z['activa']): ?>
              <span class="badge badge-activo">Activa</span>
            <?php else: ?>
              <span class="badge badge-inactivo">Inactiva</span>
            <?php endif; ?>
          </td>
          <td><?= (int)$z['total_profesionales'] ?></td>
          <td>
            <div class="admin-acciones">

              <?php if (!$z['es_todo']): ?>
                <!-- Editar -->
                <button type="button" class="btn btn-sm btn-secundario"
                        data-editar="<?= $z['id_zona'] ?>"
                        data-nombre="<?= htmlspecialchars($z['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                        data-orden="<?= (int)$z['orden'] ?>">
                  <i data-lucide="pencil"></i> Editar
                </button>

                <!-- Toggle activa -->
                <form method="post" action="zonas.php">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="accion" value="toggle">
                  <input type="hidden" name="id_zona" value="<?= $z['id_zona'] ?>">
                  <button type="submit" class="btn btn-sm btn-secundario"
                          style="color:<?= $z['activa'] ? 'var(--color-accent)' : 'var(--color-success)' ?>;"
                          title="<?= $z['activa'] ? 'Desactivar' : 'Activar' ?>">
                    <i data-lucide="<?= $z['activa'] ? 'eye-off' : 'eye' ?>"></i>
                    <?= $z['activa'] ? 'Desactivar' : 'Activar' ?>
                  </button>
                </form>

                <!-- Eliminar -->
                <?php if ((int)$z['total_profesionales'] === 0): ?>
                  <form method="post" action="zonas.php"
                        onsubmit="return confirm('¿Eliminar la zona «<?= addslashes(sanitizar($z['nombre'])) ?>»?');">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id_zona" value="<?= $z['id_zona'] ?>">
                    <button type="submit" class="btn btn-sm btn-secundario" style="color:var(--color-primary);">
                      <i data-lucide="trash-2"></i> Eliminar
                    </button>
                  </form>
                <?php else: ?>
                  <button type="button" class="btn btn-sm btn-secundario" disabled
                          title="Tiene profesionales asociados">
                    <i data-lucide="trash-2"></i> Eliminar
                  </button>
                <?php endif; ?>

              <?php else: ?>
                <span style="font-size:0.8rem;color:var(--color-text-light);">No editable</span>
              <?php endif; ?>

            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Panel editar (inline, oculto por defecto) -->
<div id="panel-editar" hidden style="margin-top:-0.5rem;margin-bottom:1.5rem;">
  <form method="post" action="zonas.php" class="admin-form-inline" style="border-color:var(--color-primary);">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="accion" value="editar">
    <input type="hidden" name="id_zona" id="ed-id">
    <h3 style="margin:0 0 0.5rem;font-size:0.95rem;color:var(--color-primary);">Editar zona</h3>
    <div class="fg">
      <label for="ed-nombre">Nombre</label>
      <input type="text" id="ed-nombre" name="nombre" maxlength="80" required>
    </div>
    <div class="fg" style="flex:0 0 120px;">
      <label for="ed-orden">Orden</label>
      <input type="number" id="ed-orden" name="orden" min="0" max="99" style="width:100%;">
    </div>
    <div style="padding-top:1.2rem;display:flex;gap:0.5rem;">
      <button type="submit" class="btn btn-primary btn-sm">
        <i data-lucide="save"></i> Guardar
      </button>
      <button type="button" id="btn-cancelar" class="btn btn-sm btn-secundario">Cancelar</button>
    </div>
  </form>
</div>

<script>
(function () {
  var panel = document.getElementById('panel-editar');
  var edId  = document.getElementById('ed-id');
  var edNom = document.getElementById('ed-nombre');
  var edOrd = document.getElementById('ed-orden');

  document.querySelectorAll('[data-editar]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      edId.value  = btn.dataset.editar;
      edNom.value = btn.dataset.nombre;
      edOrd.value = btn.dataset.orden;
      panel.hidden = false;
      edNom.focus();
      panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  });

  document.getElementById('btn-cancelar').addEventListener('click', function () {
    panel.hidden = true;
  });
})();
</script>

<?php include 'includes/footer-admin.php'; ?>
