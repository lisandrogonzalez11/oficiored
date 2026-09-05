# OficioRed — Cierre de la versión 1

Documento de cierre oficial de la v1. Resume qué es el proyecto, qué
cubre esta versión, la seguridad implementada, las mejoras de la
auditoría técnica, los pendientes clasificados para el futuro y el
checklist de publicación.

**Fecha de cierre de la v1: 13 de julio de 2026.**

---

## 1. Objetivo del proyecto

OficioRed es una plataforma web que conecta a vecinos de El Colorado
(Formosa, Argentina) con trabajadores de oficio (electricistas,
plomeros, albañiles, etc.). Funciona como un directorio público tipo
"páginas amarillas" digital: un vecino busca un oficio, ve los
profesionales activos de su zona y los contacta directamente por
teléfono o email. El sistema no intermedia el contacto ni la
transacción.

---

## 2. Alcance de la v1

**Incluye:**
- Sitio público de búsqueda y consulta de profesionales, sin requerir
  registro.
- Registro, login y área privada para que el trabajador administre su
  propio perfil.
- Contacto directo (tel/email), sin mensajería ni pagos internos.

**No incluye (por diseño de la v1):**
- Registro/login de clientes (solo se autentica el profesional).
- Reseñas, calificaciones, panel de administración, pagos, reservas,
  notificaciones, foto de perfil real y cambio de contraseña.
- API, SPA o cualquier framework: es PHP vanilla server-rendered.

**Stack:** PHP 8 vanilla (sin frameworks ni Composer), MySQL/MariaDB
vía PDO, HTML5 + CSS3 + JavaScript vanilla, Lucide Icons por CDN.
Entorno de desarrollo: XAMPP local. Deploy previsto: InfinityFree.

---

## 3. Funcionalidades implementadas

**Sitio público (sin sesión):**
- `index.php` — Home con buscador, grilla de categorías y hasta 6
  profesionales destacados (más recientes).
- `buscar.php` — Resultados por oficio (`LIKE`), con estados
  diferenciados: término inválido (<2 caracteres), sin resultados y con
  resultados.
- `perfil.php` — Perfil público individual con datos de contacto
  directo y biografía opcional.

**Autenticación:**
- `registro.php` — Alta de cuenta del profesional con validación
  server-side completa.
- `login.php` — Inicio de sesión con mensaje de error genérico.
- `logout.php` — Cierre de sesión.

**Área privada (requiere sesión):**
- `dashboard.php` — Panel con estado del perfil, alerta si falta
  teléfono, accesos rápidos y resumen de datos.
- `editar-perfil.php` — Edición del propio perfil con guardado seguro.

**Base de datos:** tres tablas (`profesionales`, `categorias`,
`usuarios` — esta última reservada, sin uso en v1), con 10 categorías
de oficio precargadas.

---

## 4. Medidas de seguridad implementadas

- **Contraseñas** hasheadas con bcrypt (`password_hash` / `password_verify`).
  Nunca en texto plano.
- **SQL** exclusivamente con PDO y prepared statements en el 100% de las
  consultas (sin concatenación de variables).
- **Salida a HTML** siempre escapada con `sanitizar()` /
  `htmlspecialchars()` (protección XSS).
- **CSRF**: token verificado con `hash_equals` en todos los formularios
  POST (registro, login, editar), centralizado en `csrf_token()` /
  `verificar_csrf()`.
- **Sesión**: `session_regenerate_id()` al autenticar; cookie de sesión
  endurecida con `HttpOnly`, `SameSite=Lax` y `Secure` bajo HTTPS
  (`iniciar_sesion()`).
- **Autorización**: el UPDATE de edición usa siempre el id de sesión en
  el `WHERE`, nunca un id del formulario (impide editar perfiles ajenos).
- **Aislamiento**: `.htaccess` deniega el acceso web directo a
  `includes/` y `sql/`, y desactiva el listado de directorios.
- **Manejo de errores**: en producción los errores se registran en el
  log y se muestra una página genérica, sin filtrar SQL, rutas ni
  detalles internos.
- **Credenciales**: fuera del código versionable, en
  `includes/config.local.php` (ignorado por Git).

---

## 5. Mejoras realizadas durante la auditoría técnica

Sobre la base inicial funcional se ejecutó una auditoría y se
implementaron los siguientes puntos, cada uno verificado sin
regresiones:

