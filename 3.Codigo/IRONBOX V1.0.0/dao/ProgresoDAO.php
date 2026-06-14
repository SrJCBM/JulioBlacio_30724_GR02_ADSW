<?php

require_once __DIR__ . '/../models/RegistroProgreso.php';

class ProgresoDAO
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
        $pdo->exec('PRAGMA foreign_keys = ON;');

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
            'CREATE TABLE IF NOT EXISTS progreso_atletas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                fecha TEXT NOT NULL,
                tiempo REAL NULL,
                repeticiones INTEGER NULL,
                peso REAL NULL,
                puntuacion REAL NOT NULL DEFAULT 0,
                notas TEXT NOT NULL DEFAULT "",
                id_atleta INTEGER NOT NULL,
                FOREIGN KEY (id_atleta) REFERENCES atletas(id)
            )'
        );

        $atletas = [
            ['nombre' => 'Daniela Moya', 'email' => 'daniela.moya@ironcladbox.local'],
            ['nombre' => 'Nicolas Perez', 'email' => 'nicolas.perez@ironcladbox.local'],
            ['nombre' => 'Andrea Vega', 'email' => 'andrea.vega@ironcladbox.local'],
            ['nombre' => 'Sebastian Flores', 'email' => 'sebastian.flores@ironcladbox.local'],
        ];

        $sentencia = $this->conexion->prepare(
            'INSERT OR IGNORE INTO atletas (nombre, email, fecha_registro)
             VALUES (:nombre, :email, :fecha_registro)'
        );

        foreach ($atletas as $atleta) {
            $sentencia->execute([
                'nombre' => $atleta['nombre'],
                'email' => $atleta['email'],
                'fecha_registro' => date('Y-m-d'),
            ]);
        }
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
                a.email AS atleta_email
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
            'SELECT id, nombre, email, fecha_registro FROM atletas ORDER BY nombre ASC'
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
            'email' => $fila['atleta_email'],
        ];

        return $registro;
    }
}
