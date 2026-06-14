<?php

require_once __DIR__ . '/../services/ClaseService.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$service = new ClaseService();
$payload = obtenerPayload();
$accion = $_GET['action'] ?? $payload['action'] ?? 'listar';

try {
    switch ($accion) {
        case 'listar':
            responder(['success' => true, 'data' => $service->listar()]);
            break;

        case 'entrenadores':
            responder(['success' => true, 'data' => $service->listarEntrenadores()]);
            break;

        case 'crear':
            asegurarMetodoPost();
            $clase = $service->crear($payload);
            responder(['success' => true, 'message' => 'Clase creada correctamente.', 'data' => $clase->toArray()], 201);
            break;

        case 'editar':
            asegurarMetodoPost();
            $id = obtenerId($payload);
            $clase = $service->editar($id, $payload);
            responder(['success' => true, 'message' => 'Clase actualizada correctamente.', 'data' => $clase->toArray()]);
            break;

        case 'eliminar':
            asegurarMetodoPost();
            $service->eliminar(obtenerId($payload));
            responder(['success' => true, 'message' => 'Clase eliminada correctamente.']);
            break;

        // Inscripciones de atletas (reservas) absorbidas por el modulo de Clases.
        case 'atletas':
            responder(['success' => true, 'data' => $service->listarAtletas()]);
            break;

        case 'clases':
            responder([
                'success' => true,
                'data' => $service->listarClasesDisponibles(obtenerIdAtleta($payload)),
            ]);
            break;

        case 'misReservas':
            responder([
                'success' => true,
                'data' => $service->listarReservasActivas(obtenerIdAtleta($payload)),
            ]);
            break;

        case 'reservar':
            asegurarMetodoPost();
            $reserva = $service->reservar($payload);
            responder([
                'success' => true,
                'message' => 'Reserva confirmada correctamente.',
                'data' => $reserva->toArray(),
            ], 201);
            break;

        case 'cancelar':
            asegurarMetodoPost();
            $service->cancelar($payload);
            responder(['success' => true, 'message' => 'Reserva cancelada correctamente.']);
            break;

        default:
            responder(['success' => false, 'message' => 'Accion no soportada.'], 404);
    }
} catch (InvalidArgumentException | DomainException $error) {
    responder(['success' => false, 'message' => $error->getMessage()], 422);
} catch (Throwable $error) {
    responder(['success' => false, 'message' => 'Error interno del servidor.'], 500);
}

function obtenerPayload(): array
{
    $contenido = file_get_contents('php://input');
    $json = json_decode($contenido ?: '{}', true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($json) && $json !== []) {
        return $json;
    }

    return $_POST;
}

function asegurarMetodoPost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new InvalidArgumentException('La operacion requiere metodo POST.');
    }
}

function obtenerId(array $payload): int
{
    $id = (int) ($_GET['id'] ?? $payload['id'] ?? 0);
    if ($id <= 0) {
        throw new InvalidArgumentException('Debe indicar un id valido.');
    }

    return $id;
}

function obtenerIdAtleta(array $payload): int
{
    return (int) ($_GET['idAtleta'] ?? $payload['idAtleta'] ?? $payload['id_atleta'] ?? 0);
}

function responder(array $respuesta, int $estadoHttp = 200): void
{
    http_response_code($estadoHttp);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}
