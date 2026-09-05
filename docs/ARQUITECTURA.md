# OficioRed — Arquitectura técnica

Documento de referencia del estado real del proyecto. Refleja el código
existente, no funcionalidades planeadas. Última revisión sobre la base v1
(con los ajustes de endurecimiento C1 y C2 ya aplicados).

---

## 1. Objetivo del proyecto

OficioRed es una plataforma web que conecta a vecinos de El Colorado
(Formosa, Argentina) con trabajadores de oficio (electricistas, plomeros,
albañiles, etc.). Un vecino busca un oficio, ve los profesionales activos de
su zona y los contacta directamente por teléfono o email. El trabajador se
registra, publica su perfil y lo mantiene actualizado.

El sistema no intermedia el contacto ni la transacción: es un directorio.

---

## 2. Filosofía del sistema (qué es y qué no es)

**Es:**
- Un directorio público de profesionales, tipo "páginas amarillas" digital.
- Una aplicación PHP clásica, server-rendered, sin build step ni dependencias
  de backend.
- Un MVP deliberadamente pequeño y legible, pensado para hosting compartido
  barato (XAMPP en local, InfinityFree en producción).

**No es:**
- Un marketplace: no hay pagos, reservas, ni mensajería interna. El contacto
  es directo vía `tel:` / `mailto:`.
- Una SPA ni una API: no hay JSON, no hay frontend framework. El JavaScript es
  solo progressive enhancement (menú, validaciones, contador).
- Un sistema con roles múltiples: en v1 el único actor autenticado es el
  profesional. El "cliente" no tiene cuenta.

Principio rector: **simplicidad sobre abstracción**. Se prefiere código
explícito y repetido antes que capas de indirección que un mantenedor futuro
tendría que descifrar.

---

## 3. Arquitectura general

Modelo page-based de PHP: cada archivo `.php` en la raíz **es** una ruta
accesible por URL. No hay router, ni MVC, ni autoloader, ni Composer.

Las páginas comparten comportamiento incluyendo archivos de `includes/` con
`require_once`. El patrón de ejecución de toda página es siempre el mismo:

1. (Opcional) `session_start()`.
2. `require_once 'includes/db.php'` → deja disponible `$pdo`.
3. `require_once 'includes/funciones.php'` → funciones globales.
4. Lógica PHP: validaciones, consultas, redirecciones. **Todo antes de emitir
   HTML**, para que los `redirigir()` (que usan `header()`) funcionen.
5. `require_once 'includes/header.php'` → abre el documento HTML y la nav.
6. HTML propio de la página.
7. `require_once 'includes/footer.php'` → cierra el documento y carga el JS.

Persistencia: MySQL/MariaDB accedido **siempre** con PDO y prepared
statements. Sesiones nativas de PHP para autenticación.

Frontend: un único `css/estilos.css` (mobile-first, variables CSS) y un único
`js/main.js` (vanilla). Íconos con Lucide vía CDN. Sin emojis en el proyecto.

---

## 4. Organización de carpetas

```
oficiored/
├── index.php            Home: buscador, categorías, destacados
├── buscar.php           Resultados de búsqueda por oficio
├── perfil.php           Perfil público de un profesional
├── registro.php         Alta de cuenta del profesional
├── login.php            Inicio de sesión
├── logout.php           Cierre de sesión (sin HTML)
├── dashboard.php        Panel privado del profesional
├── editar-perfil.php    Edición del propio perfil
│
├── includes/
│   ├── db.php               Conexión PDO + política de errores
│   ├── funciones.php        Funciones globales (6)
│   ├── header.php           <head>, nav condicional, mensajes flash
│   ├── footer.php           Cierre del HTML + carga de JS
│   ├── card-profesional.php Partial reutilizable de tarjeta
│   └── .htaccess            Deniega acceso web directo
│
├── css/estilos.css      Toda la hoja de estilos
├── js/main.js           Todo el comportamiento cliente
├── assets/img/logo.svg  Logo
├── sql/
│   ├── oficiored_v1.sql     Script de creación de la BD + seed
│   └── .htaccess            Deniega acceso web directo
└── .htaccess            Options -Indexes + charset
```

Regla: la raíz contiene solo páginas navegables. Todo lo que no es una ruta
propia vive en `includes/` y no debe ser accesible por URL.

---

## 5. Flujo de cada página

**Públicas (sin sesión):**

- `index.php` — Carga categorías (`obtener_categorias`) y hasta 6 profesionales
  activos ordenados por fecha de registro. Renderiza hero + buscador, grilla de
  categorías, destacados (o estado vacío), "cómo funciona" y CTA.

- `buscar.php` — Lee `q` por GET. Si `q` está vacío, redirige a `index.php`
  antes de todo output. Si tiene menos de 2 caracteres, marca búsqueda inválida
  y no consulta. Si es válido, busca por `oficio LIKE %q%` con `estado='activo'`.
  Distingue tres estados en pantalla: inválida, sin resultados, con resultados.

