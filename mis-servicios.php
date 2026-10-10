<?php
// =============================================================
// mis-servicios.php — Gestión de servicios del profesional
// Proyecto: OficioRed
// =============================================================

require_once 'includes/db.php';
require_once 'includes/funciones.php';

iniciar_sesion();
requerir_login();

$pid = (int)$_SESSION['profesional_id'];

// Estado de la página
$editar_id       = null;   // ID del servicio en modo edición
$editar_servicio = null;   // Fila del servicio en edición
$nombre_form     = '';     // Valor pre-cargado en el campo nombre
$error_form      = '';     // Error inline (sin redirect)
$flash_ok        = '';     // Mensaje de éxito (leído de sesión)
$flash_error     = '';     // Error de operación (leído de sesión)

// =============================================================
// MANEJO DE POST
// =============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verificar_csrf();

    $accion = $_POST['accion'] ?? '';
    $sid    = filter_input(INPUT_POST, 'servicio_id', FILTER_VALIDATE_INT);

    // ------- AGREGAR -------
    if ($accion === 'agregar') {

        $nombre      = trim($_POST['nombre'] ?? '');
        $nombre_form = $nombre;

        if ($nombre === '') {
            $error_form = 'El nombre del servicio es obligatorio.';

        } elseif (mb_strlen($nombre) > 100) {
            $error_form = 'El nombre no puede superar los 100 caracteres.';

        } else {
            $stmtC = $pdo->prepare(
                "SELECT COUNT(*) FROM servicios WHERE profesional_id = ? AND activo = 1"
            );
            $stmtC->execute([$pid]);
            if ((int)$stmtC->fetchColumn() >= 15) {
                $error_form = 'Alcanzaste el límite de 15 servicios activos.';

            } else {
                $stmtD = $pdo->prepare(
                    "SELECT id, activo FROM servicios
                     WHERE profesional_id = ? AND nombre = ? LIMIT 1"
                );
                $stmtD->execute([$pid, $nombre]);
                $dup = $stmtD->fetch();

                if ($dup && $dup['activo']) {
                    $error_form = 'Ya tenés ese servicio activo.';

                } elseif ($dup && !$dup['activo']) {
                    $error_form = 'Ya tenés ese servicio desactivado. Podés reactivarlo desde tu lista.';

                } else {
                    $pdo->prepare(
                        "INSERT INTO servicios (profesional_id, nombre, activo) VALUES (?, ?, 1)"
                    )->execute([$pid, $nombre]);
                    $_SESSION['flash_srv'] = ['tipo' => 'ok', 'msg' => 'Servicio agregado correctamente.'];
                    redirigir('mis-servicios.php');
                }
            }
        }

    // ------- EDITAR -------
    } elseif ($accion === 'editar') {

        if (!$sid || $sid <= 0) {
            redirigir('mis-servicios.php');
        }

        // Verificar propiedad
        $stmtO = $pdo->prepare(
            "SELECT id, nombre FROM servicios WHERE id = ? AND profesional_id = ? LIMIT 1"
        );
        $stmtO->execute([$sid, $pid]);
        $editar_servicio = $stmtO->fetch();

        if (!$editar_servicio) {
            redirigir('mis-servicios.php');
        }

        $editar_id   = $sid;
        $nombre      = trim($_POST['nombre'] ?? '');
        $nombre_form = $nombre;

        if ($nombre === '') {
            $error_form = 'El nombre del servicio es obligatorio.';

        } elseif (mb_strlen($nombre) > 100) {
            $error_form = 'El nombre no puede superar los 100 caracteres.';

        } else {
            // Duplicado excluyendo el propio servicio
            $stmtD = $pdo->prepare(
                "SELECT id, activo FROM servicios
                 WHERE profesional_id = ? AND nombre = ? AND id != ? LIMIT 1"
            );
            $stmtD->execute([$pid, $nombre, $sid]);
            $dup = $stmtD->fetch();

            if ($dup && $dup['activo']) {
                $error_form = 'Ya tenés ese servicio activo.';

            } elseif ($dup && !$dup['activo']) {
                $error_form = 'Ya tenés ese servicio desactivado.';

            } else {
                $pdo->prepare(
                    "UPDATE servicios SET nombre = ? WHERE id = ? AND profesional_id = ?"
                )->execute([$nombre, $sid, $pid]);
                $_SESSION['flash_srv'] = ['tipo' => 'ok', 'msg' => 'Servicio actualizado correctamente.'];
                redirigir('mis-servicios.php');
            }
        }

    // ------- TOGGLE ACTIVO/INACTIVO -------
    } elseif ($accion === 'toggle') {

        if (!$sid || $sid <= 0) {
            redirigir('mis-servicios.php');
        }

        $stmtO = $pdo->prepare(
            "SELECT id, activo FROM servicios WHERE id = ? AND profesional_id = ? LIMIT 1"
        );
        $stmtO->execute([$sid, $pid]);
        $srv = $stmtO->fetch();

        if (!$srv) {
            redirigir('mis-servicios.php');
        }

        // Al reactivar, verificar límite de 15 activos
        if ($srv['activo'] == 0) {
            $stmtC = $pdo->prepare(
                "SELECT COUNT(*) FROM servicios WHERE profesional_id = ? AND activo = 1"
            );
            $stmtC->execute([$pid]);
            if ((int)$stmtC->fetchColumn() >= 15) {
                $_SESSION['flash_srv'] = [
                    'tipo' => 'error',
                    'msg'  => 'Tenés 15 servicios activos. Desactivá uno antes de reactivar este.',
                ];
                redirigir('mis-servicios.php');
            }
        }

        $pdo->prepare(
            "UPDATE servicios SET activo = 1 - activo WHERE id = ? AND profesional_id = ?"
        )->execute([$sid, $pid]);

        $msg = $srv['activo'] ? 'Servicio desactivado.' : 'Servicio reactivado.';
        $_SESSION['flash_srv'] = ['tipo' => 'ok', 'msg' => $msg];
        redirigir('mis-servicios.php');

    // ------- ELIMINAR -------
    } elseif ($accion === 'eliminar') {

        if (!$sid || $sid <= 0) {
            redirigir('mis-servicios.php');
        }

        $stmtO = $pdo->prepare(
            "SELECT id FROM servicios WHERE id = ? AND profesional_id = ? LIMIT 1"
        );
        $stmtO->execute([$sid, $pid]);
        if (!$stmtO->fetch()) {
            redirigir('mis-servicios.php');
        }

        $pdo->prepare(
            "DELETE FROM servicios WHERE id = ? AND profesional_id = ?"
        )->execute([$sid, $pid]);

        $_SESSION['flash_srv'] = ['tipo' => 'ok', 'msg' => 'Servicio eliminado.'];
        redirigir('mis-servicios.php');
    }

