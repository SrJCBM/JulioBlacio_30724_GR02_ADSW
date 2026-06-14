<?php

require_once __DIR__ . '/../models/Usuario.php';

class UsuarioDAO
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
            'CREATE TABLE IF NOT EXISTS usuarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                contrasena TEXT NOT NULL,
                rol TEXT NOT NULL CHECK (rol IN ("Administrador", "Entrenador", "Atleta")),
                estado TEXT NOT NULL CHECK (estado IN ("Activo", "Inactivo")),
                fecha_registro TEXT NOT NULL
            )'
        );

        $usuarios = [
            [
                'nombre' => 'Admin IronClad',
                'email' => 'admin@ironcladbox.local',
                'rol' => 'Administrador',
            ],
            [
                'nombre' => 'Valeria Rios',
                'email' => 'valeria.rios@ironcladbox.local',
                'rol' => 'Entrenador',
            ],
            [
                'nombre' => 'Daniela Moya',
                'email' => 'daniela.moya@ironcladbox.local',
                'rol' => 'Atleta',
            ],
        ];

        $sentencia = $this->conexion->prepare(
            'INSERT OR IGNORE INTO usuarios
                (nombre, email, contrasena, rol, estado, fecha_registro)
             VALUES
                (:nombre, :email, :contrasena, :rol, "Activo", :fecha_registro)'
        );

        foreach ($usuarios as $usuario) {
            $sentencia->execute([
                'nombre' => $usuario['nombre'],
                'email' => $usuario['email'],
                'contrasena' => password_hash('IronClad123', PASSWORD_DEFAULT),
                'rol' => $usuario['rol'],
                'fecha_registro' => date('Y-m-d'),
            ]);
        }
    }

    public function crear(Usuario $usuario): Usuario
    {
        $sentencia = $this->conexion->prepare(
            'INSERT INTO usuarios
                (nombre, email, contrasena, rol, estado, fecha_registro)
             VALUES
                (:nombre, :email, :contrasena, :rol, :estado, :fecha_registro)'
        );

        $sentencia->execute([
            'nombre' => $usuario->getNombre(),
            'email' => $usuario->getEmail(),
            'contrasena' => $usuario->getContrasena(),
            'rol' => $usuario->getRol(),
            'estado' => $usuario->getEstado(),
            'fecha_registro' => $usuario->getFechaRegistro(),
        ]);

        return $this->buscarPorId((int) $this->conexion->lastInsertId());
    }

    public function actualizar(Usuario $usuario): Usuario
    {
        $sentencia = $this->conexion->prepare(
            'UPDATE usuarios
                SET nombre = :nombre,
                    email = :email,
                    contrasena = :contrasena,
                    rol = :rol,
                    estado = :estado,
                    fecha_registro = :fecha_registro
              WHERE id = :id'
        );

        $sentencia->execute([
            'id' => $usuario->getId(),
            'nombre' => $usuario->getNombre(),
            'email' => $usuario->getEmail(),
            'contrasena' => $usuario->getContrasena(),
            'rol' => $usuario->getRol(),
            'estado' => $usuario->getEstado(),
            'fecha_registro' => $usuario->getFechaRegistro(),
        ]);

        return $this->buscarPorId((int) $usuario->getId());
    }

    public function desactivar(int $id): bool
    {
        $sentencia = $this->conexion->prepare(
            'UPDATE usuarios SET estado = "Inactivo" WHERE id = :id AND estado = "Activo"'
        );
        $sentencia->execute(['id' => $id]);

        return $sentencia->rowCount() > 0;
    }

    public function buscarPorId(int $id): ?Usuario
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM usuarios WHERE id = :id');
        $sentencia->execute(['id' => $id]);
        $fila = $sentencia->fetch();

        return $fila ? Usuario::fromArray($fila) : null;
    }

    public function listar(): array
    {
        $sentencia = $this->conexion->query(
            'SELECT * FROM usuarios ORDER BY estado ASC, rol ASC, nombre ASC'
        );

        return array_map(
            fn (array $fila): array => Usuario::fromArray($fila)->toArray(),
            $sentencia->fetchAll()
        );
    }

    public function emailExiste(string $email, ?int $idIgnorado = null): bool
    {
        $parametros = ['email' => strtolower(trim($email))];
        $sql = 'SELECT COUNT(*) FROM usuarios WHERE lower(email) = :email';

        if ($idIgnorado !== null) {
            $sql .= ' AND id <> :id_ignorado';
            $parametros['id_ignorado'] = $idIgnorado;
        }

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);

        return (int) $sentencia->fetchColumn() > 0;
    }
}
