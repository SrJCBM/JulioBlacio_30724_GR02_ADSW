(function () {
    const AUTH_URL = '../controllers/AuthController.php';
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const publicPages = ['login.html'];

    const permissions = {
        Administrador: [
            { label: 'Usuarios', href: 'gestion_usuarios.html' },
            { label: 'Membresias', href: 'gestion_membresias.html' },
            { label: 'Clases', href: 'gestion_clases.html' },
            { label: 'Reportes', href: 'reportes_admin.html' },
        ],
        Entrenador: [
            { label: 'Clases', href: 'gestion_clases.html' },
            { label: 'Progreso', href: 'seguimiento_progreso.html' },
            { label: 'Comunicacion', href: 'comunicacion_entrenador.html' },
        ],
        Atleta: [
            { label: 'Clases', href: 'reservas_atleta.html' },
            { label: 'Mi progreso', href: 'progreso_atleta.html' },
            { label: 'Mi membresia', href: 'membresia_atleta.html' },
            { label: 'Mensajes', href: 'bandeja_atleta.html' },
        ],
    };

    async function obtenerSesion() {
        const respuesta = await fetch(`${AUTH_URL}?action=me`, { credentials: 'same-origin' });
        const cuerpo = await respuesta.json().catch(() => ({ success: false }));
        return respuesta.ok && cuerpo.success ? cuerpo.data : null;
    }

    function createLink(link) {
        const anchor = document.createElement('a');
        anchor.className = 'app-nav-link';
        anchor.href = link.href;
        anchor.textContent = link.label;

        if (currentPage === link.href) {
            anchor.classList.add('is-active');
            anchor.setAttribute('aria-current', 'page');
        }

        return anchor;
    }

    function usuarioPuedeVer(usuario, page) {
        if (page === 'index.html') {
            return true;
        }

        return (permissions[usuario.rol] || []).some((link) => link.href === page);
    }

    function filtrarDashboard(usuario) {
        document.body.dataset.rol = usuario.rol;
        document.querySelectorAll('[data-roles]').forEach((elemento) => {
            const roles = elemento.dataset.roles.split(',').map((rol) => rol.trim());
            elemento.hidden = !roles.includes(usuario.rol);
        });

        const userName = document.getElementById('sessionUserName');
        const userRole = document.getElementById('sessionUserRole');
        if (userName) {
            userName.textContent = usuario.nombre;
        }
        if (userRole) {
            userRole.textContent = usuario.rol;
        }
    }

    async function cerrarSesion() {
        await fetch(`${AUTH_URL}?action=logout`, {
            method: 'POST',
            credentials: 'same-origin',
        });
        window.location.href = 'login.html';
    }

    function renderNav(usuario) {
        if (document.querySelector('.app-nav')) {
            return;
        }

        const nav = document.createElement('nav');
        nav.className = 'app-nav';
        nav.setAttribute('aria-label', 'Navegacion principal');

        const inner = document.createElement('div');
        inner.className = 'app-nav-inner';

        const brand = document.createElement('a');
        brand.className = 'app-nav-brand';
        brand.href = 'index.html';
        if (currentPage === 'index.html') {
            brand.setAttribute('aria-current', 'page');
        }

        const mark = document.createElement('span');
        mark.className = 'app-nav-mark';
        mark.textContent = 'IC';

        const brandText = document.createElement('span');
        brandText.textContent = 'IronClad Box';

        brand.append(mark, brandText);

        const groupsContainer = document.createElement('div');
        groupsContainer.className = 'app-nav-groups';

        const roleGroup = document.createElement('div');
        roleGroup.className = 'app-nav-group';

        const label = document.createElement('span');
        label.className = 'app-nav-role';
        label.textContent = usuario.rol;
        roleGroup.appendChild(label);

        (permissions[usuario.rol] || []).forEach((link) => roleGroup.appendChild(createLink(link)));

        const sessionGroup = document.createElement('div');
        sessionGroup.className = 'app-nav-group';

        const sessionLabel = document.createElement('span');
        sessionLabel.className = 'app-nav-role';
        sessionLabel.textContent = usuario.nombre;

        const logoutButton = document.createElement('button');
        logoutButton.type = 'button';
        logoutButton.className = 'app-nav-link app-nav-button';
        logoutButton.textContent = 'Cerrar sesion';
        logoutButton.addEventListener('click', cerrarSesion);

        sessionGroup.append(sessionLabel, logoutButton);
        groupsContainer.append(roleGroup, sessionGroup);

        inner.append(brand, groupsContainer);
        nav.appendChild(inner);
        document.body.insertBefore(nav, document.body.firstChild);
    }

    async function iniciar() {
        if (publicPages.includes(currentPage)) {
            return;
        }

        const usuario = await obtenerSesion();
        if (!usuario) {
            window.location.href = 'login.html';
            return;
        }

        if (!usuarioPuedeVer(usuario, currentPage)) {
            window.location.href = 'index.html';
            return;
        }

        renderNav(usuario);
        filtrarDashboard(usuario);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
