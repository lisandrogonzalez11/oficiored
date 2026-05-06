// assets/js/usuarios.js

const API_ENDPOINT = 'api/usuarios.php';

document.addEventListener('DOMContentLoaded', () => {
    loadUsuarios();

    document.getElementById('usuario-form').addEventListener('submit', function(e) {
        e.preventDefault();
        saveUsuario();
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

async function loadUsuarios() {
    try {
        const response = await fetch(API_ENDPOINT);
        const data = await response.json();
        
        if (data.status === 'success') {
            renderTable(data.data);
        } else {
            showToast(data.message || 'Error al cargar usuarios', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión con el servidor', 'error');
    }
}

function renderTable(usuarios) {
    const tbody = document.getElementById('table-body');
    tbody.innerHTML = '';
    
    if (usuarios.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;">No hay usuarios registrados.</td></tr>`;
        return;
    }

    usuarios.forEach(user => {
        // Formatear fecha
        const date = new Date(user.fecha_registro).toLocaleDateString('es-AR');
        
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>#${user.id_usuario}</td>
            <td><strong>${user.nombre} ${user.apellido}</strong></td>
            <td>${user.email}</td>
            <td>${user.telefono || '-'}</td>
            <td>${date}</td>
            <td class="actions">
                <button class="btn btn-primary" onclick='editUsuario(${JSON.stringify(user)})'>Editar</button>
                <button class="btn btn-danger" onclick="deleteUsuario(${user.id_usuario})">Eliminar</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function openModal() {
    document.getElementById('usuario-form').reset();
    document.getElementById('id_usuario').value = '';
    document.getElementById('password').required = true;
    document.getElementById('modal-title').textContent = 'Nuevo Usuario';
    document.getElementById('modal').classList.add('active');
}

function closeModal() {
    document.getElementById('modal').classList.remove('active');
}

function editUsuario(user) {
    document.getElementById('id_usuario').value = user.id_usuario;
    document.getElementById('nombre').value = user.nombre;
    document.getElementById('apellido').value = user.apellido;
    document.getElementById('email').value = user.email;
    document.getElementById('telefono').value = user.telefono || '';
    document.getElementById('direccion').value = user.direccion || '';
    
    // El password no es obligatorio al editar
    document.getElementById('password').required = false;
    document.getElementById('password').value = '';
    
    document.getElementById('modal-title').textContent = 'Editar Usuario';
    document.getElementById('modal').classList.add('active');
}

async function saveUsuario() {
    const id = document.getElementById('id_usuario').value;
    const nombre = document.getElementById('nombre').value;
    const apellido = document.getElementById('apellido').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const telefono = document.getElementById('telefono').value;
    const direccion = document.getElementById('direccion').value;
    
    const method = id ? 'PUT' : 'POST';
    const payload = { nombre, apellido, email, telefono, direccion };
    
    if (id) payload.id_usuario = id;
    if (password) payload.password = password; // Solo enviar si hay un nuevo password

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
            loadUsuarios();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al guardar el usuario', 'error');
    }
}

async function deleteUsuario(id) {
    if (!confirm('¿Estás seguro de que deseas eliminar este usuario? Las transacciones asociadas podrían impedirlo.')) return;
    
    try {
        const response = await fetch(API_ENDPOINT, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_usuario: id })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            showToast(data.message, 'success');
            loadUsuarios();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al eliminar el usuario', 'error');
    }
}
