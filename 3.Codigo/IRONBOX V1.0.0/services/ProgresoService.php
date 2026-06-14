<?php

require_once __DIR__ . '/../builders/RegistroProgresoBuilder.php';
require_once __DIR__ . '/../dao/ProgresoDAO.php';

class ProgresoService
{
    private ProgresoDAO $progresoDAO;

    public function __construct(?ProgresoDAO $progresoDAO = null)
    {
        $this->progresoDAO = $progresoDAO ?? new ProgresoDAO();
    }

    public function listarAtletas(): array
    {
        return $this->progresoDAO->listarAtletas();
    }

    public function guardar(array $datos): RegistroProgreso
    {
        $datos = $this->normalizarDatosRegistro($datos);

        if (!$this->progresoDAO->atletaExiste((int) $datos['idAtleta'])) {
            throw new DomainException('El atleta seleccionado no existe.');
        }

        $registro = (new RegistroProgresoBuilder())
            ->asignarAtleta((int) $datos['idAtleta'])
            ->definirFecha($datos['fecha'])
            ->registrarTiempo($datos['tiempo'])
            ->registrarRepeticiones($datos['repeticiones'])
            ->registrarPeso($datos['peso'])
            ->definirPuntuacion($datos['puntuacion'])
            ->agregarNotas($datos['notas'])
            ->construir();

        return $this->progresoDAO->crear($registro);
    }

    public function obtenerHistorial(int $idAtleta): array
    {
        if ($idAtleta <= 0) {
            throw new InvalidArgumentException('Debe seleccionar un atleta valido.');
        }

        if (!$this->progresoDAO->atletaExiste($idAtleta)) {
            throw new DomainException('El atleta seleccionado no existe.');
        }

        return $this->progresoDAO->listarHistorialPorAtleta($idAtleta);
    }

    private function normalizarDatosRegistro(array $datos): array
    {
        $normalizados = [
            'idAtleta' => $datos['idAtleta'] ?? $datos['id_atleta'] ?? null,
            'fecha' => $datos['fecha'] ?? date('Y-m-d'),
            'tiempo' => $this->normalizarFloatOpcional($datos['tiempo'] ?? null),
            'repeticiones' => $this->normalizarIntOpcional($datos['repeticiones'] ?? null),
            'peso' => $this->normalizarFloatOpcional($datos['peso'] ?? null),
            'puntuacion' => $this->normalizarFloatOpcional($datos['puntuacion'] ?? null),
            'notas' => $datos['notas'] ?? '',
        ];

        foreach (['idAtleta', 'fecha'] as $campo) {
            if ($normalizados[$campo] === null || $normalizados[$campo] === '') {
                throw new InvalidArgumentException('El campo ' . $campo . ' es obligatorio.');
            }
        }

        return $normalizados;
    }

    private function normalizarFloatOpcional(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (!is_numeric($valor)) {
            throw new InvalidArgumentException('Los campos numericos deben contener valores validos.');
        }

        return (float) $valor;
    }

    private function normalizarIntOpcional(mixed $valor): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (!is_numeric($valor)) {
            throw new InvalidArgumentException('Las repeticiones deben ser un numero valido.');
        }

        return (int) $valor;
    }
}
