<?php

require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../services/UsuarioService.php';
require_once __DIR__ . '/../dao/MembresiaDAO.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$service = new UsuarioService();
$payload = obtenerPayloadAuth();
$accion = $_GET['action'] ?? $payload['action'] ?? 'me';

try {
    switch ($accion) {
        case 'login':
            asegurarPostAuth();
            $usuario = $service->autenticar(
                (string) ($payload['correo'] ?? $payload['email'] ?? ''),
                (string) ($payload['contrasena'] ?? $payload['contraseña'] ?? '')
            );

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
} catch (InvalidArgumentException | DomainException $error) {
    responderAuth(['success' => false, 'message' => $error->getMessage()], 422);
} catch (Throwable $error) {
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