| ID | Mejora | Resultado |
|----|--------|-----------|
| C1 | Política de manejo de errores centralizada | Errores nunca filtran detalles al usuario en producción; log + página genérica. |
| C2 | Aislamiento de `includes/` y `sql/` | Acceso web directo denegado (403); sin listado de directorios. |
| C3 | Credenciales fuera del código | Config de entorno en `config.local.php` (gitignored) + plantilla `config.example.php`. |
| C4 | Cookie de sesión endurecida | `HttpOnly`, `SameSite=Lax`, `Secure` bajo HTTPS. |
| I1 | Formulario de perfil deduplicado | Partial reutilizable `form-campos-profesional.php`; salida byte-idéntica. |
| I2 | Validación centralizada | Función `validar_datos_profesional()`; reglas y mensajes sin cambios. |
| I3 | CSRF centralizado | Helpers `csrf_token()` / `verificar_csrf()`. |
| I6 | Consistencia de conteo | `mb_strlen` en el contador de biografía (coincide con la validación). |
| I7 | Dependencia CDN fijada | Lucide pinneado a `1.24.0` (sin `@latest`). |
| I8 | Logging de errores unificado | `obtener_categorias()` registra la falla en vez de silenciarla. |

Los cuatro puntos críticos de la auditoría (C1–C4) quedaron resueltos.

---

## 6. Pendientes clasificados para la futura v2

Mejoras recomendables que no bloquean la v1 y son de bajo riesgo.
Orden natural de arranque de la v2:

| ID | Pendiente | Motivo |
|----|-----------|--------|
| D4 | Bootstrap único de sesión/includes (`init.php`) | Centraliza el arranque hoy repartido entre páginas; refactor autocontenido. |
| I5 | Nomenclatura mixta + código muerto | Unificar `id_profesional`/`profesional_id` y quitar `$_SESSION['profesional_nombre']` (sin uso). |
| D2 | Escapar comodines `LIKE` en el buscador | `%` y `_` del usuario actúan como comodines; exactitud, no seguridad. |
| D5 | `aria-label` dinámico del menú | Actualizar "Abrir/Cerrar menú" para lectores de pantalla. |

Además, las funcionalidades ya previstas en la base pero sin uso en v1:
reseñas, transacciones, panel de administración, registro de clientes
(`usuarios`), foto de perfil, cambio de contraseña, filtros avanzados y
paginación.

---

## 7. Pendientes que dependen del diseño de la futura plataforma unificada

Cambios que conviene postergar hasta definir la arquitectura mayor,
porque su forma correcta depende de esas decisiones:

| ID | Pendiente | De qué decisión depende |
|----|-----------|--------------------------|
| I4 | Capa de acceso a datos (SQL hoy embebido en cada página) | De si la plataforma futura adopta un framework/ORM o mantiene PDO a mano. |
| D1 | `oficio` como texto libre vs. FK a `categorias` | De si las categorías serán editables y de cómo se modele el catálogo (afecta esquema y buscador). |
| D3 | Rutas relativas vs. `base_url()`/routing | De si se introduce un front controller, subcarpetas o framework. |

---

## 8. Checklist de deploy a producción (InfinityFree)

**Preparación:**
- [ ] Todos los items de la v1 verificados en local.
- [ ] Base de datos exportada desde phpMyAdmin local (`.sql`).
- [ ] Credenciales de producción de InfinityFree a mano.

**Base de datos:**
- [ ] Base MySQL creada en el panel de InfinityFree.
- [ ] `sql/oficiored_v1.sql` importado vía phpMyAdmin de producción.
- [ ] 10 categorías visibles en la tabla `categorias`.

**Configuración (crítico):**
- [ ] En `includes/config.local.php` cargar host, base, usuario y
      password reales de InfinityFree.
- [ ] En `includes/config.local.php` poner `'app_debug' => false`.
- [ ] Confirmar que `config.local.php` NO se sube al repositorio
      (está en `.gitignore`).

**Subida de archivos:**
- [ ] Todos los archivos subidos vía FTP a `htdocs/`.
- [ ] `assets/img/` con `logo.svg` (y `og-image.jpg` si está disponible).
- [ ] `.htaccess` de la raíz presente, más los de `includes/` y `sql/`.
- [ ] `includes/config.local.php` presente en el servidor con los
      valores de producción.

**Verificación en producción:**
- [ ] El sitio abre sin error 500.
- [ ] El buscador devuelve resultados (confirma conexión a BD).
- [ ] Registro y login de un profesional funcionan.
- [ ] `…/sql/` y `…/includes/` devuelven 403 (aislamiento activo).
- [ ] No aparecen errores PHP en pantalla (confirma `app_debug=false`).
- [ ] El sitio es legible desde el celular.

**Post-deploy:**
- [ ] Credenciales de producción guardadas de forma segura, fuera del
      repositorio.
- [ ] Commit final del código (sin `config.local.php`) con el mensaje de
      lanzamiento de la v1.

---

## 9. Estado final

La v1 de OficioRed queda **cerrada y estable desde el punto de vista
técnico** al 13 de julio de 2026. Los cuatro puntos críticos de la
auditoría están resueltos y la base de seguridad está cubierta. El único
paso operativo que separa el estado actual de un entorno productivo es
completar el checklist de deploy (en particular, cargar las credenciales
reales y `app_debug=false` en `config.local.php`).
