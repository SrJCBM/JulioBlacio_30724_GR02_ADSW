(function () {
    'use strict';

    const API_URL = '../controllers/MembresiaController.php';

    const form = document.getElementById('membresiaForm');
    const formTitle = document.getElementById('form-title');
    const membresiaIdInput = document.getElementById('membresiaId');
    const atletaSelect = document.getElementById('idAtleta');
    const tipoSelect = document.getElementById('tipo');
    const precioInput = document.getElementById('precio');
    const fechaInicioInput = document.getElementById('fechaInicio');
    const estadoSelect = document.getElementById('estado');
    const todayButton = document.getElementById('todayButton');
    const cancelButton = document.getElementById('cancelButton');
    const submitButton = document.getElementById('submitButton');
    const statusMessage = document.getElementById('statusMessage');
    const atletasBody = document.getElementById('atletasBody');
    const emptyState = document.getElementById('emptyState');

    let atletas = [];

    document.addEventListener('DOMContentLoaded', iniciar);
    form.addEventListener('submit', guardarMembresia);
    todayButton.addEventListener('click', usarFechaActual);
    cancelButton.addEventListener('click', limpiarFormulario);
    tipoSelect.addEventListener('change', sugerirPrecio);

    async function iniciar() {
        usarFechaActual();
        sugerirPrecio();
        await Promise.all([cargarAtletas(), cargarListado()]);
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

    async function cargarListado() {
        const respuesta = await solicitar('listar');
        atletas = respuesta.data;
        renderizarAtletas();
    }

    async function guardarMembresia(evento) {
        evento.preventDefault();

        const editando = Boolean(membresiaIdInput.value);
        const datos = {
            idAtleta: Number(atletaSelect.value),
            tipo: tipoSelect.value,
            precio: Number(precioInput.value),
            fechaInicio: fechaInicioInput.value,
            estado: estadoSelect.value,
        };

        if (editando) {
            datos.id = Number(membresiaIdInput.value);
        }

        try {
            submitButton.disabled = true;
            await enviar(editando ? 'editar' : 'crear', datos);
            mostrarEstado(
                editando ? 'Membresia actualizada correctamente.' : 'Membresia asignada correctamente.',
                'ok'
            );
            limpiarFormulario();
            await cargarListado();
        } catch (error) {
            mostrarEstado(error.message, 'error');
        } finally {
            submitButton.disabled = false;
        }
    }

    function renderizarAtletas() {
        atletasBody.innerHTML = '';
        emptyState.hidden = atletas.length > 0;

        atletas.forEach((atleta) => {
            const membresia = atleta.membresia;
            const estado = membresia ? membresia.estado : 'Pendiente';
            const fila = document.createElement('tr');
            fila.innerHTML = `
                <td>
                    <strong>${escaparHtml(atleta.nombre)}</strong><br>
                    <span>${escaparHtml(atleta.correo)}</span>
                </td>
                <td>${membresia ? escaparHtml(membresia.tipo) : 'Sin plan'}</td>
                <td>${membresia ? formatearMoneda(membresia.precio) : '-'}</td>
                <td><span class="tag ${escaparHtml(estado)}">${escaparHtml(estado)}</span></td>
                <td>${membresia ? escaparHtml(formatearFecha(membresia.fechaVencimiento)) : '-'}</td>
                <td>
                    <button
                        type="button"
                        class="secondary"
                        data-action="pagar"
                        data-atleta-id="${atleta.id}"
                        ${membresia ? '' : 'disabled'}
                    >
                        Registrar pago
                    </button>
                </td>
                <td>
                    <button
                        type="button"
                        class="secondary"
                        data-action="editar"
                        data-atleta-id="${atleta.id}"
                        ${membresia ? '' : 'disabled'}
                    >
                        Editar
                    </button>
                </td>
            `;
            atletasBody.appendChild(fila);
        });

        atletasBody.querySelectorAll('button[data-action="pagar"]').forEach((boton) => {
            boton.addEventListener('click', registrarPago);
        });

        atletasBody.querySelectorAll('button[data-action="editar"]').forEach((boton) => {
            boton.addEventListener('click', cargarMembresiaEnFormulario);
        });
    }

    function cargarMembresiaEnFormulario(evento) {
        const idAtleta = Number(evento.currentTarget.dataset.atletaId);
        const atleta = atletas.find((item) => Number(item.id) === idAtleta);

        if (!atleta || !atleta.membresia) {
            mostrarEstado('No se encontro una membresia para editar.', 'error');
            return;
        }

        const membresia = atleta.membresia;
        membresiaIdInput.value = membresia.id;
        atletaSelect.value = String(atleta.id);
        tipoSelect.value = membresia.tipo;
        precioInput.value = Number(membresia.precio).toFixed(2);
        fechaInicioInput.value = membresia.fechaInicio;
        estadoSelect.value = membresia.estado;

        formTitle.textContent = 'Editar membresia';
        submitButton.textContent = 'Actualizar membresia';
        cancelButton.hidden = false;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function limpiarFormulario() {
        form.reset();
        membresiaIdInput.value = '';
        usarFechaActual();
        sugerirPrecio();
        formTitle.textContent = 'Crear / Asignar membresia';
        submitButton.textContent = 'Crear / Asignar membresia';
        cancelButton.hidden = true;
    }

    async function registrarPago(evento) {
        const idAtleta = Number(evento.currentTarget.dataset.atletaId);

        try {
            evento.currentTarget.disabled = true;
            await enviar('registrarPago', {
                idAtleta,
                fechaPago: obtenerFechaActual(),
            });
            mostrarEstado('Pago registrado correctamente.', 'ok');
            await cargarListado();
        } catch (error) {
            mostrarEstado(error.message, 'error');
        } finally {
            evento.currentTarget.disabled = false;
        }
    }

    function usarFechaActual() {
        fechaInicioInput.value = obtenerFechaActual();
    }

    function sugerirPrecio() {
        const precios = {
            Básica: '35.00',
            Premium: '55.00',
            Elite: '75.00',
        };

        precioInput.value = precios[tipoSelect.value] || '35.00';
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

    function escaparHtml(valor) {
        return String(valor)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
})();
