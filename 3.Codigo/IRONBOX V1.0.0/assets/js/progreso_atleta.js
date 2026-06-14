(function () {
    'use strict';

    const API_URL = '../controllers/ProgresoController.php';

    const form = document.getElementById('progresoAtletaForm');
    const atletaSelect = document.getElementById('idAtleta');
    const fechaInput = document.getElementById('fecha');
    const tiempoInput = document.getElementById('tiempo');
    const repeticionesInput = document.getElementById('repeticiones');
    const pesoInput = document.getElementById('peso');
    const notasInput = document.getElementById('notas');
    const submitButton = document.getElementById('submitButton');
    const clearButton = document.getElementById('clearButton');
    const statusMessage = document.getElementById('statusMessage');
    const historialBody = document.getElementById('historialBody');
    const emptyState = document.getElementById('emptyState');
    const graficoCanvas = document.getElementById('graficoEvolucion');

    let graficoEvolucion = null;

    document.addEventListener('DOMContentLoaded', iniciar);
    form.addEventListener('submit', guardarEntrenamiento);
    atletaSelect.addEventListener('change', cargarHistorial);
    clearButton.addEventListener('click', limpiarFormulario);

    async function iniciar() {
        fechaInput.value = obtenerFechaActual();
        await cargarAtletas();
        preseleccionarAtletaDesdeUrl();
        await cargarHistorial();
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

    async function guardarEntrenamiento(evento) {
        evento.preventDefault();

        const idAtleta = obtenerAtletaSeleccionado();
        const datos = {
            idAtleta,
            fecha: fechaInput.value,
            tiempo: valorOpcional(tiempoInput.value),
            repeticiones: valorOpcional(repeticionesInput.value),
            peso: valorOpcional(pesoInput.value),
            notas: notasInput.value.trim(),
        };

        try {
            submitButton.disabled = true;
            await enviar('guardarAtleta', datos);
            mostrarEstado('Entrenamiento registrado correctamente.', 'ok');
            limpiarFormulario(false);
            await cargarHistorial();
        } catch (error) {
            mostrarEstado(error.message, 'error');
        } finally {
            submitButton.disabled = false;
        }
    }

    async function cargarHistorial() {
        const idAtleta = obtenerAtletaSeleccionado();
        historialBody.innerHTML = '';

        if (!idAtleta) {
            emptyState.hidden = false;
            emptyState.textContent = 'Seleccione un atleta para ver su historial.';
            destruirGrafico();
            return;
        }

        try {
            const [historial, grafico] = await Promise.all([
                solicitar('historialAtleta', { idAtleta }),
                solicitar('obtenerDatosGrafico', { idAtleta }),
            ]);
            renderizarHistorial(historial.data);
            renderizarGrafico(grafico.data);
        } catch (error) {
            mostrarEstado(error.message, 'error');
        }
    }

    function renderizarHistorial(registros) {
        historialBody.innerHTML = '';
        emptyState.hidden = registros.length > 0;
        emptyState.textContent = 'Todavia no hay entrenamientos registrados.';

        registros.forEach((registro) => {
            const fila = document.createElement('tr');
            fila.innerHTML = `
                <td>${escaparHtml(formatearFecha(registro.fecha))}</td>
                <td>${formatearValor(registro.tiempo, ' min')}</td>
                <td>${formatearValor(registro.repeticiones, '')}</td>
                <td>${formatearValor(registro.peso, ' kg')}</td>
                <td><span class="score">${Number(registro.puntuacion).toFixed(2)}</span></td>
                <td>${escaparHtml(registro.notas || '-')}</td>
            `;
            historialBody.appendChild(fila);
        });
    }

    function renderizarGrafico(datos) {
        destruirGrafico();

        if (typeof Chart === 'undefined' || !graficoCanvas || datos.fechas.length === 0) {
            return;
        }

        const datasets = [
            {
                label: 'Puntuacion',
                data: datos.puntuaciones,
                borderColor: '#b8232f',
                backgroundColor: 'rgba(184, 35, 47, 0.12)',
                tension: 0.28,
                fill: true,
                pointRadius: 4,
            },
        ];

        if (datos.pesos.some((peso) => peso !== null)) {
            datasets.push({
                label: 'Peso',
                data: datos.pesos,
                borderColor: '#1f6feb',
                backgroundColor: 'rgba(31, 111, 235, 0.08)',
                tension: 0.28,
                pointRadius: 4,
            });
        }

        graficoEvolucion = new Chart(graficoCanvas, {
            type: 'line',
            data: {
                labels: datos.fechas.map(formatearFecha),
                datasets,
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                },
                scales: {
                    y: { beginAtZero: true },
                },
            },
        });
    }

    function destruirGrafico() {
        if (graficoEvolucion) {
            graficoEvolucion.destroy();
            graficoEvolucion = null;
        }
    }

    function limpiarFormulario(mantenerAtleta) {
        const atletaActual = atletaSelect.value;
        form.reset();
        fechaInput.value = obtenerFechaActual();

        if (mantenerAtleta === false) {
            atletaSelect.value = atletaActual;
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

    function mostrarEstado(mensaje, tipo) {
        statusMessage.textContent = mensaje;
        statusMessage.className = `status show ${tipo}`;
    }

    function valorOpcional(valor) {
        return valor === '' ? null : Number(valor);
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

    function formatearValor(valor, sufijo) {
        if (valor === null || valor === undefined || valor === '') {
            return '-';
        }

        return `${Number(valor).toLocaleString('es-EC')}${sufijo}`;
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
