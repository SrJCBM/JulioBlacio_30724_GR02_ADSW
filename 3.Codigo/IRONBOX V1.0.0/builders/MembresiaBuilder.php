<?php

require_once __DIR__ . '/../models/Membresia.php';

class MembresiaBuilder
{
    private const ESTADOS_VALIDOS = ['Pagado', 'Pendiente', 'Vencido', 'Cancelada'];
    private const DURACION_DIAS = 30;

    private ?int $id = null;
    private ?string $tipo = null;
    private ?float $precio = null;
    private ?string $fechaInicio = null;
    private ?string $fechaVencimiento = null;
    private ?string $estado = null;
    private ?int $idAtleta = null;

    public function conId(?int $id): self
    {
        if ($id !== null && $id <= 0) {
            throw new InvalidArgumentException('El id de la membresia no es valido.');
        }

        $this->id = $id;
        return $this;
    }

    public function asignarAtleta(int $idAtleta): self
    {
        if ($idAtleta <= 0) {
            throw new InvalidArgumentException('Debe asignar un atleta valido.');
        }

        $this->idAtleta = $idAtleta;
        return $this;
    }

    public function configurarPlan(string $tipo, float $precio): self
    {
        $tipo = trim($tipo);
        if ($tipo === '') {
            throw new InvalidArgumentException('El tipo de membresia es obligatorio.');
        }

        if (strlen($tipo) > 80) {
            throw new InvalidArgumentException('El tipo de membresia no puede superar 80 caracteres.');
        }

        if ($precio <= 0) {
            throw new InvalidArgumentException('El precio de la membresia debe ser mayor a cero.');
        }

        if ($precio > 100000) {
            throw new InvalidArgumentException('El precio de la membresia no puede superar 100000.');
        }

        $this->tipo = $tipo;
        $this->precio = round($precio, 2);
        return $this;
    }

    public function definirFechaInicio(string $fechaInicio): self
    {
        $fechaInicio = trim($fechaInicio);
        if (!$this->fechaValida($fechaInicio)) {
            throw new InvalidArgumentException('La fecha de inicio debe tener formato YYYY-MM-DD.');
        }

        $this->fechaInicio = $fechaInicio;
        return $this;
    }

    public function definirFechaVencimiento(string $fechaVencimiento): self
    {
        $fechaVencimiento = trim($fechaVencimiento);
        if (!$this->fechaValida($fechaVencimiento)) {
            throw new InvalidArgumentException('La fecha de vencimiento debe tener formato YYYY-MM-DD.');
        }

        $this->fechaVencimiento = $fechaVencimiento;
        return $this;
    }

    public function calcularFechaVencimiento(?string $base = null, int $dias = self::DURACION_DIAS): self
    {
        $fechaBase = $base ?? $this->fechaInicio ?? date('Y-m-d');
        if (!$this->fechaValida($fechaBase)) {
            throw new InvalidArgumentException('La fecha base para vencimiento no es valida.');
        }

        $fecha = DateTime::createFromFormat('Y-m-d', $fechaBase);
        $fecha->modify('+' . $dias . ' days');
        $this->fechaVencimiento = $fecha->format('Y-m-d');

        return $this;
    }

    public function definirEstado(string $estado): self
    {
        $estado = trim($estado);
        if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
            throw new InvalidArgumentException('El estado debe ser Pagado, Pendiente, Vencido o Cancelada.');
        }

        $this->estado = $estado;
        return $this;
    }

    public function marcarComoPagadoDesde(?string $fechaPago = null): self
    {
        $fechaBase = $fechaPago ?? date('Y-m-d');
        $this->definirEstado('Pagado');
        $this->definirFechaInicio($fechaBase);
        $this->calcularFechaVencimiento($fechaBase);

        return $this;
    }

    public function construir(): Membresia
    {
        if ($this->fechaVencimiento === null && $this->fechaInicio !== null) {
            $this->calcularFechaVencimiento($this->fechaInicio);
        }

        $this->estado = $this->estado ?? 'Pendiente';

        $camposFaltantes = [];
        foreach ([
            'tipo' => $this->tipo,
            'precio' => $this->precio,
            'fechaInicio' => $this->fechaInicio,
            'fechaVencimiento' => $this->fechaVencimiento,
            'estado' => $this->estado,
            'idAtleta' => $this->idAtleta,
        ] as $campo => $valor) {
            if ($valor === null || $valor === '') {
                $camposFaltantes[] = $campo;
            }
        }

        if ($camposFaltantes !== []) {
            throw new InvalidArgumentException('Faltan datos obligatorios: ' . implode(', ', $camposFaltantes) . '.');
        }

        if ($this->fechaVencimiento < $this->fechaInicio) {
            throw new InvalidArgumentException('La fecha de vencimiento no puede ser anterior a la fecha de inicio.');
        }

        return new Membresia(
            $this->id,
            $this->tipo,
            $this->precio,
            $this->fechaInicio,
            $this->fechaVencimiento,
            $this->estado,
            $this->idAtleta
        );
    }

    private function fechaValida(string $fecha): bool
    {
        $dateTime = DateTime::createFromFormat('Y-m-d', $fecha);
        return $dateTime && $dateTime->format('Y-m-d') === $fecha;
    }
}
