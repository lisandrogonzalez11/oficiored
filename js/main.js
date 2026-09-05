// =============================================================
// main.js — Comportamientos globales de OficioRed (vanilla JS)
// =============================================================

// 1. Menu hamburguesa mobile
const menuToggle = document.getElementById('menu-toggle');
const headerNav = document.getElementById('header-nav');

if (menuToggle && headerNav) {
  menuToggle.addEventListener('click', () => {
    headerNav.classList.toggle('nav-abierto');
    const icono = menuToggle.querySelector('[data-lucide]');
    if (icono) {
      const esAbierto = headerNav.classList.contains('nav-abierto');
      icono.setAttribute('data-lucide', esAbierto ? 'x' : 'menu');
      if (window.lucide) lucide.createIcons();
    }
  });
}

// 2. Cierre automatico del menu al hacer click fuera
document.addEventListener('click', (evento) => {
  if (!headerNav || !menuToggle) return;
  const clickDentroDelMenu = headerNav.contains(evento.target);
  const clickEnElBoton = menuToggle.contains(evento.target);
  if (!clickDentroDelMenu && !clickEnElBoton) {
    headerNav.classList.remove('nav-abierto');
  }
});

// 3. Auto-cierre de mensajes flash
const mensajeFlash = document.querySelector('.mensaje-flash');
if (mensajeFlash) {
  setTimeout(() => {
    mensajeFlash.style.transition = 'opacity 0.3s';
    mensajeFlash.style.opacity = '0';
    setTimeout(() => mensajeFlash.remove(), 300);
  }, 4000);
}

// 4. Validacion basica del formulario de busqueda
const formsBusqueda = document.querySelectorAll('.form-busqueda');
formsBusqueda.forEach((form) => {
  form.addEventListener('submit', (evento) => {
    const input = form.querySelector('input[name="q"]');
    const valor = input.value.trim();
    if (valor.length < 2) {
      evento.preventDefault();
      mostrarErrorBusqueda(input, 'Escribí al menos 2 caracteres');
    }
  });
});

function mostrarErrorBusqueda(input, mensaje) {
  let error = input.parentElement.querySelector('.form-error');
  if (!error) {
    error = document.createElement('span');
    error.className = 'form-error';
    input.parentElement.appendChild(error);
  }
  error.textContent = mensaje;
}

// 5. Contador de caracteres en textarea de biografia
const textareaBio = document.getElementById('biografia');
const contadorBio = document.getElementById('contador-biografia');

if (textareaBio && contadorBio) {
  const max = 500;
  const actualizarContador = () => {
    contadorBio.textContent = `${textareaBio.value.length} / ${max} caracteres`;
  };
  textareaBio.addEventListener('input', actualizarContador);
  actualizarContador();
}

// 6. Inicializacion de Lucide Icons
if (window.lucide) {
  lucide.createIcons();
}
