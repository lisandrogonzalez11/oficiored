<?php
// =============================================================
// admin/apariencia.php — Editor de apariencia del sitio
// Proyecto: OficioRed
// =============================================================

require_once '../includes/db.php';
require_once '../includes/funciones.php';

iniciar_sesion();
requerir_admin();

$titulo_pagina = 'Apariencia';
$pagina_activa = 'apariencia';

// ----------------------------------------------------------------
// Helper: subir imagen al directorio uploads/hero/
// ----------------------------------------------------------------
function subir_imagen_hero(string $campo): string {
    if (empty($_FILES[$campo]['tmp_name'])) return '';
    $file    = $_FILES[$campo];
    if ($file['error'] !== UPLOAD_ERR_OK) return '';
    if ($file['size'] > 6 * 1024 * 1024) return ''; // máx 6 MB

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $exts  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($exts[$mime])) return '';

    $nombre  = 'hero_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $exts[$mime];
    $destino = __DIR__ . '/../uploads/hero/' . $nombre;
    if (!is_dir(dirname($destino))) mkdir(dirname($destino), 0755, true);
    if (move_uploaded_file($file['tmp_name'], $destino)) {
        return 'uploads/hero/' . $nombre;
    }
    return '';
}

// ----------------------------------------------------------------
// POST: guardar
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $hero_tipo = in_array($_POST['hero_tipo'] ?? '', ['color', 'imagen', 'video']) ? $_POST['hero_tipo'] : 'color';
    $cta_tipo  = in_array($_POST['cta_bg_tipo'] ?? '', ['color', 'imagen'])         ? $_POST['cta_bg_tipo'] : 'color';

    // Imagen hero: subida de archivo tiene prioridad sobre URL
    $hero_img = subir_imagen_hero('hero_imagen_file');
    if ($hero_img === '') $hero_img = trim($_POST['hero_imagen_url'] ?? '');

    // Imagen CTA
    $cta_img = subir_imagen_hero('cta_imagen_file');
    if ($cta_img === '') $cta_img = trim($_POST['cta_imagen_url'] ?? '');

    $campos = [
        'hero_tipo'             => $hero_tipo,
        'hero_imagen_url'       => $hero_img,
        'hero_video_url'        => trim($_POST['hero_video_url'] ?? ''),
        'hero_overlay_opacidad' => (string) max(0, min(90, (int)($_POST['hero_overlay_opacidad'] ?? 55))),
        'hero_imagen_pos_x'     => (string) max(0, min(100, (int)($_POST['hero_imagen_pos_x'] ?? 50))),
        'hero_imagen_pos_y'     => (string) max(0, min(100, (int)($_POST['hero_imagen_pos_y'] ?? 50))),
        'hero_titulo'           => trim($_POST['hero_titulo']    ?? ''),
        'hero_subtitulo'        => trim($_POST['hero_subtitulo'] ?? ''),
        'cta_bg_tipo'           => $cta_tipo,
        'cta_imagen_url'        => $cta_img,
        'cta_overlay_opacidad'  => (string) max(0, min(90, (int)($_POST['cta_overlay_opacidad'] ?? 50))),
        'cta_imagen_pos_x'      => (string) max(0, min(100, (int)($_POST['cta_imagen_pos_x'] ?? 50))),
        'cta_imagen_pos_y'      => (string) max(0, min(100, (int)($_POST['cta_imagen_pos_y'] ?? 50))),
        'cta_titulo'            => trim($_POST['cta_titulo']     ?? ''),
        'cta_subtitulo'         => trim($_POST['cta_subtitulo']  ?? ''),
    ];

    $stmt = $pdo->prepare(
        "INSERT INTO configuracion_sitio (clave, valor) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
    );
    foreach ($campos as $k => $v) $stmt->execute([$k, $v]);

    $_SESSION['flash_admin']      = 'Apariencia guardada correctamente.';
    $_SESSION['flash_admin_tipo'] = 'exito';
    redirigir('apariencia.php');
}

// ----------------------------------------------------------------
// Leer config actual
// ----------------------------------------------------------------
$cfg = [];
$stmt_cfg = $pdo->query("SELECT clave, valor FROM configuracion_sitio");
while ($row = $stmt_cfg->fetch()) $cfg[$row['clave']] = $row['valor'];

