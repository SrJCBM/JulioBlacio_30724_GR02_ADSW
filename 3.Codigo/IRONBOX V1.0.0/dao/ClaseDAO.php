<?php

require_once __DIR__ . '/../models/Clase.php';
require_once __DIR__ . '/../models/Reserva.php';

class ClaseDAO
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
                entrenador_id INTEGER NOT NULL,
                FOREIGN KEY (entrenador_id) REFERENCES entrenadores(id)
            )'
        );

        $this->conexion->exec(
            'CREATE TABLE IF NOT EXISTS membresias (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tipo TEXT NOT NULL,
                precio REAL NOT NULL,
                fecha_inicio TEXT NOT NULL,
                fecha_vencimiento TEXT NOT NULL,
                estado TEXT NOT NULL CHECK (estado IN ("Pagado", "Pendiente", "Vencido")),
                id_atleta INTEGER NOT NULL,
                FOREIGN KEY (id_atleta) REFERENCES atletas(id)
            )'
        );

        $this->conexion->exec(
            'CREATE TABLE IF NOT EXISTS reservas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_atleta INTEGER NOT NULL,
                id_clase INTEGER NOT NULL,
                fecha_reserva TEXT NOT NULL,
                estado TEXT NOT NULL CHECK (estado IN ("Confirmada", "Cancelada")),
                FOREIGN KEY (id_atleta) REFERENCES atletas(id),
                FOREIGN KEY (id_clase) REFERENCES clases(id)
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

        if ((int) $this->conexion->query('SELECT COUNT(*) FROM clases')->fetchColumn() === 0) {
            $insertClase = $this->conexion->prepare(
                'INSERT INTO clases
                    (dia, hora, duracion, cupo_maximo, cupos_disponibles, entrenador_id)
                 VALUES
                    (:dia, :hora, :duracion, :cupo_maximo, :cupos_disponibles, :entrenador_id)'
            );

            $clases = [
                ['dia' => date('Y-m-d', strtotime('+1 day')), 'hora' => '07:00', 'duracion' => 60, 'cupo' => 12, 'entrenador' => 1],
                ['dia' => date('Y-m-d', strtotime('+1 day')), 'hora' => '18:00', 'duracion' => 60, 'cupo' => 10, 'entrenador' => 2],
                ['dia' => date('Y-m-d', strtotime('+2 day')), 'hora' => '06:00', 'duracion' => 45, 'cupo' => 8, 'entrenador' => 3],
            ];

            foreach ($clases as $clase) {
                $insertClase->execute([
                    'dia' => $clase['dia'],
                    'hora' => $clase['hora'],
                    'duracion' => $clase['duracion'],
                    'cupo_maximo' => $clase['cupo'],
                    'cupos_disponibles' => $clase['cupo'],
                    'entrenador_id' => $clase['entrenador'],
                ]);
            }
        }

        $hayMembresiaVigente = (int) $this->conexion->query(
            'SELECT COUNT(*)
               FROM membresias
              WHERE estado = "Pagado"
                AND fecha_vencimiento >= date("now")'
        )->fetchColumn();

        if ($hayMembresiaVigente === 0) {
            $sentencia = $this->conexion->prepare(
                'INSERT INTO membresias
                    (tipo, precio, fecha_inicio, fecha_vencimiento, estado, id_atleta)
                 VALUES
                    ("Premium", 55.00, :fecha_inicio, :fecha_vencimiento, "Pagado", 1)'
            );
            $sentencia->execute([
                'fecha_inicio' => date('Y-m-d'),
                'fecha_vencimiento' => date('Y-m-d', strtotime('+30 days')),
            ]);
        }
    }

    public function crear(Clase $clase): Clase
    {
        $sentencia = $this->conexion->prepare(
            'INSERT INTO clases
                (dia, hora, duracion, cupo_maximo, cupos_disponibles, entrenador_id)
             VALUES
                (:dia, :hora, :duracion, :cupo_maximo, :cupos_disponibles, :entrenador_id)'
        );

        $sentencia->execute([
            'dia' => $clase->getDia(),
            'hora' => $clase->getHora(),
            'duracion' => $clase->getDuracion(),
            'cupo_maximo' => $clase->getCupoMaximo(),
            'cupos_disponibles' => $clase->getCuposDisponibles(),
            'entrenador_id' => $clase->getEntrenadorId(),
        ]);

        return $this->buscarPorId((int) $this->conexion->lastInsertId());
    }

    public function actualizar(Clase $clase): Clase
    {
        $sentencia = $this->conexion->prepare(
            'UPDATE clases
                SET dia = :dia,
                    hora = :hora,
                    duracion = :duracion,
                    cupo_maximo = :cupo_maximo,
                    cupos_disponibles = :cupos_disponibles,
                    entrenador_id = :entrenador_id
              WHERE id = :id'
        );

        $sentencia->execute([
            'id' => $clase->getId(),
            'dia' => $clase->getDia(),
            'hora' => $clase->getHora(),
            'duracion' => $clase->getDuracion(),
            'cupo_maximo' => $clase->getCupoMaximo(),
            'cupos_disponibles' => $clase->getCuposDisponibles(),
            'entrenador_id' => $clase->getEntrenadorId(),
        ]);

        return $this->buscarPorId((int) $clase->getId());
    }

    public function eliminar(int $id): bool
    {
        $sentencia = $this->conexion->prepare('DELETE FROM clases WHERE id = :id');
        $sentencia->execute(['id' => $id]);

        return $sentencia->rowCount() > 0;
    }

    public function buscarPorId(int $id): ?Clase
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM clases WHERE id = :id');
        $sentencia->execute(['id' => $id]);
        $fila = $sentencia->fetch();

        return $fila ? Clase::fromArray($fila) : null;
    }

    public function listar(): array
    {
        $sentencia = $this->conexion->query(
            'SELECT
                c.*,
                e.nombre AS entrenador_nombre,
                e.email AS entrenador_email
             FROM clases c
             INNER JOIN entrenadores e ON e.id = c.entrenador_id
             ORDER BY c.dia ASC, c.hora ASC'
        );

        return array_map([$this, 'mapearClaseConEntrenador'], $sentencia->fetchAll());
    }

    public function listarEntrenadores(): array
    {
        $sentencia = $this->conexion->query(
            'SELECT id, nombre, email, disponible FROM entrenadores ORDER BY nombre ASC'
        );

        return $sentencia->fetchAll();
    }

    public function entrenadorExisteYDisponible(int $entrenadorId): bool
    {
        $sentencia = $this->conexion->prepare(
            'SELECT COUNT(*) FROM entrenadores WHERE id = :id AND disponible = 1'
        );
        $sentencia->execute(['id' => $entrenadorId]);

        return (int) $sentencia->fetchColumn() > 0;
    }

    public function existeSolapamientoGeneral(
        string $dia,
        string $hora,
        int $duracion,
        ?int $idIgnorado = null
    ): bool {
        return $this->existeSolapamiento($dia, $hora, $duracion, null, $idIgnorado);
    }

    public function existeSolapamientoEntrenador(
        string $dia,
        string $hora,
        int $duracion,
        int $entrenadorId,
        ?int $idIgnorado = null
    ): bool {
        return $this->existeSolapamiento($dia, $hora, $duracion, $entrenadorId, $idIgnorado);
    }

    private function existeSolapamiento(
        string $dia,
        string $hora,
        int $duracion,
        ?int $entrenadorId,
        ?int $idIgnorado
    ): bool {
        $parametros = [
            'dia' => $dia,
            'inicio' => $hora,
            'fin' => $this->calcularHoraFin($hora, $duracion),
        ];

        $sql = 'SELECT COUNT(*)
                  FROM clases
                 WHERE dia = :dia
                   AND time(hora) < time(:fin)
                   AND time(:inicio) < time(hora, "+" || duracion || " minutes")';

        if ($entrenadorId !== null) {
            $sql .= ' AND entrenador_id = :entrenador_id';
            $parametros['entrenador_id'] = $entrenadorId;
        }

        if ($idIgnorado !== null) {
            $sql .= ' AND id <> :id_ignorado';
            $parametros['id_ignorado'] = $idIgnorado;
        }

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);

        return (int) $sentencia->fetchColumn() > 0;
    }

    private function calcularHoraFin(string $hora, int $duracion): string
    {
        $inicio = DateTime::createFromFormat('H:i', $hora);
        $inicio->modify('+' . $duracion . ' minutes');

        return $inicio->format('H:i');
    }

    private function mapearClaseConEntrenador(array $fila): array
    {
        $clase = Clase::fromArray($fila)->toArray();
        $clase['entrenador'] = [
            'id' => (int) $fila['entrenador_id'],
            'nombre' => $fila['entrenador_nombre'],
            'email' => $fila['entrenador_email'],
        ];

        return $clase;
    }

    // ------------------------------------------------------------------
    // Inscripciones de atletas (reservas) consolidadas en el modulo Clases.
    // El SQL transaccional vive aqui; la validacion cruzada de membresia se
    // coordina desde ClaseService usando MembresiaDAO.
    // ------------------------------------------------------------------

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

    public function claseExiste(int $idClase): bool
    {
        $sentencia = $this->conexion->prepare('SELECT COUNT(*) FROM clases WHERE id = :id');
        $sentencia->execute(['id' => $idClase]);

        return (int) $sentencia->fetchColumn() > 0;
    }

    public function consultarCupos(int $idClase): ?int
    {
        $sentencia = $this->conexion->prepare('SELECT cupos_disponibles FROM clases WHERE id = :id');
        $sentencia->execute(['id' => $idClase]);
        $cupos = $sentencia->fetchColumn();

        return $cupos === false ? null : (int) $cupos;
    }

    public function existeReservaActiva(int $idAtleta, int $idClase): bool
    {
        $sentencia = $this->conexion->prepare(
            'SELECT COUNT(*)
               FROM reservas
              WHERE id_atleta = :id_atleta
                AND id_clase = :id_clase
                AND estado = "Confirmada"'
        );
        $sentencia->execute([
            'id_atleta' => $idAtleta,
            'id_clase' => $idClase,
        ]);

        return (int) $sentencia->fetchColumn() > 0;
    }

    public function listarClasesDisponibles(int $idAtleta): array
    {
        $sentencia = $this->conexion->prepare(
            'SELECT
                c.*,
                e.nombre AS entrenador_nombre,
                CASE
                    WHEN r.id IS NULL THEN 0
                    ELSE 1
                END AS ya_reservada
             FROM clases c
             INNER JOIN entrenadores e ON e.id = c.entrenador_id
             LEFT JOIN reservas r
                ON r.id_clase = c.id
               AND r.id_atleta = :id_atleta
               AND r.estado = "Confirmada"
             WHERE datetime(c.dia || " " || c.hora) >= datetime("now", "localtime")
               AND c.cupos_disponibles > 0
             ORDER BY c.dia ASC, c.hora ASC'
        );
        $sentencia->execute(['id_atleta' => $idAtleta]);

        return array_map([$this, 'mapearClaseDisponible'], $sentencia->fetchAll());
    }

    public function listarReservasActivas(int $idAtleta): array
    {
        $sentencia = $this->conexion->prepare(
            'SELECT
                r.*,
                c.dia,
                c.hora,
                c.duracion,
                c.cupo_maximo,
                c.cupos_disponibles,
                e.nombre AS entrenador_nombre,
                a.nombre AS atleta_nombre
             FROM reservas r
             INNER JOIN clases c ON c.id = r.id_clase
             INNER JOIN entrenadores e ON e.id = c.entrenador_id
             INNER JOIN atletas a ON a.id = r.id_atleta
             WHERE r.id_atleta = :id_atleta
               AND r.estado = "Confirmada"
             ORDER BY c.dia ASC, c.hora ASC'
        );
        $sentencia->execute(['id_atleta' => $idAtleta]);

        return array_map([$this, 'mapearReservaConClase'], $sentencia->fetchAll());
    }

    public function reservarCupo(int $idAtleta, int $idClase): Reserva
    {
        try {
            $this->conexion->beginTransaction();

            $actualizacion = $this->conexion->prepare(
                'UPDATE clases
                    SET cupos_disponibles = cupos_disponibles - 1
                  WHERE id = :id_clase
                    AND cupos_disponibles > 0'
            );
            $actualizacion->execute(['id_clase' => $idClase]);

            if ($actualizacion->rowCount() === 0) {
                throw new DomainException('La clase ya no tiene cupos disponibles.');
            }

            $fechaReserva = date('Y-m-d H:i:s');
            $insercion = $this->conexion->prepare(
                'INSERT INTO reservas (id_atleta, id_clase, fecha_reserva, estado)
                 VALUES (:id_atleta, :id_clase, :fecha_reserva, "Confirmada")'
            );
            $insercion->execute([
                'id_atleta' => $idAtleta,
                'id_clase' => $idClase,
                'fecha_reserva' => $fechaReserva,
            ]);

            $idReserva = (int) $this->conexion->lastInsertId();
            $this->conexion->commit();

            return $this->buscarReservaPorId($idReserva);
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $error;
        }
    }

    public function cancelarReservaYLiberarCupo(int $idReserva, int $idAtleta): bool
    {
        try {
            $this->conexion->beginTransaction();

            $reserva = $this->buscarReservaCruda($idReserva, $idAtleta);
            if (!$reserva || $reserva['estado'] !== 'Confirmada') {
                throw new DomainException('La reserva no existe o ya fue cancelada.');
            }

            $cancelacion = $this->conexion->prepare(
                'UPDATE reservas
                    SET estado = "Cancelada"
                  WHERE id = :id_reserva
                    AND id_atleta = :id_atleta
                    AND estado = "Confirmada"'
            );
            $cancelacion->execute([
                'id_reserva' => $idReserva,
                'id_atleta' => $idAtleta,
            ]);

            $liberarCupo = $this->conexion->prepare(
                'UPDATE clases
                    SET cupos_disponibles = CASE
                        WHEN cupos_disponibles < cupo_maximo THEN cupos_disponibles + 1
                        ELSE cupos_disponibles
                    END
                  WHERE id = :id_clase'
            );
            $liberarCupo->execute(['id_clase' => (int) $reserva['id_clase']]);

            $this->conexion->commit();
            return true;
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $error;
        }
    }

    public function buscarReservaPorId(int $id): ?Reserva
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM reservas WHERE id = :id');
        $sentencia->execute(['id' => $id]);
        $fila = $sentencia->fetch();

        return $fila ? Reserva::fromArray($fila) : null;
    }

    private function buscarReservaCruda(int $idReserva, int $idAtleta): ?array
    {
        $sentencia = $this->conexion->prepare(
            'SELECT * FROM reservas WHERE id = :id_reserva AND id_atleta = :id_atleta'
        );
        $sentencia->execute([
            'id_reserva' => $idReserva,
            'id_atleta' => $idAtleta,
        ]);
        $fila = $sentencia->fetch();

        return $fila ?: null;
    }

    private function mapearClaseDisponible(array $fila): array
    {
        return [
            'id' => (int) $fila['id'],
            'dia' => $fila['dia'],
            'hora' => $fila['hora'],
            'duracion' => (int) $fila['duracion'],
            'cupoMaximo' => (int) $fila['cupo_maximo'],
            'cuposDisponibles' => (int) $fila['cupos_disponibles'],
            'entrenador' => [
                'id' => (int) $fila['entrenador_id'],
                'nombre' => $fila['entrenador_nombre'],
            ],
            'yaReservada' => (bool) $fila['ya_reservada'],
        ];
    }

    private function mapearReservaConClase(array $fila): array
    {
        $reserva = Reserva::fromArray($fila)->toArray();
        $reserva['atleta'] = [
            'id' => (int) $fila['id_atleta'],
            'nombre' => $fila['atleta_nombre'],
        ];
        $reserva['clase'] = [
            'id' => (int) $fila['id_clase'],
            'dia' => $fila['dia'],
            'hora' => $fila['hora'],
            'duracion' => (int) $fila['duracion'],
            'cupoMaximo' => (int) $fila['cupo_maximo'],
            'cuposDisponibles' => (int) $fila['cupos_disponibles'],
            'entrenador' => [
                'nombre' => $fila['entrenador_nombre'],
            ],
        ];

        return $reserva;
    }
}
