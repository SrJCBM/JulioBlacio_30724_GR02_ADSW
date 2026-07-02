<?php

ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Cors.php';
require_once __DIR__ . '/../services/UsuarioService.php';
require_once __DIR__ . '/../dao/MembresiaDAO.php';

header('Content-Type: application/json; charset=utf-8');
aplicarCors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$payload = obtenerPayloadAuth();
$accion = $_GET['action'] ?? $payload['action'] ?? 'me';

try {
    switch ($accion) {
        case 'login':
            asegurarPostAuth();
            $service = new UsuarioService();
            $correo = (string) ($payload['correo'] ?? $payload['email'] ?? '');
            verificarThrottleLogin($correo);

            try {
                $usuario = $service->autenticar(
                    $correo,
                    (string) ($payload['contrasena'] ?? $payload['contraseña'] ?? '')
                );
            } catch (DomainException $error) {
                registrarFalloLogin($correo);
                throw $error;
            }

            limpiarThrottleLogin($correo);

            $usuarioSesion = $usuario->toArray();
            $idAtleta = resolverIdAtletaSesion($usuarioSesion);
            authGuardarUsuario($usuarioSesion, $idAtleta);

            responderAuth(['success' => true, 'data' => $usuarioSesion]);
            break;

        case 'me':
            $usuarioActual = authUsuarioActual();
            responderAuth([
                'success' => (bool) $usuarioActual,
                'data' => $usuarioActual,
            ], $usuarioActual ? 200 : 401);
            break;

        case 'logout':
            authCerrarSesion();
            responderAuth(['success' => true, 'message' => 'Sesion cerrada correctamente.']);
            break;

        default:
            responderAuth(['success' => false, 'message' => 'Accion no soportada.'], 404);
    }
} catch (AuthException $error) {
    responderAuth(['success' => false, 'message' => $error->getMessage()], $error->getEstadoHttp());
} catch (InvalidArgumentException | DomainException $error) {
    responderAuth(['success' => false, 'message' => $error->getMessage()], 422);
} catch (Throwable $error) {
    error_log((string) $error);
    responderAuth(['success' => false, 'message' => 'Error interno del servidor.'], 500);
}

function obtenerPayloadAuth(): array
{
    $contenido = file_get_contents('php://input');
    $json = json_decode($contenido ?: '{}', true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($json) && $json !== []) {
        return $json;
    }

    return $_POST;
}

function asegurarPostAuth(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new InvalidArgumentException('La operacion requiere metodo POST.');
    }
}

function resolverIdAtletaSesion(array $usuario): ?int
{
    if (($usuario['rol'] ?? '') !== 'Atleta') {
        return null;
    }

    $dao = new MembresiaDAO();
    $atleta = $dao->buscarAtletaPorCorreo((string) ($usuario['correo'] ?? ''));

    return $atleta ? (int) $atleta['id'] : null;
}

function responderAuth(array $respuesta, int $estadoHttp = 200): void
{
    http_response_code($estadoHttp);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

function verificarThrottleLogin(string $correo): void
{
    authIniciarSesion();
    $clave = claveThrottleLogin($correo);
    $intento = $_SESSION['login_intentos'][$clave] ?? null;

    if (!is_array($intento) || time() - (int) ($intento['inicio'] ?? 0) > 900) {
        unset($_SESSION['login_intentos'][$clave]);
        return;
    }

    if ((int) ($intento['fallos'] ?? 0) >= 5) {
        throw new AuthException('Demasiados intentos fallidos. Intente nuevamente en unos minutos.', 429);
    }
}

function registrarFalloLogin(string $correo): void
{
    authIniciarSesion();
    $clave = claveThrottleLogin($correo);
    $intento = $_SESSION['login_intentos'][$clave] ?? ['fallos' => 0, 'inicio' => time()];

    if (time() - (int) ($intento['inicio'] ?? 0) > 900) {
        $intento = ['fallos' => 0, 'inicio' => time()];
    }

    $intento['fallos'] = (int) ($intento['fallos'] ?? 0) + 1;
    $_SESSION['login_intentos'][$clave] = $intento;

    if ($intento['fallos'] > 1) {
        sleep(min($intento['fallos'] - 1, 5));
    }
}

function limpiarThrottleLogin(string $correo): void
{
    authIniciarSesion();
    unset($_SESSION['login_intentos'][claveThrottleLogin($correo)]);
}

function claveThrottleLogin(string $correo): string
{
    $correo = strtolower(trim($correo));
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'sin-ip';

    return hash('sha256', $correo . '|' . $ip);
}
