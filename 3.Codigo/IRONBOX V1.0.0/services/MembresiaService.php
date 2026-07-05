<?php

require_once __DIR__ . '/../builders/MembresiaBuilder.php';
require_once __DIR__ . '/../dao/MembresiaDAO.php';

class MembresiaService
{
    private MembresiaDAO $membresiaDAO;

    public function __construct(?MembresiaDAO $membresiaDAO = null)
    {
        $this->membresiaDAO = $membresiaDAO ?? new MembresiaDAO();
    }

    public function listar(): array
    {
        $this->actualizarVencidas();
        return $this->membresiaDAO->listar();
    }

    public function listarAtletas(): array
    {
        return $this->membresiaDAO->listarAtletas();
    }

    public function listarAtletasConMembresia(): array
    {
        $this->actualizarVencidas();
        return $this->membresiaDAO->listarAtletasConMembresia();
    }

    public function obtenerActualPorAtleta(int $idAtleta): ?array
    {
        if ($idAtleta <= 0 || !$this->membresiaDAO->atletaExiste($idAtleta)) {
            throw new InvalidArgumentException('Debe indicar un atleta valido.');
        }

        $this->actualizarVencidas();
        $membresia = $this->membresiaDAO->buscarActualPorAtleta($idAtleta);

        return $membresia ? $membresia->toArray() : null;
    }

    public function crear(array $datos): Membresia
    {
        $datos = $this->normalizarDatosCreacion($datos);

        if (!$this->membresiaDAO->atletaExiste((int) $datos['idAtleta'])) {
            throw new DomainException('El atleta seleccionado no existe.');
        }

        $builder = (new MembresiaBuilder())
            ->asignarAtleta((int) $datos['idAtleta'])
            ->configurarPlan($datos['tipo'], (float) $datos['precio'])
            ->definirFechaInicio($datos['fechaInicio'])
            ->definirEstado($datos['estado']);

        if (!empty($datos['fechaVencimiento'])) {
            $builder->definirFechaVencimiento($datos['fechaVencimiento']);
        } else {
            $builder->calcularFechaVencimiento($datos['fechaInicio']);
        }

        return $this->membresiaDAO->crear($builder->construir());
    }

    public function actualizar(array $datos): Membresia
    {
        $idMembresia = (int) ($datos['id'] ?? $datos['membresiaId'] ?? 0);
        if ($idMembresia <= 0) {
            throw new InvalidArgumentException('Debe indicar la membresia a editar.');
        }

        if (!$this->membresiaDAO->buscarPorId($idMembresia)) {
            throw new DomainException('La membresia indicada no existe.');
        }

        $datos = $this->normalizarDatosCreacion($datos);

        if (!$this->membresiaDAO->atletaExiste((int) $datos['idAtleta'])) {
            throw new DomainException('El atleta seleccionado no existe.');
        }

        $builder = (new MembresiaBuilder())
            ->conId($idMembresia)
            ->asignarAtleta((int) $datos['idAtleta'])
            ->configurarPlan($datos['tipo'], (float) $datos['precio'])
            ->definirFechaInicio($datos['fechaInicio'])
            ->definirEstado($datos['estado']);

        if (!empty($datos['fechaVencimiento'])) {
            $builder->definirFechaVencimiento($datos['fechaVencimiento']);
        } else {
            $builder->calcularFechaVencimiento($datos['fechaInicio']);
        }

        return $this->membresiaDAO->actualizar($builder->construir());
    }