$hero_tipo      = $cfg['hero_tipo']             ?? 'color';
$hero_img_url   = $cfg['hero_imagen_url']        ?? '';
$hero_vid_url   = $cfg['hero_video_url']         ?? '';
$hero_overlay   = (int)($cfg['hero_overlay_opacidad'] ?? 55);
$hero_pos_x     = (int)($cfg['hero_imagen_pos_x'] ?? 50);
$hero_pos_y     = (int)($cfg['hero_imagen_pos_y'] ?? 50);
$hero_titulo    = $cfg['hero_titulo']            ?? 'Encontrá el profesional que necesitás';
$hero_subtitulo = $cfg['hero_subtitulo']         ?? 'Electricistas, plomeros, albañiles y más en El Colorado';

$cta_tipo       = $cfg['cta_bg_tipo']            ?? 'color';
$cta_img_url    = $cfg['cta_imagen_url']         ?? '';
$cta_overlay    = (int)($cfg['cta_overlay_opacidad'] ?? 50);
$cta_pos_x      = (int)($cfg['cta_imagen_pos_x'] ?? 50);
$cta_pos_y      = (int)($cfg['cta_imagen_pos_y'] ?? 50);
$cta_titulo     = $cfg['cta_titulo']             ?? '¿Sos trabajador independiente?';
$cta_subtitulo  = $cfg['cta_subtitulo']          ?? 'Publicá tu perfil gratis y conseguí más clientes en El Colorado.';

// URL para preview (archivos locales necesitan prefijo ../)
function preview_url(string $url): string {
    return strpos($url, 'uploads/') === 0 ? '../' . $url : $url;
}

require_once 'includes/header-admin.php';
?>

<div class="admin-page-header">
  <h1>Apariencia del sitio</h1>
  <a href="../index.php" target="_blank" class="btn btn-sm btn-secundario">Ver sitio →</a>
</div>