// =============================================================
// GET: leer flash + modo edición
// =============================================================
} else {

    if (isset($_SESSION['flash_srv'])) {
        $f = $_SESSION['flash_srv'];
        unset($_SESSION['flash_srv']);
        if ($f['tipo'] === 'ok') {
            $flash_ok    = $f['msg'];
        } else {
            $flash_error = $f['msg'];
        }
    }

    $editar_id_get = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
    if ($editar_id_get && $editar_id_get > 0) {
        $stmtE = $pdo->prepare(
            "SELECT id, nombre, activo FROM servicios WHERE id = ? AND profesional_id = ? LIMIT 1"
        );
        $stmtE->execute([$editar_id_get, $pid]);
        $editar_servicio = $stmtE->fetch();
        if ($editar_servicio) {
            $editar_id   = $editar_id_get;
            $nombre_form = $editar_servicio['nombre'];
        }
    }
}

// =============================================================
// Cargar servicios (siempre, para el listado)
// =============================================================
$stmtS = $pdo->prepare(
    "SELECT id, nombre, activo
     FROM servicios
     WHERE profesional_id = ?
     ORDER BY activo DESC, created_at ASC"
);
$stmtS->execute([$pid]);
$todos_servicios = $stmtS->fetchAll();

$activos_lista   = array_values(array_filter($todos_servicios, fn($s) => $s['activo'] == 1));
$inactivos_lista = array_values(array_filter($todos_servicios, fn($s) => $s['activo'] == 0));
$total_activos   = count($activos_lista);

$titulo_pagina = 'Mis servicios';
require_once 'includes/header.php';
?>

