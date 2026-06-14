<?php

require_once __DIR__ . '/../models/RegistroProgreso.php';

class RegistroProgresoBuilder
{
    private ?int $id = null;
    private ?string $fecha = null;
    private ?float $tiempo = null;
    private ?int $repeticiones = null;
    private ?float $peso = null;
    private ?float $puntuacion = null;
    private string $notas = '';
    private ?int $idAtleta = null;

    public function conId(?int $id): self
    {
        if ($id !== null && $id <= 0) {
            throw new InvalidArgumentException('El id del registro no es valido.');
        }

        $this->id = $id;
        return $this;
    }

    public function asignarAtleta(int $idAtleta): self
    {
        if ($idAtleta <= 0) {
            throw new InvalidArgumentException('Debe seleccionar un atleta valido.');
        }

        $this->idAtleta = $idAtleta;
        return $this;
    }

    public function definirFecha(string $fecha): self
    {
        $fecha = trim($fecha);
        $fechaValida = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$fechaValida || $fechaValida->format('Y-m-d') !== $fecha) {
            throw new InvalidArgumentException('La fecha debe tener formato YYYY-MM-DD.');
        }

        $this->fecha = $fecha;
        return $this;
    }

    public function registrarTiempo(?float $tiempo): self
    {
        if ($tiempo === null) {
            return $this;
        }

        if ($tiempo <= 0) {
            throw new InvalidArgumentException('El tiempo debe ser mayor a cero.');
        }

        $this->tiempo = round($tiempo, 2);
        return $this;
    }

    public function registrarRepeticiones(?int $repeticiones): self
    {
        if ($repeticiones === null) {
            return $this;
        }

        if ($repeticiones <= 0) {
            throw new InvalidArgumentException('Las repeticiones deben ser mayores a cero.');
        }

        $this->repeticiones = $repeticiones;
        return $this;
    }

    public function registrarPeso(?float $peso): self
    {
        if ($peso === null) {
            return $this;
        }

        if ($peso <= 0) {
            throw new InvalidArgumentException('El peso debe ser mayor a cero.');
        }

        $this->peso = round($peso, 2);
        return $this;
    }

    public function definirPuntuacion(?float $puntuacion): self
    {
        if ($puntuacion === null) {
            return $this;
        }

        if ($puntuacion < 0) {
            throw new InvalidArgumentException('La puntuacion no puede ser negativa.');
        }

        $this->puntuacion = round($puntuacion, 2);
        return $this;
    }

    public function agregarNotas(?string $notas): self
    {
        $this->notas = trim((string) $notas);
        return $this;
    }

    public function construir(): RegistroProgreso
    {
        $camposFaltantes = [];
        foreach ([
            'fecha' => $this->fecha,
            'idAtleta' => $this->idAtleta,
        ] as $campo => $valor) {
            if ($valor === null || $valor === '') {
                $camposFaltantes[] = $campo;
            }
        }

        if ($camposFaltantes !== []) {
            throw new InvalidArgumentException('Faltan datos obligatorios: ' . implode(', ', $camposFaltantes) . '.');
        }

        if ($this->tiempo === null && $this->repeticiones === null && $this->peso === null) {
            throw new InvalidArgumentException('Debe registrar al menos tiempo, repeticiones o peso.');
        }

        return new RegistroProgreso(
            $this->id,
            $this->fecha,
            $this->tiempo,
            $this->repeticiones,
            $this->peso,
            $this->puntuacion ?? $this->calcularPuntuacionBase(),
            $this->notas,
            $this->idAtleta
        );
    }

    private function calcularPuntuacionBase(): float
    {
        $puntos = 0.0;

        if ($this->repeticiones !== null) {
            $puntos += $this->repeticiones;
        }

        if ($this->peso !== null) {
            $puntos += $this->peso;
        }

        if ($this->tiempo !== null) {
            $puntos += max(0, 100 - $this->tiempo);
        }

        return round($puntos, 2);
    }
}
