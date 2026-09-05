<?php
// =============================================================
// includes/form-campos-profesional.php — Campos compartidos del
// formulario de profesional (registro y editar-perfil).
// Espera en el scope: $errores, $valores, $categorias.
// Parametros opcionales (definidos por la pagina antes del include):
//   $incluir_password              (bool)   registro=true, editar=false
//   $zona_placeholder              (string) '' = sin placeholder
//   $telefono_placeholder          (string) '' = sin placeholder
//   $biografia_contador_multilinea (bool)   editar=true, registro=false
// =============================================================
$incluir_password              = $incluir_password              ?? false;
$zona_placeholder              = $zona_placeholder              ?? '';
$telefono_placeholder          = $telefono_placeholder          ?? '';
$biografia_contador_multilinea = $biografia_contador_multilinea ?? false;
?>
        <!-- NOMBRE -->
        <div class="form-group">
          <label class="form-label" for="nombre">Nombre *</label>
          <input class="form-input <?= isset($errores['nombre']) ? 'input-error' : '' ?>"
                 type="text" id="nombre" name="nombre"
                 value="<?= sanitizar($valores['nombre']) ?>"
                 maxlength="50" required>
          <?php if (isset($errores['nombre'])): ?>
            <span class="form-error"><?= $errores['nombre'] ?></span>
          <?php endif; ?>
        </div>

        <!-- APELLIDO -->
        <div class="form-group">
          <label class="form-label" for="apellido">Apellido *</label>
          <input class="form-input <?= isset($errores['apellido']) ? 'input-error' : '' ?>"
                 type="text" id="apellido" name="apellido"
                 value="<?= sanitizar($valores['apellido']) ?>"
                 maxlength="50" required>
          <?php if (isset($errores['apellido'])): ?>
            <span class="form-error"><?= $errores['apellido'] ?></span>
          <?php endif; ?>
        </div>

        <!-- EMAIL -->
        <div class="form-group">
          <label class="form-label" for="email">Email *</label>
          <input class="form-input <?= isset($errores['email']) ? 'input-error' : '' ?>"
                 type="email" id="email" name="email"
                 value="<?= sanitizar($valores['email']) ?>"
                 required>
          <?php if (isset($errores['email'])): ?>
            <span class="form-error"><?= $errores['email'] ?></span>
          <?php endif; ?>
        </div>

<?php if ($incluir_password): ?>
        <!-- PASSWORD -->
        <div class="form-group">
          <label class="form-label" for="password">Contraseña *</label>
          <input class="form-input <?= isset($errores['password']) ? 'input-error' : '' ?>"
                 type="password" id="password" name="password"
                 minlength="8" required>
          <?php if (isset($errores['password'])): ?>
            <span class="form-error"><?= $errores['password'] ?></span>
          <?php endif; ?>
        </div>

        <!-- CONFIRMAR PASSWORD -->
        <div class="form-group">
          <label class="form-label" for="password_conf">
            Confirmar contraseña *
          </label>
          <input class="form-input <?= isset($errores['password_conf']) ? 'input-error' : '' ?>"
                 type="password" id="password_conf" name="password_conf"
                 minlength="8" required>
          <?php if (isset($errores['password_conf'])): ?>
            <span class="form-error"><?= $errores['password_conf'] ?></span>
          <?php endif; ?>
        </div>

<?php endif; ?>
        <!-- OFICIO -->
        <div class="form-group">
          <label class="form-label" for="oficio">Oficio *</label>
          <select class="form-input <?= isset($errores['oficio']) ? 'input-error' : '' ?>"
                  id="oficio" name="oficio" required>
            <option value="">Seleccioná tu oficio</option>
            <?php foreach ($categorias as $cat): ?>
              <option value="<?= sanitizar($cat['nombre']) ?>"
                <?= $valores['oficio'] === $cat['nombre'] ? 'selected' : '' ?>>
                <?= sanitizar($cat['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errores['oficio'])): ?>
            <span class="form-error"><?= $errores['oficio'] ?></span>
          <?php endif; ?>
        </div>

        <!-- ZONA DE COBERTURA -->
        <div class="form-group">
          <label class="form-label" for="zona_cobertura">
            Zona de cobertura *
          </label>
          <input class="form-input <?= isset($errores['zona_cobertura']) ? 'input-error' : '' ?>"
                 type="text" id="zona_cobertura" name="zona_cobertura"
                 value="<?= sanitizar($valores['zona_cobertura']) ?>"
<?php if ($zona_placeholder !== ''): ?>
                 placeholder="<?= $zona_placeholder ?>"
<?php endif; ?>
                 maxlength="80" required>
          <?php if (isset($errores['zona_cobertura'])): ?>
            <span class="form-error"><?= $errores['zona_cobertura'] ?></span>
          <?php endif; ?>
        </div>

        <!-- TELEFONO (opcional) -->
        <div class="form-group">
          <label class="form-label" for="telefono">Teléfono</label>
          <input class="form-input <?= isset($errores['telefono']) ? 'input-error' : '' ?>"
                 type="tel" id="telefono" name="telefono"
                 value="<?= sanitizar($valores['telefono']) ?>"
<?php if ($telefono_placeholder !== ''): ?>
                 placeholder="<?= $telefono_placeholder ?>"
<?php endif; ?>
                 maxlength="20">
          <?php if (isset($errores['telefono'])): ?>
            <span class="form-error"><?= $errores['telefono'] ?></span>
          <?php endif; ?>
        </div>

        <!-- BIOGRAFIA (opcional) -->
        <div class="form-group">
          <label class="form-label" for="biografia">
            Contá algo sobre vos (opcional)
          </label>
          <textarea class="form-input <?= isset($errores['biografia']) ? 'input-error' : '' ?>"
                    id="biografia" name="biografia"
                    maxlength="500"
                    rows="4"><?= sanitizar($valores['biografia']) ?></textarea>
<?php if ($biografia_contador_multilinea): ?>
          <span id="contador-biografia" class="texto-secundario">
            <?= mb_strlen($valores['biografia']) ?> / 500 caracteres
          </span>
<?php else: ?>
          <span id="contador-biografia" class="texto-secundario">0 / 500 caracteres</span>
<?php endif; ?>
          <?php if (isset($errores['biografia'])): ?>
            <span class="form-error"><?= $errores['biografia'] ?></span>
          <?php endif; ?>
        </div>
