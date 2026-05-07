// assets/js/transacciones.js

const API_ENDPOINT = 'api/transacciones.php';
const API_USUARIOS = 'api/usuarios.php';
const API_SERVICIOS = 'api/servicios.php';

document.addEventListener('DOMContentLoaded', () => {
    loadTransacciones();
    loadSelectOptions();

    document.getElementById('transaccion-form').addEventListener('submit', function(e) {
        e.preventDefault();
        saveTransaccion();
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
        // Cargar Usuarios (Clientes)
        const resUsu = await fetch(API_USUARIOS);
        const dataUsu = await resUsu.json();
        if (dataUsu.status === 'success') {
            const selectUsu = document.getElementById('id_usuario');
            dataUsu.data.forEach(u => {
                selectUsu.innerHTML += `<option value="${u.id_usuario}">${u.nombre} ${u.apellido} (${u.email})</option>`;
            });
        }

        // Cargar Servicios
        const resSrv = await fetch(API_SERVICIOS);
        const dataSrv = await resSrv.json();
        if (dataSrv.status === 'success') {
            const selectSrv = document.getElementById('id_servicio_ofrecido');
            dataSrv.data.forEach(s => {
                selectSrv.innerHTML += `<option value="${s.id_servicio_ofrecido}">${s.titulo} - ${s.profesional_nombre} ${s.profesional_apellido} ($${s.precio_base})</option>`;
            });
        }
    } catch (error) {
        console.error('Error cargando opciones:', error);
    }
}

async function loadTransacciones() {
    try {
        const response = await fetch(API_ENDPOINT);
        const data = await response.json();
        
        if (data.status === 'success') {
            renderTable(data.data);
        } else {
            showToast(data.message || 'Error al cargar transacciones', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión con el servidor', 'error');
    }
}

function renderTable(transacciones) {
    const tbody = document.getElementById('table-body');
    tbody.innerHTML = '';
    
    if (transacciones.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;">No hay transacciones registradas.</td></tr>`;
        return;
    }

    transacciones.forEach(t => {
        let color = '';
        switch(t.estado) {
            case 'pendiente': color = 'orange'; break;
            case 'aceptado': color = 'blue'; break;
            case 'en_proceso': color = '#10B981'; break;
            case 'finalizado': color = 'green'; break;
            case 'cancelado': color = 'red'; break;
        }
        const estadoBadge = `<span style="background:${color};color:white;padding:2px 8px;border-radius:12px;font-size:12px;">${t.estado.toUpperCase()}</span>`;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>#${t.id_transaccion}</td>
            <td>${t.usuario_nombre} ${t.usuario_apellido}</td>
            <td><strong>${t.servicio_titulo}</strong></td>
            <td>${t.profesional_nombre} ${t.profesional_apellido}</td>
            <td>${estadoBadge}</td>
            <td>$${t.monto_acordado}</td>
            <td class="actions">
                <button class="btn btn-primary" onclick='editTransaccion(${JSON.stringify(t).replace(/'/g, "&#39;")})'>Editar</button>
                <button class="btn btn-danger" onclick="deleteTransaccion(${t.id_transaccion})">Eliminar</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function openModal() {
    document.getElementById('transaccion-form').reset();
    document.getElementById('id_transaccion').value = '';
    
    // Al crear, permitimos seleccionar cliente y servicio
    document.getElementById('id_usuario').disabled = false;
    document.getElementById('id_servicio_ofrecido').disabled = false;
    
    document.getElementById('modal-title').textContent = 'Nueva Transacción';
    document.getElementById('modal').classList.add('active');
}

function closeModal() {
    document.getElementById('modal').classList.remove('active');
}

function editTransaccion(t) {
    document.getElementById('id_transaccion').value = t.id_transaccion;
    document.getElementById('id_usuario').value = t.id_usuario;
    document.getElementById('id_servicio_ofrecido').value = t.id_servicio_ofrecido;
    document.getElementById('estado').value = t.estado;
    document.getElementById('monto_acordado').value = t.monto_acordado;
    
    // Al editar, no se debe cambiar el cliente ni el servicio, solo el estado y el monto
    document.getElementById('id_usuario').disabled = true;
    document.getElementById('id_servicio_ofrecido').disabled = true;
    
    document.getElementById('modal-title').textContent = 'Editar Transacción';
    document.getElementById('modal').classList.add('active');
}

async function saveTransaccion() {
    const id = document.getElementById('id_transaccion').value;
    const estado = document.getElementById('estado').value;
    const monto_acordado = document.getElementById('monto_acordado').value;
    
    let payload = {};
    let method = 'POST';

    if (id) {
        // Edit
        method = 'PUT';
        payload = { id_transaccion: id, estado, monto_acordado };
    } else {
        // Create
        method = 'POST';
        const id_usuario = document.getElementById('id_usuario').value;
        const id_servicio_ofrecido = document.getElementById('id_servicio_ofrecido').value;
        payload = { id_usuario, id_servicio_ofrecido, monto_acordado };
    }

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
            loadTransacciones();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al guardar la transacción', 'error');
    }
}

async function deleteTransaccion(id) {
    if (!confirm('¿Estás seguro de que deseas eliminar esta transacción? Esto también eliminará los pagos y reseñas asociados.')) return;
    
    try {
        const response = await fetch(API_ENDPOINT, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_transaccion: id })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            showToast(data.message, 'success');
            loadTransacciones();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al eliminar la transacción', 'error');
    }
}