- `perfil.php` — Valida `id` como entero (`filter_input`). Consulta el
  profesional por id con `estado='activo'`. Si no existe, deja mensaje flash y
  redirige a `index.php`. Si existe, muestra datos, contacto directo y biografía
  (opcional). El contacto es público a propósito.

**Autenticación:**

- `registro.php` — Si ya hay sesión, va a `dashboard`. Genera token CSRF. En
  POST: valida CSRF, valida todos los campos server-side, verifica email único,
  hashea la contraseña con bcrypt, hace INSERT, regenera la sesión, inicia
  sesión y redirige a `dashboard`. Ante error, repinta el formulario con los
  valores previos (los campos de contraseña nunca se repintan) y errores por
  campo.

- `login.php` — Si ya hay sesión, va a `dashboard`. Token CSRF. En POST: valida
  CSRF, busca por email con `estado='activo'`, verifica con `password_verify`.
  El mensaje de error es **genérico** (no revela si falló el email o la clave).
  Al éxito regenera la sesión y redirige a `dashboard`.

- `logout.php` — Vacía `$_SESSION`, destruye la sesión y redirige a `index`.
  No emite HTML.

**Privadas (requieren sesión, protegidas con `requerir_login()`):**

- `dashboard.php` — Carga el profesional por `$_SESSION['profesional_id']`. Si
  el registro ya no existe, destruye la sesión y va a `login`. Muestra estado
  del perfil, alerta si falta teléfono, accesos rápidos y resumen de datos. El
  link al perfil público usa el id que viene de la BD, no de la sesión.

- `editar-perfil.php` — Token CSRF. Carga los datos actuales como valores por
  defecto. En POST: valida CSRF, valida campos, verifica email único
  **excluyendo el propio id**, y hace UPDATE con `WHERE id_profesional =
  $_SESSION['profesional_id']`. Al éxito deja mensaje flash y redirige a
  `dashboard`.

---

## 6. Modelo de datos resumido

Base `oficiored`, charset `utf8mb4`, engine InnoDB. Tres tablas, sin foreign
keys entre ellas en v1.

- **profesionales** — Entidad central. Campos: `id_profesional` (PK),
  `nombre`, `apellido`, `email` (único), `password` (hash bcrypt), `telefono`
  (nullable), `foto_perfil` (nullable, sin uso en v1), `biografia` (nullable),
  `fecha_registro`, `oficio`, `zona_cobertura`, `estado` (ENUM
  `activo`/`inactivo`, default `activo`). Índices en email, oficio y estado.

- **categorias** — Catálogo fijo de oficios. Campos: `id_categoria` (PK),
  `nombre` (único), `descripcion`, `icono_lucide` (default `briefcase`). Trae 10
  registros seed. Alimenta los `<select>` de oficio y la grilla de la home.

- **usuarios** — Creada pero **reservada**, sin uso funcional en v1. Prevista
  para el futuro registro de clientes.

Nota clave: `profesionales.oficio` es un VARCHAR de texto libre que replica el
`nombre` de una categoría. **No es una foreign key.**

---

## 7. Convenciones actuales

- **Idioma:** todo en español (nombres de archivos, variables, funciones,
  columnas, textos).
- **SQL:** exclusivamente PDO con prepared statements. Cero concatenación de
  variables en SQL.
- **Regla de negocio transversal:** toda consulta que lista o muestra
  profesionales incluye `WHERE estado = 'activo'`.
- **Salida a HTML:** todo dato dinámico pasa por `sanitizar()` (o
  `htmlspecialchars()` directo en el header). En la biografía se aplica
  `sanitizar()` y luego `nl2br()`, en ese orden.
- **Zona:** el campo se llama siempre `zona_cobertura`, nunca `zona`.
- **Íconos:** siempre `<i data-lucide="nombre"></i>`. Nunca emojis.
- **Redirecciones:** vía `redirigir()`, siempre antes de cualquier output.
- **Formularios que escriben:** método POST, con token CSRF verificado con
  `hash_equals`.
- **CSS:** mobile-first; toda la paleta y tipografía en variables CSS
  (`:root`); breakpoints en 768px y 1024px; sin `!important`.
- **JS:** cada comportamiento verifica que su elemento exista antes de operar,
  porque el mismo `main.js` corre en todas las páginas.
- **Rutas:** relativas a la raíz del proyecto (`css/estilos.css`, no
  `/oficiored/css/...`), para que funcione igual en local y en producción.

---

## 8. Decisiones arquitectónicas importantes

- **PHP vanilla sin framework.** El equipo es chico y el hosting es compartido.
  Un framework agregaría curva de aprendizaje, build y dependencias que no se
  justifican para el tamaño actual. Prioriza que cualquiera pueda leer el
  proyecto sin conocimiento previo.

