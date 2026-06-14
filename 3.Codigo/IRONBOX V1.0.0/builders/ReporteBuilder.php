<?php

require_once __DIR__ . '/../models/Reporte.php';

class ReporteBuilder
{
    private const TIPOS_VALIDOS = ['Finanzas', 'Asistencia'];

    private ?int $id = null;
    private ?string $tipo = null;
    private ?string $fechaInicio = null;
    private ?string $fechaFin = null;
    private array $filas = [];
    private array $columnas = [];
    private array $resumen = [];

    public function conId(?int $id): self
    {
        if ($id !== null && $id <= 0) {
            throw new InvalidArgumentException('El id del reporte no es valido.');
        }

        $this->id = $id;
        return $this;
    }

    public function conTipo(string $tipo): self
    {
        $tipo = trim($tipo);
        if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
            throw new InvalidArgumentException('El tipo de reporte debe ser Finanzas o Asistencia.');
        }

        $this->tipo = $tipo;
        return $this;
    }

    public function conRangoFechas(string $fechaInicio, string $fechaFin): self
    {
        if (!$this->fechaValida($fechaInicio) || !$this->fechaValida($fechaFin)) {
            throw new InvalidArgumentException('Las fechas deben tener formato YYYY-MM-DD.');
        }

        if ($fechaInicio > $fechaFin) {
            throw new InvalidArgumentException('La fecha de inicio no puede ser posterior a la fecha fin.');
        }

        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        return $this;
    }

    public function conFilas(array $filas): self
    {
        $this->filas = $filas;
        return $this;
    }

    public function formatearDatos(): self
    {
        if ($this->tipo === 'Finanzas') {
            $this->columnas = ['fecha', 'atleta', 'tipo', 'estado', 'precio'];
            $total = array_reduce(
                $this->filas,
                fn (float $acumulado, array $fila): float => $acumulado + (float) ($fila['precio'] ?? 0),
                0.0
            );
            $this->resumen = [
                'totalRegistros' => count($this->filas),
                'totalIngresos' => round($total, 2),
            ];
        }

        if ($this->tipo === 'Asistencia') {
            $this->columnas = ['fecha', 'hora', 'entrenador', 'reservasConfirmadas', 'reservasCanceladas', 'cupoMaximo'];
            $confirmadas = array_reduce(
                $this->filas,
                fn (int $acumulado, array $fila): int => $acumulado + (int) ($fila['reservasConfirmadas'] ?? 0),
                0
            );
            $this->resumen = [
                'totalClases' => count($this->filas),
                'totalReservasConfirmadas' => $confirmadas,
            ];
        }

        return $this;
    }

    public function construir(): Reporte
    {
        $faltantes = [];
        foreach ([
            'tipo' => $this->tipo,
            'fechaInicio' => $this->fechaInicio,
            'fechaFin' => $this->fechaFin,
        ] as $campo => $valor) {
            if ($valor === null || $valor === '') {
                $faltantes[] = $campo;
            }
        }

        if ($faltantes !== []) {
            throw new InvalidArgumentException('Faltan datos obligatorios: ' . implode(', ', $faltantes) . '.');
        }

        if ($this->columnas === []) {
            $this->formatearDatos();
        }

        return new Reporte(
            $this->id,
            $this->tipo,
            [
                'filtros' => [
                    'fechaInicio' => $this->fechaInicio,
                    'fechaFin' => $this->fechaFin,
                ],
                'columnas' => $this->columnas,
                'filas' => $this->filas,
                'resumen' => $this->resumen,
            ],
            date('Y-m-d H:i:s')
        );
    }

    private function fechaValida(string $fecha): bool
    {
        $dateTime = DateTime::createFromFormat('Y-m-d', $fecha);
        return $dateTime && $dateTime->format('Y-m-d') === $fecha;
    }
}
