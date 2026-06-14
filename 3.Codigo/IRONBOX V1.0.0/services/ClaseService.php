<?php

require_once __DIR__ . '/../builders/ClaseBuilder.php';
require_once __DIR__ . '/../dao/ClaseDAO.php';
require_once __DIR__ . '/../dao/MembresiaDAO.php';

class ClaseService
{
    private const CAPACIDAD_CROSSFIT = 30;

    private ClaseDAO $claseDAO;
    private MembresiaDAO $membresiaDAO;

    public function __construct(?ClaseDAO $claseDAO = null, ?MembresiaDAO $membresiaDAO = null)
    {
        $this->claseDAO = $claseDAO ?? new ClaseDAO();
        $this->membresiaDAO = $membresiaDAO ?? new MembresiaDAO();
    }

    public function listar(): array
    {
        return $this->claseDAO->listar();
    }

    public function listarEntrenadores(): array
    {
        return $this->claseDAO->listarEntrenadores();
    }

    public function crear(array $datos): Clase
    {
        $datos = $this->normalizarDatos($datos);
        $clase = $this->crearBuilderDesdeDatos($datos)->construir();

        $this->validarReglasDeAgenda($clase);

        return $this->claseDAO->crear($clase);
    }

    public function editar(int $id, array $datos): Clase
    {
        $claseActual = $this->claseDAO->buscarPorId($id);
        if (!$claseActual) {
            throw new DomainException('La clase solicitada no existe.');
        }

        $datos = $this->normalizarDatos($datos);
        $cuposDisponibles = isset($datos['cuposDisponibles'])
            ? (int) $datos['cuposDisponibles']
            : min($claseActual->getCuposDisponibles(), (int) $datos['cupoMaximo']);

        $clase = $this->crearBuilderDesdeDatos($datos)
            ->conId($id)
            ->definirCuposDisponibles($cuposDisponibles)
            ->construir();

        $this->validarReglasDeAgenda($clase, $id);

        return $this->claseDAO->actualizar($clase);
    }

    public function eliminar(int $id): void
    {
        if (!$this->claseDAO->eliminar($id)) {
            throw new DomainException('La clase solicitada no existe o ya fue eliminada.');
        }
    }

    // ------------------------------------------------------------------
    // Inscripciones de atletas (reservas).
    // La logica de reservas vive en el modulo de Clases: ClaseService
    // coordina la validacion cruzada de membresia (MembresiaDAO) y delega
    // la escritura transaccional en ClaseDAO::reservarCupo().
    // ------------------------------------------------------------------

    public function listarAtletas(): array
    {
        return $this->claseDAO->listarAtletas();
    }

    public function listarClasesDisponibles(int $idAtleta): array
    {
        $this->validarAtleta($idAtleta);
        return $this->claseDAO->listarClasesDisponibles($idAtleta);
    }

    public function listarReservasActivas(int $idAtleta): array
    {
        $this->validarAtleta($idAtleta);
        return $this->claseDAO->listarReservasActivas($idAtleta);
    }

    public function reservar(array $datos): Reserva
    {
        $idAtleta = (int) ($datos['idAtleta'] ?? $datos['id_atleta'] ?? 0);
        $idClase = (int) ($datos['idClase'] ?? $datos['id_clase'] ?? 0);

        $this->validarAtleta($idAtleta);

        if ($idClase <= 0 || !$this->claseDAO->claseExiste($idClase)) {
            throw new InvalidArgumentException('Debe seleccionar una clase valida.');
        }

        // Validacion cruzada de membresia: el atleta debe tener una membresia
        // en estado "Pagado" y no vencida, consultada a traves de MembresiaDAO.
        if (!$this->tieneMembresiaVigente($idAtleta)) {
            throw new DomainException('El atleta no tiene una membresia pagada y vigente.');
        }

        if ($this->claseDAO->consultarCupos($idClase) <= 0) {
            throw new DomainException('La clase seleccionada no tiene cupos disponibles.');
        }

        if ($this->claseDAO->existeReservaActiva($idAtleta, $idClase)) {
            throw new DomainException('El atleta ya tiene una reserva activa para esta clase.');
        }

        return $this->claseDAO->reservarCupo($idAtleta, $idClase);
    }

    public function cancelar(array $datos): void
    {
        $idAtleta = (int) ($datos['idAtleta'] ?? $datos['id_atleta'] ?? 0);
        $idReserva = (int) ($datos['id'] ?? $datos['idReserva'] ?? $datos['id_reserva'] ?? 0);

        $this->validarAtleta($idAtleta);

        if ($idReserva <= 0) {
            throw new InvalidArgumentException('Debe indicar una reserva valida.');
        }

        $this->claseDAO->cancelarReservaYLiberarCupo($idReserva, $idAtleta);
    }

    private function tieneMembresiaVigente(int $idAtleta): bool
    {
        $membresia = $this->membresiaDAO->buscarActualPorAtleta($idAtleta);

        return $membresia !== null
            && $membresia->getEstado() === 'Pagado'
            && $membresia->getFechaVencimiento() >= date('Y-m-d');
    }

    private function validarAtleta(int $idAtleta): void
    {
        if ($idAtleta <= 0 || !$this->claseDAO->atletaExiste($idAtleta)) {
            throw new InvalidArgumentException('Debe seleccionar un atleta valido.');
        }
    }

    private function crearBuilderDesdeDatos(array $datos): ClaseBuilder
    {
        return (new ClaseBuilder())
            ->definirDiaHora($datos['dia'], $datos['hora'])
            ->definirDuracion((int) $datos['duracion'])
            ->definirCupoMaximo((int) $datos['cupoMaximo'], self::CAPACIDAD_CROSSFIT)
            ->asignarEntrenador((int) $datos['entrenadorId']);
    }

    private function validarReglasDeAgenda(Clase $clase, ?int $idIgnorado = null): void
    {
        if (!$this->claseDAO->entrenadorExisteYDisponible($clase->getEntrenadorId())) {
            throw new DomainException('El entrenador asignado no existe o no esta disponible.');
        }

        if ($this->claseDAO->existeSolapamientoEntrenador(
            $clase->getDia(),
            $clase->getHora(),
            $clase->getDuracion(),
            $clase->getEntrenadorId(),
            $idIgnorado
        )) {
            throw new DomainException('El entrenador asignado no esta disponible en ese horario.');
        }

        if ($this->claseDAO->existeSolapamientoGeneral(
            $clase->getDia(),
            $clase->getHora(),
            $clase->getDuracion(),
            $idIgnorado
        )) {
            throw new DomainException('El horario se solapa con otra clase existente.');
        }
    }

    private function normalizarDatos(array $datos): array
    {
        $normalizados = [
            'dia' => $datos['dia'] ?? '',
            'hora' => $datos['hora'] ?? '',
            'duracion' => $datos['duracion'] ?? null,
            'cupoMaximo' => $datos['cupoMaximo'] ?? $datos['cupo_maximo'] ?? null,
            'cuposDisponibles' => $datos['cuposDisponibles'] ?? $datos['cupos_disponibles'] ?? null,
            'entrenadorId' => $datos['entrenadorId'] ?? $datos['entrenador_id'] ?? null,
        ];

        foreach (['dia', 'hora', 'duracion', 'cupoMaximo', 'entrenadorId'] as $campo) {
            if ($normalizados[$campo] === null || $normalizados[$campo] === '') {
                throw new InvalidArgumentException('El campo ' . $campo . ' es obligatorio.');
            }
        }

        return $normalizados;
    }
}
