// assets/js/servicios.js

const API_ENDPOINT = 'api/servicios.php';
const API_PROFESIONALES = 'api/profesionales.php';
const API_CATEGORIAS = 'api/categorias.php';

document.addEventListener('DOMContentLoaded', () => {
    loadServicios();
    loadSelectOptions();

    document.getElementById('servicio-form').addEventListener('submit', function(e) {
        e.preventDefault();
        saveServicio();
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

async function loadSelectOptions() {
    try {
        // Cargar Profesionales
        const resProf = await fetch(API_PROFESIONALES);
        const dataProf = await resProf.json();
        if (dataProf.status === 'success') {
            const selectProf = document.getElementById('id_profesional');
            dataProf.data.forEach(p => {
                selectProf.innerHTML += `<option value="${p.id_profesional}">${p.nombre} ${p.apellido}</option>`;
            });
        }

        // Cargar Categorías
        const resCat = await fetch(API_CATEGORIAS);
        const dataCat = await resCat.json();
        if (dataCat.status === 'success') {
            const selectCat = document.getElementById('id_categoria');
            dataCat.data.forEach(c => {
                selectCat.innerHTML += `<option value="${c.id_categoria}">${c.nombre}</option>`;
            });
        }
    } catch (error) {
        console.error('Error cargando opciones:', error);
    }
}

async function loadServicios() {
    try {
        const response = await fetch(API_ENDPOINT);
        const data = await response.json();
        
        if (data.status === 'success') {
            renderTable(data.data);
        } else {
            showToast(data.message || 'Error al cargar servicios', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión con el servidor', 'error');
    }
}

function renderTable(servicios) {
    const tbody = document.getElementById('table-body');
    tbody.innerHTML = '';
    
    if (servicios.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;">No hay servicios registrados.</td></tr>`;
        return;
    }

    servicios.forEach(srv => {
        const estadoBadge = srv.estado === 'activo' 
            ? `<span style="background:var(--secondary);color:white;padding:2px 8px;border-radius:12px;font-size:12px;">Activo</span>`
            : `<span style="background:var(--danger);color:white;padding:2px 8px;border-radius:12px;font-size:12px;">Inactivo</span>`;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>#${srv.id_servicio_ofrecido}</td>
            <td><strong>${srv.titulo}</strong></td>
            <td>${srv.profesional_nombre} ${srv.profesional_apellido}</td>
            <td>${srv.categoria_nombre}</td>
            <td>$${srv.precio_base}</td>
            <td>${estadoBadge}</td>
            <td class="actions">
                <button class="btn btn-primary" onclick='editServicio(${JSON.stringify(srv).replace(/'/g, "&#39;")})'>Editar</button>
                <button class="btn btn-danger" onclick="deleteServicio(${srv.id_servicio_ofrecido})">Eliminar</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function openModal() {
    document.getElementById('servicio-form').reset();
    document.getElementById('id_servicio_ofrecido').value = '';
    document.getElementById('modal-title').textContent = 'Nuevo Servicio';
    document.getElementById('modal').classList.add('active');
}

function closeModal() {
    document.getElementById('modal').classList.remove('active');
}

function editServicio(srv) {
    document.getElementById('id_servicio_ofrecido').value = srv.id_servicio_ofrecido;
    document.getElementById('id_profesional').value = srv.id_profesional;
    document.getElementById('id_categoria').value = srv.id_categoria;
    document.getElementById('titulo').value = srv.titulo;
    document.getElementById('descripcion').value = srv.descripcion;
    document.getElementById('zona_cobertura').value = srv.zona_cobertura || '';
    document.getElementById('precio_base').value = srv.precio_base;
    document.getElementById('estado').value = srv.estado;
    
    document.getElementById('modal-title').textContent = 'Editar Servicio';
    document.getElementById('modal').classList.add('active');
}

async function saveServicio() {
    const id = document.getElementById('id_servicio_ofrecido').value;
    const id_profesional = document.getElementById('id_profesional').value;
    const id_categoria = document.getElementById('id_categoria').value;
    const titulo = document.getElementById('titulo').value;
    const descripcion = document.getElementById('descripcion').value;
    const zona_cobertura = document.getElementById('zona_cobertura').value;
    const precio_base = document.getElementById('precio_base').value;
    const estado = document.getElementById('estado').value;
    
    const method = id ? 'PUT' : 'POST';
    const payload = { id_profesional, id_categoria, titulo, descripcion, zona_cobertura, precio_base, estado };
    
    if (id) payload.id_servicio_ofrecido = id;

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
            loadServicios();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al guardar el servicio', 'error');
    }
}

async function deleteServicio(id) {
    if (!confirm('¿Estás seguro de que deseas eliminar este servicio?')) return;
    
    try {
        const response = await fetch(API_ENDPOINT, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_servicio_ofrecido: id })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            showToast(data.message, 'success');
            loadServicios();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al eliminar el servicio', 'error');
    }
}
