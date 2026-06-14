<?php

require_once __DIR__ . '/../services/ComunicacionService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$service = new ComunicacionService();
$payload = obtenerPayloadComunicacion();
$accion = $_GET['action'] ?? $payload['action'] ?? 'recibidos';

try {
    switch ($accion) {
        case 'atletas':
            responderComunicacion(['success' => true, 'data' => $service->listarAtletas()]);
            break;

        case 'entrenadores':
            responderComunicacion(['success' => true, 'data' => $service->listarEntrenadores()]);
            break;

        case 'enviar':
            asegurarPostComunicacion();
            $mensaje = $service->enviar($payload);
            responderComunicacion([
                'success' => true,
                'message' => 'Mensaje enviado correctamente.',
                'data' => $mensaje->toArray(),
            ], 201);
            break;

        case 'recibidos':
            responderComunicacion([
                'success' => true,
                'data' => $service->listarRecibidos(obtenerIdAtletaComunicacion($payload)),
            ]);
            break;

        case 'historial':
            responderComunicacion([
                'success' => true,
                'data' => $service->listarHistorial(obtenerIdEntrenadorComunicacion($payload)),
            ]);
            break;

        default:
            responderComunicacion(['success' => false, 'message' => 'Accion no soportada.'], 404);
    }
} catch (InvalidArgumentException | DomainException $error) {
    responderComunicacion(['success' => false, 'message' => $error->getMessage()], 422);
} catch (Throwable $error) {
    responderComunicacion(['success' => false, 'message' => 'Error interno del servidor.'], 500);
}

function obtenerPayloadComunicacion(): array
{
    $contenido = file_get_contents('php://input');
    $json = json_decode($contenido ?: '{}', true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($json) && $json !== []) {
        return $json;
    }

    return $_POST;
}

function asegurarPostComunicacion(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new InvalidArgumentException('La operacion requiere metodo POST.');
    }
}

function obtenerIdAtletaComunicacion(array $payload): int
{
    return (int) (
        $_GET['idAtleta']
        ?? $_GET['id_atleta']
        ?? $payload['idAtleta']
        ?? $payload['id_atleta']
        ?? $_SESSION['id_atleta']
        ?? 0
    );
}

function obtenerIdEntrenadorComunicacion(array $payload): ?int
{
    $id = (int) (
        $_GET['idEntrenador']
        ?? $_GET['id_entrenador']
        ?? $payload['idEntrenador']
        ?? $payload['id_entrenador']
        ?? $_SESSION['id_entrenador']
        ?? 0
    );

    return $id > 0 ? $id : null;
}

function responderComunicacion(array $respuesta, int $estadoHttp = 200): void
{
    http_response_code($estadoHttp);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}
