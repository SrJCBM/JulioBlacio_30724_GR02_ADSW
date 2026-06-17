(function () {
    'use strict';

    const API_URL = '../controllers/MembresiaController.php';

    const atletaSelect = document.getElementById('idAtleta');
    const tipoMembresia = document.getElementById('tipoMembresia');
    const fechaVencimiento = document.getElementById('fechaVencimiento');
    const precioMembresia = document.getElementById('precioMembresia');
    const estadoMembresia = document.getElementById('estadoMembresia');
    const statusMessage = document.getElementById('statusMessage');
    const pagoForm = document.getElementById('pagoForm');
    const submitButton = document.getElementById('submitButton');
    const refreshButton = document.getElementById('refreshButton');
    const cancelMembershipButton = document.getElementById('cancelMembershipButton');

    let usarSesionAtleta = false;

    document.addEventListener('DOMContentLoaded', iniciar);
    atletaSelect.addEventListener('change', cargarMiMembresia);
    pagoForm.addEventListener('submit', pagarMembresia);
    refreshButton.addEventListener('click', cargarMiMembresia);
    cancelMembershipButton.addEventListener('click', cancelarMembresia);

    async function iniciar() {
        const usuario = await obtenerSesion();
        usarSesionAtleta = usuario && usuario.rol === 'Atleta';

        if (usarSesionAtleta) {
            atletaSelect.closest('label').hidden = true;
        } else {
            await cargarAtletas();
            preseleccionarAtletaDesdeUrl();
        }

        await cargarMiMembresia();
    }

    async function cargarAtletas() {
        const respuesta = await solicitar('atletas');
        atletaSelect.innerHTML = '<option value="">Seleccione un atleta</option>';

        respuesta.data.forEach((atleta) => {
            const option = document.createElement('option');
            option.value = atleta.id;
            option.textContent = atleta.nombre;
            atletaSelect.appendChild(option);
        });
    }

    async function cargarMiMembresia() {
        const idAtleta = obtenerAtletaSeleccionado();
        if (!usarSesionAtleta && !idAtleta) {
            renderizarMembresia(null);
            return;
        }

        try {
            const respuesta = await solicitar('miMembresia', usarSesionAtleta ? {} : { idAtleta });
            renderizarMembresia(respuesta.data);
        } catch (error) {
            mostrarEstado(error.message, 'error');
        }
    }

    async function pagarMembresia(evento) {
        evento.preventDefault();

        const idAtleta = obtenerAtletaSeleccionado();
        if (!usarSesionAtleta && !idAtleta) {
            mostrarEstado('Seleccione un atleta antes de pagar.', 'error');
            return;
        }

        try {
            submitButton.disabled = true;
            await enviar('pagarMembresia', {
                ...(usarSesionAtleta ? {} : { idAtleta }),
                metodoPago: document.getElementById('metodoPago').value,
                fechaPago: obtenerFechaActual(),
            });
            pagoForm.reset();
            mostrarEstado('Pago simulado registrado correctamente.', 'ok');
            await cargarMiMembresia();
        } catch (error) {
            mostrarEstado(error.message, 'error');
        } finally {
            submitButton.disabled = false;
        }
    }

    async function cancelarMembresia() {
        const idAtleta = obtenerAtletaSeleccionado();
        if (!usarSesionAtleta && !idAtleta) {
            mostrarEstado('Seleccione un atleta antes de cancelar.', 'error');
            return;
        }

        if (!window.confirm('Desea cancelar su membresia?')) {
            return;
        }

        try {
            cancelMembershipButton.disabled = true;
            await enviar('cancelarMembresia', usarSesionAtleta ? {} : { idAtleta });
            mostrarEstado('Membresia cancelada correctamente.', 'ok');
            await cargarMiMembresia();
        } catch (error) {
            mostrarEstado(error.message, 'error');
        } finally {
            cancelMembershipButton.disabled = false;
        }
    }

    function renderizarMembresia(membresia) {
        if (!membresia) {
            tipoMembresia.textContent = '-';
            fechaVencimiento.textContent = '-';
            precioMembresia.textContent = '-';
            estadoMembresia.textContent = 'Sin membresia';
            estadoMembresia.className = 'tag Pendiente';
            return;
        }

        tipoMembresia.textContent = membresia.tipo;
        fechaVencimiento.textContent = formatearFecha(membresia.fechaVencimiento);
        precioMembresia.textContent = formatearMoneda(membresia.precio);
        estadoMembresia.textContent = membresia.estado;
        estadoMembresia.className = `tag ${membresia.estado}`;
    }

    async function solicitar(accion, parametros) {
        const query = new URLSearchParams({ action: accion, ...(parametros || {}) });
        const respuesta = await fetch(`${API_URL}?${query.toString()}`);
        return procesarRespuesta(respuesta);
    }

    async function obtenerSesion() {
        const respuesta = await fetch('../controllers/AuthController.php?action=me');
        if (!respuesta.ok) {
            return null;
        }

        const cuerpo = await respuesta.json();
        return cuerpo.success ? cuerpo.data : null;
    }

    async function enviar(accion, datos) {
        const respuesta = await fetch(`${API_URL}?action=${encodeURIComponent(accion)}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos),
        });

        return procesarRespuesta(respuesta);
    }

    async function procesarRespuesta(respuesta) {
        const cuerpo = await respuesta.json();
        if (!respuesta.ok || cuerpo.success === false) {
            throw new Error(cuerpo.message || 'No se pudo completar la operacion.');
        }

        return cuerpo;
    }

    function preseleccionarAtletaDesdeUrl() {
        const parametros = new URLSearchParams(window.location.search);
        const idAtleta = parametros.get('idAtleta') || parametros.get('id_atleta');

        if (idAtleta) {
            atletaSelect.value = idAtleta;
        }
    }

    function obtenerAtletaSeleccionado() {
        return Number(atletaSelect.value || 0);
    }

    function obtenerFechaActual() {
        const hoy = new Date();
        const year = hoy.getFullYear();
        const month = String(hoy.getMonth() + 1).padStart(2, '0');
        const day = String(hoy.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function formatearFecha(valor) {
        const partes = valor.split('-');
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }

    function formatearMoneda(valor) {
        return `$${Number(valor).toFixed(2)}`;
    }

    function mostrarEstado(mensaje, tipo) {
        statusMessage.textContent = mensaje;
        statusMessage.className = `status show ${tipo}`;
    }
})();
