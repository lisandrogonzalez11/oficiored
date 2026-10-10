# OficioRed

Directorio web de **profesionales de oficios de El Colorado (Formosa)**. Permite a los
vecinos buscar y contactar electricistas, plomeros, albañiles y otros trabajadores
independientes por categoría y zona de cobertura; a los profesionales publicar y gestionar
su perfil de forma gratuita; y a un administrador moderar los perfiles antes de que sean
públicos.

El contacto entre vecino y profesional es **directo** (teléfono / email / WhatsApp), sin
intermediarios ni comisiones.

---

## Stack

- **Backend:** PHP 8 (vanilla, sin framework), arquitectura monolítica por páginas.
- **Base de datos:** MariaDB 10.4 / MySQL, accedida con **PDO** (prepared statements en toda la capa de datos).
- **Frontend:** HTML5 + CSS3 + JavaScript vanilla. Íconos con Lucide (CDN, versión fijada).
- **Email:** PHPMailer vía SMTP (recuperación de contraseña, avisos).
- **Entorno:** XAMPP (Apache + PHP + MariaDB) en local.

---

## Estructura del proyecto

```
oficiored/
├── index.php              Home: hero, categorías y destacados
├── buscar.php             Búsqueda y filtro (texto / categoría / zona) + paginación
├── perfil.php             Perfil público de un profesional
├── registro.php           Alta de profesional (queda en estado 'en_revision')
├── login.php / logout.php Autenticación de profesional
├── dashboard.php          Panel privado del profesional
├── editar-perfil.php      Edición de datos y foto
├── mis-servicios.php      Gestión de servicios ofrecidos (CRUD)
├── recuperacion.php       Solicitud de recuperación de contraseña
├── nueva-password.php     Reset de contraseña con token
├── verificar-telefono.php Verificación de teléfono (OTP)
├── en-revision.php        Aviso tras el registro
│
├── includes/
│   ├── db.php                    Conexión PDO + política de errores
│   ├── config.local.php          Credenciales del entorno (NO se versiona)
│   ├── config.example.php        Plantilla de configuración
│   ├── funciones.php             sanitizar(), csrf_token(), validaciones, helpers
│   ├── mailer.php                Envío de emails (PHPMailer/SMTP)
│   ├── header.php / footer.php   Cabecera, navegación y pie
│   ├── card-profesional.php      Tarjeta reutilizable de profesional
│   └── form-campos-profesional.php  Campos compartidos de registro/edición
│
├── admin/                 Panel de administración (login propio)
│   ├── index.php          Tablero
│   ├── profesionales.php  Moderación: aprobar / rechazar / activar / eliminar
│   ├── categorias.php     CRUD de categorías
│   ├── zonas.php          Gestión de zonas de cobertura
│   ├── verificaciones.php Cola de verificaciones de identidad (DNI)
│   └── apariencia.php     Configuración del hero / CTA / textos del sitio
│
├── css/estilos.css        Estilos del sitio público
├── css/admin.css          Estilos del panel de administración
├── js/main.js             Comportamientos del sitio
├── uploads/fotos/         Fotos de perfil subidas (no se versionan)
└── sql/                   Scripts SQL por versión (ver Instalación)
```

---

## Instalación

1. Instalar **XAMPP** (PHP 8.x + Apache + MariaDB 10.4).
2. Copiar el proyecto en `C:\xampp\htdocs\oficiored\`.
3. Iniciar **Apache** y **MySQL** desde el panel de XAMPP.
4. En phpMyAdmin (`http://localhost/phpmyadmin`) crear la base de datos **`oficiored`**.
5. Importar los scripts SQL en este orden:
   1. `sql/oficiored_v1.sql` (base)
   2. `sql/v2_fase1_a_categoria_id.sql`
   3. `sql/v2_fase1_b_fecha_actualizacion.sql`
   4. `sql/v2_fase1_c_servicios.sql`
   5. `sql/v2_fase2_a_tokens_recuperacion.sql`
   6. `sql/v2_fase2_b_verificacion.sql`
   7. `sql/v2_fase2_c_en_revision.sql`
   8. `sql/v2_fase3_zonas.sql`
   9. `sql/v3_configuracion_sitio.sql`
   10. `sql/datos_prueba.sql` *(opcional — datos de demostración)*

---

## Configuración

1. Copiar `includes/config.example.php` como `includes/config.local.php`.
2. Completar las credenciales del entorno:
   - **Base de datos:** `db_host`, `db_name`, `db_user`, `db_pass`, `db_charset`.
   - **Depuración:** `app_debug` → `true` en local, **`false` en producción**.
   - **SMTP** (recuperación de contraseña): `smtp_host`, `smtp_port`, `smtp_user`, `smtp_pass`.
   - **OTP por WhatsApp** (opcional): credenciales de Twilio.

> `includes/config.local.php` está excluido en `.gitignore`. **No subir credenciales reales al repositorio.**

---

## Ejecución

- Sitio público: `http://localhost/oficiored/`
- Panel de administración: `http://localhost/oficiored/admin/` (credenciales del usuario administrador cargado en la BD).

---

## Versión

- **Rama:** `main`
- **Commit de referencia:** `7e3b31b73cb04a5269f6fb4fcf0c76de49766284`
- **Fecha de la versión estable:** 05/09/2026

---

## Estado actual

Prototipo funcional validado en entorno local. Cubre de punta a punta los tres flujos
principales:

- **Cliente:** búsqueda por texto / categoría / zona, perfil público y contacto directo.
- **Profesional:** registro, login, dashboard, edición de perfil y foto, gestión de servicios, recuperación de contraseña.
- **Administrador:** moderación de profesionales (aprobar/rechazar), categorías, zonas, verificación de identidad y apariencia del sitio.

Seguridad: PDO con prepared statements, hash **bcrypt** de contraseñas, **CSRF** en todos
los formularios, `sanitizar()` en toda salida y estado `en_revision` para moderar altas.

---

## Pendientes

- **Formulario de solicitud de presupuesto** — brecha activa del MVP original (a implementar antes del coloquio).
- Sistema de reseñas — Fase 2.
- Paginación avanzada / filtros adicionales — Fase 2.
- Deploy en producción — Fase 2.

---

## Equipo

**Grupo 12 — Prácticas Profesionalizantes II (Ciclo 2026)**
Juan Leistmeister · Lisandro · Mirian
I.S.F.D. y T. "República Federal de Alemania" — El Colorado, Formosa.
