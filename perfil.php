<?php
// =============================================================
// perfil.php — Vista de detalle publica de un profesional
// Proyecto: OficioRed
// =============================================================

require_once 'includes/db.php';
require_once 'includes/funciones.php';

iniciar_sesion();

// Validar el parametro id.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id <= 0) {
    redirigir('index.php');
}

// Consultar el profesional activo.
$stmt = $pdo->prepare(
    "SELECT id_profesional, nombre, apellido, email, telefono,
            instagram_url, biografia, fecha_registro, oficio, zona_cobertura, foto_perfil
     FROM profesionales
     WHERE id_profesional = :id AND estado = 'activo'"
);
$stmt->execute([':id' => $id]);
$profesional = $stmt->fetch();

// No encontrado: mensaje flash y vuelta al inicio.
if (!$profesional) {
    $_SESSION['mensaje'] = 'Profesional no encontrado.';
    $_SESSION['mensaje_tipo'] = 'error';
    redirigir('index.php');
}

$titulo_pagina = $profesional['nombre'] . ' ' . $profesional['apellido'];

// Normalizar telefono para WhatsApp (null si vacio o formato no reconocible)
$wa_numero = normalizar_telefono_wa($profesional['telefono'] ?? '');

// Zonas de cobertura desde la tabla pivot
$zonas_perfil = obtener_zonas_profesional($pdo, $id);

// Cargar servicios activos para mostrar en el perfil
$stmtSvc = $pdo->prepare(
    "SELECT nombre FROM servicios
     WHERE profesional_id = :id AND activo = 1
     ORDER BY created_at ASC"
);
$stmtSvc->execute([':id' => $id]);
$servicios_perfil = $stmtSvc->fetchAll();

require_once 'includes/header.php';
?>

<!-- PERFIL PRINCIPAL -->
<section class="seccion perfil-header">
  <div class="container">
    <?php if (!empty($profesional['foto_perfil']) && foto_nombre_valido($profesional['foto_perfil'])): ?>
      <img src="uploads/fotos/<?= sanitizar($profesional['foto_perfil']) ?>"
           alt="<?= sanitizar($profesional['nombre'] . ' ' . $profesional['apellido']) ?>"
           class="perfil-foto">
    <?php else: ?>
      <div class="perfil-avatar">
        <?= strtoupper(substr($profesional['nombre'], 0, 1)
            . substr($profesional['apellido'], 0, 1)) ?>
      </div>
    <?php endif; ?>
    <h1><?= sanitizar($profesional['nombre'] . ' '
            . $profesional['apellido']) ?></h1>
    <span class="badge"><?= sanitizar($profesional['oficio']) ?></span>
    <?php if (!empty($zonas_perfil)): ?>
    <div class="perfil-zonas">
      <i data-lucide="map-pin"></i>
      <?php foreach ($zonas_perfil as $zp): ?>
        <span class="zona-chip-display <?= $zp['es_todo'] ? 'zona-chip-display--todo' : '' ?>">
          <?= sanitizar($zp['nombre']) ?>
        </span>
      <?php endforeach; ?>
    </div>
    <?php elseif (!empty($profesional['zona_cobertura'])): ?>
    <p><i data-lucide="map-pin"></i> <?= sanitizar($profesional['zona_cobertura']) ?></p>
    <?php endif; ?>
    <p class="texto-secundario">
      En OficioRed desde <?= formatear_fecha($profesional['fecha_registro']) ?>
    </p>
  </div>
</section>

<!-- DATOS DE CONTACTO -->
<section class="seccion tarjeta-contacto">
  <div class="container">
    <h2>Contactar a <?= sanitizar($profesional['nombre']) ?></h2>

    <?php if ($wa_numero !== null): ?>
      <?php
        $wa_mensaje = 'Hola ' . $profesional['nombre']
                    . ', te contacto desde OficioRed.';
        $wa_href = 'https://wa.me/' . $wa_numero
                 . '?text=' . rawurlencode($wa_mensaje);
      ?>
      <a href="<?= sanitizar($wa_href) ?>"
         class="btn btn-whatsapp"
         target="_blank" rel="noopener noreferrer">
        <svg class="icon-brand" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
        Escribir por WhatsApp
      </a>
    <?php endif; ?>

    <?php if (!empty($profesional['telefono'])): ?>
      <a href="tel:<?= sanitizar($profesional['telefono']) ?>"
         class="contacto-link">
        <i data-lucide="phone"></i>
        Llamar
      </a>
    <?php endif; ?>

    <a href="mailto:<?= sanitizar($profesional['email']) ?>"
       class="contacto-link">
      <i data-lucide="mail"></i>
      <?= sanitizar($profesional['email']) ?>
    </a>

    <?php if (!empty($profesional['instagram_url'])): ?>
      <a href="https://instagram.com/<?= sanitizar($profesional['instagram_url']) ?>"
         class="contacto-link contacto-link--instagram"
         target="_blank" rel="noopener noreferrer">
        <svg class="icon-brand" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
        </svg>
        @<?= sanitizar($profesional['instagram_url']) ?>
      </a>
    <?php endif; ?>

    <p class="texto-secundario">
      Mencioná que lo encontraste en OficioRed
    </p>
  </div>
