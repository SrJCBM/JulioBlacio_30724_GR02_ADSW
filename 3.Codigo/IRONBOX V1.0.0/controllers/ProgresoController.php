<?php

require_once __DIR__ . '/../services/ProgresoService.php';

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

$service = new ProgresoService();
$payload = obtenerPayloadProgreso();
$accion = $_GET['action'] ?? $payload['action'] ?? 'atletas';

try {
    switch ($accion) {
        case 'atletas':
            responderProgreso(['success' => true, 'data' => $service->listarAtletas()]);
            break;

        case 'historial':
        case 'historialAtleta':
            $idAtleta = obtenerIdAtletaProgreso($payload);
            responderProgreso(['success' => true, 'data' => $service->obtenerHistorial($idAtleta)]);
            break;

        case 'obtenerDatosGrafico':
            $idAtleta = obtenerIdAtletaProgreso($payload);
            responderProgreso(['success' => true, 'data' => obtenerDatosGrafico($service, $idAtleta)]);
            break;

        case 'guardar':
        case 'guardarAtleta':
            asegurarPostProgreso();
            $payload['idAtleta'] = obtenerIdAtletaProgreso($payload);
            $registro = $service->guardar($payload);
            responderProgreso([
                'success' => true,
                'message' => 'Resultado WOD registrado correctamente.',
                'data' => $registro->toArray(),
            ], 201);
            break;

        default:
            responderProgreso(['success' => false, 'message' => 'Accion no soportada.'], 404);
    }
} catch (InvalidArgumentException | DomainException $error) {
    responderProgreso(['success' => false, 'message' => $error->getMessage()], 422);
} catch (Throwable $error) {
    responderProgreso(['success' => false, 'message' => 'Error interno del servidor.'], 500);
}

function obtenerPayloadProgreso(): array
{
    $contenido = file_get_contents('php://input');
    $json = json_decode($contenido ?: '{}', true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($json) && $json !== []) {
        return $json;
    }

    return $_POST;
}

function asegurarPostProgreso(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new InvalidArgumentException('La operacion requiere metodo POST.');
    }
}

function obtenerIdAtletaProgreso(array $payload): int
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

function obtenerDatosGrafico(ProgresoService $service, int $idAtleta): array
{
    $historial = array_reverse($service->obtenerHistorial($idAtleta));

    return [
        'fechas' => array_map(fn (array $registro): string => $registro['fecha'], $historial),
        'puntuaciones' => array_map(fn (array $registro): float => (float) $registro['puntuacion'], $historial),
        'pesos' => array_map(
            fn (array $registro): ?float => $registro['peso'] !== null ? (float) $registro['peso'] : null,
            $historial
        ),
    ];
}

function responderProgreso(array $respuesta, int $estadoHttp = 200): void
{
    http_response_code($estadoHttp);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}
