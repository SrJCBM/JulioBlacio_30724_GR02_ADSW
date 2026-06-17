<?php

require_once __DIR__ . '/../models/Mensaje.php';
require_once __DIR__ . '/../includes/Database.php';

class ComunicacionDAO
{
    private PDO $conexion;

    public function __construct(?PDO $conexion = null)
    {
        $this->conexion = $conexion ?? Database::conectar();
    }

    public function crear(Mensaje $mensaje): Mensaje
    {
        $sentencia = $this->conexion->prepare(
            'INSERT INTO mensajes
                (contenido, fecha_envio, tipo, id_atleta, id_entrenador)
             VALUES
                (:contenido, :fecha_envio, :tipo, :id_atleta, :id_entrenador)'
        );

        $sentencia->execute([
            'contenido' => $mensaje->getContenido(),
            'fecha_envio' => $mensaje->getFechaEnvio(),
            'tipo' => $mensaje->getTipo(),
            'id_atleta' => $mensaje->getIdAtleta(),
            'id_entrenador' => $mensaje->getIdEntrenador(),
        ]);

        return $this->buscarPorId((int) $this->conexion->lastInsertId());
    }

    public function buscarPorId(int $id): ?Mensaje
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM mensajes WHERE id = :id');
        $sentencia->execute(['id' => $id]);
        $fila = $sentencia->fetch();

        return $fila ? Mensaje::fromArray($fila) : null;
    }

    public function listarRecibidosPorAtleta(int $idAtleta): array
    {
        $sentencia = $this->conexion->prepare(
            'SELECT
                m.*,
                a.nombre AS atleta_nombre,
                e.nombre AS entrenador_nombre
             FROM mensajes m
             LEFT JOIN atletas a ON a.id = m.id_atleta
             LEFT JOIN entrenadores e ON e.id = m.id_entrenador
             WHERE m.id_atleta = :id_atleta
                OR m.id_atleta IS NULL
             ORDER BY m.fecha_envio DESC, m.id DESC'
        );
        $sentencia->execute(['id_atleta' => $idAtleta]);

        return array_map([$this, 'mapearMensajeConRelaciones'], $sentencia->fetchAll());
    }

    public function listarHistorialEntrenador(?int $idEntrenador = null): array
    {
        $parametros = [];
        $sql = 'SELECT
                    m.*,
                    a.nombre AS atleta_nombre,
                    e.nombre AS entrenador_nombre
                FROM mensajes m
                LEFT JOIN atletas a ON a.id = m.id_atleta
                LEFT JOIN entrenadores e ON e.id = m.id_entrenador';

        if ($idEntrenador !== null) {
            $sql .= ' WHERE m.id_entrenador = :id_entrenador';
            $parametros['id_entrenador'] = $idEntrenador;
        }

        $sql .= ' ORDER BY m.fecha_envio DESC, m.id DESC';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);

        return array_map([$this, 'mapearMensajeConRelaciones'], $sentencia->fetchAll());
    }

    public function listarAtletas(): array
    {
        $sentencia = $this->conexion->query(
            'SELECT id, nombre, correo, fecha_registro FROM atletas ORDER BY nombre ASC'
        );

        return $sentencia->fetchAll();
    }

    public function listarEntrenadores(): array
    {
        $sentencia = $this->conexion->query(
            'SELECT id, nombre, correo, disponible FROM entrenadores ORDER BY nombre ASC'
        );

        return $sentencia->fetchAll();
    }

    public function atletaExiste(int $idAtleta): bool
    {
        $sentencia = $this->conexion->prepare('SELECT COUNT(*) FROM atletas WHERE id = :id');
        $sentencia->execute(['id' => $idAtleta]);

        return (int) $sentencia->fetchColumn() > 0;
    }

    public function entrenadorExiste(int $idEntrenador): bool
    {
        $sentencia = $this->conexion->prepare('SELECT COUNT(*) FROM entrenadores WHERE id = :id');
        $sentencia->execute(['id' => $idEntrenador]);

        return (int) $sentencia->fetchColumn() > 0;
    }

    private function mapearMensajeConRelaciones(array $fila): array
    {
        $mensaje = Mensaje::fromArray($fila)->toArray();
        $mensaje['destinatario'] = $fila['id_atleta'] !== null
            ? ['id' => (int) $fila['id_atleta'], 'nombre' => $fila['atleta_nombre']]
            : ['id' => null, 'nombre' => 'Todos los atletas'];
        $mensaje['entrenador'] = $fila['id_entrenador'] !== null
            ? ['id' => (int) $fila['id_entrenador'], 'nombre' => $fila['entrenador_nombre']]
            : null;

        return $mensaje;
    }
}
