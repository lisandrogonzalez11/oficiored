<?php
require_once '../includes/db.php';
require_once '../includes/funciones.php';

iniciar_sesion();
requerir_admin();

$titulo_pagina = 'Categorías';
$pagina_activa = 'categorias';

$error  = '';

// Lista de íconos disponibles para el picker (Lucide 0.263)
$ICONOS_PICKER = [
  'hammer','wrench','scissors','hard-hat','ruler','paintbrush','paint-bucket',
  'tool','screwdriver','drill',
  'zap','plug','lightbulb','battery-charging','power',
  'flame','droplet','droplets','waves','wind','thermometer',
  'home','building','building-2','warehouse','door-open',
  'car','truck','bus','bike','anchor','plane','ship',
  'leaf','tree-pine','sun','cloud','flower-2','sprout','mountain',
  'heart','stethoscope','pill','cross','activity',
  'chef-hat','utensils','coffee','cookie','apple',
  'laptop','monitor','code','cpu','smartphone','tablet','wifi',
  'camera','video','music','mic','headphones',
  'sparkles','star','award','medal','trophy',
  'book-open','graduation-cap','pencil','pen',
  'lock','key','shield','shield-check',
  'briefcase','tag','box','package','archive',
  'shopping-cart','shopping-bag','store',
  'users','user-check','user','contact',
  'map-pin','navigation','compass','globe',
  'settings','sliders','cog',
  'phone','mail','message-circle',
  'image','layout','grid-3x3',
];

// ----------------------------------------------------------------
// Helpers
// ----------------------------------------------------------------
function es_categoria_otros(string $nombre): bool
{
    return mb_strtolower(trim($nombre), 'UTF-8') === 'otros';
}

function contar_profesionales_categoria(PDO $pdo, int $id_categoria): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM profesionales WHERE categoria_id = ?");
    $stmt->execute([$id_categoria]);
    return (int) $stmt->fetchColumn();
}

// ----------------------------------------------------------------
// POST
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $accion = $_POST['accion'] ?? '';

    // ---- CREAR ----
    if ($accion === 'crear') {
        $nombre      = trim($_POST['nombre']      ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $icono       = trim($_POST['icono']       ?? 'briefcase');

        if ($nombre === '') {
            $error = 'El nombre de la categoría es obligatorio.';
        } elseif (mb_strlen($nombre) > 80) {
            $error = 'El nombre no puede superar 80 caracteres.';
        } else {
            try {
                $pdo->prepare(
                    "INSERT INTO categorias (nombre, descripcion, icono_lucide) VALUES (?, ?, ?)"
                )->execute([$nombre, $descripcion ?: null, $icono ?: 'briefcase']);
                $_SESSION['flash_admin']      = "Categoría «$nombre» creada exitosamente.";
                $_SESSION['flash_admin_tipo'] = 'exito';
                redirigir('categorias.php');
            } catch (PDOException $e) {
                $error = $e->getCode() === '23000'
                    ? 'Ya existe una categoría con ese nombre.'
                    : throw $e;
            }
        }
    }

    // ---- EDITAR ----
    elseif ($accion === 'editar') {
        $id_cat      = (int) ($_POST['id_categoria'] ?? 0);
        $nombre      = trim($_POST['nombre']         ?? '');
        $descripcion = trim($_POST['descripcion']    ?? '');
        $icono       = trim($_POST['icono']          ?? 'briefcase');

        if ($id_cat <= 0) {
            $error = 'Categoría inválida.';
        } elseif ($nombre === '') {
            $error = 'El nombre de la categoría es obligatorio.';
        } elseif (mb_strlen($nombre) > 80) {
            $error = 'El nombre no puede superar 80 caracteres.';
        } else {
            try {
                $pdo->prepare(
                    "UPDATE categorias SET nombre = ?, descripcion = ?, icono_lucide = ? WHERE id_categoria = ?"
                )->execute([$nombre, $descripcion ?: null, $icono ?: 'briefcase', $id_cat]);
                $_SESSION['flash_admin']      = "Categoría actualizada exitosamente.";
                $_SESSION['flash_admin_tipo'] = 'exito';
                redirigir('categorias.php');
            } catch (PDOException $e) {
                $error = $e->getCode() === '23000'
                    ? 'Ya existe una categoría con ese nombre.'
                    : throw $e;
            }
        }
    }

    // ---- ELIMINAR ----
    elseif ($accion === 'eliminar') {
        $id_cat = (int) ($_POST['id_categoria'] ?? 0);

        if ($id_cat <= 0) {
            $error = 'Categoría inválida.';
        } else {
            $stmt = $pdo->prepare("SELECT nombre FROM categorias WHERE id_categoria = ?");
            $stmt->execute([$id_cat]);
            $cat  = $stmt->fetch();

            if (!$cat) {
                $error = 'Categoría no encontrada.';
            } elseif (es_categoria_otros($cat['nombre'])) {
                $error = 'La categoría "Otros" no puede eliminarse.';
            } elseif (contar_profesionales_categoria($pdo, $id_cat) > 0) {
                $error = 'No se puede eliminar una categoría que tiene profesionales asociados.';
            } else {
                $pdo->prepare("DELETE FROM categorias WHERE id_categoria = ?")->execute([$id_cat]);
                $_SESSION['flash_admin']      = "Categoría eliminada.";
                $_SESSION['flash_admin_tipo'] = 'info';
                redirigir('categorias.php');
            }
        }
    }
}

