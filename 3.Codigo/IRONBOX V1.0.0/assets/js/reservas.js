(function () {
    'use strict';

    const API_URL = '../controllers/ClaseController.php';
    const MEMBRESIA_URL = '../controllers/MembresiaController.php';

    const atletaSelect = document.getElementById('idAtleta');
    const selectorPanel = document.getElementById('selectorPanel');
    const refreshButton = document.getElementById('refreshButton');
    const statusMessage = document.getElementById('statusMessage');
    const clasesBody = document.getElementById('clasesBody');
    const reservasBody = document.getElementById('reservasBody');
    const clasesEmpty = document.getElementById('clasesEmpty');
    const reservasEmpty = document.getElementById('reservasEmpty');
    const membresiaBanner = document.getElementById('membresiaBanner');

    let usarSesionAtleta = false;
    let usuarioActual = null;
    let membresiaVigente = true;

    document.addEventListener('DOMContentLoaded', iniciar);
    atletaSelect.addEventListener('change', cargarPanelAtleta);
    refreshButton.addEventListener('click', cargarPanelAtleta);

    async function iniciar() {
        usuarioActual = await obtenerSesion();
        usarSesionAtleta = usuarioActual && usuarioActual.rol === 'Atleta';

        if (usarSesionAtleta) {
            // El atleta opera sobre su propia sesion: no se carga ni se expone
            // la lista de atletas, y el panel selector se oculta por completo.
            selectorPanel.hidden = true;
            await cargarPanelAtleta();
            return;
        }

        await cargarAtletas();
    }

    async function cargarAtletas() {
        const respuesta = await solicitar('atletas');
        atletaSelect.innerHTML = '<option value="">Seleccione un atleta</option>';

        respuesta.data.forEach((atleta) => {
            const option = document.createElement('option');
            option.value = atleta.id;
            option.textContent = atleta.nombre;
            option.dataset.correo = atleta.correo;
            atletaSelect.appendChild(option);
        });
    }

    async function cargarPanelAtleta() {
        const idAtleta = obtenerAtletaSeleccionado();

        clasesBody.innerHTML = '';
        reservasBody.innerHTML = '';

        if (!usarSesionAtleta && !idAtleta) {
            clasesEmpty.hidden = false;
            reservasEmpty.hidden = false;
            clasesEmpty.textContent = 'Seleccione un atleta para ver clases disponibles.';
            reservasEmpty.textContent = 'Seleccione un atleta para ver sus reservas.';
            ocultarBanner();
            return;
        }

        try {
            const [clases, reservas] = await Promise.all([
                solicitar('clases', idAtleta ? { idAtleta } : {}),
                solicitar('misReservas', idAtleta ? { idAtleta } : {}),
            ]);
            // El banner se resuelve antes de pintar para saber si se pueden
            // habilitar los botones de reserva (requiere membresia vigente).
            await actualizarBannerMembresia(idAtleta);
            renderizarClases(clases.data);
            renderizarReservas(reservas.data);
        } catch (error) {
            mostrarEstado(error.message, 'error');
        }
    }

    async function actualizarBannerMembresia(idAtleta) {
        try {
            const params = new URLSearchParams({ action: 'miMembresia' });
            if (!usarSesionAtleta && idAtleta) {
                params.set('idAtleta', idAtleta);
            }

            const respuesta = await fetch(`${MEMBRESIA_URL}?${params.toString()}`);
            const cuerpo = await respuesta.json();
            renderBannerMembresia(respuesta.ok && cuerpo.success ? cuerpo.data : null);
        } catch (error) {
            // Ante un error transitorio no bloqueamos: el servidor valida igual.
            membresiaVigente = true;
            ocultarBanner();
        }
    }

    function renderBannerMembresia(membresia) {
        const vigente = membresia
            && membresia.estado === 'Pagado'
            && membresia.fechaVencimiento >= hoyISO();

        membresiaVigente = vigente;

        if (vigente) {
            pintarBanner('ok',
                `Membresia ${membresia.tipo} activa. Vence el ${formatearFecha(membresia.fechaVencimiento)}.`, false);
            return;
        }

        if (membresia && membresia.estado === 'Pendiente') {
            pintarBanner('warn',
                'Tu solicitud de membresia esta pendiente de aprobacion del administrador.', false);
            return;
        }

        let mensaje;
        if (membresia && membresia.estado === 'Vencido') {
            mensaje = 'Tu membresia esta vencida. Solicita una nueva para seguir reservando.';
        } else {
            mensaje = 'No tienes una membresia activa. Solicita una para poder reservar.';
        }
        pintarBanner('warn', mensaje, true);
    }

    function pintarBanner(tipo, texto, conBoton) {
        membresiaBanner.className = `banner ${tipo}`;
        membresiaBanner.hidden = false;
        membresiaBanner.innerHTML = '';

        const span = document.createElement('span');
        span.className = 'banner-text';
        span.textContent = texto;
        membresiaBanner.appendChild(span);

        // El CTA de autoservicio solo aplica a la propia sesion del atleta.
        if (conBoton && usarSesionAtleta) {
            const boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'primary btn-sm';
            boton.id = 'solicitarMembresiaButton';
            boton.textContent = 'Solicitar membresia';
            boton.addEventListener('click', solicitarMembresia);
            membresiaBanner.appendChild(boton);
        }
    }

    async function solicitarMembresia(evento) {
        const boton = evento.currentTarget;
        try {
            boton.disabled = true;
            const respuesta = await fetch(`${MEMBRESIA_URL}?action=solicitar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: '{}',
            });
            const cuerpo = await respuesta.json();
            if (!respuesta.ok || cuerpo.success === false) {
                throw new Error(cuerpo.message || 'No se pudo enviar la solicitud.');
            }
            mostrarEstado('Solicitud enviada. El administrador la revisara pronto.', 'ok');
            await cargarPanelAtleta();
        } catch (error) {
            mostrarEstado(error.message, 'error');
            boton.disabled = false;
        }
    }

    function ocultarBanner() {
        membresiaBanner.hidden = true;
        membresiaBanner.innerHTML = '';
    }

    function hoyISO() {
        return new Date().toISOString().slice(0, 10);
    }

    function renderizarClases(clases) {
        clasesBody.innerHTML = '';
        clasesEmpty.hidden = clases.length > 0;
        clasesEmpty.textContent = 'No hay clases disponibles.';

        clases.forEach((clase) => {
            const bloqueadaPorMembresia = !membresiaVigente && !clase.yaReservada;
            const deshabilitada = clase.yaReservada || bloqueadaPorMembresia;
            const etiqueta = clase.yaReservada ? 'Reservada' : 'Reservar';
            const titulo = bloqueadaPorMembresia ? 'title="Necesitas una membresia activa para reservar."' : '';

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
                        ${deshabilitada ? 'disabled' : ''}
                        ${titulo}
                    >
                        ${etiqueta}
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
            await enviar('reservar', { ...(idAtleta ? { idAtleta } : {}), idClase });
            mostrarEstado('Reserva confirmada correctamente.', 'ok');
            await cargarPanelAtleta();
        } catch (error) {
            mostrarEstado(error.message, 'error');
            evento.currentTarget.disabled = false;
            // Si el bloqueo fue por membresia, refresca el aviso accionable.
            await actualizarBannerMembresia(idAtleta);
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
            await enviar('cancelar', { ...(idAtleta ? { idAtleta } : {}), id });
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
