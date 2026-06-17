<?php

class AuthException extends RuntimeException
{
    private int $estadoHttp;

    public function __construct(string $mensaje, int $estadoHttp = 401)
    {
        parent::__construct($mensaje);
        $this->estadoHttp = $estadoHttp;
    }

    public function getEstadoHttp(): int
    {
        return $this->estadoHttp;
    }
}

function authIniciarSesion(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function authUsuarioActual(): ?array
{
    authIniciarSesion();
    return $_SESSION['usuario'] ?? null;
}

function authGuardarUsuario(array $usuario, ?int $idAtleta = null): void
{
    authIniciarSesion();
    $_SESSION['usuario'] = $usuario;

    if ($idAtleta !== null) {
        $_SESSION['id_atleta'] = $idAtleta;
    } else {
        unset($_SESSION['id_atleta']);
    }
}

function authCerrarSesion(): void
{
    authIniciarSesion();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function authRequerirSesion(): array
{
    $usuario = authUsuarioActual();
    if (!$usuario) {
        throw new AuthException('Debe iniciar sesion.', 401);
    }

    return $usuario;
}

function authRequerirRol(array $rolesPermitidos): array
{
    $usuario = authRequerirSesion();
    if (!in_array($usuario['rol'] ?? '', $rolesPermitidos, true)) {
        throw new AuthException('No tiene permisos para esta operacion.', 403);
    }

    return $usuario;
}
