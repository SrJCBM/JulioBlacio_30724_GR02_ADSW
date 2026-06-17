<?php

require_once __DIR__ . '/../models/Membresia.php';
require_once __DIR__ . '/../includes/Database.php';

class MembresiaDAO
{
    private PDO $conexion;

    public function __construct(?PDO $conexion = null)
    {
        $this->conexion = $conexion ?? Database::conectar();
    }

    public function crear(Membresia $membresia): Membresia
    {
        $sentencia = $this->conexion->prepare(
            'INSERT INTO membresias
                (tipo, precio, fecha_inicio, fecha_vencimiento, estado, id_atleta)
             VALUES
                (:tipo, :precio, :fecha_inicio, :fecha_vencimiento, :estado, :id_atleta)'
        );

        $sentencia->execute([
            'tipo' => $membresia->getTipo(),
            'precio' => $membresia->getPrecio(),
            'fecha_inicio' => $membresia->getFechaInicio(),
            'fecha_vencimiento' => $membresia->getFechaVencimiento(),
            'estado' => $membresia->getEstado(),
            'id_atleta' => $membresia->getIdAtleta(),
        ]);

        return $this->buscarPorId((int) $this->conexion->lastInsertId());
    }

    public function actualizar(Membresia $membresia): Membresia
    {
        $sentencia = $this->conexion->prepare(
            'UPDATE membresias
                SET tipo = :tipo,
                    precio = :precio,
                    fecha_inicio = :fecha_inicio,
                    fecha_vencimiento = :fecha_vencimiento,
                    estado = :estado,
                    id_atleta = :id_atleta
              WHERE id = :id'
        );

        $sentencia->execute([
            'id' => $membresia->getId(),
            'tipo' => $membresia->getTipo(),
            'precio' => $membresia->getPrecio(),
            'fecha_inicio' => $membresia->getFechaInicio(),
            'fecha_vencimiento' => $membresia->getFechaVencimiento(),
            'estado' => $membresia->getEstado(),
            'id_atleta' => $membresia->getIdAtleta(),
        ]);

        return $this->buscarPorId((int) $membresia->getId());
    }

    public function actualizarTrasPago(Membresia $membresia): Membresia
    {
        return $this->actualizar($membresia);
    }

    public function cancelar(int $idMembresia): ?Membresia
    {
        $sentencia = $this->conexion->prepare(
            "UPDATE membresias
                SET estado = 'Cancelada'
              WHERE id = :id"
        );
        $sentencia->execute(['id' => $idMembresia]);

        return $this->buscarPorId($idMembresia);
    }

    public function marcarVencidas(string $fechaActual): void
    {
        $sentencia = $this->conexion->prepare(
            "UPDATE membresias
                SET estado = 'Vencido'
              WHERE estado = 'Pagado'
                AND fecha_vencimiento < :fecha_actual"
        );
        $sentencia->execute(['fecha_actual' => $fechaActual]);
    }

    public function buscarPorId(int $id): ?Membresia
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM membresias WHERE id = :id');
        $sentencia->execute(['id' => $id]);
        $fila = $sentencia->fetch();

        return $fila ? Membresia::fromArray($fila) : null;
    }

    public function buscarActualPorAtleta(int $idAtleta): ?Membresia
    {
        $sentencia = $this->conexion->prepare(
            'SELECT *
               FROM membresias
              WHERE id_atleta = :id_atleta
              ORDER BY id DESC
              LIMIT 1'
        );
        $sentencia->execute(['id_atleta' => $idAtleta]);
        $fila = $sentencia->fetch();

        return $fila ? Membresia::fromArray($fila) : null;
    }

    public function listar(): array
    {
        $sentencia = $this->conexion->query(
            'SELECT
                m.*,
                a.nombre AS atleta_nombre,
                a.correo AS atleta_correo
             FROM membresias m
             INNER JOIN atletas a ON a.id = m.id_atleta
             ORDER BY m.fecha_vencimiento ASC, a.nombre ASC'
        );

        return array_map([$this, 'mapearMembresiaConAtleta'], $sentencia->fetchAll());
    }

    public function listarAtletas(): array
    {
        $sentencia = $this->conexion->query(
            'SELECT id, nombre, correo, fecha_registro FROM atletas ORDER BY nombre ASC'
        );

        return $sentencia->fetchAll();
    }

    public function buscarAtletaPorCorreo(string $correo): ?array
    {
        $sentencia = $this->conexion->prepare(
            'SELECT id, nombre, correo, fecha_registro
               FROM atletas
              WHERE lower(correo) = :correo
              LIMIT 1'
        );
        $sentencia->execute(['correo' => strtolower(trim($correo))]);
        $fila = $sentencia->fetch();

        return $fila ?: null;
    }

    public function listarAtletasConMembresia(): array
    {
        $sentencia = $this->conexion->query(
            'SELECT
                a.id,
                a.nombre,
                a.correo,
                a.fecha_registro,
                m.id AS membresia_id,
                m.tipo,
                m.precio,
                m.fecha_inicio,
                m.fecha_vencimiento,
                m.estado
             FROM atletas a
             LEFT JOIN membresias m
                ON m.id = (
                    SELECT mm.id
                      FROM membresias mm
                     WHERE mm.id_atleta = a.id
                     ORDER BY mm.id DESC
                     LIMIT 1
                )
             ORDER BY a.nombre ASC'
        );

        return array_map([$this, 'mapearAtletaConMembresia'], $sentencia->fetchAll());
    }

    public function atletaExiste(int $idAtleta): bool
    {
        $sentencia = $this->conexion->prepare('SELECT COUNT(*) FROM atletas WHERE id = :id');
        $sentencia->execute(['id' => $idAtleta]);

        return (int) $sentencia->fetchColumn() > 0;
    }

    private function mapearMembresiaConAtleta(array $fila): array
    {
        $membresia = Membresia::fromArray($fila)->toArray();
        $membresia['atleta'] = [
            'id' => (int) $fila['id_atleta'],
            'nombre' => $fila['atleta_nombre'],
            'correo' => $fila['atleta_correo'],
        ];

        return $membresia;
    }

    private function mapearAtletaConMembresia(array $fila): array
    {
        $membresia = null;
        if ($fila['membresia_id'] !== null) {
            $membresia = [
                'id' => (int) $fila['membresia_id'],
                'tipo' => $fila['tipo'],
                'precio' => (float) $fila['precio'],
                'fechaInicio' => $fila['fecha_inicio'],
                'fechaVencimiento' => $fila['fecha_vencimiento'],
                'estado' => $fila['estado'],
            ];
        }

        return [
            'id' => (int) $fila['id'],
            'nombre' => $fila['nombre'],
            'correo' => $fila['correo'],
            'fechaRegistro' => $fila['fecha_registro'],
            'membresia' => $membresia,
        ];
    }
}