<form method="post" action="apariencia.php" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

  <!-- ======================== HERO ======================== -->
  <div class="admin-card" style="margin-bottom:1.5rem;">
    <h2 class="apariencia-seccion-titulo">Banner principal (hero)</h2>

    <div class="form-grupo">
      <label class="form-label">Título principal</label>
      <input type="text" name="hero_titulo" id="inp-hero-titulo"
             value="<?= sanitizar($hero_titulo) ?>" class="form-input" maxlength="120">
    </div>
    <div class="form-grupo" style="margin-top:.75rem;">
      <label class="form-label">Subtítulo</label>
      <input type="text" name="hero_subtitulo" id="inp-hero-subtitulo"
             value="<?= sanitizar($hero_subtitulo) ?>" class="form-input" maxlength="200">
    </div>

    <div style="margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--color-border);">
      <p class="form-label" style="margin-bottom:.75rem;">Tipo de fondo</p>
      <div class="apariencia-tipos">
        <?php foreach (['color' => ['Color sólido','Fondo oscuro por defecto'],'imagen'=>['Imagen','Foto de fondo'],'video'=>['Video','YouTube o .mp4 en bucle']] as $v=>[$l,$d]): ?>
        <label class="apariencia-tipo-opt<?= $hero_tipo===$v?' apariencia-tipo-opt--activo':'' ?>"
               data-group="hero">
          <input type="radio" name="hero_tipo" value="<?= $v ?>" class="apariencia-radio"
                 data-group="hero" <?= $hero_tipo===$v?'checked':'' ?>>
          <strong><?= $l ?></strong><small><?= $d ?></small>
        </label>
        <?php endforeach; ?>
      </div>

      <!-- Campo imagen hero -->
      <div class="apariencia-campo" id="hero-campo-imagen"
           style="<?= $hero_tipo!=='imagen'?'display:none;':'' ?>">
        <label class="form-label">URL de imagen</label>
        <input type="url" name="hero_imagen_url" id="hero-inp-img-url"
               value="<?= sanitizar($hero_img_url) ?>" class="form-input"
               placeholder="https://ejemplo.com/foto.jpg">

        <div class="apariencia-upload-sep">
          <span>— o subí un archivo desde tu dispositivo —</span>
        </div>

        <label class="apariencia-upload-btn" for="hero-archivo">
          <i data-lucide="upload"></i> Elegir imagen
        </label>
        <input type="file" name="hero_imagen_file" id="hero-archivo"
               accept="image/jpeg,image/png,image/webp"
               class="apariencia-file-input" data-preview="hero-preview">
        <p class="form-ayuda">
          JPG, PNG o WebP · máx. 6 MB<br>
          <strong>Tamaño ideal: 1920 × 600 px</strong> (proporción ~3:1 horizontal).<br>
          Si la imagen tiene otro tamaño se hará zoom automático para cubrir el espacio completo sin deformarla.
        </p>
      </div>

      <!-- Campo video hero -->
      <div class="apariencia-campo" id="hero-campo-video"
           style="<?= $hero_tipo!=='video'?'display:none;':'' ?>">
        <label class="form-label">URL del video</label>
        <input type="url" name="hero_video_url" id="hero-inp-vid-url"
               value="<?= sanitizar($hero_vid_url) ?>" class="form-input"
               placeholder="https://www.youtube.com/watch?v=...">
        <p class="form-ayuda">
          <strong>YouTube:</strong> pegá la URL normal del video.<br>
          <strong>MP4:</strong> pegá un enlace directo al archivo .mp4.<br>
          <strong>Tamaño ideal:</strong> 1920 × 1080 px (16:9). El video se recortará para cubrir el banner completo.<br>
          La vista previa de video no se muestra aquí; verificala en el sitio real.
        </p>
      </div>

      <!-- Slider overlay hero -->
      <div class="apariencia-campo" id="hero-campo-overlay"
           style="<?= $hero_tipo==='color'?'display:none;':'' ?>">
        <label class="form-label">
          Oscuridad del fondo: <strong id="hero-overlay-val"><?= $hero_overlay ?></strong>%
        </label>
        <input type="range" name="hero_overlay_opacidad" id="hero-overlay-slider"
               min="0" max="90" value="<?= $hero_overlay ?>" class="apariencia-slider">
        <p class="form-ayuda">Aumentalo si el texto no se lee bien sobre la imagen o el video.</p>
      </div>

      <!-- Posición de imagen hero -->
      <div class="apariencia-campo" id="hero-campo-posicion"
           style="<?= $hero_tipo!=='imagen'?'display:none;':'' ?>">
        <label class="form-label">Posición del encuadre</label>
        <div class="apariencia-pos-grid">
          <div>
            <label class="form-label" style="font-size:.8rem;font-weight:500;">
              Horizontal: <strong id="hero-pos-x-val"><?= $hero_pos_x ?></strong>%
              <small style="color:var(--color-text-light)">(0=izquierda · 100=derecha)</small>
            </label>
            <input type="range" name="hero_imagen_pos_x" id="hero-pos-x-slider"
                   min="0" max="100" value="<?= $hero_pos_x ?>" class="apariencia-slider">
          </div>
          <div>
            <label class="form-label" style="font-size:.8rem;font-weight:500;">
              Vertical: <strong id="hero-pos-y-val"><?= $hero_pos_y ?></strong>%
              <small style="color:var(--color-text-light)">(0=arriba · 100=abajo)</small>
            </label>
            <input type="range" name="hero_imagen_pos_y" id="hero-pos-y-slider"
                   min="0" max="100" value="<?= $hero_pos_y ?>" class="apariencia-slider">
          </div>
        </div>
        <p class="form-ayuda">Desplazá para elegir qué parte de la imagen queda centrada en el banner.</p>
      </div>
    </div>

    <!-- Preview hero -->
    <div style="margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--color-border);">
      <p class="form-label" style="margin-bottom:.75rem;">Vista previa del hero</p>
      <div class="apariencia-preview" id="hero-preview"
           <?php if ($hero_tipo==='imagen'&&$hero_img_url): ?>
             style="background-image:url('<?= sanitizar(preview_url($hero_img_url)) ?>');background-position:<?= $hero_pos_x ?>% <?= $hero_pos_y ?>%;"
           <?php endif; ?>>
        <div class="apariencia-preview__overlay" id="hero-overlay-div"
             style="background:rgba(0,0,0,<?= $hero_tipo!=='color'?$hero_overlay/100:0 ?>);"></div>
        <div class="apariencia-preview__contenido">
          <div class="apariencia-preview__titulo" id="hero-prev-titulo"><?= sanitizar($hero_titulo) ?></div>
          <div class="apariencia-preview__subtitulo" id="hero-prev-subtitulo"><?= sanitizar($hero_subtitulo) ?></div>
          <div class="apariencia-preview__buscador">
            <span>¿Qué necesitás?</span>
            <button type="button" disabled>Buscar</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ======================== CTA ======================== -->
  <div class="admin-card" style="margin-bottom:1.5rem;">
    <h2 class="apariencia-seccion-titulo">Sección inferior (CTA)</h2>

    <div class="form-grupo">
      <label class="form-label">Título</label>
      <input type="text" name="cta_titulo" id="inp-cta-titulo"
             value="<?= sanitizar($cta_titulo) ?>" class="form-input" maxlength="120">
    </div>
    <div class="form-grupo" style="margin-top:.75rem;">
      <label class="form-label">Subtítulo</label>
      <input type="text" name="cta_subtitulo" id="inp-cta-subtitulo"
             value="<?= sanitizar($cta_subtitulo) ?>" class="form-input" maxlength="200">
    </div>

    <div style="margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--color-border);">
      <p class="form-label" style="margin-bottom:.75rem;">Tipo de fondo</p>
      <div class="apariencia-tipos">
        <?php foreach (['color'=>['Color sólido','Fondo rojo/oscuro por defecto'],'imagen'=>['Imagen','Foto de fondo']] as $v=>[$l,$d]): ?>
        <label class="apariencia-tipo-opt<?= $cta_tipo===$v?' apariencia-tipo-opt--activo':'' ?>"
               data-group="cta">
          <input type="radio" name="cta_bg_tipo" value="<?= $v ?>" class="apariencia-radio"
                 data-group="cta" <?= $cta_tipo===$v?'checked':'' ?>>
          <strong><?= $l ?></strong><small><?= $d ?></small>
        </label>
        <?php endforeach; ?>
      </div>

      <!-- Campo imagen CTA -->
      <div class="apariencia-campo" id="cta-campo-imagen"
           style="<?= $cta_tipo!=='imagen'?'display:none;':'' ?>">
        <label class="form-label">URL de imagen</label>
        <input type="url" name="cta_imagen_url" id="cta-inp-img-url"
               value="<?= sanitizar($cta_img_url) ?>" class="form-input"
               placeholder="https://ejemplo.com/foto.jpg">

        <div class="apariencia-upload-sep"><span>— o subí un archivo —</span></div>

        <label class="apariencia-upload-btn" for="cta-archivo">
          <i data-lucide="upload"></i> Elegir imagen
        </label>
        <input type="file" name="cta_imagen_file" id="cta-archivo"
               accept="image/jpeg,image/png,image/webp"
               class="apariencia-file-input" data-preview="cta-preview">
        <p class="form-ayuda">
          JPG, PNG o WebP · máx. 6 MB<br>
          <strong>Tamaño ideal: 1920 × 400 px</strong> (proporción ~5:1 horizontal).<br>
          Si la imagen tiene otro tamaño se hará zoom automático para cubrir el espacio.
        </p>
      </div>

      <!-- Slider overlay CTA -->
      <div class="apariencia-campo" id="cta-campo-overlay"
           style="<?= $cta_tipo==='color'?'display:none;':'' ?>">
        <label class="form-label">
          Oscuridad del fondo: <strong id="cta-overlay-val"><?= $cta_overlay ?></strong>%
        </label>
        <input type="range" name="cta_overlay_opacidad" id="cta-overlay-slider"
               min="0" max="90" value="<?= $cta_overlay ?>" class="apariencia-slider">
      </div>

      <!-- Posición de imagen CTA -->
      <div class="apariencia-campo" id="cta-campo-posicion"
           style="<?= $cta_tipo!=='imagen'?'display:none;':'' ?>">
        <label class="form-label">Posición del encuadre</label>
        <div class="apariencia-pos-grid">
          <div>
            <label class="form-label" style="font-size:.8rem;font-weight:500;">
              Horizontal: <strong id="cta-pos-x-val"><?= $cta_pos_x ?></strong>%
              <small style="color:var(--color-text-light)">(0=izquierda · 100=derecha)</small>
            </label>
            <input type="range" name="cta_imagen_pos_x" id="cta-pos-x-slider"
                   min="0" max="100" value="<?= $cta_pos_x ?>" class="apariencia-slider">
          </div>
          <div>
            <label class="form-label" style="font-size:.8rem;font-weight:500;">
              Vertical: <strong id="cta-pos-y-val"><?= $cta_pos_y ?></strong>%
              <small style="color:var(--color-text-light)">(0=arriba · 100=abajo)</small>
            </label>
            <input type="range" name="cta_imagen_pos_y" id="cta-pos-y-slider"
                   min="0" max="100" value="<?= $cta_pos_y ?>" class="apariencia-slider">
          </div>
        </div>
        <p class="form-ayuda">Desplazá para elegir qué parte de la imagen queda centrada en la sección.</p>
      </div>
    </div>

    <!-- Preview CTA -->
    <div style="margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--color-border);">
      <p class="form-label" style="margin-bottom:.75rem;">Vista previa de la sección CTA</p>
      <div class="apariencia-preview apariencia-preview--cta" id="cta-preview"
           <?php if ($cta_tipo==='imagen'&&$cta_img_url): ?>
             style="background-image:url('<?= sanitizar(preview_url($cta_img_url)) ?>');background-position:<?= $cta_pos_x ?>% <?= $cta_pos_y ?>%;"
           <?php else: ?>
             style="background:linear-gradient(135deg,#1A0008,#2D0515 55%,#0A0B1A);"
           <?php endif; ?>>
        <div class="apariencia-preview__overlay" id="cta-overlay-div"
             style="background:rgba(0,0,0,<?= $cta_tipo!=='color'?$cta_overlay/100:0 ?>);"></div>
        <div class="apariencia-preview__contenido">
          <div class="apariencia-preview__titulo" id="cta-prev-titulo"><?= sanitizar($cta_titulo) ?></div>
          <div class="apariencia-preview__subtitulo" id="cta-prev-subtitulo"><?= sanitizar($cta_subtitulo) ?></div>
          <div style="margin-top:.6rem;">
            <span style="background:var(--color-accent);color:#fff;padding:6px 16px;border-radius:999px;font-size:.82rem;font-weight:700;">Registrarme gratis</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
    <button type="submit" class="btn btn-primary">Guardar cambios</button>
    <a href="../index.php" target="_blank" class="btn btn-secundario">Ver sitio →</a>
  </div>
