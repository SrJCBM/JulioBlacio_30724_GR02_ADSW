(function () {
    'use strict';

    const AUTH_URL = '../controllers/AuthController.php';
    const form = document.getElementById('loginForm');
    const correoInput = document.getElementById('correo');
    const contrasenaInput = document.getElementById('contrasena');
    const statusMessage = document.getElementById('statusMessage');
    const submitButton = document.getElementById('submitButton');

    document.addEventListener('DOMContentLoaded', verificarSesionActiva);
    form.addEventListener('submit', iniciarSesion);

    async function verificarSesionActiva() {
        const respuesta = await fetch(`${AUTH_URL}?action=me`, { credentials: 'same-origin' });
        if (respuesta.ok) {
            const cuerpo = await respuesta.json();
            if (cuerpo.success) {
                window.location.href = 'index.html';
            }
        }
    }

    async function iniciarSesion(evento) {
        evento.preventDefault();

        try {
            submitButton.disabled = true;
            const respuesta = await fetch(`${AUTH_URL}?action=login`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    correo: correoInput.value.trim(),
                    contrasena: contrasenaInput.value,
                }),
            });

            const cuerpo = await respuesta.json();
            if (!respuesta.ok || cuerpo.success === false) {
                throw new Error(cuerpo.message || 'No se pudo iniciar sesion.');
            }

            window.location.href = 'index.html';
        } catch (error) {
            mostrarEstado(error.message, 'error');
        } finally {
            submitButton.disabled = false;
        }
    }

    function mostrarEstado(mensaje, tipo) {
        statusMessage.textContent = mensaje;
        statusMessage.className = `status show ${tipo}`;
    }
})();
