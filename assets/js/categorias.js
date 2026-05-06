// assets/js/categorias.js

const API_URL = 'http://localhost/OficioRed/api/categorias.php'; // Ajustar según ubicación en XAMPP
// Si están usando un servidor distinto, esto podría requerir cambios. 
// Por ahora, usamos rutas relativas donde sea posible, o configuramos dinámicamente.
const API_ENDPOINT = 'api/categorias.php';

document.addEventListener('DOMContentLoaded', () => {
    loadCategorias();

    document.getElementById('categoria-form').addEventListener('submit', function(e) {
        e.preventDefault();
        saveCategoria();
    });
});

// Utilidad para mostrar notificaciones
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

// Cargar categorías (GET)
async function loadCategorias() {
    try {
        const response = await fetch(API_ENDPOINT);
        const data = await response.json();
        
        if (data.status === 'success') {
            renderTable(data.data);
        } else {
            showToast(data.message || 'Error al cargar categorías', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión con el servidor', 'error');
    }
}

// Renderizar tabla
function renderTable(categorias) {
    const tbody = document.getElementById('table-body');
    tbody.innerHTML = '';
    
    if (categorias.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;">No hay categorías registradas.</td></tr>`;
        return;
    }

    categorias.forEach(cat => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>#${cat.id_categoria}</td>
            <td><strong>${cat.nombre}</strong></td>
            <td>${cat.descripcion}</td>
            <td class="actions">
                <button class="btn btn-primary" onclick="editCategoria(${cat.id_categoria}, '${cat.nombre.replace(/'/g, "\\'")}', '${cat.descripcion.replace(/'/g, "\\'")}')">Editar</button>
                <button class="btn btn-danger" onclick="deleteCategoria(${cat.id_categoria})">Eliminar</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Abrir Modal (Crear)
function openModal() {
    document.getElementById('categoria-form').reset();
    document.getElementById('id_categoria').value = '';
    document.getElementById('modal-title').textContent = 'Nueva Categoría';
    document.getElementById('modal').classList.add('active');
}

// Cerrar Modal
function closeModal() {
    document.getElementById('modal').classList.remove('active');
}

// Editar Categoría (Cargar datos en Modal)
function editCategoria(id, nombre, descripcion) {
    document.getElementById('id_categoria').value = id;
    document.getElementById('nombre').value = nombre;
    document.getElementById('descripcion').value = descripcion;
    document.getElementById('modal-title').textContent = 'Editar Categoría';
    document.getElementById('modal').classList.add('active');
}

// Guardar Categoría (POST o PUT)
async function saveCategoria() {
    const id = document.getElementById('id_categoria').value;
    const nombre = document.getElementById('nombre').value;
    const descripcion = document.getElementById('descripcion').value;
    
    const method = id ? 'PUT' : 'POST';
    const payload = { nombre, descripcion };
    if (id) payload.id_categoria = id;

    try {
        const response = await fetch(API_ENDPOINT, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            showToast(data.message, 'success');
            closeModal();
            loadCategorias();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al guardar la categoría', 'error');
    }
}

// Eliminar Categoría (DELETE)
async function deleteCategoria(id) {
    if (!confirm('¿Estás seguro de que deseas eliminar esta categoría?')) return;
    
    try {
        const response = await fetch(API_ENDPOINT, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id_categoria: id })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            showToast(data.message, 'success');
            loadCategorias();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al eliminar la categoría', 'error');
    }
}
