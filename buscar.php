<?php
// =============================================================
// buscar.php — Resultados de busqueda de profesionales
// Proyecto: OficioRed
// =============================================================

require_once 'includes/db.php';
require_once 'includes/funciones.php';

// -------------------------------------------------------
// Parametros de entrada
// -------------------------------------------------------
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// Filtro por categoria: validar como entero positivo
$cat_raw         = filter_input(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
$categoria_activa = null;

if ($cat_raw !== null && $cat_raw !== false && $cat_raw > 0) {
    $stmt_c = $pdo->prepare(
        "SELECT id_categoria, nombre FROM categorias WHERE id_categoria = ? LIMIT 1"
    );
    $stmt_c->execute([$cat_raw]);
    $categoria_activa = $stmt_c->fetch();
    // Si el id no existe en BD, se descarta silenciosamente
}

$cat = $categoria_activa ? (int)$categoria_activa['id_categoria'] : null;

// Filtro por zona (debe resolverse antes de la query)
$zona_raw    = filter_input(INPUT_GET, 'zona', FILTER_VALIDATE_INT);
$zona_activa = null;
if ($zona_raw !== null && $zona_raw !== false && $zona_raw > 0) {
    $stmt_z = $pdo->prepare("SELECT id_zona, nombre FROM zonas WHERE id_zona = ? AND activa = 1 LIMIT 1");
    $stmt_z->execute([$zona_raw]);
    $zona_activa = $stmt_z->fetch();
}
$zona_id = $zona_activa ? (int)$zona_activa['id_zona'] : null;

// -------------------------------------------------------
// Titulo de pagina
// -------------------------------------------------------
if ($q !== '' && $cat !== null) {
    $titulo_pagina = '"' . $q . '" en ' . $categoria_activa['nombre'];
} elseif ($q !== '') {
    $titulo_pagina = 'Resultados para "' . $q . '"';
} elseif ($cat !== null) {
    $titulo_pagina = $categoria_activa['nombre'];
} else {
    $titulo_pagina = 'Profesionales';
}
// -------------------------------------------------------
// Paginacion
// -------------------------------------------------------
$POR_PAGINA = 12;
$pagina     = max(1, (int) ($_GET['pagina'] ?? 1));

// -------------------------------------------------------
// Busqueda
// -------------------------------------------------------
$resultados        = [];
$total_resultados  = 0;
$total_paginas     = 1;
$busqueda_invalida = false;

// Solo validar longitud minima si hay texto (no aplica al filtro de categoria solo)
if ($q !== '' && mb_strlen($q) < 2) {
    $busqueda_invalida = true;
} else {
    // WHERE compartido entre COUNT y SELECT
    $where  = " WHERE p.estado = 'activo'";
    $params = [];

    if ($q !== '') {
        // escapar_like() + ESCAPE '#' previene que % y _ del input
        // sean interpretados como comodines LIKE por MariaDB.
        $t = '%' . escapar_like($q) . '%';
        $where .= "
          AND (
               p.oficio   LIKE ? ESCAPE '#'
            OR c.nombre   LIKE ? ESCAPE '#'
            OR p.nombre   LIKE ? ESCAPE '#'
            OR p.apellido LIKE ? ESCAPE '#'
            OR EXISTS (
                   SELECT 1 FROM servicios s
                   WHERE  s.profesional_id = p.id_profesional
                     AND  s.activo = 1
                     AND  s.nombre LIKE ? ESCAPE '#'
               )
          )";
        $params = [$t, $t, $t, $t, $t];
    }

    if ($cat !== null) {
        $where   .= " AND p.categoria_id = ?";
        $params[] = $cat;
    }

    if ($zona_id !== null) {
        $where .= "
          AND EXISTS (
            SELECT 1 FROM profesional_zonas pz2
            JOIN zonas z2 ON z2.id_zona = pz2.zona_id
            WHERE pz2.profesional_id = p.id_profesional
              AND (pz2.zona_id = ? OR z2.es_todo = 1)
          )";
        $params[] = $zona_id;
    }

    $joins = " LEFT JOIN categorias c ON c.id_categoria = p.categoria_id";

    // Total de resultados (para calcular paginas)
    $stmt_count = $pdo->prepare(
        "SELECT COUNT(*) FROM profesionales p" . $joins . $where
    );
    $stmt_count->execute($params);
    $total_resultados = (int) $stmt_count->fetchColumn();
    $total_paginas    = max(1, (int) ceil($total_resultados / $POR_PAGINA));
    $pagina           = min($pagina, $total_paginas); // no pasar del ultimo

    // Resultados de la pagina actual
    $offset = ($pagina - 1) * $POR_PAGINA;
    $sql = "SELECT p.id_profesional, p.nombre, p.apellido, p.oficio,
                   p.zona_cobertura, p.telefono, p.email, p.foto_perfil
            FROM   profesionales p" . $joins . $where
         . " ORDER BY p.nombre ASC"
         . " LIMIT " . $POR_PAGINA . " OFFSET " . $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll();
}

// Categorias y zonas para chips de filtro
$categorias = obtener_categorias($pdo);
$zonas      = obtener_zonas($pdo);

require_once 'includes/header.php';
?>

<!-- BARRA DE BUSQUEDA COMPACTA -->
<section class="barra-busqueda-compacta">
  <div class="container">
    <form action="buscar.php" method="GET" class="form-busqueda">
      <?php if ($cat !== null): ?>
        <input type="hidden" name="cat" value="<?= (int)$cat ?>">
      <?php endif; ?>
      <input type="text" name="q" value="<?= sanitizar($q) ?>"
             placeholder="¿Qué necesitás?"
             minlength="2">
      <button type="submit" class="btn btn-accent">
        <i data-lucide="search"></i> Buscar
      </button>
    </form>
  </div>
</section>

<!-- CHIPS DE CATEGORIA -->
<?php if (!empty($categorias) && !$busqueda_invalida): ?>
<section class="filtros-categoria-wrap">
  <div class="container">
    <div class="filtros-categoria">
      <?php foreach ($categorias as $ctg): ?>
        <?php
          $es_activo = ($cat === (int)$ctg['id_categoria']);
          if ($es_activo) {
              $chip_url = $q !== '' ? 'buscar.php?q=' . urlencode($q) : 'buscar.php';
              if ($zona_id !== null) $chip_url .= ($q !== '' ? '&' : '?') . 'zona=' . $zona_id;
          } else {
              $chip_url = 'buscar.php?cat=' . (int)$ctg['id_categoria'];
              if ($q !== '')       $chip_url .= '&q='   . urlencode($q);
              if ($zona_id !== null) $chip_url .= '&zona=' . $zona_id;
          }
        ?>
        <a href="<?= sanitizar($chip_url) ?>"
           class="chip-categoria<?= $es_activo ? ' chip-categoria--activo' : '' ?>">
          <i data-lucide="<?= sanitizar($ctg['icono_lucide']) ?>"></i>
          <?= sanitizar($ctg['nombre']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- CHIPS DE ZONA -->
<?php if (!empty($zonas) && !$busqueda_invalida): ?>
<section class="filtros-categoria-wrap filtros-zona-wrap">
  <div class="container">
    <div class="filtros-categoria">
      <span class="filtros-zona-label"><i data-lucide="map-pin"></i> Zona:</span>
      <?php foreach ($zonas as $z): ?>
        <?php
          $es_activa_zona = ($zona_id === (int)$z['id_zona']);
          $params_zona = [];
          if ($q !== '')   $params_zona['q']   = $q;
          if ($cat !== null) $params_zona['cat'] = $cat;
          if ($es_activa_zona) {
              $chip_url_z = 'buscar.php' . (!empty($params_zona) ? '?' . http_build_query($params_zona) : '');
              if (empty($params_zona)) $chip_url_z = 'index.php';
          } else {
              $params_zona['zona'] = $z['id_zona'];
              $chip_url_z = 'buscar.php?' . http_build_query($params_zona);
          }
        ?>
        <a href="<?= sanitizar($chip_url_z) ?>"
           class="chip-categoria chip-zona<?= $es_activa_zona ? ' chip-categoria--activo' : '' ?>">
          <?= sanitizar($z['nombre']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- RESULTADOS -->
<section class="seccion">
  <div class="container">

    <?php if ($busqueda_invalida): ?>
      <div class="estado-vacio">
        <i data-lucide="alert-circle"></i>
        <p>Escribí al menos 2 caracteres para buscar.</p>
      </div>

    <?php elseif (empty($resultados)): ?>
      <div class="estado-vacio">
        <i data-lucide="search-x"></i>
        <?php if ($q !== '' && $cat !== null): ?>
          <p>No encontramos "<strong><?= sanitizar($q) ?></strong>"
             en <strong><?= sanitizar($categoria_activa['nombre']) ?></strong>.</p>
          <p class="texto-secundario">Probá con otro término o quitá el filtro de categoría.</p>
        <?php elseif ($q !== ''): ?>
          <p>No encontramos profesionales para "<strong><?= sanitizar($q) ?></strong>".</p>
          <p class="texto-secundario">Intentá con otro término o explorá las categorías.</p>
        <?php else: ?>
          <p>No hay profesionales registrados en
             <strong><?= sanitizar($categoria_activa['nombre']) ?></strong> todavía.</p>
        <?php endif; ?>
        <a href="index.php" class="btn btn-secondary">Volver al inicio</a>
      </div>

    <?php else: ?>
      <h2>
        <i data-lucide="check-circle"></i>
        <?php if ($q === '' && $cat === null): ?>
          <?= $total_resultados ?> <?= $total_resultados === 1 ? 'profesional disponible' : 'profesionales disponibles' ?>
        <?php else: ?>
          <?= $total_resultados ?> <?= $total_resultados === 1 ? 'profesional encontrado' : 'profesionales encontrados' ?>
          <?php if ($q !== ''): ?> para "<?= sanitizar($q) ?>"<?php endif; ?>
          <?php if ($cat !== null): ?> en <span><?= sanitizar($categoria_activa['nombre']) ?></span><?php endif; ?>
        <?php endif; ?>
      </h2>

      <div class="grid-profesionales">
        <?php foreach ($resultados as $p): ?>
          <?php include 'includes/card-profesional.php'; ?>
        <?php endforeach; ?>
      </div>

      <?php if ($total_paginas > 1): ?>
        <?php
          // URL base sin pagina para los links
          $params_url = [];
          if ($q !== '')         $params_url['q']    = $q;
          if ($cat !== null)     $params_url['cat']  = $cat;
          if ($zona_id !== null) $params_url['zona'] = $zona_id;
          $url_base = 'buscar.php?' . http_build_query($params_url);
        ?>
        <nav class="paginacion" aria-label="Paginación">
          <?php if ($pagina > 1): ?>
            <a href="<?= $url_base ?>&pagina=<?= $pagina - 1 ?>" class="paginacion-btn">
              <i data-lucide="chevron-left"></i> Anterior
            </a>
          <?php endif; ?>

          <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="<?= $url_base ?>&pagina=<?= $i ?>"
               class="paginacion-num<?= $i === $pagina ? ' paginacion-num--activo' : '' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>

          <?php if ($pagina < $total_paginas): ?>
            <a href="<?= $url_base ?>&pagina=<?= $pagina + 1 ?>" class="paginacion-btn">
              Siguiente <i data-lucide="chevron-right"></i>
            </a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>

    <?php endif; ?>

  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