</form>

<script>
(function () {
  function setupSeccion(prefix, inpTitEl, inpSubEl, previewEl, ovlDivEl, ovlSlider, ovlValEl) {
    var posXSlider = document.getElementById(prefix + '-pos-x-slider');
    var posYSlider = document.getElementById(prefix + '-pos-y-slider');
    var posXVal    = document.getElementById(prefix + '-pos-x-val');
    var posYVal    = document.getElementById(prefix + '-pos-y-val');

    function tipoActual() {
      var r = document.querySelector('input[name="' + (prefix==='hero'?'hero_tipo':'cta_bg_tipo') + '"]:checked');
      return r ? r.value : 'color';
    }
    function posicion() {
      var x = posXSlider ? posXSlider.value : 50;
      var y = posYSlider ? posYSlider.value : 50;
      return x + '% ' + y + '%';
    }
    function actualizarPreview() {
      var t    = tipoActual();
      var opac = (t !== 'color' && ovlSlider) ? ovlSlider.value / 100 : 0;
      if (ovlDivEl) ovlDivEl.style.background = 'rgba(0,0,0,' + opac + ')';
      if (t === 'imagen') previewEl.style.backgroundPosition = posicion();

      if (prefix === 'cta' && t === 'color') {
        previewEl.style.backgroundImage = '';
        previewEl.style.background = 'linear-gradient(135deg,#1A0008,#2D0515 55%,#0A0B1A)';
      }
      if (inpTitEl) document.getElementById(prefix + '-prev-titulo').textContent  = inpTitEl.value.trim()  || 'Título';
      if (inpSubEl) document.getElementById(prefix + '-prev-subtitulo').textContent = inpSubEl.value.trim() || 'Subtítulo';
    }

    // Cambio de tipo de fondo
    document.querySelectorAll('input.apariencia-radio[data-group="' + prefix + '"]').forEach(function(r) {
      r.addEventListener('change', function() {
        var t = tipoActual();
        var campoImg = document.getElementById(prefix + '-campo-imagen');
        var campoVid = document.getElementById(prefix + '-campo-video');
        var campoOvl = document.getElementById(prefix + '-campo-overlay');
        var campoPos = document.getElementById(prefix + '-campo-posicion');
        if (campoImg) campoImg.style.display = t === 'imagen' ? '' : 'none';
        if (campoVid) campoVid.style.display = t === 'video'  ? '' : 'none';
        if (campoOvl) campoOvl.style.display = t === 'color'  ? 'none' : '';
        if (campoPos) campoPos.style.display = t === 'imagen' ? '' : 'none';
        document.querySelectorAll('.apariencia-tipo-opt[data-group="' + prefix + '"]').forEach(function(el) {
          el.classList.toggle('apariencia-tipo-opt--activo', el.querySelector('input').value === t);
        });
        if (t !== 'imagen') previewEl.style.backgroundImage = '';
        actualizarPreview();
      });
    });

    // Slider opacidad
    if (ovlSlider) {
      ovlSlider.addEventListener('input', function() {
        if (ovlValEl) ovlValEl.textContent = ovlSlider.value;
        actualizarPreview();
      });
    }

    // Sliders de posición
    if (posXSlider) {
      posXSlider.addEventListener('input', function() {
        if (posXVal) posXVal.textContent = posXSlider.value;
        previewEl.style.backgroundPosition = posicion();
      });
    }
    if (posYSlider) {
      posYSlider.addEventListener('input', function() {
        if (posYVal) posYVal.textContent = posYSlider.value;
        previewEl.style.backgroundPosition = posicion();
      });
    }

    // URL de imagen → preview en tiempo real
    var inpImgUrl = document.getElementById(prefix + '-inp-img-url');
    if (inpImgUrl) {
      inpImgUrl.addEventListener('input', function() {
        if (inpImgUrl.value.trim()) {
          previewEl.style.backgroundImage = "url('" + inpImgUrl.value.trim() + "')";
          previewEl.style.backgroundPosition = posicion();
        } else {
          previewEl.style.backgroundImage = '';
        }
        actualizarPreview();
      });
    }

    // Carga de archivo → FileReader → preview instantánea
    var fileInput = document.getElementById(prefix + '-archivo');
    if (fileInput) {
      fileInput.addEventListener('change', function() {
        if (!fileInput.files || !fileInput.files[0]) return;
        var reader = new FileReader();
        reader.onload = function(e) {
          previewEl.style.backgroundImage = "url('" + e.target.result + "')";
          previewEl.style.backgroundPosition = posicion();
          if (inpImgUrl) inpImgUrl.value = '';
          actualizarPreview();
        };
        reader.readAsDataURL(fileInput.files[0]);
      });
    }

    // Título y subtítulo
    if (inpTitEl) inpTitEl.addEventListener('input', actualizarPreview);
    if (inpSubEl) inpSubEl.addEventListener('input', actualizarPreview);
  }

  setupSeccion(
    'hero',
    document.getElementById('inp-hero-titulo'),
    document.getElementById('inp-hero-subtitulo'),
    document.getElementById('hero-preview'),
    document.getElementById('hero-overlay-div'),
    document.getElementById('hero-overlay-slider'),
    document.getElementById('hero-overlay-val')
  );

  setupSeccion(
    'cta',
    document.getElementById('inp-cta-titulo'),
    document.getElementById('inp-cta-subtitulo'),
    document.getElementById('cta-preview'),
    document.getElementById('cta-overlay-div'),
    document.getElementById('cta-overlay-slider'),
    document.getElementById('cta-overlay-val')
  );
})();
</script>

<?php require_once 'includes/footer-admin.php'; ?>
