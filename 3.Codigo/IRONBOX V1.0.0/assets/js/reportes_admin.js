(function () {
    'use strict';

    const API_URL = '../controllers/ReporteController.php';

    const form = document.getElementById('reporteForm');
    const tipoReporte = document.getElementById('tipoReporte');
    const fechaInicio = document.getElementById('fechaInicio');
    const fechaFin = document.getElementById('fechaFin');
    const csvButton = document.getElementById('csvButton');
    const pdfButton = document.getElementById('pdfButton');
    const statusMessage = document.getElementById('statusMessage');
    const summary = document.getElementById('summary');
    const tableHead = document.getElementById('tableHead');
    const tableBody = document.getElementById('tableBody');
    const emptyState = document.getElementById('emptyState');

    document.addEventListener('DOMContentLoaded', iniciar);
    form.addEventListener('submit', generarReporte);
    csvButton.addEventListener('click', exportarCsv);
    pdfButton.addEventListener('click', exportarPdf);
    if (fechaInicio) {
        fechaInicio.addEventListener('change', actualizarLimitesFechas);
    }
    if (fechaFin) {
        fechaFin.addEventListener('change', actualizarLimitesFechas);
    }

    function iniciar() {
        const hoy = new Date();
        if (fechaFin) {
            fechaFin.value = formatearFechaInput(hoy);
        }
        if (fechaInicio) {
            fechaInicio.value = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-01`;
        }
        actualizarLimitesFechas();
    }

    function actualizarLimitesFechas() {
        const hoy = new Date().toISOString().slice(0, 10);

        if (fechaInicio) {
            fechaInicio.max = fechaFin && fechaFin.value && fechaFin.value < hoy ? fechaFin.value : hoy;
        }

        if (fechaFin) {
            fechaFin.min = fechaInicio ? fechaInicio.value : '';
            fechaFin.max = hoy;
        }
    }

    async function generarReporte(evento) {
        evento.preventDefault();

        try {
            const respuesta = await solicitar('generar', obtenerFiltros());
            renderizarReporte(respuesta.data);
            mostrarEstado('Reporte generado correctamente.', 'ok');
        } catch (error) {
            mostrarEstado(error.message, 'error');
        }
    }

    function renderizarReporte(reporte) {
        const datos = reporte.datos;
        tableHead.innerHTML = '';
        tableBody.innerHTML = '';
        summary.innerHTML = '';

        Object.entries(datos.resumen).forEach(([clave, valor]) => {
            const metric = document.createElement('div');
            metric.className = 'metric';
            metric.innerHTML = `<span>${escaparHtml(separarCamelCase(clave))}</span><strong>${escaparHtml(formatearValor(valor, clave))}</strong>`;
            summary.appendChild(metric);
        });

        datos.columnas.forEach((columna) => {
            const th = document.createElement('th');
            th.textContent = separarCamelCase(columna);
            tableHead.appendChild(th);
        });

        datos.filas.forEach((fila) => {
            const tr = document.createElement('tr');
            tr.innerHTML = datos.columnas
                .map((columna) => `<td>${escaparHtml(formatearValor(fila[columna], columna))}</td>`)
                .join('');
            tableBody.appendChild(tr);
        });

        emptyState.hidden = datos.filas.length > 0;
        emptyState.textContent = 'No hay datos para los filtros seleccionados.';
    }

    function exportarCsv() {
        const query = new URLSearchParams({ action: 'exportarCsv', ...obtenerFiltros() });
        window.location.href = `${API_URL}?${query.toString()}`;
    }

    async function exportarPdf() {
        try {
            await solicitar('exportarPdf', obtenerFiltros());
            window.print();
        } catch (error) {
            mostrarEstado(error.message, 'error');
        }
    }

    async function solicitar(accion, filtros) {
        const query = new URLSearchParams({ action: accion, ...filtros });
        const respuesta = await fetch(`${API_URL}?${query.toString()}`);
        const cuerpo = await respuesta.json();

        if (!respuesta.ok || cuerpo.success === false) {
            throw new Error(cuerpo.message || 'No se pudo completar la operacion.');
        }

        return cuerpo;
    }

    function obtenerFiltros() {
        return {
            tipo: tipoReporte.value,
            fechaInicio: fechaInicio.value,
            fechaFin: fechaFin.value,
        };
    }

    function mostrarEstado(mensaje, tipo) {
        statusMessage.textContent = mensaje;
        statusMessage.className = `status show ${tipo}`;
    }

    function formatearFechaInput(fecha) {
        const year = fecha.getFullYear();
        const month = String(fecha.getMonth() + 1).padStart(2, '0');
        const day = String(fecha.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function separarCamelCase(valor) {
        return String(valor)
            .replace(/([A-Z])/g, ' $1')
            .replace(/^./, (letra) => letra.toUpperCase());
    }

    function formatearValor(valor, clave) {
        if (valor === null || valor === undefined || valor === '') {
            return '-';
        }

        if (typeof valor === 'number') {
            const esMoneda = String(clave).toLowerCase().includes('precio')
                || String(clave).toLowerCase().includes('ingresos');
            return esMoneda ? `$${valor.toFixed(2)}` : String(valor);
        }

        return String(valor);
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