- **Modelo page-based sin router.** Cada archivo es una URL. Es el patrón que
  mejor se mapea a hosting compartido (FTP + Apache) y el más transparente de
  depurar.

- **`oficio` como texto libre, no FK.** Simplifica el registro y evita joins.
  El costo (sin integridad referencial) se aceptó conscientemente para v1.

- **Contacto público sin login.** El modelo de negocio es un directorio: el
  vecino debe poder contactar sin registrarse. Por eso solo el profesional
  autentica, y la tabla `usuarios` queda reservada.

- **Seguridad de base desde el día uno.** bcrypt para contraseñas, CSRF en
  todos los POST, prepared statements en todo, `sanitizar()` en toda salida,
  `session_regenerate_id()` al autenticar, y el UPDATE de edición atado al id de
  sesión (no a un campo del formulario) para impedir editar perfiles ajenos.

- **Política de errores centralizada (C1).** `db.php` define la constante
  `APP_DEBUG`. En local (true) los errores se muestran para depurar. En
  producción (false) se ocultan, se registran en el log y un handler global
  muestra una página genérica sin filtrar SQL ni rutas. Se eligió un único punto
  de control en lugar de try/catch por consulta.

- **Aislamiento de `includes/` y `sql/` (C2).** `.htaccess` deniega el acceso
  web directo a esas carpetas, evitando descargar el dump SQL o provocar fatales
  al abrir un include suelto. La lógica sigue siendo accesible por `require`.

- **Un solo CSS y un solo JS.** Menos requests HTTP y menos superficie que
  mantener para un proyecto de este tamaño.

---

## 9. Deuda técnica conocida

Registrada, no resuelta. No compromete el funcionamiento actual.

- **Formulario de perfil duplicado** entre `registro.php` y
  `editar-perfil.php` (~7 campos de HTML repetido). Candidato a extraerse como
  partial.
- **Validación server-side duplicada** entre alta y edición, con diferencias
  sutiles (contraseña, exclusión de id propio). Candidata a una función común.
- **Lógica CSRF repetida** en tres archivos (generar + verificar). Candidata a
  helpers en `funciones.php`.
- **SQL embebido en cada página**, sin capa de acceso a datos. La consulta
  "profesional por id" vive en tres lugares. Riesgo: olvidar `estado='activo'`
  en una consulta futura.
- **Nomenclatura mixta:** PK `id_profesional` vs `$_SESSION['profesional_id']`.
  Además `$_SESSION['profesional_nombre']` se setea pero no se usa (código
  muerto).
- **Contador de biografía** en `editar-perfil.php` calcula el largo inicial con
  `strlen` (bytes) mientras la validación usa `mb_strlen` (caracteres);
  desajuste con tildes/ñ.
- **Dependencia CDN sin versión fijada:** `lucide@latest` puede cambiar sin
  aviso.
- **`session_start()` disperso:** algunas páginas lo llaman, otras dependen del
  header.
- **Manejo de errores no uniforme:** `obtener_categorias()` traga excepciones y
  devuelve `[]`; el resto del código las deja propagar hacia el handler global.
- **Comodines LIKE sin escapar** en el buscador: `%` y `_` del usuario actúan
  como comodines (no es inyección, es exactitud).
- **Credenciales de BD en `db.php`** (archivo versionable). El deploy depende de
  editarlo a mano sin commitear.

---

## 10. Roadmap técnico

**Corresponde a v2 (evolución, no urgente):**
- Extraer la capa de acceso a datos (`includes/profesionales.php`) para
  centralizar consultas y la regla `estado='activo'`.
- Deduplicar formulario y validación de perfil en partials/funciones.
- Centralizar CSRF y el arranque de sesión (`includes/init.php`).
- Migrar `oficio` a `id_categoria` como FK, con integridad referencial.
- Separar credenciales en config fuera del repositorio.
- Escapar comodines LIKE y pinnear la versión de Lucide.
- Funcionalidades ya previstas en la BD pero sin uso: reseñas, transacciones,
  panel de administración, registro de clientes (`usuarios`), foto de perfil,
  cambio de contraseña, filtros avanzados, paginación.

**No corresponde (fuera del alcance del proyecto tal como está concebido):**
- Convertir el proyecto a un framework o a una SPA/API.
- Introducir pagos, reservas o mensajería interna.
- Sistemas de notificaciones que requieran infraestructura externa
  (email/SMS/push).

**Anclas que no deben romperse al evolucionar:**
- `zona_cobertura` como nombre de campo.
- `estado='activo'` en toda consulta de listado.
- PDO + prepared statements en el 100% de las queries.
- `sanitizar()`/`htmlspecialchars()` en toda salida.
- Sin emojis; íconos solo con Lucide.