// ----------------------------------------------------------------
// GET
// ----------------------------------------------------------------
$categorias = $pdo->query(
    "SELECT c.id_categoria, c.nombre, c.descripcion, c.icono_lucide,
            COUNT(p.id_profesional) AS total_profesionales
     FROM categorias c
     LEFT JOIN profesionales p ON p.categoria_id = c.id_categoria
     GROUP BY c.id_categoria
     ORDER BY c.nombre"
)->fetchAll();

// ----------------------------------------------------------------
// Helper: HTML del picker de íconos (pre-renderizado en PHP)
// ----------------------------------------------------------------
function icono_picker_html(string $pid, string $icono_actual, array $iconos): string
{
    $ico = htmlspecialchars($icono_actual ?: 'briefcase', ENT_QUOTES, 'UTF-8');
    $html  = '<div class="icono-picker-wrap" id="pick-wrap-' . $pid . '">';
    $html .= '  <input type="hidden" name="icono" id="pick-val-' . $pid . '" value="' . $ico . '">';
    // Botón que muestra el ícono seleccionado actualmente
    $html .= '  <button type="button" class="icono-picker-btn" id="pick-btn-' . $pid . '">';
    $html .= '    <span class="icono-picker-preview" id="pick-prev-' . $pid . '">';
    $html .= '      <i data-lucide="' . $ico . '"></i>';
    $html .= '    </span>';
    $html .= '    <span class="icono-picker-name" id="pick-name-' . $pid . '">' . $ico . '</span>';
    $html .= '    <i data-lucide="chevron-down" class="icono-picker-arrow"></i>';
    $html .= '  </button>';
    // Panel desplegable con la cuadrícula de íconos
    $html .= '  <div class="icono-picker-panel" id="pick-panel-' . $pid . '" hidden>';
    $html .= '    <input type="text" placeholder="Buscar ícono…" autocomplete="off"';
    $html .= '           class="icono-picker-buscar" id="pick-buscar-' . $pid . '">';
    $html .= '    <div class="icono-picker-grid" id="pick-grid-' . $pid . '">';
    foreach ($iconos as $ico_name) {
        $s = htmlspecialchars($ico_name, ENT_QUOTES, 'UTF-8');
        $activo = ($ico_name === $icono_actual) ? ' icono-item--activo' : '';
        $html .= '<button type="button" class="icono-item' . $activo . '" data-ico="' . $s . '" title="' . $s . '">';
        $html .= '<i data-lucide="' . $s . '"></i>';
        $html .= '<span>' . $s . '</span>';
        $html .= '</button>';
    }
    $html .= '    </div>';
    $html .= '  </div>';
    $html .= '</div>';
    return $html;
}

include 'includes/header-admin.php';
?>

<div class="admin-page-header">
  <h1>Categorías</h1>
  <a href="index.php">← Volver al inicio</a>
</div>

<?php if ($error !== ''): ?>
  <div class="alerta alerta-error" style="margin-bottom:1rem;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Formulario crear -->
<form method="post" action="categorias.php" class="admin-form-inline">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
  <input type="hidden" name="accion" value="crear">
  <h3 style="margin:0 0 0.5rem;font-size:0.95rem;">Nueva categoría</h3>
  <div class="fg">
    <label for="cn-nombre">Nombre</label>
    <input type="text" id="cn-nombre" name="nombre" maxlength="80" required placeholder="Ej: Carpintería">
  </div>
  <div class="fg">
    <label for="cn-descripcion">Descripción (opcional)</label>
    <input type="text" id="cn-descripcion" name="descripcion" maxlength="255" placeholder="Breve descripción">
  </div>
  <div class="fg" style="flex:0 0 220px;">
    <label>Ícono</label>
    <?= icono_picker_html('crear', 'briefcase', $ICONOS_PICKER) ?>
  </div>
  <div style="padding-top:1.2rem;">
    <button type="submit" class="btn btn-primary btn-sm">
      <i data-lucide="plus"></i> Crear
    </button>
  </div>
