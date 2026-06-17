<?php

final class Database
{
    private static ?PDO $conexion = null;

    public static function conectar(): PDO
    {
        if (self::$conexion instanceof PDO) {
            return self::$conexion;
        }

        self::cargarEnvLocal();

        $host = self::env('DB_HOST', '127.0.0.1');
        $port = self::env('DB_PORT', '3306');
        $name = self::env('DB_NAME', 'ironclad_box');
        $user = self::env('DB_USER', 'root');
        $password = self::env('DB_PASSWORD', '');
        $charset = self::env('DB_CHARSET', 'utf8mb4');

        $opciones = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $sslCa = self::env('DB_SSL_CA', '');
        if ($sslCa !== '') {
            $opciones[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
        self::$conexion = new PDO($dsn, $user, $password, $opciones);

        return self::$conexion;
    }

    private static function env(string $key, string $default = ''): string
    {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }

        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }

    private static function cargarEnvLocal(): void
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

            [$key, $value] = array_map('trim', explode('=', $linea, 2));
            $value = trim($value, "\"'");
            if (getenv($key) === false) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }
    }
}
