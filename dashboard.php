<?php
// =============================================================
// dashboard.php — Panel privado del profesional
// Proyecto: OficioRed
// =============================================================

require_once 'includes/db.php';
require_once 'includes/funciones.php';

iniciar_sesion();

requerir_login();

// Cargar datos del profesional logueado.
$stmt = $pdo->prepare(
    "SELECT id_profesional, nombre, apellido, email, telefono,
            biografia, oficio, zona_cobertura, estado, fecha_registro,
            foto_perfil
     FROM profesionales
     WHERE id_profesional = :id
     LIMIT 1"
);
$stmt->execute([':id' => $_SESSION['profesional_id']]);
$profesional = $stmt->fetch();

// Registro inexistente (borrado manual): cerrar sesion.
if (!$profesional) {
    session_destroy();
    redirigir('login.php');
}

// Cantidad de servicios activos (para completitud y acciones rapidas)
$stmtSA = $pdo->prepare(
    "SELECT COUNT(*) FROM servicios WHERE profesional_id = ? AND activo = 1"
);
$stmtSA->execute([$_SESSION['profesional_id']]);
$servicios_activos_count = (int)$stmtSA->fetchColumn();

// Solicitudes de presupuesto recibidas
$filtro_sol = ($_GET['filtro'] ?? '') === 'todas' ? 'todas' : 'nuevas';
$where_leida = $filtro_sol === 'nuevas' ? ' AND leida = 0' : '';

$stmtSol = $pdo->prepare(
    "SELECT id, nombre_cliente, email_cliente, telefono_cliente,
            modalidad, zona_servicio, descripcion, leida, fecha
     FROM solicitudes_presupuesto
     WHERE profesional_id = ?" . $where_leida . "
     ORDER BY fecha DESC
     LIMIT 20"
);
$stmtSol->execute([$_SESSION['profesional_id']]);
$solicitudes = $stmtSol->fetchAll();

// Conteo total de no leídas (independiente del filtro activo)
$stmtNL = $pdo->prepare(
    "SELECT COUNT(*) FROM solicitudes_presupuesto
     WHERE profesional_id = ? AND leida = 0"
);
$stmtNL->execute([$_SESSION['profesional_id']]);
$no_leidas = (int) $stmtNL->fetchColumn();

$completitud = calcular_completitud($profesional, $servicios_activos_count);

$titulo_pagina = 'Mi panel';
require_once 'includes/header.php';
?>

