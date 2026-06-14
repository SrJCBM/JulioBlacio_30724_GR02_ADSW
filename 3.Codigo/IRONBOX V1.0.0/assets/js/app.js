(function () {
    'use strict';

    const API_URL = '../controllers/ClaseController.php';

    const form = document.getElementById('claseForm');
    const formTitle = document.getElementById('form-title');
    const submitButton = document.getElementById('submitButton');
    const cancelButton = document.getElementById('cancelButton');
    const statusMessage = document.getElementById('statusMessage');
    const clasesBody = document.getElementById('clasesBody');
    const emptyState = document.getElementById('emptyState');
    const entrenadorSelect = document.getElementById('entrenadorId');
    const claseIdInput = document.getElementById('claseId');
    const diaInput = document.getElementById('dia');
    const horaInput = document.getElementById('hora');
    const duracionInput = document.getElementById('duracion');
    const cupoMaximoInput = document.getElementById('cupoMaximo');

    let clases = [];

    document.addEventListener('DOMContentLoaded', iniciar);
    form.addEventListener('submit', guardarClase);
    cancelButton.addEventListener('click', limpiarFormulario);

    async function iniciar() {
        await Promise.all([cargarEntrenadores(), cargarClases()]);
    }

    async function cargarEntrenadores() {
        const respuesta = await solicitar('entrenadores');
        entrenadorSelect.innerHTML = '<option value="">Seleccione un entrenador</option>';

        respuesta.data.forEach((entrenador) => {
            const option = document.createElement('option');
            option.value = entrenador.id;
            option.textContent = entrenador.nombre;
            entrenadorSelect.appendChild(option);
        });
    }

    async function cargarClases() {
        const respuesta = await solicitar('listar');
        clases = respuesta.data;
        renderizarClases();
    }

    async function guardarClase(evento) {
        evento.preventDefault();

        const datos = Object.fromEntries(new FormData(form).entries());
        const editando = Boolean(datos.id);
        datos.duracion = Number(datos.duracion);
        datos.cupoMaximo = Number(datos.cupoMaximo);
        datos.entrenadorId = Number(datos.entrenadorId);

        try {
            submitButton.disabled = true;
            await enviar(editando ? 'editar' : 'crear', datos);
            mostrarEstado(editando ? 'Clase actualizada correctamente.' : 'Clase creada correctamente.', 'ok');
            limpiarFormulario();
            await cargarClases();
        } catch (error) {
            mostrarEstado(error.message, 'error');
        } finally {
            submitButton.disabled = false;
        }
    }

    function renderizarClases() {
        clasesBody.innerHTML = '';
        emptyState.hidden = clases.length > 0;

        clases.forEach((clase) => {
            const fila = document.createElement('tr');
            fila.innerHTML = `
                <td>${escaparHtml(formatearFecha(clase.dia))}</td>
                <td><span class="tag">${escaparHtml(clase.hora)}</span></td>
                <td>${Number(clase.duracion)} min</td>
                <td>${Number(clase.cupoMaximo) - Number(clase.cuposDisponibles)} ocupados &middot; ${Number(clase.cuposDisponibles)} / ${Number(clase.cupoMaximo)}</td>
                <td>${escaparHtml(clase.entrenador.nombre)}</td>
                <td>
                    <div class="row-actions">
                        <button type="button" class="secondary" data-action="editar" data-id="${clase.id}">Editar</button>
                        <button type="button" class="danger" data-action="eliminar" data-id="${clase.id}">Eliminar</button>
                    </div>
                </td>
            `;
            clasesBody.appendChild(fila);
        });

        clasesBody.querySelectorAll('button[data-action]').forEach((boton) => {
            boton.addEventListener('click', manejarAccionFila);
        });
    }

    async function manejarAccionFila(evento) {
        const id = Number(evento.currentTarget.dataset.id);
        const accion = evento.currentTarget.dataset.action;

        if (accion === 'editar') {
            cargarClaseEnFormulario(id);
            return;
        }

        if (accion === 'eliminar' && window.confirm('Desea eliminar esta clase?')) {
            try {
                await enviar('eliminar', { id });
                mostrarEstado('Clase eliminada correctamente.', 'ok');
                await cargarClases();
            } catch (error) {
                mostrarEstado(error.message, 'error');
            }
        }
    }

    function cargarClaseEnFormulario(id) {
        const clase = clases.find((item) => Number(item.id) === id);
        if (!clase) {
            mostrarEstado('No se encontro la clase seleccionada.', 'error');
            return;
        }

        claseIdInput.value = clase.id;
        diaInput.value = clase.dia;
        horaInput.value = clase.hora;
        duracionInput.value = clase.duracion;
        cupoMaximoInput.value = clase.cupoMaximo;
        entrenadorSelect.value = clase.entrenadorId;

        formTitle.textContent = 'Editar clase';
        submitButton.textContent = 'Actualizar clase';
        cancelButton.hidden = false;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function limpiarFormulario() {
        form.reset();
        claseIdInput.value = '';
        duracionInput.value = 60;
        cupoMaximoInput.value = 12;
        formTitle.textContent = 'Nueva clase';
        submitButton.textContent = 'Guardar clase';
        cancelButton.hidden = true;
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

    function formatearFecha(valor) {
        const partes = valor.split('-');
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
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
