<?php

require_once __DIR__ . '/../services/UsuarioService.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$service = new UsuarioService();
$payload = obtenerPayloadUsuario();
$metodo = strtoupper($payload['_method'] ?? $_SERVER['REQUEST_METHOD']);
$accion = $_GET['action'] ?? $payload['action'] ?? accionPorMetodoUsuario($metodo);

try {
    switch ($accion) {
        case 'listar':
            responderUsuario(['success' => true, 'data' => $service->listar()]);
            break;

        case 'crear':
            asegurarMetodoUsuario($metodo, ['POST']);
            $usuario = $service->crear($payload);
            responderUsuario([
                'success' => true,
                'message' => 'Usuario creado correctamente.',
                'data' => $usuario->toArray(),
            ], 201);
            break;

        case 'editar':
            asegurarMetodoUsuario($metodo, ['POST', 'PUT']);
            $usuario = $service->editar(obtenerIdUsuario($payload), $payload);
            responderUsuario([
                'success' => true,
                'message' => 'Usuario actualizado correctamente.',
                'data' => $usuario->toArray(),
            ]);
            break;

        case 'desactivar':
            asegurarMetodoUsuario($metodo, ['POST', 'DELETE']);
            $service->desactivar(obtenerIdUsuario($payload));
            responderUsuario(['success' => true, 'message' => 'Usuario desactivado correctamente.']);
            break;

        default:
            responderUsuario(['success' => false, 'message' => 'Accion no soportada.'], 404);
    }
} catch (InvalidArgumentException | DomainException $error) {
    responderUsuario(['success' => false, 'message' => $error->getMessage()], 422);
} catch (Throwable $error) {
    responderUsuario(['success' => false, 'message' => 'Error interno del servidor.'], 500);
}

function obtenerPayloadUsuario(): array
{
    $contenido = file_get_contents('php://input');
    $json = json_decode($contenido ?: '{}', true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($json) && $json !== []) {
        return $json;
    }

    return $_POST;
}

function accionPorMetodoUsuario(string $metodo): string
{
    return match ($metodo) {
        'PUT' => 'editar',
        'DELETE' => 'desactivar',
        default => 'listar',
    };
}

function asegurarMetodoUsuario(string $metodo, array $permitidos): void
{
    if (!in_array($metodo, $permitidos, true)) {
        throw new InvalidArgumentException('Metodo HTTP no permitido para esta operacion.');
    }
}

function obtenerIdUsuario(array $payload): int
{
    $id = (int) ($_GET['id'] ?? $payload['id'] ?? 0);
    if ($id <= 0) {
        throw new InvalidArgumentException('Debe indicar un id valido.');
    }

    return $id;
}

function responderUsuario(array $respuesta, int $estadoHttp = 200): void
{
    http_response_code($estadoHttp);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}
