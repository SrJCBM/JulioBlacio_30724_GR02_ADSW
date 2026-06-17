<?php

require_once __DIR__ . '/../models/Reporte.php';
require_once __DIR__ . '/../includes/Database.php';

class ReporteDAO
{
    private PDO $conexion;

    public function __construct(?PDO $conexion = null)
    {
        $this->conexion = $conexion ?? Database::conectar();
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
            "SELECT
                c.dia AS fecha,
                c.hora,
                e.nombre AS entrenador,
                SUM(CASE WHEN r.estado = 'Confirmada' THEN 1 ELSE 0 END) AS reservas_confirmadas,
                SUM(CASE WHEN r.estado = 'Cancelada' THEN 1 ELSE 0 END) AS reservas_canceladas,
                c.cupo_maximo,
                c.cupos_disponibles
             FROM clases c
             INNER JOIN entrenadores e ON e.id = c.entrenador_id
             LEFT JOIN reservas r ON r.id_clase = c.id
             WHERE c.dia BETWEEN :fecha_inicio AND :fecha_fin
             GROUP BY c.id, c.dia, c.hora, e.nombre, c.cupo_maximo, c.cupos_disponibles
             ORDER BY c.dia ASC, c.hora ASC"
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