<section class="seccion">
  <div class="container">

    <!-- FOTO DE PERFIL + BIENVENIDA -->
    <div class="dashboard-perfil-top">
      <div class="dashboard-foto-wrap">
        <?php if (!empty($profesional['foto_perfil']) && foto_nombre_valido($profesional['foto_perfil'])): ?>
          <img src="uploads/fotos/<?= sanitizar($profesional['foto_perfil']) ?>"
               alt="Tu foto de perfil"
               class="dashboard-foto-img">
        <?php else: ?>
          <div class="dashboard-foto-avatar">
            <?= strtoupper(substr($profesional['nombre'], 0, 1)
                . substr($profesional['apellido'], 0, 1)) ?>
          </div>
        <?php endif; ?>
        <a href="editar-perfil.php" class="dashboard-foto-editar" title="Cambiar foto">
          <i data-lucide="pencil"></i>
        </a>
      </div>
      <div class="dashboard-bienvenida">
        <h1>Hola, <?= sanitizar($profesional['nombre']) ?></h1>
        <?php
          $badge_clase = 'badge-inactivo';
          $badge_icono = 'pause-circle';
          $badge_texto = 'Perfil inactivo';
          if ($profesional['estado'] === 'activo') {
              $badge_clase = 'badge-activo';
              $badge_icono = 'check-circle';
              $badge_texto = 'Perfil activo';
          } elseif ($profesional['estado'] === 'en_revision') {
              $badge_clase = 'badge-revision';
              $badge_icono = 'clock';
              $badge_texto = 'Pendiente de aprobación';
          }
        ?>
        <span class="badge <?= $badge_clase ?>">
          <i data-lucide="<?= $badge_icono ?>"></i>
          <?= $badge_texto ?>
        </span>
      </div>
    </div><!-- /dashboard-perfil-top -->

    <?php if ($profesional['estado'] === 'en_revision'): ?>
    <div class="alerta alerta-advertencia" style="margin-bottom:24px;">
      <i data-lucide="clock"></i>
      <span>
        <strong>Tu cuenta está pendiente de aprobación.</strong>
        El equipo de OficioRed revisará tu perfil pronto. Una vez aprobado, aparecerás en las búsquedas. Mientras tanto podés completar tus datos.
      </span>
    </div>
    <?php endif; ?>


    <!-- ALERTA SI FALTA TELEFONO -->
    <?php if (empty($profesional['telefono'])): ?>
      <div class="alerta alerta-error" style="margin-bottom: 24px;">
        <i data-lucide="alert-triangle"></i>
        <span>
          Agregá tu teléfono para que los clientes puedan contactarte más fácil.
          <a href="editar-perfil.php">Actualizar perfil</a>
        </span>
      </div>
    <?php endif; ?>

    <!-- ACCIONES RAPIDAS -->
    <h2>¿Qué querés hacer?</h2>
    <div class="acciones-rapidas">

      <a href="editar-perfil.php" class="accion-rapida">
        <i data-lucide="edit"></i>
        <div>
          <strong>Editar mi perfil</strong>
          <p class="texto-secundario">Actualizá tus datos y oficio</p>
        </div>
      </a>

      <a href="perfil.php?id=<?= (int)$profesional['id_profesional'] ?>"
         class="accion-rapida">
        <i data-lucide="eye"></i>
        <div>
          <strong>Ver mi perfil público</strong>
          <p class="texto-secundario">Así te ven los clientes</p>
        </div>
      </a>

      <a href="mis-servicios.php" class="accion-rapida">
        <i data-lucide="list-checks"></i>
        <div>
          <strong>Mis servicios</strong>
          <p class="texto-secundario">
            <?= $servicios_activos_count ?> activo<?= $servicios_activos_count !== 1 ? 's' : '' ?>
          </p>
        </div>
      </a>

      <a href="logout.php" class="accion-rapida accion-rapida--logout">
        <i data-lucide="log-out"></i>
        <div>
          <strong>Cerrar sesión</strong>
          <p class="texto-secundario">Salir de tu cuenta</p>
        </div>
      </a>

    </div>

    <!-- SOLICITUDES DE PRESUPUESTO -->
    <div class="solicitudes-seccion">

      <?php if ($no_leidas > 0): ?>
      <div class="alerta-solicitudes">
        <i data-lucide="bell-ring"></i>
        <div>
          <strong>Tenés <?= $no_leidas ?> solicitud<?= $no_leidas > 1 ? 'es' : '' ?> de presupuesto sin leer</strong>
          <span>Clientes están esperando tu respuesta.</span>
        </div>
      </div>
      <?php endif; ?>

      <details class="solicitudes-panel" <?= ($no_leidas > 0 || isset($_GET['filtro'])) ? 'open' : '' ?>>
        <summary class="solicitudes-summary">
          <span class="solicitudes-summary__titulo">
            <i data-lucide="inbox"></i>
            Solicitudes de presupuesto
            <?php if ($no_leidas > 0): ?>
              <span class="badge-no-leidas"><?= $no_leidas ?></span>
            <?php endif; ?>
          </span>
          <i data-lucide="chevron-down" class="solicitudes-chevron"></i>
        </summary>

        <div class="solicitudes-body">
          <div class="solicitudes-filtros">
            <a href="dashboard.php?filtro=nuevas" class="filtro-chip <?= $filtro_sol === 'nuevas' ? 'filtro-chip--activo' : '' ?>">
              No leídas<?= $no_leidas > 0 ? ' (' . $no_leidas . ')' : '' ?>
            </a>
            <a href="dashboard.php?filtro=todas" class="filtro-chip <?= $filtro_sol === 'todas' ? 'filtro-chip--activo' : '' ?>">
              Ver todas
            </a>
          </div>

          <?php if (empty($solicitudes)): ?>
            <p class="texto-secundario" style="padding: 0.5rem 0;">
              <?= $filtro_sol === 'nuevas' ? 'No tenés solicitudes sin leer.' : 'Aún no recibiste solicitudes.' ?>
            </p>
          <?php else: ?>

            <!-- Barra de selección múltiple -->
            <div class="bulk-bar">
              <label class="bulk-sel-todas">
                <input type="checkbox" id="sel-todas" onchange="solSelTodas(this)">
                <span>Seleccionar todas</span>
              </label>
              <button type="button" id="btn-eliminar-sel" class="btn btn-sm btn-danger" disabled
                      onclick="solEliminarSel()">
                <i data-lucide="trash-2"></i>
                <span id="lbl-eliminar-sel">Eliminar seleccionadas</span>
              </button>
            </div>

            <!-- Lista -->
            <div class="solicitudes-lista" id="solicitudes-lista">
              <?php foreach ($solicitudes as $sol): ?>
                <?php
                  $wa_tel = preg_replace('/\D/', '', $sol['telefono_cliente']);
                  $wa_msg = 'Hola ' . $sol['nombre_cliente'] . ', te contacto por tu solicitud en OficioRed.';
                  $wa_href = 'https://wa.me/' . $wa_tel . '?text=' . rawurlencode($wa_msg);
                  $fecha_exacta = date('d/m/Y H:i', strtotime($sol['fecha']));
                ?>
                <div class="solicitud-card <?= !$sol['leida'] ? 'solicitud-card--nueva' : '' ?>">
                  <div class="solicitud-card__header">
                    <label class="solicitud-check-wrap" title="Seleccionar">
                      <input type="checkbox" class="sol-check" data-id="<?= (int)$sol['id'] ?>"
                             onchange="solActualizarConteo()">
                    </label>
                    <strong><?= sanitizar($sol['nombre_cliente']) ?></strong>
                    <?php if (!$sol['leida']): ?>
                      <span class="solicitud-nueva-chip">Nueva</span>
                    <?php endif; ?>
                    <span class="solicitud-modalidad-chip solicitud-modalidad-chip--<?= $sol['modalidad'] ?>">
                      <i data-lucide="<?= $sol['modalidad'] === 'presencial' ? 'map-pin' : 'wifi' ?>"></i>
                      <?= $sol['modalidad'] === 'presencial' ? 'Presencial' : 'Digital' ?>
                    </span>
                    <span class="texto-secundario solicitud-fecha" title="<?= $fecha_exacta ?>">
                      <?= tiempo_relativo($sol['fecha']) ?>
                    </span>
                  </div>

                  <?php if ($sol['modalidad'] === 'presencial' && !empty($sol['zona_servicio'])): ?>
                    <p class="solicitud-zona">
                      <i data-lucide="map-pin"></i> <?= sanitizar($sol['zona_servicio']) ?>
                    </p>
                  <?php endif; ?>

                  <div class="solicitud-card__contacto">
                    <a href="<?= sanitizar($wa_href) ?>" class="contacto-link-mini contacto-link-mini--wa"
                       target="_blank" rel="noopener noreferrer">
                      <i data-lucide="message-circle"></i> <?= sanitizar($sol['telefono_cliente']) ?>
                    </a>
                    <?php if (!empty($sol['email_cliente'])): ?>
                      <a href="mailto:<?= sanitizar($sol['email_cliente']) ?>" class="contacto-link-mini">
                        <i data-lucide="mail"></i> <?= sanitizar($sol['email_cliente']) ?>
                      </a>
                    <?php endif; ?>
                  </div>

                  <p class="solicitud-card__desc"><?= nl2br(sanitizar($sol['descripcion'])) ?></p>

                  <div class="solicitud-card__acciones">
                    <?php if (!$sol['leida']): ?>
                      <form method="POST" action="marcar-leida.php">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="id" value="<?= (int)$sol['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-secundario">
                          <i data-lucide="check"></i> Marcar como leída
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Formulario oculto para bulk delete -->
            <form id="form-bulk-delete" method="POST" action="eliminar-solicitud.php" style="display:none;">
              <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
              <input type="hidden" name="filtro" value="<?= $filtro_sol ?>">
              <div id="bulk-ids"></div>
            </form>

            <script>
            function solActualizarConteo() {
              var n = document.querySelectorAll('.sol-check:checked').length;
              var btn = document.getElementById('btn-eliminar-sel');
              var lbl = document.getElementById('lbl-eliminar-sel');
              btn.disabled = n === 0;
              lbl.textContent = n > 0 ? 'Eliminar seleccionadas (' + n + ')' : 'Eliminar seleccionadas';
              // sincronizar "seleccionar todas"
              var total = document.querySelectorAll('.sol-check').length;
              document.getElementById('sel-todas').checked = n === total && total > 0;
            }
            function solSelTodas(cb) {
              document.querySelectorAll('.sol-check').forEach(function(c){ c.checked = cb.checked; });
              solActualizarConteo();
            }
            function solEliminarSel() {
              var ids = Array.from(document.querySelectorAll('.sol-check:checked')).map(function(c){ return c.dataset.id; });
              if (ids.length === 0) return;
              var plural = ids.length > 1 ? 'estas ' + ids.length + ' solicitudes' : 'esta solicitud';
              if (!confirm('¿Eliminar ' + plural + '? Esta acción no se puede deshacer.')) return;
              var cont = document.getElementById('bulk-ids');
              cont.innerHTML = '';
              ids.forEach(function(id) {
                var inp = document.createElement('input');
                inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
                cont.appendChild(inp);
              });
              document.getElementById('form-bulk-delete').submit();
            }
            </script>

          <?php endif; ?>
        </div>
      </details>
    </div>

    <!-- RESUMEN DEL PERFIL -->
    <div class="resumen-perfil">
      <h2>Tu información actual</h2>
      <div class="resumen-perfil__grid">

        <div class="resumen-item">
          <i data-lucide="briefcase"></i>
          <div>
            <span class="texto-secundario">Oficio</span>
            <strong><?= sanitizar($profesional['oficio']) ?></strong>
          </div>
        </div>

        <div class="resumen-item">
          <i data-lucide="map-pin"></i>
          <div>
            <span class="texto-secundario">Zona de cobertura</span>
            <strong><?= sanitizar($profesional['zona_cobertura']) ?></strong>
          </div>
        </div>

        <div class="resumen-item">
          <i data-lucide="mail"></i>
          <div>
            <span class="texto-secundario">Email</span>
            <strong><?= sanitizar($profesional['email']) ?></strong>
          </div>
        </div>

        <div class="resumen-item">
          <i data-lucide="phone"></i>
          <div>
            <span class="texto-secundario">Teléfono</span>
            <strong>
              <?= !empty($profesional['telefono'])
                  ? sanitizar($profesional['telefono'])
                  : '<span class="texto-secundario">No cargado</span>' ?>
            </strong>
          </div>
        </div>

        <div class="resumen-item">
          <i data-lucide="calendar"></i>
          <div>
            <span class="texto-secundario">En OficioRed desde</span>
            <strong><?= formatear_fecha($profesional['fecha_registro']) ?></strong>
          </div>
        </div>

      </div>
    </div>

    <!-- COMPLETITUD DEL PERFIL -->
    <div class="completitud-perfil">
      <h2>Completitud de tu perfil</h2>
      <div class="completitud-barra-outer">
        <div class="completitud-barra-inner"
             style="width: <?= $completitud['porcentaje'] ?>%"></div>
      </div>
      <p class="completitud-pct"><?= $completitud['porcentaje'] ?>% completo</p>
      <ul class="completitud-items">
        <?php foreach ($completitud['items'] as $item): ?>
          <li class="completitud-item
              <?= $item['completo'] ? 'completitud-item--ok' : 'completitud-item--pendiente' ?>">
            <i data-lucide="<?= $item['completo'] ? 'check-circle' : 'circle' ?>"></i>
            <?php if (!$item['completo']): ?>
              <a href="<?= sanitizar($item['accion']) ?>"><?= sanitizar($item['label']) ?></a>
            <?php else: ?>
              <span><?= sanitizar($item['label']) ?></span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