<section class="seccion">
  <div class="container">

    <!-- ENCABEZADO + CONTADOR -->
    <div class="servicios-header">
      <h1>Mis servicios</h1>
      <span class="servicios-contador <?= $total_activos >= 15 ? 'servicios-contador--lleno' : '' ?>">
        <?= $total_activos ?> de 15 activos
      </span>
    </div>

    <!-- MENSAJES FLASH -->
    <?php if ($flash_ok): ?>
      <div class="alerta alerta-exito">
        <i data-lucide="check-circle"></i>
        <span><?= sanitizar($flash_ok) ?></span>
      </div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
      <div class="alerta alerta-error">
        <i data-lucide="alert-circle"></i>
        <span><?= sanitizar($flash_error) ?></span>
      </div>
    <?php endif; ?>

    <!-- FORMULARIO: EDITAR o AGREGAR -->
    <?php if ($editar_id): ?>

      <div class="servicios-form">
        <p class="servicios-form__titulo">
          <i data-lucide="pencil"></i> Editando servicio
        </p>
        <?php if ($error_form): ?>
          <div class="alerta alerta-error">
            <i data-lucide="alert-circle"></i>
            <span><?= sanitizar($error_form) ?></span>
          </div>
        <?php endif; ?>
        <form action="mis-servicios.php" method="POST" class="form-servicio">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="accion" value="editar">
          <input type="hidden" name="servicio_id" value="<?= (int)$editar_id ?>">
          <div class="form-servicio__campo">
            <input class="form-input <?= $error_form ? 'input-error' : '' ?>"
                   type="text" name="nombre"
                   value="<?= sanitizar($nombre_form) ?>"
                   placeholder="Nombre del servicio"
                   maxlength="100" required>
            <button type="submit" class="btn btn-primary">
              <i data-lucide="save"></i> Guardar
            </button>
          </div>
        </form>
        <a href="mis-servicios.php" class="link-cancelar">
          <i data-lucide="x"></i> Cancelar edición
        </a>
      </div>

    <?php elseif ($total_activos < 15): ?>

      <div class="servicios-form">
        <?php if ($error_form): ?>
          <div class="alerta alerta-error">
            <i data-lucide="alert-circle"></i>
            <span><?= sanitizar($error_form) ?></span>
          </div>
        <?php endif; ?>
        <form action="mis-servicios.php" method="POST" class="form-servicio">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="accion" value="agregar">
          <div class="form-servicio__campo">
            <input class="form-input <?= $error_form ? 'input-error' : '' ?>"
                   type="text" name="nombre"
                   value="<?= sanitizar($nombre_form) ?>"
                   placeholder="Ej: Instalación eléctrica, Reparación de tableros..."
                   maxlength="100" required>
            <button type="submit" class="btn btn-primary">
              <i data-lucide="plus"></i> Agregar
            </button>
          </div>
        </form>
      </div>

    <?php else: ?>

      <div class="alerta alerta-error">
        <i data-lucide="alert-circle"></i>
        <span>Alcanzaste el límite de 15 servicios activos. Desactivá alguno para agregar uno nuevo.</span>
      </div>

    <?php endif; ?>

    <!-- LISTA ACTIVOS -->
    <?php if (!empty($activos_lista)): ?>
      <div class="servicios-seccion">
        <h2>Activos</h2>
        <ul class="servicios-lista-gestion">
          <?php foreach ($activos_lista as $s): ?>
            <li class="servicio-row">
              <span class="servicio-row__nombre"><?= sanitizar($s['nombre']) ?></span>
              <div class="servicio-row__acciones">

                <a href="mis-servicios.php?editar=<?= (int)$s['id'] ?>"
                   class="btn btn-secondary btn-sm">
                  <i data-lucide="pencil"></i> Editar
                </a>

                <form method="POST" action="mis-servicios.php" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="accion" value="toggle">
                  <input type="hidden" name="servicio_id" value="<?= (int)$s['id'] ?>">
                  <button type="submit" class="btn btn-secondary btn-sm">
                    <i data-lucide="eye-off"></i> Desactivar
                  </button>
                </form>

                <form method="POST" action="mis-servicios.php" style="display:inline;"
                      onsubmit="return confirm('¿Seguro que querés eliminar este servicio?\nEsta acción no se puede deshacer.')">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="servicio_id" value="<?= (int)$s['id'] ?>">
                  <button type="submit" class="btn-danger-sm">
                    <i data-lucide="trash-2"></i>
                  </button>
                </form>

              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

    <?php elseif (!$editar_id && empty($inactivos_lista)): ?>
      <p class="texto-secundario" style="margin-top: 1.5rem;">
        Todavía no tenés servicios. Agregá el primero arriba.
      </p>
    <?php endif; ?>

    <!-- LISTA INACTIVOS -->
    <?php if (!empty($inactivos_lista)): ?>
      <div class="servicios-seccion servicios-seccion--inactivos">
        <h2>Inactivos</h2>
        <p class="texto-secundario">Los clientes no los ven. Podés reactivarlos cuando quieras.</p>
        <ul class="servicios-lista-gestion servicios-lista-gestion--inactivos">
          <?php foreach ($inactivos_lista as $s): ?>
            <li class="servicio-row servicio-row--inactivo">
              <span class="servicio-row__nombre"><?= sanitizar($s['nombre']) ?></span>
              <div class="servicio-row__acciones">

                <form method="POST" action="mis-servicios.php" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="accion" value="toggle">
                  <input type="hidden" name="servicio_id" value="<?= (int)$s['id'] ?>">
                  <button type="submit" class="btn btn-secondary btn-sm">
                    <i data-lucide="eye"></i> Activar
                  </button>
                </form>

                <form method="POST" action="mis-servicios.php" style="display:inline;"
                      onsubmit="return confirm('¿Seguro que querés eliminar este servicio?\nEsta acción no se puede deshacer.')">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="servicio_id" value="<?= (int)$s['id'] ?>">
                  <button type="submit" class="btn-danger-sm">
                    <i data-lucide="trash-2"></i>
                  </button>
                </form>

              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div style="margin-top: 2.5rem;">
      <a href="dashboard.php" class="link-volver">
        <i data-lucide="arrow-left"></i> Volver al panel
      </a>
    </div>

  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
