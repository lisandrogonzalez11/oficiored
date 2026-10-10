# Decisiones Arquitectónicas — OficioRed V2

Este documento registra decisiones de diseño no obvias que afectan la estructura del
proyecto a largo plazo. El objetivo es evitar que futuros colaboradores o sesiones de
mantenimiento deshagan decisiones tomadas con fundamento.

---

## DA-01 — Tabla `usuarios` reservada para administradores

**Decisión (V2, Fase 5):** La tabla `usuarios` queda reservada exclusivamente para
cuentas administrativas. Los profesionales continúan autenticándose mediante la tabla
`profesionales`.

**Contexto:** La tabla `usuarios` fue diseñada originalmente para "clientes" en un modelo
de marketplace. Nunca llegó a tener datos reales. En V2, en lugar de crearla tabla
`administradores`, se reutiliza `usuarios` aprovechando que ya tiene los campos necesarios
(email único, password, nombre, apellido) y no contiene datos que migrar.

**Consecuencias:**
- Cualquier fila en `usuarios` se considera un administrador del sistema.
- Los campos `telefono` y `direccion` de `usuarios` no se usan para admins; son
  nullable y no representan ningún problema operativo.
- Si en el futuro se necesitan múltiples niveles de admin, agregar entonces una columna
  `rol ENUM('admin','superadmin')` o crear una tabla de roles dedicada.
- NO crear rutas web para registrar administradores. El único mecanismo es el script CLI
  `scripts/crear-admin.php`.

---

## DA-02 — Convivencia de `profesionales.oficio` (legacy) y `categoria_id`

**Decisión (V2, Fase 1):** El campo `profesionales.oficio` (VARCHAR, texto libre) continúa
existiendo como campo legacy junto a la nueva clave foránea `categoria_id`.

**Contexto:** Al introducir la tabla `categorias` en V2, se optó por no migrar
inmediatamente los datos de `oficio` a `categoria_id`, preservando la compatibilidad con
el código existente de búsqueda, visualización y registro.

**Consecuencias:**
- `categoria_id` es la referencia estructurada y normalizada. Es el campo que debe usarse
  para filtros, conteos y clasificación.
- `profesionales.oficio` es un campo de texto libre que puede estar desincronizado con el
  nombre de la categoría. Esta desincronización es aceptada y documentada.
- Al editar el nombre de una categoría desde el panel admin, NO se actualiza
  automáticamente `profesionales.oficio`. Esto es intencional: `oficio` es un campo
  legacy que el profesional controla desde su propio perfil; sincronizarlo
  automáticamente crearía cambios silenciosos en los perfiles sin consentimiento.
- En una futura migración (V3 o posterior), evaluar deprecar `oficio` y depender
  completamente de `categoria_id`.

---

## DA-03 — Categoría "Otros" protegida contra eliminación

**Decisión (V2, Fase 5):** La categoría cuyo nombre es "Otros" (case-insensitive) no puede
eliminarse desde el panel de administración, independientemente de que tenga o no
profesionales asociados.

**Contexto:** "Otros" actúa como categoría de respaldo para profesionales que no encajan
en ninguna categoría específica. Eliminarla podría dejar perfiles sin categoría de manera
no intencional, incluso si en ese momento su conteo es cero.

**Consecuencias:**
- La protección se implementa a nivel de aplicación (validación en el handler de
  eliminación), adicionalmente a la restricción de FK (que impide eliminar categorías
  con profesionales asociados).
- Si en el futuro se decide renombrar "Otros" o eliminarla, debe hacerse via migración
  SQL directa con supervisión, no desde el panel.

---

## DA-04 — Sesión compartida, claves separadas para admin y profesional

**Decisión (V2, Fase 5):** La autenticación de administradores usa la misma sesión PHP
que la de profesionales, diferenciada por claves distintas en `$_SESSION`.

| Contexto     | Clave de sesión      |
|--------------|----------------------|
| Profesional  | `profesional_id`     |
| Administrador| `admin_id`           |

**Consecuencias:**
- Un usuario profesional logueado no tiene `admin_id` → no puede acceder al panel admin.
- Un admin logueado no tiene necesariamente `profesional_id`.
- El token CSRF (`$_SESSION['csrf_token']`) es compartido, lo cual es estándar y seguro.
- No se usa `session_name()` diferente (sesión separada), ya que agrega complejidad sin
  beneficio real en este proyecto.
