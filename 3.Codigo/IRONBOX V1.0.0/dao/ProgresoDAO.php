<?php

require_once __DIR__ . '/../models/RegistroProgreso.php';
require_once __DIR__ . '/../includes/Database.php';

class ProgresoDAO
{
    private PDO $conexion;

    public function __construct(?PDO $conexion = null)
    {
        $this->conexion = $conexion ?? Database::conectar();
    }

    public function crear(RegistroProgreso $registro): RegistroProgreso
    {
        $sentencia = $this->conexion->prepare(
            'INSERT INTO progreso_atletas
                (fecha, tiempo, repeticiones, peso, puntuacion, notas, id_atleta)
             VALUES
                (:fecha, :tiempo, :repeticiones, :peso, :puntuacion, :notas, :id_atleta)'
        );

        $sentencia->execute([
            'fecha' => $registro->getFecha(),
            'tiempo' => $registro->getTiempo(),
            'repeticiones' => $registro->getRepeticiones(),
            'peso' => $registro->getPeso(),
            'puntuacion' => $registro->getPuntuacion(),
            'notas' => $registro->getNotas(),
            'id_atleta' => $registro->getIdAtleta(),
        ]);

        return $this->buscarPorId((int) $this->conexion->lastInsertId());
    }

    public function buscarPorId(int $id): ?RegistroProgreso
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM progreso_atletas WHERE id = :id');
        $sentencia->execute(['id' => $id]);
        $fila = $sentencia->fetch();

        return $fila ? RegistroProgreso::fromArray($fila) : null;
    }

    public function listarHistorialPorAtleta(int $idAtleta): array
    {
        $sentencia = $this->conexion->prepare(
            'SELECT
                p.*,
                a.nombre AS atleta_nombre,
                a.correo AS atleta_correo
             FROM progreso_atletas p
             INNER JOIN atletas a ON a.id = p.id_atleta
             WHERE p.id_atleta = :id_atleta
             ORDER BY p.fecha DESC, p.id DESC'
        );
        $sentencia->execute(['id_atleta' => $idAtleta]);

        return array_map([$this, 'mapearRegistroConAtleta'], $sentencia->fetchAll());
    }

    public function listarAtletas(): array
    {
        $sentencia = $this->conexion->query(
            'SELECT id, nombre, correo, fecha_registro FROM atletas ORDER BY nombre ASC'
        );

        return $sentencia->fetchAll();
    }

    public function atletaExiste(int $idAtleta): bool
    {
        $sentencia = $this->conexion->prepare('SELECT COUNT(*) FROM atletas WHERE id = :id');
        $sentencia->execute(['id' => $idAtleta]);

        return (int) $sentencia->fetchColumn() > 0;
    }

    private function mapearRegistroConAtleta(array $fila): array
    {
        $registro = RegistroProgreso::fromArray($fila)->toArray();
        $registro['atleta'] = [
            'id' => (int) $fila['id_atleta'],
            'nombre' => $fila['atleta_nombre'],
            'correo' => $fila['atleta_correo'],
        ];

        return $registro;
    }
}
