(function () {
    'use strict';

    const API_URL = '../controllers/ProgresoController.php';

    const form = document.getElementById('progresoAtletaForm');
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
    const drawer = document.getElementById('formDrawer');
    const scrim = document.getElementById('drawerScrim');
    const nuevaButton = document.getElementById('nuevaRegistroButton');
    const drawerClose = document.getElementById('drawerClose');

    let graficoEvolucion = null;

    document.addEventListener('DOMContentLoaded', iniciar);
    form.addEventListener('submit', guardarEntrenamiento);
    clearButton.addEventListener('click', () => limpiarFormulario());
    nuevaButton.addEventListener('click', nuevoRegistro);
    drawerClose.addEventListener('click', cerrarFormulario);
    scrim.addEventListener('click', cerrarFormulario);
    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape' && drawer.classList.contains('open')) {
            cerrarFormulario();
        }
    });

    function abrirDrawer() {
        drawer.classList.add('open');
        scrim.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
        document.body.classList.add('no-scroll');
        window.setTimeout(() => fechaInput.focus(), 60);
    }

    function cerrarDrawer() {
        drawer.classList.remove('open');
        scrim.classList.remove('open');
        drawer.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('no-scroll');
    }

    function nuevoRegistro() {
        limpiarFormulario(false);
        abrirDrawer();
    }

    function cerrarFormulario() {
        limpiarFormulario(false);
        cerrarDrawer();
    }

    async function iniciar() {
        if (fechaInput) {
            fechaInput.max = new Date().toISOString().slice(0, 10);
        }
        fechaInput.value = obtenerFechaActual();
        // El progreso se resuelve por la sesion del atleta en el servidor:
        // no se envia ni selecciona idAtleta desde el cliente.
        await cargarHistorial();
    }

    async function guardarEntrenamiento(evento) {
        evento.preventDefault();

        const datos = {
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
            cerrarDrawer();
            await cargarHistorial();
        } catch (error) {
            mostrarEstado(error.message, 'error');
        } finally {
            submitButton.disabled = false;
        }
    }

    async function cargarHistorial() {
        historialBody.innerHTML = '';

        try {
            const [historial, grafico] = await Promise.all([
                solicitar('historialAtleta'),
                solicitar('obtenerDatosGrafico'),
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
                borderColor: '#e8542a',
                backgroundColor: 'rgba(232, 84, 42, 0.16)',
                tension: 0.28,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: '#e8542a',
            },
        ];

        if (datos.pesos.some((peso) => peso !== null)) {
            datasets.push({
                label: 'Peso',
                data: datos.pesos,
                borderColor: '#6ea8fe',
                backgroundColor: 'rgba(110, 168, 254, 0.10)',
                tension: 0.28,
                pointRadius: 4,
                pointBackgroundColor: '#6ea8fe',
            });
        }

        const tinta = '#eceae3';
        const tenue = '#8a929e';
        const rejilla = 'rgba(236, 234, 227, 0.08)';

        graficoEvolucion = new Chart(graficoCanvas, {
            type: 'line',
            data: {
                labels: datos.fechas.map(formatearFecha),
                datasets,
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                color: tinta,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: tinta },
                    },
                    tooltip: { titleColor: tinta, bodyColor: tinta },
                },
                scales: {
                    x: {
                        ticks: { color: tenue },
                        grid: { color: rejilla },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: tenue },
                        grid: { color: rejilla },
                    },
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

    function limpiarFormulario() {
        form.reset();
        fechaInput.value = obtenerFechaActual();
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