</form>

<!-- Tabla de categorías -->
<div class="admin-card">
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead>
        <tr>
          <th>#</th>
          <th style="width:52px;text-align:center;">Ícono</th>
          <th>Nombre</th>
          <th>Profesionales</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categorias as $cat):
          $protegida     = es_categoria_otros($cat['nombre']);
          $tiene_pros    = (int)$cat['total_profesionales'] > 0;
          $puede_eliminar = !$protegida && !$tiene_pros;
        ?>
        <tr>
          <td><?= $cat['id_categoria'] ?></td>
          <td style="text-align:center;">
            <span class="icono-tabla-preview">
              <i data-lucide="<?= sanitizar($cat['icono_lucide']) ?>"></i>
            </span>
          </td>
          <td>
            <strong><?= sanitizar($cat['nombre']) ?></strong>
            <small style="display:block;color:var(--color-text-light);font-size:0.72rem;"><?= sanitizar($cat['icono_lucide']) ?></small>
            <?php if ($protegida): ?>
              <span style="font-size:0.72rem;color:var(--color-text-light);">(protegida)</span>
            <?php endif; ?>
          </td>
          <td><?= (int)$cat['total_profesionales'] ?></td>
          <td>
            <div class="admin-acciones">
              <button type="button" class="btn btn-sm btn-secundario"
                      data-editar="<?= $cat['id_categoria'] ?>"
                      data-nombre="<?= htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                      data-descripcion="<?= htmlspecialchars($cat['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                      data-icono="<?= htmlspecialchars($cat['icono_lucide'], ENT_QUOTES, 'UTF-8') ?>">
                <i data-lucide="pencil"></i> Editar
              </button>
              <?php if ($puede_eliminar): ?>
                <form method="post" action="categorias.php"
                      onsubmit="return confirm('¿Eliminar «<?= addslashes(sanitizar($cat['nombre'])) ?>»? Esta acción no se puede deshacer.');">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="id_categoria" value="<?= $cat['id_categoria'] ?>">
                  <button type="submit" class="btn btn-sm btn-secundario" style="color:var(--color-primary);">
                    <i data-lucide="trash-2"></i> Eliminar
                  </button>
                </form>
              <?php else: ?>
                <div style="display:inline-flex;align-items:center;gap:0.4rem;">
                  <button type="button" class="btn btn-sm btn-secundario" disabled
                          title="<?= $protegida
                            ? 'Categoría protegida'
                            : 'Tiene ' . (int)$cat['total_profesionales'] . ' profesional(es) asignado(s). Reasignalos a otra categoría primero.' ?>">
                    <i data-lucide="trash-2"></i> Eliminar
                  </button>
                  <?php if ($tiene_pros): ?>
                    <span style="font-size:0.72rem;color:var(--color-text-light);">
                      <i data-lucide="lock" style="width:12px;height:12px;vertical-align:middle;"></i>
                      <?= (int)$cat['total_profesionales'] ?> prof.
                    </span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Panel editar -->
<div id="panel-editar" hidden style="margin-top:-0.5rem;margin-bottom:1.5rem;">
  <form method="post" action="categorias.php" class="admin-form-inline" style="border-color:var(--color-primary);">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="accion" value="editar">
    <input type="hidden" name="id_categoria" id="ed-id">
    <h3 style="margin:0 0 0.5rem;font-size:0.95rem;color:var(--color-primary);">Editar categoría</h3>
    <div class="fg">
      <label for="ed-nombre">Nombre</label>
      <input type="text" id="ed-nombre" name="nombre" maxlength="80" required>
    </div>
    <div class="fg">
      <label for="ed-descripcion">Descripción</label>
      <input type="text" id="ed-descripcion" name="descripcion" maxlength="255">
    </div>
    <div class="fg" style="flex:0 0 220px;">
      <label>Ícono</label>
      <?= icono_picker_html('editar', 'briefcase', $ICONOS_PICKER) ?>
    </div>
    <div style="padding-top:1.2rem;display:flex;gap:0.5rem;">
      <button type="submit" class="btn btn-primary btn-sm">
        <i data-lucide="save"></i> Guardar
      </button>
      <button type="button" id="btn-cancelar-editar" class="btn btn-sm btn-secundario">
        Cancelar
      </button>
    </div>
  </form>
</div>

<script>
(function () {
  // ----------------------------------------------------------------
  // Picker de íconos
  // ----------------------------------------------------------------
  function setupPicker(pid) {
    var btn    = document.getElementById('pick-btn-'    + pid);
    var panel  = document.getElementById('pick-panel-'  + pid);
    var grid   = document.getElementById('pick-grid-'   + pid);
    var buscar = document.getElementById('pick-buscar-' + pid);
    var valInp = document.getElementById('pick-val-'    + pid);
    var prevEl = document.getElementById('pick-prev-'   + pid);
    var nameEl = document.getElementById('pick-name-'   + pid);
    if (!btn) return;

    // Abrir / cerrar
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var estaAbierto = !panel.hidden;
      cerrarTodos();
      if (!estaAbierto) {
        panel.hidden = false;
        buscar.value = '';
        mostrarTodos(grid);
        buscar.focus();
        scrollAlActivo(grid);
      }
    });

    // Buscar
    buscar.addEventListener('input', function () {
      var q = this.value.trim().toLowerCase();
      grid.querySelectorAll('.icono-item').forEach(function (item) {
        item.style.display = (q === '' || item.dataset.ico.includes(q)) ? '' : 'none';
      });
    });

    // Seleccionar ícono
    grid.addEventListener('click', function (e) {
      var item = e.target.closest('.icono-item');
      if (!item) return;
      seleccionarIcono(pid, item.dataset.ico, item, valInp, nameEl, prevEl, grid);
      panel.hidden = true;
    });
  }

  function seleccionarIcono(pid, ico, itemEl, valInp, nameEl, prevEl, grid) {
    valInp.value = ico;
    nameEl.textContent = ico;
    // Clonar el SVG del ítem seleccionado al preview del botón
    var svg = itemEl ? itemEl.querySelector('svg') : null;
    if (svg) {
      prevEl.innerHTML = '';
      prevEl.appendChild(svg.cloneNode(true));
    } else {
      prevEl.innerHTML = '<i data-lucide="' + ico + '"></i>';
      lucide.createIcons();
    }
    // Marcar activo
    if (grid) {
      grid.querySelectorAll('.icono-item').forEach(function (b) {
        b.classList.toggle('icono-item--activo', b.dataset.ico === ico);
      });
    }
  }

  function mostrarTodos(grid) {
    grid.querySelectorAll('.icono-item').forEach(function (b) { b.style.display = ''; });
  }

  function scrollAlActivo(grid) {
    var activo = grid.querySelector('.icono-item--activo');
    if (activo) activo.scrollIntoView({ block: 'nearest' });
  }

  function cerrarTodos() {
    document.querySelectorAll('.icono-picker-panel').forEach(function (p) {
      p.hidden = true;
    });
  }

  // Cerrar al clic fuera
  document.addEventListener('click', cerrarTodos);

  // Inicializar pickers (los íconos ya están renderizados por Lucide al final del body)
  setupPicker('crear');
  setupPicker('editar');

  // ----------------------------------------------------------------
  // Panel editar: cargar datos desde el botón
  // ----------------------------------------------------------------
  var panelEditar = document.getElementById('panel-editar');

  document.querySelectorAll('[data-editar]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var ico  = btn.dataset.icono || 'briefcase';
      document.getElementById('ed-id').value          = btn.dataset.editar;
      document.getElementById('ed-nombre').value      = btn.dataset.nombre;
      document.getElementById('ed-descripcion').value = btn.dataset.descripcion;

      // Actualizar picker editar con el ícono de esta categoría
      var valInp = document.getElementById('pick-val-editar');
      var nameEl = document.getElementById('pick-name-editar');
      var prevEl = document.getElementById('pick-prev-editar');
      var grid   = document.getElementById('pick-grid-editar');
      var itemEl = grid ? grid.querySelector('[data-ico="' + ico + '"]') : null;
      seleccionarIcono('editar', ico, itemEl, valInp, nameEl, prevEl, grid);

      panelEditar.hidden = false;
      document.getElementById('ed-nombre').focus();
      panelEditar.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  });

  document.getElementById('btn-cancelar-editar').addEventListener('click', function () {
    panelEditar.hidden = true;
  });
})();
</script>

<?php include 'includes/footer-admin.php'; ?>
