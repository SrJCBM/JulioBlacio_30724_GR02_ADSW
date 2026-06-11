(function () {
    'use strict';

    const API_URL = '../controllers/ComunicacionController.php';

    const atletaSelect = document.getElementById('idAtleta');
    const refreshButton = document.getElementById('refreshButton');
    const statusMessage = document.getElementById('statusMessage');
    const mensajesBody = document.getElementById('mensajesBody');
    const emptyState = document.getElementById('emptyState');

    document.addEventListener('DOMContentLoaded', iniciar);
    atletaSelect.addEventListener('change', cargarMensajes);
    refreshButton.addEventListener('click', cargarMensajes);

    async function iniciar() {
        await cargarAtletas();
        preseleccionarAtletaDesdeUrl();
        await cargarMensajes();
    }

    async function cargarAtletas() {
        const respuesta = await solicitar('atletas');
        atletaSelect.innerHTML = '<option value="">Seleccione atleta</option>';

        respuesta.data.forEach((atleta) => {
            const option = document.createElement('option');
            option.value = atleta.id;
            option.textContent = atleta.nombre;
            atletaSelect.appendChild(option);
        });
    }

    async function cargarMensajes() {
        const idAtleta = Number(atletaSelect.value || 0);
        mensajesBody.innerHTML = '';

        if (!idAtleta) {
            emptyState.hidden = false;
            emptyState.textContent = 'Seleccione un atleta para ver su bandeja.';
            return;
        }

        try {
            const respuesta = await solicitar('recibidos', { idAtleta });
            renderizarMensajes(respuesta.data);
        } catch (error) {
            mostrarEstado(error.message, 'error');
        }
    }

    function renderizarMensajes(mensajes) {
        mensajesBody.innerHTML = '';
        emptyState.hidden = mensajes.length > 0;
        emptyState.textContent = 'No hay mensajes recibidos.';

        mensajes.forEach((mensaje) => {
            const tarjeta = document.createElement('article');
            tarjeta.className = 'message';
            tarjeta.innerHTML = `
                <div class="message-meta">
                    <span class="tag">${escaparHtml(mensaje.tipo)}</span>
                    <span>${escaparHtml(formatearFechaHora(mensaje.fechaEnvio))}</span>
                    <span>${escaparHtml(mensaje.entrenador ? mensaje.entrenador.nombre : 'IronClad Box')}</span>
                </div>
                <p>${escaparHtml(mensaje.contenido)}</p>
            `;
            mensajesBody.appendChild(tarjeta);
        });
    }

    async function solicitar(accion, parametros) {
        const query = new URLSearchParams({ action: accion, ...(parametros || {}) });
        const respuesta = await fetch(`${API_URL}?${query.toString()}`);
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

    function mostrarEstado(mensaje, tipo) {
        statusMessage.textContent = mensaje;
        statusMessage.className = `status show ${tipo}`;
    }

    function formatearFechaHora(valor) {
        const [fecha, hora] = valor.split(' ');
        const partes = fecha.split('-');
        return `${partes[2]}/${partes[1]}/${partes[0]} ${hora || ''}`.trim();
    }

    function escaparHtml(valor) {
        return String(valor)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
})();
