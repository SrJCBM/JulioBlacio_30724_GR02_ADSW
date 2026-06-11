<?php

require_once __DIR__ . '/../services/ReporteService.php';

$service = new ReporteService();
$payload = obtenerPayloadReporte();
$accion = $_GET['action'] ?? $payload['action'] ?? 'generar';

try {
    switch ($accion) {
        case 'generar':
            header('Content-Type: application/json; charset=utf-8');
            $reporte = $service->generar($payload);
            responderReporte(['success' => true, 'data' => $reporte->toArray()]);
            break;

        case 'exportarCsv':
            $csv = $service->generarCsv($payload);
            $nombreArchivo = 'reporte_' . strtolower($payload['tipo'] ?? 'general') . '_' . date('Ymd_His') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
            echo $csv;
            exit;

        case 'exportarPdf':
            header('Content-Type: application/json; charset=utf-8');
            responderReporte(['success' => true, 'data' => $service->prepararPdf($payload)]);
            break;

        default:
            header('Content-Type: application/json; charset=utf-8');
            responderReporte(['success' => false, 'message' => 'Accion no soportada.'], 404);
    }
} catch (InvalidArgumentException | DomainException $error) {
    header('Content-Type: application/json; charset=utf-8');
    responderReporte(['success' => false, 'message' => $error->getMessage()], 422);
} catch (Throwable $error) {
    header('Content-Type: application/json; charset=utf-8');
    responderReporte(['success' => false, 'message' => 'Error interno del servidor.'], 500);
}

function obtenerPayloadReporte(): array
{
    $contenido = file_get_contents('php://input');
    $json = json_decode($contenido ?: '{}', true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($json) && $json !== []) {
        return array_merge($_GET, $json);
    }

    return array_merge($_GET, $_POST);
}

function responderReporte(array $respuesta, int $estadoHttp = 200): void
{
    http_response_code($estadoHttp);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}
