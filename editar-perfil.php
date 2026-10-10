<?php
// =============================================================
// editar-perfil.php — Edicion del perfil del profesional logueado
// Proyecto: OficioRed
// =============================================================

require_once 'includes/db.php';
require_once 'includes/funciones.php';

iniciar_sesion();

requerir_login();

// Token CSRF.
csrf_token();

// Categorias y zonas para el formulario.
$categorias    = obtener_categorias($pdo);
$zonas_activas = obtener_zonas($pdo);

// Cargar datos actuales del profesional.
$stmt = $pdo->prepare(
    "SELECT id_profesional, nombre, apellido, email, telefono,
            instagram_url, biografia, oficio, zona_cobertura, foto_perfil
     FROM profesionales
     WHERE id_profesional = :id"
);
$stmt->execute([':id' => $_SESSION['profesional_id']]);
$profesional = $stmt->fetch();

if (!$profesional) {
    session_destroy();
    redirigir('login.php');
}

$errores             = [];
$valores             = $profesional;
$flash_foto          = '';
$error_foto          = '';
// Zonas actualmente seleccionadas (IDs) para pre-marcar los checkboxes.
$zonas_seleccionadas = obtener_ids_zonas_profesional($pdo, (int)$_SESSION['profesional_id']);

// Consumir flash de foto de operaciones anteriores.
if (isset($_SESSION['flash_foto'])) {
    $flash_foto = $_SESSION['flash_foto'];
    unset($_SESSION['flash_foto']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Detectar POST demasiado grande: $_POST vacio pero hay contenido.
    if (
        empty($_POST) &&
        isset($_SERVER['CONTENT_LENGTH']) &&
        (int) $_SERVER['CONTENT_LENGTH'] > 0
    ) {
        $error_foto = 'El archivo excede el límite máximo del servidor. Usá una imagen de hasta 5 MB.';

    } else {

        verificar_csrf();

        $accion = $_POST['accion'] ?? '';

        // ---- SUBIR FOTO ----
        if ($accion === 'subir_foto') {

            $resultado = procesar_foto_perfil($_FILES['foto'] ?? []);

            if (!$resultado['ok']) {
                $error_foto = $resultado['error'];
            } else {
                $nuevo_nombre  = $resultado['nombre'];
                $foto_anterior = $profesional['foto_perfil'];
                $ruta_nueva    = __DIR__ . '/uploads/fotos/' . $nuevo_nombre;

                if (!imagejpeg($resultado['imagen'], $ruta_nueva, 85)) {
                    imagedestroy($resultado['imagen']);
                    $error_foto = 'No se pudo guardar la imagen. Intentá de nuevo.';
                } else {
                    imagedestroy($resultado['imagen']);

                    $stmtU = $pdo->prepare(
                        "UPDATE profesionales SET foto_perfil = ?
                         WHERE id_profesional = ?"
                    );
                    $ok = $stmtU->execute([$nuevo_nombre, $_SESSION['profesional_id']]);

                    if (!$ok) {
                        // UPDATE falló: borrar nuevo archivo, conservar el anterior.
                        if (foto_nombre_valido($nuevo_nombre)) {
                            @unlink($ruta_nueva);
                        }
                        $error_foto = 'No se pudo actualizar el perfil. Intentá de nuevo.';
                    } else {
                        // UPDATE exitoso: borrar foto anterior.
                        if (!empty($foto_anterior) && foto_nombre_valido($foto_anterior)) {
                            @unlink(__DIR__ . '/uploads/fotos/' . $foto_anterior);
                        }
                        $_SESSION['flash_foto'] = 'Tu foto de perfil fue actualizada.';
                        redirigir('editar-perfil.php');
                    }
                }
            }

        // ---- ELIMINAR FOTO ----
        } elseif ($accion === 'eliminar_foto') {

            $foto_actual = $profesional['foto_perfil'];

            $stmtU = $pdo->prepare(
                "UPDATE profesionales SET foto_perfil = NULL
                 WHERE id_profesional = ?"
            );
            $stmtU->execute([$_SESSION['profesional_id']]);

            if (!empty($foto_actual) && foto_nombre_valido($foto_actual)) {
                @unlink(__DIR__ . '/uploads/fotos/' . $foto_actual);
            }

            $_SESSION['flash_foto'] = 'Tu foto de perfil fue eliminada.';
            redirigir('editar-perfil.php');

        // ---- DATOS DE PERFIL ----
        } else {

            $nombre         = trim($_POST['nombre'] ?? '');
            $apellido       = trim($_POST['apellido'] ?? '');
            $email          = trim($_POST['email'] ?? '');
            $telefono       = trim($_POST['telefono'] ?? '');
            $instagram_url  = trim($_POST['instagram_url'] ?? '');
            $oficio         = trim($_POST['oficio'] ?? '');
            $zona_cobertura = trim($_POST['zona_cobertura'] ?? '');
            $biografia      = trim($_POST['biografia'] ?? '');

            $valores = compact('nombre', 'apellido', 'email', 'telefono',
                               'instagram_url', 'oficio', 'zona_cobertura', 'biografia');

            $zonas_seleccionadas = array_map('intval',
                array_filter($_POST['zonas'] ?? [], fn($v) => (int)$v > 0)
            );

            $errores = validar_datos_profesional($pdo, [
                'nombre'         => $nombre,
                'apellido'       => $apellido,
                'email'          => $email,
                'oficio'         => $oficio,
                'zona_cobertura' => '',
                'biografia'      => $biografia,
                'telefono'       => $telefono,
            ], ['excluir_id' => $_SESSION['profesional_id']]);

            if (empty($zonas_seleccionadas)) {
                $errores['zonas'] = 'Seleccioná al menos una zona de cobertura.';
            }

            if (empty($errores)) {
                // Resolver categoria_id a partir del nombre de oficio seleccionado.
                $categoria_id = null;
                foreach ($categorias as $cat) {
                    if ($cat['nombre'] === $oficio) {
                        $categoria_id = (int) $cat['id_categoria'];
                        break;
                    }
                }

                $stmt = $pdo->prepare(
                    "UPDATE profesionales
                     SET nombre = ?, apellido = ?, email = ?, telefono = ?,
                         instagram_url = ?, oficio = ?, zona_cobertura = ?,
                         biografia = ?, categoria_id = ?
                     WHERE id_profesional = ?"
                );
                $texto_zona = guardar_zonas_profesional(
                    $pdo, (int)$_SESSION['profesional_id'], $zonas_seleccionadas
                );

                $stmt->execute([
                    $nombre, $apellido, $email, $telefono,
                    $instagram_url ?: null,
                    $oficio, $texto_zona, $biografia,
                    $categoria_id, $_SESSION['profesional_id'],
                ]);

                $_SESSION['mensaje']      = 'Tu perfil fue actualizado correctamente.';
                $_SESSION['mensaje_tipo'] = 'exito';

                redirigir('dashboard.php');
            }
        }
    }
}

$titulo_pagina = 'Editar mi perfil';
require_once 'includes/header.php';
?>

<section class="seccion">
  <div class="container">
    <div class="auth-card">

      <h1>Editar mi perfil</h1>
      <p class="texto-secundario">
        <a href="dashboard.php">
          <i data-lucide="arrow-left"></i> Volver al panel
        </a>
      </p>

      <!-- FOTO DE PERFIL -->
      <div class="foto-upload-section">

        <?php if ($flash_foto): ?>
          <div class="alerta alerta-exito">
            <i data-lucide="check-circle"></i>
            <span><?= sanitizar($flash_foto) ?></span>
          </div>
        <?php endif; ?>
        <?php if ($error_foto): ?>
          <div class="alerta alerta-error">
            <i data-lucide="alert-circle"></i>
            <span><?= sanitizar($error_foto) ?></span>
          </div>
        <?php endif; ?>

        <div class="foto-actual-wrap">
          <?php if (!empty($profesional['foto_perfil']) && foto_nombre_valido($profesional['foto_perfil'])): ?>
            <img src="uploads/fotos/<?= sanitizar($profesional['foto_perfil']) ?>"
                 alt="Tu foto de perfil"
                 class="foto-actual-img">
          <?php else: ?>
            <div class="perfil-avatar perfil-avatar--sm">
              <?= strtoupper(substr($profesional['nombre'], 0, 1)
                  . substr($profesional['apellido'], 0, 1)) ?>
            </div>
          <?php endif; ?>
        </div>

        <form action="editar-perfil.php" method="POST"
              enctype="multipart/form-data" class="foto-upload-form">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="accion" value="subir_foto">
          <div class="foto-upload-campo">
            <input type="file" name="foto" id="foto-input"
                   accept="image/jpeg,image/png,image/webp">
            <button type="submit" class="btn btn-secondary btn-sm" id="btn-subir-foto" disabled>
              <i data-lucide="upload"></i>
              <?= !empty($profesional['foto_perfil']) ? 'Cambiar foto' : 'Subir foto' ?>
            </button>
          </div>
          <div class="preview-foto-wrap" id="preview-foto-wrap" style="display:none;">
            <img id="preview-foto-img" src="" alt="Vista previa" class="preview-foto-img">
            <span class="preview-foto-label">Vista previa</span>
          </div>
          <p class="texto-secundario foto-upload-hint">
            JPEG &middot; PNG &middot; WebP &nbsp;&middot;&nbsp;
            M&iacute;n. 200&times;200 px &nbsp;&middot;&nbsp; M&aacute;x. 5 MB
          </p>
        </form>

        <?php if (!empty($profesional['foto_perfil'])): ?>
          <form action="editar-perfil.php" method="POST"
                onsubmit="return confirm('¿Eliminar tu foto de perfil?')">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="accion" value="eliminar_foto">
            <button type="submit" class="link-cancelar">
              <i data-lucide="trash-2"></i> Eliminar foto
            </button>
          </form>
        <?php endif; ?>

      </div><!-- /foto-upload-section -->

      <hr class="separador-seccion">

      <!-- DATOS DEL PERFIL -->
      <form action="editar-perfil.php" method="POST" novalidate>
        <input type="hidden" name="csrf_token"
               value="<?= csrf_token() ?>">

<?php
$incluir_password              = false;
$zona_placeholder              = '';
$telefono_placeholder          = '';
$biografia_contador_multilinea = true;
include 'includes/form-campos-profesional.php';
?>

        <div style="display: flex; gap: 16px; flex-wrap: wrap;">
          <button type="submit" class="btn btn-primary">
            <i data-lucide="save"></i> Guardar cambios
          </button>
          <a href="dashboard.php" class="btn btn-secondary">
            Cancelar
          </a>
        </div>

      </form>

    </div>
  </div>
</section>

<script>
function configurarPreview(inputId, previewWrapperId, previewImgId, btnId) {
  var input  = document.getElementById(inputId);
  var wrap   = document.getElementById(previewWrapperId);
  var img    = document.getElementById(previewImgId);
  var btn    = document.getElementById(btnId);
  if (!input) return;

  input.addEventListener('change', function() {
    var file = this.files[0];
    if (!file) {
      wrap.style.display = 'none';
      img.src = '';
      if (btn) btn.disabled = true;
      return;
    }
    var reader = new FileReader();
    reader.onload = function(e) {
      img.src = e.target.result;
      wrap.style.display = 'flex';
      if (btn) btn.disabled = false;
    };
    reader.readAsDataURL(file);
  });
}

configurarPreview('foto-input',    'preview-foto-wrap', 'preview-foto-img', 'btn-subir-foto');
configurarPreview('foto-dni-input', 'preview-dni-wrap',  'preview-dni-img',  'btn-subir-dni');
</script>

<?php require_once 'includes/footer.php'; ?>
