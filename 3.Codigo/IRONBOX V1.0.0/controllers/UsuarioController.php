<?php

require_once __DIR__ . '/../services/UsuarioService.php';
require_once __DIR__ . '/../services/MembresiaService.php';
require_once __DIR__ . '/../dao/MembresiaDAO.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Cors.php';

header('Content-Type: application/json; charset=utf-8');
aplicarCors('GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$service = new UsuarioService();
$payload = obtenerPayloadUsuario();
$metodo = strtoupper($payload['_method'] ?? $_SERVER['REQUEST_METHOD']);
$accion = $_GET['action'] ?? $payload['action'] ?? accionPorMetodoUsuario($metodo);

try {
    authRequerirRol(['Administrador']);

    switch ($accion) {
        case 'listar':
            responderUsuario(['success' => true, 'data' => $service->listar()]);
            break;

        case 'crear':
            asegurarMetodoUsuario($metodo, ['POST']);
            $usuario = $service->crear($payload);
            $avisoMembresia = asignarMembresiaInicial($usuario, $payload);
            responderUsuario([
                'success' => true,
                'message' => 'Usuario creado correctamente.' . $avisoMembresia,
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
} catch (AuthException $error) {
    responderUsuario(['success' => false, 'message' => $error->getMessage()], $error->getEstadoHttp());
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

function asignarMembresiaInicial(Usuario $usuario, array $payload): string
{
    // Orquestacion entre modulos: tras crear el usuario Atleta (que sincroniza
    // su fila en 'atletas'), se le asigna una membresia opcional en el mismo paso.
    $datos = $payload['membresia'] ?? null;
    if (!is_array($datos) || $usuario->getRol() !== 'Atleta') {
        return '';
    }

    $tipo = trim((string) ($datos['tipo'] ?? ''));
    if ($tipo === '') {
        return '';
    }

    try {
        $atleta = (new MembresiaDAO())->buscarAtletaPorCorreo($usuario->getCorreo());
        if (!$atleta) {
            return ' Nota: el usuario se creo, pero aun no se pudo asignar la membresia.';
        }

        $precio = $datos['precio'] ?? null;
        (new MembresiaService())->crear([
            'idAtleta' => (int) $atleta['id'],
            'tipo' => $tipo,
            'precio' => ($precio === null || $precio === '') ? 0 : $precio,
            'fechaInicio' => !empty($datos['fechaInicio']) ? $datos['fechaInicio'] : date('Y-m-d'),
            'estado' => $datos['estado'] ?? 'Pendiente',
        ]);

        return ' Membresia asignada.';
    } catch (Throwable $error) {
        return ' Nota: el usuario se creo, pero no se pudo asignar la membresia (' . $error->getMessage() . ').';
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
