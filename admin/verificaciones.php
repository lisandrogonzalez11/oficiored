<?php
require_once '../includes/db.php';
require_once '../includes/funciones.php';
require_once '../includes/mailer.php';

iniciar_sesion();
requerir_admin();

// ----------------------------------------------------------------
// POST: aprobar o rechazar
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $id_prof = (int) ($_POST['id_profesional'] ?? 0);
    $accion  = $_POST['accion'] ?? '';

    if ($id_prof > 0 && in_array($accion, ['aprobar', 'rechazar'], true)) {

        $stmt = $pdo->prepare(
            "SELECT nombre, email, foto_dni FROM profesionales WHERE id_profesional = ?"
        );
        $stmt->execute([$id_prof]);
        $p = $stmt->fetch();

        if ($p) {
            if ($accion === 'aprobar') {
                $pdo->prepare(
                    "UPDATE profesionales
                     SET dni_verificado = 1,
                         dni_revisado_por = ?,
                         dni_revisado_en  = NOW()
                     WHERE id_profesional = ?"
                )->execute([$_SESSION['admin_id'], $id_prof]);

                $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head><body style="font-family:sans-serif;padding:24px;">
                  <h2 style="color:#C41E3A;">OficioRed</h2>
                  <p>Hola <strong>' . htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>
                  <p>¡Tu identidad fue <strong>verificada exitosamente</strong>! Tu perfil ahora muestra la insignia <em>Identidad verificada</em>.</p>
                  <hr><p style="color:#999;font-size:0.75rem;">OficioRed · El Colorado, Formosa</p>
                  </body></html>';
                enviar_email($p['email'], $p['nombre'], 'Tu identidad fue verificada – OficioRed', $html);

                $_SESSION['flash_admin']      = 'Identidad aprobada. Se notificó al profesional.';
                $_SESSION['flash_admin_tipo'] = 'exito';

            } elseif ($accion === 'rechazar') {
                // Borrar foto del servidor.
                if (!empty($p['foto_dni']) && foto_nombre_valido($p['foto_dni'])) {
                    @unlink(__DIR__ . '/../uploads/dni/' . $p['foto_dni']);
                }

                $pdo->prepare(
                    "UPDATE profesionales
                     SET foto_dni = NULL,
                         dni_revisado_por = ?,
                         dni_revisado_en  = NOW()
                     WHERE id_profesional = ?"
                )->execute([$_SESSION['admin_id'], $id_prof]);

                $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head><body style="font-family:sans-serif;padding:24px;">
                  <h2 style="color:#C41E3A;">OficioRed</h2>
                  <p>Hola <strong>' . htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>
                  <p>La foto de DNI que enviaste no pudo ser verificada. Por favor subí una foto más clara del frente de tu documento.</p>
                  <hr><p style="color:#999;font-size:0.75rem;">OficioRed · El Colorado, Formosa</p>
                  </body></html>';
                enviar_email($p['email'], $p['nombre'], 'Verificación de identidad rechazada – OficioRed', $html);

                $_SESSION['flash_admin']      = 'Foto rechazada. Se notificó al profesional para que la reenvíe.';
                $_SESSION['flash_admin_tipo'] = 'info';
            }
        }
    }

    redirigir('verificaciones.php');
}

// ----------------------------------------------------------------
// GET: listar pendientes
// ----------------------------------------------------------------
$stmt = $pdo->prepare(
    "SELECT id_profesional, nombre, apellido, email, foto_dni, fecha_registro
     FROM profesionales
     WHERE foto_dni IS NOT NULL AND dni_verificado = 0
     ORDER BY fecha_registro ASC"
);
$stmt->execute();
$pendientes = $stmt->fetchAll();

$titulo_pagina = 'Verificaciones de identidad';
$pagina_activa = 'verificaciones';

include 'includes/header-admin.php';
?>

<div class="admin-page-header">
  <h1>Verificaciones de identidad (DNI)</h1>
  <span><?= count($pendientes) ?> pendiente<?= count($pendientes) !== 1 ? 's' : '' ?></span>
</div>

<?php if (empty($pendientes)): ?>
  <div class="admin-card" style="text-align:center;padding:48px;">
    <i data-lucide="shield-check" style="width:48px;height:48px;color:var(--color-success);"></i>
    <p style="margin-top:12px;">No hay verificaciones pendientes.</p>
  </div>
<?php else: ?>
  <div class="admin-card">
    <table class="admin-tabla">
      <thead>
        <tr>
          <th>Profesional</th>
          <th>Email</th>
          <th>Registrado</th>
          <th>DNI</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pendientes as $p): ?>
          <tr>
            <td><?= sanitizar($p['nombre'] . ' ' . $p['apellido']) ?></td>
            <td><?= sanitizar($p['email']) ?></td>
            <td><?= htmlspecialchars(formatear_fecha($p['fecha_registro']), ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <a href="ver-dni.php?id=<?= (int)$p['id_profesional'] ?>"
                 target="_blank" class="btn btn-sm btn-secundario">
                <i data-lucide="eye"></i> Ver foto
              </a>
            </td>
            <td style="display:flex;gap:8px;flex-wrap:wrap;">
              <form method="post" action="verificaciones.php">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id_profesional" value="<?= (int)$p['id_profesional'] ?>">
                <input type="hidden" name="accion" value="aprobar">
                <button type="submit" class="btn btn-sm btn-primary">
                  <i data-lucide="check"></i> Aprobar
                </button>
              </form>
              <form method="post" action="verificaciones.php"
                    onsubmit="return confirm('¿Rechazar y pedir nueva foto al profesional?')">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id_profesional" value="<?= (int)$p['id_profesional'] ?>">
                <input type="hidden" name="accion" value="rechazar">
                <button type="submit" class="btn btn-sm btn-secundario" style="color:var(--color-accent);">
                  <i data-lucide="x"></i> Rechazar
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php include 'includes/footer-admin.php'; ?>