    public function registrarPago(array $datos): Membresia
    {
        $idMembresia = (int) ($datos['id'] ?? $datos['membresiaId'] ?? 0);
        $idAtleta = (int) ($datos['idAtleta'] ?? $datos['id_atleta'] ?? 0);

        if ($idMembresia > 0) {
            $membresiaActual = $this->membresiaDAO->buscarPorId($idMembresia);
        } elseif ($idAtleta > 0) {
            $membresiaActual = $this->membresiaDAO->buscarActualPorAtleta($idAtleta);
        } else {
            throw new InvalidArgumentException('Debe indicar la membresia o el atleta para registrar el pago.');
        }

        if (!$membresiaActual) {
            throw new DomainException('No existe una membresia asignada para registrar el pago.');
        }

        $fechaPago = $datos['fechaPago'] ?? date('Y-m-d');
        $membresiaPagada = (new MembresiaBuilder())
            ->conId($membresiaActual->getId())
            ->asignarAtleta($membresiaActual->getIdAtleta())
            ->configurarPlan($membresiaActual->getTipo(), $membresiaActual->getPrecio())
            ->marcarComoPagadoDesde($fechaPago)
            ->construir();

        return $this->membresiaDAO->actualizarTrasPago($membresiaPagada);
    }

    public function cancelar(array $datos): Membresia
    {
        $idMembresia = (int) ($datos['id'] ?? $datos['membresiaId'] ?? 0);
        $idAtleta = (int) ($datos['idAtleta'] ?? $datos['id_atleta'] ?? 0);

        if ($idMembresia <= 0 && $idAtleta > 0) {
            $membresiaActual = $this->membresiaDAO->buscarActualPorAtleta($idAtleta);
        } elseif ($idMembresia > 0) {
            $membresiaActual = $this->membresiaDAO->buscarPorId($idMembresia);
        } else {
            throw new InvalidArgumentException('Debe indicar la membresia o el atleta para cancelar.');
        }

        if (!$membresiaActual) {
            throw new DomainException('No existe una membresia para cancelar.');
        }

        $membresiaCancelada = $this->membresiaDAO->cancelar((int) $membresiaActual->getId());
        if (!$membresiaCancelada) {
            throw new DomainException('No se pudo cancelar la membresia.');
        }

        return $membresiaCancelada;
    }

    public function solicitar(int $idAtleta): Membresia
    {
        if ($idAtleta <= 0 || !$this->membresiaDAO->atletaExiste($idAtleta)) {
            throw new InvalidArgumentException('Debe indicar un atleta valido.');
        }

        // Anti-duplicado: no permitir otra solicitud si ya hay una pendiente
        // o una membresia pagada aun vigente.
        $actual = $this->membresiaDAO->buscarActualPorAtleta($idAtleta);
        if ($actual !== null) {
            $estado = $actual->getEstado();
            $bloquea = $estado === 'Pendiente'
                || ($estado === 'Pagado' && $actual->getFechaVencimiento() >= date('Y-m-d'));

            if ($bloquea) {
                throw new DomainException('Ya tienes una solicitud pendiente o una membresia activa.');
            }
        }

        return $this->crear([
            'idAtleta' => $idAtleta,
            'tipo' => 'Por definir',
            'precio' => 0,
            'fechaInicio' => date('Y-m-d'),
            'estado' => 'Pendiente',
        ]);
    }

    private function actualizarVencidas(): void
    {
        $this->membresiaDAO->marcarVencidas(date('Y-m-d'));
    }

    private function normalizarDatosCreacion(array $datos): array
    {
        $normalizados = [
            'idAtleta' => $datos['idAtleta'] ?? $datos['id_atleta'] ?? null,
            'tipo' => $datos['tipo'] ?? '',
            'precio' => $datos['precio'] ?? null,
            'fechaInicio' => $datos['fechaInicio'] ?? $datos['fecha_inicio'] ?? date('Y-m-d'),
            'fechaVencimiento' => $datos['fechaVencimiento'] ?? $datos['fecha_vencimiento'] ?? '',
            'estado' => $datos['estado'] ?? 'Pendiente',
        ];

        foreach (['idAtleta', 'tipo', 'precio', 'fechaInicio'] as $campo) {
            if ($normalizados[$campo] === null || $normalizados[$campo] === '') {
                throw new InvalidArgumentException('El campo ' . $campo . ' es obligatorio.');
            }
        }

        return $normalizados;
    }
}
