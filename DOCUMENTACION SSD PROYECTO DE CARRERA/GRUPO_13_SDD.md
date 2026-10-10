# GRUPO 13 - SDD

## Integrantes
- Juan Leistmeister  
- Gonzalez Lisandro  
- Mirian Rivero  

---

## 1. Identificación del Problema (Etapa I)

En la localidad de El Colorado (Formosa), los trabajadores de oficio (albañiles, electricistas, carpinteros, costureras, entre otros) no cuentan con un medio formal, accesible y organizado para promocionar sus servicios.

Por otro lado, los clientes dependen del boca a boca o redes sociales informales, lo que dificulta encontrar trabajadores confiables, comparar opciones y tomar decisiones.

Esto genera:
- Baja visibilidad para los trabajadores  
- Pérdida de oportunidades laborales  
- Dificultad para los clientes al momento de contratar servicios  

---

## 2. Constitución (SDD Fase 1)

### Stack tecnológico
- Frontend: HTML, CSS, JavaScript  
- Backend: PHP  
- Base de datos: MySQL (gestionada con phpMyAdmin vía XAMPP)  
- Servidor local: Apache (incluido en XAMPP)  
- Control de versiones: Git + GitHub  
- Herramientas: Figma  

### Convenciones de código y trabajo
- Uso de nombres descriptivos en variables y funciones  
- Código modular y reutilizable  
- Commits claros (ej: "feat: registro de usuario")  
- Uso de ramas (feature, develop, main)  
- Documentación básica en cada módulo  

### Reglas no negociables
- Todo código debe estar versionado en Git  
- Todo código debe tener testing básico  
- Ninguna funcionalidad se integra sin revisión previa  
- La documentación se actualiza antes que el código  
- No se sube código que no funcione  

---

## 3. Especificación (SDD Fase 2)

### ¿Qué problema exacto resuelve?
La falta de una plataforma centralizada, confiable y accesible que conecte a trabajadores de oficio con clientes en la comunidad local de El Colorado, Formosa.

### ¿Para quién está dirigido?

El sistema contempla tres perfiles de usuario:

**Usuario Trabajador (usuario primario):** es el actor principal del sistema. Puede registrarse, crear y editar su perfil profesional indicando oficio, experiencia, zona de trabajo y datos de contacto. Su objetivo es conseguir mayor visibilidad y acceso a potenciales clientes dentro de la comunidad local.

**Usuario Cliente (usuario secundario):** vecino que necesita contratar un servicio. Puede buscar y filtrar trabajadores por categoría de oficio, visualizar perfiles y tomar contacto directo con el trabajador. No requiere registro obligatorio para realizar búsquedas.

**Usuario Administrador:** perfil con acceso total al sistema. Puede gestionar usuarios (alta, baja y modificación de cuentas), moderar perfiles de trabajadores y supervisar el contenido general de la plataforma. Su panel debe ser intuitivo y centralizado para facilitar la administración sin conocimientos técnicos avanzados.

### Requerimientos funcionales
- RF1: El sistema debe permitir a los usuarios registrarse e iniciar sesión  
- RF2: El sistema debe permitir a los trabajadores crear y editar su perfil  
- RF3: El sistema debe permitir buscar trabajadores por categoría de oficio  
- RF4: El sistema debe permitir a los clientes contactar al trabajador a través de la plataforma (formulario de contacto o datos visibles en el perfil)  
- RF5 *(proyectado para versión 2):* El sistema debe permitir a los clientes publicar reseñas y calificaciones sobre los trabajadores contratados  

### Requerimientos no funcionales
- RNF1: El sistema debe presentar una interfaz intuitiva que permita a un usuario sin experiencia previa completar las tareas principales (registro, búsqueda y contacto) en un máximo de 3 pasos, sin necesidad de capacitación  
- RNF2: El sistema debe tener un diseño responsivo que garantice su correcto funcionamiento y visualización en dispositivos móviles (Android/iOS) y navegadores de escritorio, sin pérdida de funcionalidad  
- RNF3: El sistema debe proteger los datos personales de los usuarios, garantizando acceso seguro mediante contraseñas cifradas y control de roles (trabajador / cliente / administrador)  
- RNF4: El sistema debe cargar las páginas principales en un tiempo máximo de 3 segundos bajo condiciones normales de red  

### Posibles riesgos o dificultades

| Riesgo / dificultad | Cómo podría afectar al proyecto |
|---|---|
| Baja adopción inicial por parte de los usuarios | Si los trabajadores no se registran o los clientes no utilizan la plataforma, el sistema pierde su valor como bolsa de trabajo local. Podría requerir campañas de difusión, demostraciones presenciales o incentivos para lograr la masa crítica de usuarios necesaria. |
| Falta de confianza en los perfiles | Los clientes podrían dudar en contratar a un trabajador desconocido sin historial verificable. Esto reduce la tasa de contrataciones y afecta la credibilidad de la plataforma, especialmente en las etapas iniciales donde aún no hay reseñas acumuladas. |
| Perfiles desactualizados o trabajadores inactivos | Un trabajador registrado que luego no mantiene su perfil activo o no responde contactos genera una mala experiencia para el cliente y daña la imagen del sistema. Podría requerirse un mecanismo de estado de disponibilidad o verificación periódica de actividad. |
