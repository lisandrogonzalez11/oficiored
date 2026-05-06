// assets/js/profesionales.js

const API_ENDPOINT = 'api/profesionales.php';

document.addEventListener('DOMContentLoaded', () => {
    loadProfesionales();

    document.getElementById('profesional-form').addEventListener('submit', function(e) {
        e.preventDefault();
        saveProfesional();
    });
});

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

async function loadProfesionales() {
    try {
        const response = await fetch(API_ENDPOINT);
        const data = await response.json();
        
        if (data.status === 'success') {
            renderTable(data.data);
        } else {
            showToast(data.message || 'Error al cargar profesionales', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión con el servidor', 'error');
    }
}

function renderTable(profesionales) {
    const tbody = document.getElementById('table-body');
    tbody.innerHTML = '';
    
    if (profesionales.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;">No hay profesionales registrados.</td></tr>`;
        return;
    }

    profesionales.forEach(prof => {
        const date = new Date(prof.fecha_registro).toLocaleDateString('es-AR');
        
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>#${prof.id_profesional}</td>
            <td><strong>${prof.nombre} ${prof.apellido}</strong></td>
            <td>${prof.email}</td>
            <td>${prof.telefono || '-'}</td>
            <td>${date}</td>
            <td class="actions">
                <button class="btn btn-primary" onclick='editProfesional(${JSON.stringify(prof).replace(/'/g, "&#39;")})'>Editar</button>
                <button class="btn btn-danger" onclick="deleteProfesional(${prof.id_profesional})">Eliminar</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function openModal() {
    document.getElementById('profesional-form').reset();
    document.getElementById('id_profesional').value = '';
    document.getElementById('password').required = true;
    document.getElementById('modal-title').textContent = 'Nuevo Profesional';
    document.getElementById('modal').classList.add('active');
}

function closeModal() {
    document.getElementById('modal').classList.remove('active');
}

function editProfesional(prof) {
    document.getElementById('id_profesional').value = prof.id_profesional;
    document.getElementById('nombre').value = prof.nombre;
    document.getElementById('apellido').value = prof.apellido;
    document.getElementById('email').value = prof.email;
    document.getElementById('telefono').value = prof.telefono || '';
    document.getElementById('foto_perfil').value = prof.foto_perfil || '';
    document.getElementById('biografia').value = prof.biografia || '';
    
    document.getElementById('password').required = false;
    document.getElementById('password').value = '';
    
    document.getElementById('modal-title').textContent = 'Editar Profesional';
    document.getElementById('modal').classList.add('active');
}

async function saveProfesional() {
    const id = document.getElementById('id_profesional').value;
    const nombre = document.getElementById('nombre').value;
    const apellido = document.getElementById('apellido').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const telefono = document.getElementById('telefono').value;
    const foto_perfil = document.getElementById('foto_perfil').value;
    const biografia = document.getElementById('biografia').value;
    
    const method = id ? 'PUT' : 'POST';
    const payload = { nombre, apellido, email, telefono, foto_perfil, biografia };
    
    if (id) payload.id_profesional = id;
    if (password) payload.password = password;

    try {
        const response = await fetch(API_ENDPOINT, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            showToast(data.message, 'success');
            closeModal();
            loadProfesionales();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al guardar el profesional', 'error');
    }
}

async function deleteProfesional(id) {
    if (!confirm('¿Estás seguro de que deseas eliminar este profesional? No podrás hacerlo si tiene servicios publicados.')) return;
    
    try {
        const response = await fetch(API_ENDPOINT, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_profesional: id })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            showToast(data.message, 'success');
            loadProfesionales();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al eliminar el profesional', 'error');
    }
}
