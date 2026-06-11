<?php

require_once __DIR__ . '/../models/Mensaje.php';

class ComunicacionDAO
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
            'CREATE TABLE IF NOT EXISTS mensajes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                contenido TEXT NOT NULL,
                fecha_envio TEXT NOT NULL,
                tipo TEXT NOT NULL CHECK (tipo IN ("Mensaje", "Anuncio")),
                id_atleta INTEGER NULL,
                id_entrenador INTEGER NULL,
                FOREIGN KEY (id_atleta) REFERENCES atletas(id),
                FOREIGN KEY (id_entrenador) REFERENCES entrenadores(id)
            )'
        );

        $this->sembrarDatosBase();
    }

    private function sembrarDatosBase(): void
    {
        $atletas = [
            ['nombre' => 'Daniela Moya', 'email' => 'daniela.moya@ironcladbox.local'],
            ['nombre' => 'Nicolas Perez', 'email' => 'nicolas.perez@ironcladbox.local'],
            ['nombre' => 'Andrea Vega', 'email' => 'andrea.vega@ironcladbox.local'],
            ['nombre' => 'Sebastian Flores', 'email' => 'sebastian.flores@ironcladbox.local'],
        ];

        $insertAtleta = $this->conexion->prepare(
            'INSERT OR IGNORE INTO atletas (nombre, email, fecha_registro)
             VALUES (:nombre, :email, :fecha_registro)'
        );

        foreach ($atletas as $atleta) {
            $insertAtleta->execute([
                'nombre' => $atleta['nombre'],
                'email' => $atleta['email'],
                'fecha_registro' => date('Y-m-d'),
            ]);
        }

        $entrenadores = [
            ['nombre' => 'Valeria Rios', 'email' => 'valeria.rios@ironcladbox.local'],
            ['nombre' => 'Mateo Silva', 'email' => 'mateo.silva@ironcladbox.local'],
            ['nombre' => 'Camila Torres', 'email' => 'camila.torres@ironcladbox.local'],
        ];

        $insertEntrenador = $this->conexion->prepare(
            'INSERT OR IGNORE INTO entrenadores (nombre, email, disponible)
             VALUES (:nombre, :email, 1)'
        );

        foreach ($entrenadores as $entrenador) {
            $insertEntrenador->execute($entrenador);
        }
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
            'SELECT id, nombre, email, fecha_registro FROM atletas ORDER BY nombre ASC'
        );

        return $sentencia->fetchAll();
    }

    public function listarEntrenadores(): array
    {
        $sentencia = $this->conexion->query(
            'SELECT id, nombre, email, disponible FROM entrenadores ORDER BY nombre ASC'
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
