<?php
// =============================================================
// index.php — Home publica con buscador, categorias y destacados
// Proyecto: OficioRed
// =============================================================

require_once 'includes/db.php';
require_once 'includes/funciones.php';

$titulo_pagina     = 'Inicio';
$mostrar_cats_nav  = true;

// Configuracion del hero
$cfg_hero = [];
try {
    $s = $pdo->query("SELECT clave, valor FROM configuracion_sitio");
    while ($r = $s->fetch()) $cfg_hero[$r['clave']] = $r['valor'];
} catch (Exception $e) { /* tabla aún no existe */ }

$hero_tipo      = $cfg_hero['hero_tipo']             ?? 'color';
$hero_titulo    = $cfg_hero['hero_titulo']            ?? 'Encontrá el profesional que necesitás';
$hero_subtitulo = $cfg_hero['hero_subtitulo']         ?? 'Electricistas, plomeros, albañiles y más en El Colorado';
$hero_overlay   = max(0, min(90, (int)($cfg_hero['hero_overlay_opacidad'] ?? 55)));
$hero_img_url   = trim($cfg_hero['hero_imagen_url']   ?? '');
$hero_vid_url   = trim($cfg_hero['hero_video_url']    ?? '');
$hero_pos_x     = max(0, min(100, (int)($cfg_hero['hero_imagen_pos_x'] ?? 50)));
$hero_pos_y     = max(0, min(100, (int)($cfg_hero['hero_imagen_pos_y'] ?? 50)));

// Convierte URL de YouTube a embed con autoplay/loop/mute
function hero_youtube_embed(string $url): string {
    if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?.*v=|embed\/))([a-zA-Z0-9_-]{11})/', $url, $m)) {
        $id = $m[1];
        return 'https://www.youtube.com/embed/' . $id
             . '?autoplay=1&mute=1&loop=1&playlist=' . $id
             . '&controls=0&disablekb=1&modestbranding=1&rel=0&playsinline=1';
    }
    return '';
}

// Categorias para la seccion de explorar.
$categorias = obtener_categorias($pdo);

