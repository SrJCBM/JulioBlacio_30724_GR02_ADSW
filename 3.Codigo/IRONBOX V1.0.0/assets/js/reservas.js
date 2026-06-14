(function () {
    'use strict';

    const API_URL = '../controllers/ClaseController.php';

    const atletaSelect = document.getElementById('idAtleta');
    const refreshButton = document.getElementById('refreshButton');
    const statusMessage = document.getElementById('statusMessage');
    const clasesBody = document.getElementById('clasesBody');
    const reservasBody = document.getElementById('reservasBody');
    const clasesEmpty = document.getElementById('clasesEmpty');
    const reservasEmpty = document.getElementById('reservasEmpty');

    document.addEventListener('DOMContentLoaded', iniciar);
    atletaSelect.addEventListener('change', cargarPanelAtleta);
    refreshButton.addEventListener('click', cargarPanelAtleta);

    async function iniciar() {
        await cargarAtletas();
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

    async function cargarPanelAtleta() {
        const idAtleta = obtenerAtletaSeleccionado();

        clasesBody.innerHTML = '';
        reservasBody.innerHTML = '';

        if (!idAtleta) {
            clasesEmpty.hidden = false;
            reservasEmpty.hidden = false;
            clasesEmpty.textContent = 'Seleccione un atleta para ver clases disponibles.';
            reservasEmpty.textContent = 'Seleccione un atleta para ver sus reservas.';
            return;
        }

        try {
            const [clases, reservas] = await Promise.all([
                solicitar('clases', { idAtleta }),
                solicitar('misReservas', { idAtleta }),
            ]);
            renderizarClases(clases.data);
            renderizarReservas(reservas.data);
        } catch (error) {
            mostrarEstado(error.message, 'error');
        }
    }

    function renderizarClases(clases) {
        clasesBody.innerHTML = '';
        clasesEmpty.hidden = clases.length > 0;
        clasesEmpty.textContent = 'No hay clases disponibles.';

        clases.forEach((clase) => {
            const fila = document.createElement('tr');
            fila.innerHTML = `
                <td>${escaparHtml(formatearFecha(clase.dia))}</td>
                <td><span class="tag">${escaparHtml(clase.hora)}</span></td>
                <td>${Number(clase.duracion)} min</td>
                <td>${Number(clase.cuposDisponibles)} / ${Number(clase.cupoMaximo)}</td>
                <td>${escaparHtml(clase.entrenador.nombre)}</td>
                <td>
                    <button
                        type="button"
                        class="primary"
                        data-action="reservar"
                        data-id="${clase.id}"
                        ${clase.yaReservada ? 'disabled' : ''}
                    >
                        ${clase.yaReservada ? 'Reservada' : 'Reservar'}
                    </button>
                </td>
            `;
            clasesBody.appendChild(fila);
        });

        clasesBody.querySelectorAll('button[data-action="reservar"]').forEach((boton) => {
            boton.addEventListener('click', reservarClase);
        });
    }

    function renderizarReservas(reservas) {
        reservasBody.innerHTML = '';
        reservasEmpty.hidden = reservas.length > 0;
        reservasEmpty.textContent = 'No tienes reservas activas.';

        reservas.forEach((reserva) => {
            const fila = document.createElement('tr');
            fila.innerHTML = `
                <td>${escaparHtml(formatearFecha(reserva.clase.dia))}</td>
                <td><span class="tag">${escaparHtml(reserva.clase.hora)}</span></td>
                <td>${Number(reserva.clase.duracion)} min con ${escaparHtml(reserva.clase.entrenador.nombre)}</td>
                <td>${escaparHtml(formatearFechaHora(reserva.fechaReserva))}</td>
                <td><span class="tag">${escaparHtml(reserva.estado)}</span></td>
                <td>
                    <button type="button" class="danger" data-action="cancelar" data-id="${reserva.id}">
                        Cancelar
                    </button>
                </td>
            `;
            reservasBody.appendChild(fila);
        });

        reservasBody.querySelectorAll('button[data-action="cancelar"]').forEach((boton) => {
            boton.addEventListener('click', cancelarReserva);
        });
    }

    async function reservarClase(evento) {
        const idAtleta = obtenerAtletaSeleccionado();
        const idClase = Number(evento.currentTarget.dataset.id);

        try {
            evento.currentTarget.disabled = true;
            await enviar('reservar', { idAtleta, idClase });
            mostrarEstado('Reserva confirmada correctamente.', 'ok');
            await cargarPanelAtleta();
        } catch (error) {
            mostrarEstado(error.message, 'error');
            evento.currentTarget.disabled = false;
        }
    }

    async function cancelarReserva(evento) {
        const idAtleta = obtenerAtletaSeleccionado();
        const id = Number(evento.currentTarget.dataset.id);

        if (!window.confirm('Desea cancelar esta reserva?')) {
            return;
        }

        try {
            evento.currentTarget.disabled = true;
            await enviar('cancelar', { idAtleta, id });
            mostrarEstado('Reserva cancelada correctamente.', 'ok');
            await cargarPanelAtleta();
        } catch (error) {
            mostrarEstado(error.message, 'error');
            evento.currentTarget.disabled = false;
        }
    }

    async function solicitar(accion, parametros) {
        const query = new URLSearchParams({ action: accion, ...(parametros || {}) });
        const respuesta = await fetch(`${API_URL}?${query.toString()}`);
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

    function obtenerAtletaSeleccionado() {
        return Number(atletaSelect.value || 0);
    }

    function mostrarEstado(mensaje, tipo) {
        statusMessage.textContent = mensaje;
        statusMessage.className = `status show ${tipo}`;
    }

    function formatearFecha(valor) {
        const partes = valor.split('-');
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }

    function formatearFechaHora(valor) {
        const [fecha, hora] = valor.split(' ');
        return `${formatearFecha(fecha)} ${hora || ''}`.trim();
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