</section>

<!-- SERVICIOS (solo si existen) -->
<?php if (!empty($servicios_perfil)): ?>
<section class="seccion servicios-publicos">
  <div class="container">
    <h2>Servicios</h2>
    <div class="servicios-publ-lista">
      <?php foreach ($servicios_perfil as $srv): ?>
        <span class="servicio-chip">
          <i data-lucide="check"></i>
          <?= sanitizar($srv['nombre']) ?>
        </span>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- DESCRIPCION / BIOGRAFIA (solo si existe) -->
<?php if (!empty($profesional['biografia'])): ?>
  <section class="seccion">
    <div class="container">
      <h2>Sobre <?= sanitizar($profesional['nombre']) ?></h2>
      <p><?= nl2br(sanitizar($profesional['biografia'])) ?></p>
    </div>
  </section>
<?php endif; ?>

<!-- FORMULARIO DE SOLICITUD DE PRESUPUESTO -->
<?php
$es_propio_perfil = esta_logueado()
    && (int)($_SESSION['profesional_id'] ?? 0) === (int)$profesional['id_profesional'];
?>
<?php if ($es_propio_perfil): ?>
<section class="seccion">
  <div class="container">
    <div class="alerta alerta-info">
      <i data-lucide="info"></i>
      <span>Este es tu perfil público. Los clientes ven esta página y pueden enviarte solicitudes de presupuesto.</span>
    </div>
  </div>
</section>
<?php else: ?>
<section class="seccion seccion-presupuesto">
  <div class="container">
    <h2>Solicitar presupuesto</h2>
    <p class="texto-secundario" style="margin-bottom:20px;">
      Completá el formulario y <?= sanitizar($profesional['nombre']) ?> se pondrá en contacto con vos.
    </p>

    <form action="solicitar-presupuesto.php" method="POST" class="form-presupuesto" novalidate>
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="profesional_id" value="<?= (int)$profesional['id_profesional'] ?>">

      <div class="form-group">
        <label class="form-label" for="nombre_cliente">Tu nombre *</label>
        <input class="form-input" type="text" id="nombre_cliente" name="nombre_cliente"
               maxlength="100" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="telefono_cliente">Tu teléfono (WhatsApp) *</label>
        <input class="form-input" type="tel" id="telefono_cliente" name="telefono_cliente"
               maxlength="30" placeholder="Ej: 3718 123456" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="email_cliente">Tu email (opcional)</label>
        <input class="form-input" type="email" id="email_cliente" name="email_cliente">
      </div>

      <div class="form-group">
        <label class="form-label">¿El servicio es presencial o a distancia? *</label>
        <div class="modalidad-opciones">
          <label class="modalidad-opcion">
            <input type="radio" name="modalidad" value="presencial" checked
                   onchange="toggleZona(this.value)">
            <span><i data-lucide="map-pin"></i> Presencial</span>
          </label>
          <label class="modalidad-opcion">
            <input type="radio" name="modalidad" value="digital"
                   onchange="toggleZona(this.value)">
            <span><i data-lucide="wifi"></i> A distancia / digital</span>
          </label>
        </div>
      </div>

      <div class="form-group" id="grupo-zona">
        <label class="form-label" for="zona_servicio">¿En qué zona o dirección? *</label>
        <input class="form-input" type="text" id="zona_servicio" name="zona_servicio"
               maxlength="150" placeholder="Ej: Barrio centro, Ruta 81 km 5...">
      </div>

      <div class="form-group">
        <label class="form-label" for="descripcion">¿Qué necesitás? *</label>
        <textarea class="form-input" id="descripcion" name="descripcion"
                  rows="4" maxlength="1000" required
                  placeholder="Describí el trabajo, el estado actual, urgencia o cualquier detalle útil."></textarea>
      </div>

      <button type="submit" class="btn btn-primary">
        <i data-lucide="send"></i> Enviar solicitud
      </button>
    </form>
  </div>
</section>
<?php endif; ?>
<script>
function toggleZona(modalidad) {
  var grupo = document.getElementById('grupo-zona');
  var input = document.getElementById('zona_servicio');
  if (modalidad === 'presencial') {
    grupo.style.display = '';
    input.required = true;
  } else {
    grupo.style.display = 'none';
    input.required = false;
    input.value = '';
  }
}
</script>

<!-- NAVEGACION DE RETORNO -->
<section class="seccion">
  <div class="container">
    <a href="javascript:history.back()" class="link-volver">
      <i data-lucide="arrow-left"></i> Volver a los resultados
    </a>
    <a href="index.php" class="link-volver">
      Ver más profesionales
    </a>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
