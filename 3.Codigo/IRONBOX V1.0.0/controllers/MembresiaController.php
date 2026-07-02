<?php

require_once __DIR__ . '/../services/MembresiaService.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Cors.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
aplicarCors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$service = new MembresiaService();
$payload = obtenerPayloadMembresia();
$accion = $_GET['action'] ?? $payload['action'] ?? 'listar';

try {
    switch ($accion) {
        case 'listar':
            authRequerirRol(['Administrador']);
            responderMembresia(['success' => true, 'data' => $service->listarAtletasConMembresia()]);
            break;

        case 'membresias':
            authRequerirRol(['Administrador']);
            responderMembresia(['success' => true, 'data' => $service->listar()]);
            break;

        case 'atletas':
            authRequerirRol(['Administrador', 'Atleta']);
            responderMembresia(['success' => true, 'data' => $service->listarAtletas()]);
            break;

        case 'miMembresia':
            authRequerirRol(['Administrador', 'Atleta']);
            $membresia = $service->obtenerActualPorAtleta(obtenerIdAtletaMembresia($payload));
            responderMembresia([
                'success' => true,
                'data' => $membresia,
                'message' => $membresia ? 'Membresia encontrada.' : 'El atleta no tiene membresia registrada.',
            ]);
            break;

        case 'crear':
            authRequerirRol(['Administrador']);
            asegurarPostMembresia();
            $membresia = $service->crear($payload);
            responderMembresia([
                'success' => true,
                'message' => 'Membresia asignada correctamente.',
                'data' => $membresia->toArray(),
            ], 201);
            break;

        case 'editar':
        case 'actualizar':
            authRequerirRol(['Administrador']);
            asegurarPostMembresia();
            $membresia = $service->actualizar($payload);
            responderMembresia([
                'success' => true,
                'message' => 'Membresia actualizada correctamente.',
                'data' => $membresia->toArray(),
            ]);
            break;

        case 'registrarPago':
            authRequerirRol(['Administrador']);
            asegurarPostMembresia();
            $membresia = $service->registrarPago($payload);
            responderMembresia([
                'success' => true,
                'message' => 'Pago registrado correctamente.',
                'data' => $membresia->toArray(),
            ]);
            break;

        case 'pagarMembresia':
            authRequerirRol(['Atleta']);
            asegurarPostMembresia();
            $payload['idAtleta'] = obtenerIdAtletaMembresia($payload);
            $membresia = $service->registrarPago($payload);
            responderMembresia([
                'success' => true,
                'message' => 'Pago registrado correctamente.',
                'data' => $membresia->toArray(),
            ]);
            break;

        case 'cancelarMembresia':
            authRequerirRol(['Atleta']);
            asegurarPostMembresia();
            $payload['idAtleta'] = obtenerIdAtletaMembresia($payload);
            $membresia = $service->cancelar($payload);
            responderMembresia([
                'success' => true,
                'message' => 'Membresia cancelada correctamente.',
                'data' => $membresia->toArray(),
            ]);
            break;

        default:
            responderMembresia(['success' => false, 'message' => 'Accion no soportada.'], 404);
    }
} catch (AuthException $error) {
    responderMembresia(['success' => false, 'message' => $error->getMessage()], $error->getEstadoHttp());
} catch (InvalidArgumentException | DomainException $error) {
    responderMembresia(['success' => false, 'message' => $error->getMessage()], 422);
} catch (Throwable $error) {
    responderMembresia(['success' => false, 'message' => 'Error interno del servidor.'], 500);
}

function obtenerPayloadMembresia(): array
{
    $contenido = file_get_contents('php://input');
    $json = json_decode($contenido ?: '{}', true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($json) && $json !== []) {
        return $json;
    }

    return $_POST;
}

function asegurarPostMembresia(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new InvalidArgumentException('La operacion requiere metodo POST.');
    }
}

function obtenerIdAtletaMembresia(array $payload): int
{
    $usuario = authUsuarioActual();
    if (($usuario['rol'] ?? '') === 'Atleta') {
        return (int) ($_SESSION['id_atleta'] ?? 0);
    }

    return (int) (
        $_GET['idAtleta']
        ?? $_GET['id_atleta']
        ?? $payload['idAtleta']
        ?? $payload['id_atleta']
        ?? $_SESSION['id_atleta']
        ?? 0
    );
}

function responderMembresia(array $respuesta, int $estadoHttp = 200): void
{
    http_response_code($estadoHttp);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}
