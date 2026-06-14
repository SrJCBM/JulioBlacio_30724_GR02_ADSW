(function () {
    'use strict';

    const API_URL = '../controllers/ComunicacionController.php';

    const form = document.getElementById('mensajeForm');
    const entrenadorSelect = document.getElementById('idEntrenador');
    const tipoSelect = document.getElementById('tipo');
    const atletaSelect = document.getElementById('idAtleta');
    const atletaLabel = document.getElementById('atletaLabel');
    const contenidoInput = document.getElementById('contenido');
    const submitButton = document.getElementById('submitButton');
    const refreshButton = document.getElementById('refreshButton');
    const statusMessage = document.getElementById('statusMessage');
    const historialBody = document.getElementById('historialBody');
    const emptyState = document.getElementById('emptyState');

    document.addEventListener('DOMContentLoaded', iniciar);
    form.addEventListener('submit', enviarMensaje);
    tipoSelect.addEventListener('change', sincronizarTipo);
    refreshButton.addEventListener('click', cargarHistorial);

    async function iniciar() {
        await Promise.all([cargarEntrenadores(), cargarAtletas()]);
        sincronizarTipo();
        await cargarHistorial();
    }

    async function cargarEntrenadores() {
        const respuesta = await solicitar('entrenadores');
        entrenadorSelect.innerHTML = '<option value="">Seleccione entrenador</option>';

        respuesta.data.forEach((entrenador) => {
            const option = document.createElement('option');
            option.value = entrenador.id;
            option.textContent = entrenador.nombre;
            entrenadorSelect.appendChild(option);
        });
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

    async function enviarMensaje(evento) {
        evento.preventDefault();

        const tipo = tipoSelect.value;
        const datos = {
            idEntrenador: Number(entrenadorSelect.value),
            tipo,
            idAtleta: tipo === 'Anuncio' ? null : Number(atletaSelect.value),
            contenido: contenidoInput.value.trim(),
        };

        try {
            submitButton.disabled = true;
            await enviar('enviar', datos);
            mostrarEstado('Mensaje enviado correctamente.', 'ok');
            contenidoInput.value = '';
            await cargarHistorial();
        } catch (error) {
            mostrarEstado(error.message, 'error');
        } finally {
            submitButton.disabled = false;
        }
    }

    async function cargarHistorial() {
        try {
            const respuesta = await solicitar('historial');
            renderizarHistorial(respuesta.data);
        } catch (error) {
            mostrarEstado(error.message, 'error');
        }
    }

    function renderizarHistorial(mensajes) {
        historialBody.innerHTML = '';
        emptyState.hidden = mensajes.length > 0;

        mensajes.forEach((mensaje) => {
            const fila = document.createElement('tr');
            fila.innerHTML = `
                <td>${escaparHtml(formatearFechaHora(mensaje.fechaEnvio))}</td>
                <td><span class="tag">${escaparHtml(mensaje.tipo)}</span></td>
                <td>${escaparHtml(mensaje.destinatario.nombre)}</td>
                <td>${escaparHtml(mensaje.contenido)}</td>
            `;
            historialBody.appendChild(fila);
        });
    }

    function sincronizarTipo() {
        const esAnuncio = tipoSelect.value === 'Anuncio';
        atletaLabel.style.display = esAnuncio ? 'none' : 'grid';
        atletaSelect.required = !esAnuncio;
        if (esAnuncio) {
            atletaSelect.value = '';
        }
    }

    async function solicitar(accion) {
        const respuesta = await fetch(`${API_URL}?action=${encodeURIComponent(accion)}`);
        return procesarRespuesta(respuesta);
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
