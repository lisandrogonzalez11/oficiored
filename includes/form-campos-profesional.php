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
          <div class="password-wrap">
            <input class="form-input <?= isset($errores['password']) ? 'input-error' : '' ?>"
                   type="password" id="password" name="password"
                   minlength="8" required>
            <button type="button" class="password-toggle"
                    onclick="togglePwd('password', this)"
                    tabindex="-1" aria-label="Mostrar contraseña">
              <i data-lucide="eye" class="ico-eye"></i>
              <i data-lucide="eye-off" class="ico-eye-off" style="display:none"></i>
            </button>
          </div>
          <?php if (isset($errores['password'])): ?>
            <span class="form-error"><?= $errores['password'] ?></span>
          <?php endif; ?>
        </div>

        <!-- CONFIRMAR PASSWORD -->
        <div class="form-group">
          <label class="form-label" for="password_conf">
            Confirmar contraseña *
          </label>
          <div class="password-wrap">
            <input class="form-input <?= isset($errores['password_conf']) ? 'input-error' : '' ?>"
                   type="password" id="password_conf" name="password_conf"
                   minlength="8" required>
            <button type="button" class="password-toggle"
                    onclick="togglePwd('password_conf', this)"
                    tabindex="-1" aria-label="Mostrar contraseña">
              <i data-lucide="eye" class="ico-eye"></i>
              <i data-lucide="eye-off" class="ico-eye-off" style="display:none"></i>
            </button>
          </div>
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
        <?php
          $zonas_activas       = $zonas_activas       ?? [];
          $zonas_seleccionadas = $zonas_seleccionadas ?? [];
        ?>
        <div class="form-group">
          <label class="form-label">Zona de cobertura *</label>
          <div class="zona-picker <?= isset($errores['zonas']) ? 'zona-picker--error' : '' ?>">
            <?php foreach ($zonas_activas as $zona): ?>
              <label class="zona-chip <?= $zona['es_todo'] ? 'zona-chip--todo' : '' ?>">
                <input type="checkbox" name="zonas[]"
                       value="<?= (int)$zona['id_zona'] ?>"
                       <?= in_array((int)$zona['id_zona'], array_map('intval', $zonas_seleccionadas)) ? 'checked' : '' ?>
                       <?= $zona['es_todo'] ? 'id="zona-todo-check"' : '' ?>>
                <?= sanitizar($zona['nombre']) ?>
              </label>
            <?php endforeach; ?>
          </div>
          <?php if (isset($errores['zonas'])): ?>
            <span class="form-error"><?= $errores['zonas'] ?></span>
          <?php endif; ?>
        </div>
        <script>
        (function(){
          var todo = document.getElementById('zona-todo-check');
          if (!todo) return;
          var otros = Array.from(document.querySelectorAll('[name="zonas[]"]:not(#zona-todo-check)'));
          function aplicarTodo(marcar) {
            otros.forEach(function(c){ c.checked = marcar; c.disabled = marcar; });
          }
          // Estado inicial
          if (todo.checked) aplicarTodo(true);
          todo.addEventListener('change', function(){ aplicarTodo(this.checked); });
          // Si se desmarca uno individualmente, quitar "todo"
          otros.forEach(function(c){
            c.addEventListener('change', function(){ if (!this.checked) todo.checked = false; });
          });
        })();
        </script>

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

<?php if (!($incluir_password ?? false)): // Solo en editar-perfil, no en registro ?>
        <!-- INSTAGRAM (opcional, solo edición) -->
        <div class="form-group">
          <label class="form-label" for="instagram_url">Instagram (opcional)</label>
          <div class="instagram-input-wrap">
            <span class="instagram-prefix">instagram.com/</span>
            <input class="form-input" type="text" id="instagram_url" name="instagram_url"
                   value="<?= sanitizar($valores['instagram_url'] ?? '') ?>"
                   placeholder="tu_usuario" maxlength="100">
          </div>
          <span class="texto-secundario" style="font-size:0.8rem;">Solo el nombre de usuario, sin @</span>
        </div>
<?php endif; ?>

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
