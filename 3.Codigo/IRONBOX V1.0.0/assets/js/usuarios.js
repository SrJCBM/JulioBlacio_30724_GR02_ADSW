(function () {
    'use strict';

    const API_URL = '../controllers/UsuarioController.php';

    const form = document.getElementById('usuarioForm');
    const formTitle = document.getElementById('form-title');
    const usuarioIdInput = document.getElementById('usuarioId');
    const nombreInput = document.getElementById('nombre');
    const emailInput = document.getElementById('email');
    const contrasenaInput = document.getElementById('contrasena');
    const rolSelect = document.getElementById('rol');
    const estadoSelect = document.getElementById('estado');
    const submitButton = document.getElementById('submitButton');
    const cancelButton = document.getElementById('cancelButton');
    const statusMessage = document.getElementById('statusMessage');
    const usuariosBody = document.getElementById('usuariosBody');
    const emptyState = document.getElementById('emptyState');

    let usuarios = [];

    document.addEventListener('DOMContentLoaded', cargarUsuarios);
    form.addEventListener('submit', guardarUsuario);
    cancelButton.addEventListener('click', limpiarFormulario);

    async function cargarUsuarios() {
        const respuesta = await solicitar('listar');
        usuarios = respuesta.data;
        renderizarUsuarios();
    }

    async function guardarUsuario(evento) {
        evento.preventDefault();

        const editando = Boolean(usuarioIdInput.value);
        const datos = {
            id: Number(usuarioIdInput.value || 0),
            nombre: nombreInput.value.trim(),
            email: emailInput.value.trim(),
            contrasena: contrasenaInput.value,
            rol: rolSelect.value,
            estado: estadoSelect.value,
        };

        if (editando && datos.contrasena === '') {
            delete datos.contrasena;
        }

        try {
            submitButton.disabled = true;
            await enviar(editando ? 'editar' : 'crear', {
                ...datos,
                _method: editando ? 'PUT' : 'POST',
            });
            mostrarEstado(editando ? 'Usuario actualizado correctamente.' : 'Usuario creado correctamente.', 'ok');
            limpiarFormulario();
            await cargarUsuarios();
        } catch (error) {
            mostrarEstado(error.message, 'error');
        } finally {
            submitButton.disabled = false;
        }
    }

    function renderizarUsuarios() {
        usuariosBody.innerHTML = '';
        emptyState.hidden = usuarios.length > 0;

        usuarios.forEach((usuario) => {
            const fila = document.createElement('tr');
            const activo = usuario.estado === 'Activo';
            fila.innerHTML = `
                <td>
                    <strong>${escaparHtml(usuario.nombre)}</strong><br>
                    <span>${escaparHtml(usuario.email)}</span>
                </td>
                <td>${escaparHtml(usuario.rol)}</td>
                <td><span class="tag ${escaparHtml(usuario.estado)}">${escaparHtml(usuario.estado)}</span></td>
                <td>${escaparHtml(formatearFecha(usuario.fechaRegistro))}</td>
                <td>
                    <div class="row-actions">
                        <button type="button" class="secondary" data-action="editar" data-id="${usuario.id}">Editar</button>
                        <button
                            type="button"
                            class="danger"
                            data-action="desactivar"
                            data-id="${usuario.id}"
                            ${activo ? '' : 'disabled'}
                        >
                            Desactivar
                        </button>
                    </div>
                </td>
            `;
            usuariosBody.appendChild(fila);
        });

        usuariosBody.querySelectorAll('button[data-action]').forEach((boton) => {
            boton.addEventListener('click', manejarAccionFila);
        });
    }

    async function manejarAccionFila(evento) {
        const id = Number(evento.currentTarget.dataset.id);
        const accion = evento.currentTarget.dataset.action;

        if (accion === 'editar') {
            cargarUsuarioEnFormulario(id);
            return;
        }

        if (accion === 'desactivar' && window.confirm('Desea desactivar este usuario?')) {
            try {
                evento.currentTarget.disabled = true;
                await enviar('desactivar', { id, _method: 'DELETE' });
                mostrarEstado('Usuario desactivado correctamente.', 'ok');
                await cargarUsuarios();
            } catch (error) {
                mostrarEstado(error.message, 'error');
            }
        }
    }

    function cargarUsuarioEnFormulario(id) {
        const usuario = usuarios.find((item) => Number(item.id) === id);
        if (!usuario) {
            mostrarEstado('No se encontro el usuario seleccionado.', 'error');
            return;
        }

        usuarioIdInput.value = usuario.id;
        nombreInput.value = usuario.nombre;
        emailInput.value = usuario.email;
        contrasenaInput.value = '';
        contrasenaInput.required = false;
        contrasenaInput.placeholder = 'Dejar vacio para conservarla';
        rolSelect.value = usuario.rol;
        estadoSelect.value = usuario.estado;

        formTitle.textContent = 'Editar usuario';
        submitButton.textContent = 'Actualizar usuario';
        cancelButton.hidden = false;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function limpiarFormulario() {
        form.reset();
        usuarioIdInput.value = '';
        contrasenaInput.required = true;
        contrasenaInput.placeholder = '';
        estadoSelect.value = 'Activo';
        rolSelect.value = 'Administrador';
        formTitle.textContent = 'Crear usuario';
        submitButton.textContent = 'Guardar usuario';
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
