<?php

function aplicarCors(string $metodos = 'GET, POST, OPTIONS', string $cabeceras = 'Content-Type'): void
{
    cargarEnvCors();

    $origen = $_SERVER['HTTP_ORIGIN'] ?? '';
    $origenesPermitidos = array_filter(array_map('trim', explode(',', envCors('APP_ALLOWED_ORIGINS'))));

    if ($origen !== '' && in_array($origen, $origenesPermitidos, true)) {
        header('Access-Control-Allow-Origin: ' . $origen);
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    }

    header('Access-Control-Allow-Methods: ' . $metodos);
    header('Access-Control-Allow-Headers: ' . $cabeceras);
}

function envCors(string $key, string $default = ''): string
{
    $valor = getenv($key);
    if ($valor !== false && $valor !== '') {
        return $valor;
    }

    return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
}

function cargarEnvCors(): void
{
    $ruta = dirname(__DIR__) . '/.env';
    if (!is_file($ruta)) {
        return;
    }

    foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
            continue;
        }

        [$key, $valor] = array_map('trim', explode('=', $linea, 2));
        $valor = trim($valor, "\"'");
        if (getenv($key) === false) {
            putenv("{$key}={$valor}");
            $_ENV[$key] = $valor;
        }
    }
}