// Hasta 6 profesionales destacados (mas recientes).
$stmt = $pdo->prepare(
    "SELECT id_profesional, nombre, apellido, oficio,
            zona_cobertura, telefono, email, foto_perfil
     FROM profesionales
     WHERE estado = 'activo'
     ORDER BY fecha_registro DESC
     LIMIT 6"
);
$stmt->execute();
$destacados = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<!-- HERO / BUSCADOR PRINCIPAL -->
<?php
$hero_clase  = 'hero hero--' . htmlspecialchars($hero_tipo, ENT_QUOTES, 'UTF-8');
$hero_style  = '';
if ($hero_tipo === 'imagen' && $hero_img_url) {
    $hero_style = ' style="background-image:url(\'' . htmlspecialchars($hero_img_url, ENT_QUOTES, 'UTF-8') . '\');background-position:' . $hero_pos_x . '% ' . $hero_pos_y . '%"';
}
$es_yt       = $hero_tipo === 'video' && (strpos($hero_vid_url, 'youtube') !== false || strpos($hero_vid_url, 'youtu.be') !== false);
$es_mp4      = $hero_tipo === 'video' && !$es_yt && $hero_vid_url !== '';
$yt_embed    = $es_yt ? hero_youtube_embed($hero_vid_url) : '';
$overlay_css = ($hero_tipo !== 'color') ? 'rgba(0,0,0,' . ($hero_overlay / 100) . ')' : 'transparent';
?>
<section class="<?= $hero_clase ?>"<?= $hero_style ?>>

  <?php if ($hero_tipo !== 'color'): ?>
    <div class="hero__overlay" style="background:<?= $overlay_css ?>;"></div>
  <?php endif; ?>

  <?php if ($es_yt && $yt_embed): ?>
    <div class="hero__video-wrap">
      <iframe class="hero__video-iframe"
              src="<?= htmlspecialchars($yt_embed, ENT_QUOTES, 'UTF-8') ?>"
              allow="autoplay; encrypted-media; gyroscope"
              allowfullscreen frameborder="0" title="Fondo del hero"></iframe>
    </div>
  <?php elseif ($es_mp4): ?>
    <video class="hero__video" autoplay muted loop playsinline>
      <source src="<?= htmlspecialchars($hero_vid_url, ENT_QUOTES, 'UTF-8') ?>">
    </video>
  <?php endif; ?>

  <div class="container">
    <h1><?= htmlspecialchars($hero_titulo, ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars($hero_subtitulo, ENT_QUOTES, 'UTF-8') ?></p>
    <form action="buscar.php" method="GET" class="form-busqueda">
      <input type="text" name="q" id="campo-busqueda"
             placeholder="¿Qué necesitás?"
             minlength="2" required>
      <button type="submit" class="btn btn-accent">
        <i data-lucide="search"></i> Buscar
      </button>
    </form>
  </div>
</section>

<!-- CATEGORIAS DESTACADAS -->
<section class="seccion seccion-categorias">
  <div class="container">
    <h2>Explorá por categoría</h2>
    <div class="grid-categorias">
      <?php foreach ($categorias as $cat): ?>
        <a href="buscar.php?cat=<?= (int)$cat['id_categoria'] ?>"
           class="card-categoria">
          <i data-lucide="<?= sanitizar($cat['icono_lucide']) ?>"></i>
          <span><?= sanitizar($cat['nombre']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- PROFESIONALES DESTACADOS -->
<section class="seccion seccion-destacados">
  <div class="container">
    <h2>Profesionales disponibles</h2>

    <?php if (empty($destacados)): ?>
      <div class="estado-vacio">
        <i data-lucide="users"></i>
        <p>Todavía no hay profesionales registrados.</p>
        <a href="registro.php" class="btn btn-primary">
          ¿Sos trabajador? Registrate
        </a>
      </div>
    <?php else: ?>
      <div class="grid-profesionales">
        <?php foreach ($destacados as $p): ?>
          <?php include 'includes/card-profesional.php'; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- COMO FUNCIONA -->
<section class="seccion seccion-como-funciona">
  <div class="container">
    <h2>¿Cómo funciona?</h2>
    <div class="pasos">
      <div class="paso">
        <i data-lucide="search"></i>
        <p>Buscá el servicio que necesitás</p>
      </div>
      <div class="paso">
        <i data-lucide="user"></i>
        <p>Encontralo cerca tuyo</p>
      </div>
      <div class="paso">
        <i data-lucide="phone"></i>
        <p>Contactalo directamente</p>
      </div>
    </div>
  </div>
</section>

<!-- POR QUE OFICIORED -->
<section class="seccion seccion-confianza">
  <div class="container">
    <div class="confianza-layout">
      <div class="confianza-texto">
        <p class="confianza-eyebrow">Profesionales de El Colorado</p>
        <h2>Trabajá con personas<br>verificadas y de confianza.</h2>
      </div>
      <div class="confianza-descripcion-bloque">
        <p>
          Nuestro equipo revisa cada perfil antes de que aparezca en el directorio.
          Solo publicamos trabajadores reales, de la zona, con datos correctos.
        </p>
        <p>
          Al usar OficioRed podés esperar profesionales locales comprometidos,
          información actualizada y contacto directo — sin comisiones ni intermediarios.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- COMERCIOS QUE CONFIAN -->
<section class="seccion seccion-comercios">
  <div class="container">
    <p class="comercios-eyebrow">Confían en OficioRed</p>
    <div class="comercios-logos">
      <div class="comercios-logo">Municipalidad<br><span>de El Colorado</span></div>
      <div class="comercios-logo">Cooperativa<br><span>Eléctrica Local</span></div>
      <div class="comercios-logo">Inmobiliaria<br><span>Del Norte</span></div>
      <div class="comercios-logo">Constructora<br><span>Formosa</span></div>
      <div class="comercios-logo">Ferretería<br><span>El Clavo</span></div>
      <div class="comercios-logo">Hotel<br><span>Colorado</span></div>
    </div>
  </div>
</section>

<!-- CTA PARA PROFESIONALES (solo visible si no hay sesión activa) -->
<?php if (!esta_logueado()): ?>
<?php
$cta_tipo      = $cfg_hero['cta_bg_tipo']          ?? 'color';
$cta_img_url   = trim($cfg_hero['cta_imagen_url']  ?? '');
$cta_overlay   = max(0, min(90, (int)($cfg_hero['cta_overlay_opacidad'] ?? 50)));
$cta_titulo    = $cfg_hero['cta_titulo']            ?? '¿Sos trabajador independiente?';
$cta_subtitulo = $cfg_hero['cta_subtitulo']         ?? 'Publicá tu perfil gratis y conseguí más clientes en El Colorado.';
$cta_pos_x     = max(0, min(100, (int)($cfg_hero['cta_imagen_pos_x'] ?? 50)));
$cta_pos_y     = max(0, min(100, (int)($cfg_hero['cta_imagen_pos_y'] ?? 50)));

$cta_style = '';
if ($cta_tipo === 'imagen' && $cta_img_url) {
    $url_cta   = htmlspecialchars($cta_img_url, ENT_QUOTES, 'UTF-8');
    $cta_style = ' style="background-image:url(\'' . $url_cta . '\');background-size:cover;background-position:' . $cta_pos_x . '% ' . $cta_pos_y . '%;"';
}
?>
<section class="seccion seccion-cta seccion-cta--<?= htmlspecialchars($cta_tipo, ENT_QUOTES, 'UTF-8') ?>"<?= $cta_style ?>>
  <?php if ($cta_tipo === 'imagen' && $cta_img_url): ?>
    <div class="seccion-cta__overlay" style="background:rgba(0,0,0,<?= $cta_overlay / 100 ?>);"></div>
  <?php endif; ?>
  <div class="container" style="position:relative;z-index:1;">
    <h2><?= htmlspecialchars($cta_titulo, ENT_QUOTES, 'UTF-8') ?></h2>
    <p><?= htmlspecialchars($cta_subtitulo, ENT_QUOTES, 'UTF-8') ?></p>
    <a href="registro.php" class="btn btn-accent">Registrarme gratis</a>
  </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
