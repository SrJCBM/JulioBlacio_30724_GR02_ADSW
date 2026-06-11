<?php

require_once __DIR__ . '/../models/Reporte.php';

class ReporteDAO
{
    private PDO $conexion;

    public function __construct(?PDO $conexion = null)
    {
        $this->conexion = $conexion ?? $this->crearConexionPdoSimulada();
        $this->inicializarEsquemaSimulado();
    }

    private function crearConexionPdoSimulada(): PDO
    {
        $directorioDatos = __DIR__ . '/../data';
        if (!is_dir($directorioDatos)) {
            mkdir($directorioDatos, 0777, true);
        }

        $rutaBase = $directorioDatos . '/ironclad_box.sqlite';
        $pdo = new PDO('sqlite:' . $rutaBase);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;

        /*
        Para MySQL real:
        $dsn = 'mysql:host=localhost;dbname=ironclad_box;charset=utf8mb4';
        return new PDO($dsn, 'usuario', 'password', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        */
    }

    private function inicializarEsquemaSimulado(): void
    {
        $this->conexion->exec(
            'CREATE TABLE IF NOT EXISTS atletas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                fecha_registro TEXT NOT NULL
            )'
        );

        $this->conexion->exec(
            'CREATE TABLE IF NOT EXISTS entrenadores (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                disponible INTEGER NOT NULL DEFAULT 1
            )'
        );

        $this->conexion->exec(
            'CREATE TABLE IF NOT EXISTS clases (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                dia TEXT NOT NULL,
                hora TEXT NOT NULL,
                duracion INTEGER NOT NULL,
                cupo_maximo INTEGER NOT NULL,
                cupos_disponibles INTEGER NOT NULL,
                entrenador_id INTEGER NOT NULL
            )'
        );

        $this->conexion->exec(
            'CREATE TABLE IF NOT EXISTS membresias (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tipo TEXT NOT NULL,
                precio REAL NOT NULL,
                fecha_inicio TEXT NOT NULL,
                fecha_vencimiento TEXT NOT NULL,
                estado TEXT NOT NULL,
                id_atleta INTEGER NOT NULL
            )'
        );

        $this->conexion->exec(
            'CREATE TABLE IF NOT EXISTS reservas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_atleta INTEGER NOT NULL,
                id_clase INTEGER NOT NULL,
                fecha_reserva TEXT NOT NULL,
                estado TEXT NOT NULL
            )'
        );

        $this->conexion->exec(
            'CREATE TABLE IF NOT EXISTS reportes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tipo TEXT NOT NULL,
                datos TEXT NOT NULL,
                fecha_generacion TEXT NOT NULL
            )'
        );
    }

    public function consultarFinanzas(string $fechaInicio, string $fechaFin): array
    {
        $sentencia = $this->conexion->prepare(
            'SELECT
                m.fecha_inicio AS fecha,
                a.nombre AS atleta,
                m.tipo,
                m.estado,
                ROUND(m.precio, 2) AS precio
             FROM membresias m
             INNER JOIN atletas a ON a.id = m.id_atleta
             WHERE m.fecha_inicio BETWEEN :fecha_inicio AND :fecha_fin
             ORDER BY m.fecha_inicio ASC, a.nombre ASC'
        );
        $sentencia->execute([
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ]);

        return array_map(fn (array $fila): array => [
            'fecha' => $fila['fecha'],
            'atleta' => $fila['atleta'],
            'tipo' => $fila['tipo'],
            'estado' => $fila['estado'],
            'precio' => (float) $fila['precio'],
        ], $sentencia->fetchAll());
    }

    public function consultarAsistencia(string $fechaInicio, string $fechaFin): array
    {
        $sentencia = $this->conexion->prepare(
            'SELECT
                c.dia AS fecha,
                c.hora,
                e.nombre AS entrenador,
                SUM(CASE WHEN r.estado = "Confirmada" THEN 1 ELSE 0 END) AS reservas_confirmadas,
                SUM(CASE WHEN r.estado = "Cancelada" THEN 1 ELSE 0 END) AS reservas_canceladas,
                c.cupo_maximo,
                c.cupos_disponibles
             FROM clases c
             INNER JOIN entrenadores e ON e.id = c.entrenador_id
             LEFT JOIN reservas r ON r.id_clase = c.id
             WHERE c.dia BETWEEN :fecha_inicio AND :fecha_fin
             GROUP BY c.id, c.dia, c.hora, e.nombre, c.cupo_maximo, c.cupos_disponibles
             ORDER BY c.dia ASC, c.hora ASC'
        );
        $sentencia->execute([
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ]);

        return array_map(fn (array $fila): array => [
            'fecha' => $fila['fecha'],
            'hora' => $fila['hora'],
            'entrenador' => $fila['entrenador'],
            'reservasConfirmadas' => (int) $fila['reservas_confirmadas'],
            'reservasCanceladas' => (int) $fila['reservas_canceladas'],
            'cupoMaximo' => (int) $fila['cupo_maximo'],
            'cuposDisponibles' => (int) $fila['cupos_disponibles'],
        ], $sentencia->fetchAll());
    }

    public function guardar(Reporte $reporte): Reporte
    {
        $sentencia = $this->conexion->prepare(
            'INSERT INTO reportes (tipo, datos, fecha_generacion)
             VALUES (:tipo, :datos, :fecha_generacion)'
        );
        $sentencia->execute([
            'tipo' => $reporte->getTipo(),
            'datos' => json_encode($reporte->getDatos(), JSON_UNESCAPED_UNICODE),
            'fecha_generacion' => $reporte->getFechaGeneracion(),
        ]);

        return $this->buscarPorId((int) $this->conexion->lastInsertId());
    }

    public function buscarPorId(int $id): ?Reporte
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM reportes WHERE id = :id');
        $sentencia->execute(['id' => $id]);
        $fila = $sentencia->fetch();

        return $fila ? Reporte::fromArray($fila) : null;
    }
}
