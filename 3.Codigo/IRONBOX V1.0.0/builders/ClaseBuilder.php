<?php

require_once __DIR__ . '/../models/Clase.php';

class ClaseBuilder
{
    private ?int $id = null;
    private ?string $dia = null;
    private ?string $hora = null;
    private ?int $duracion = null;
    private ?int $cupoMaximo = null;
    private ?int $cuposDisponibles = null;
    private ?int $entrenadorId = null;

    public function conId(?int $id): self
    {
        if ($id !== null && $id <= 0) {
            throw new InvalidArgumentException('El id de la clase no es valido.');
        }

        $this->id = $id;
        return $this;
    }

    public function definirDiaHora(string $dia, string $hora): self
    {
        $dia = trim($dia);
        $hora = trim($hora);

        $fecha = DateTime::createFromFormat('Y-m-d', $dia);
        $fechaValida = $fecha && $fecha->format('Y-m-d') === $dia;
        if (!$fechaValida) {
            throw new InvalidArgumentException('El dia debe tener formato YYYY-MM-DD.');
        }

        $horaNormalizada = strlen($hora) === 5 ? $hora . ':00' : $hora;
        $tiempo = DateTime::createFromFormat('H:i:s', $horaNormalizada);
        $horaValida = $tiempo && $tiempo->format('H:i:s') === $horaNormalizada;
        if (!$horaValida) {
            throw new InvalidArgumentException('La hora debe tener formato HH:MM.');
        }

        $horaCorta = substr($horaNormalizada, 0, 5);
        $momento = DateTime::createFromFormat('Y-m-d H:i', $dia . ' ' . $horaCorta);
        if (!$momento || $momento < new DateTime('now')) {
            throw new InvalidArgumentException('La clase no puede programarse en una fecha u hora pasada.');
        }

        $this->dia = $dia;
        $this->hora = $horaCorta;
        return $this;
    }

    public function definirDuracion(int $duracion): self
    {
        if ($duracion <= 0) {
            throw new InvalidArgumentException('La duracion debe ser mayor a cero.');
        }

        if ($duracion > 240) {
            throw new InvalidArgumentException('La duracion no puede superar 240 minutos.');
        }

        $this->duracion = $duracion;
        return $this;
    }

    public function definirCupoMaximo(int $cupoMaximo, int $capacidadCrossfit): self
    {
        if ($cupoMaximo <= 0) {
            throw new InvalidArgumentException('El cupo maximo debe ser mayor a cero.');
        }

        if ($cupoMaximo > $capacidadCrossfit) {
            throw new InvalidArgumentException(
                'El cupo maximo no puede exceder la capacidad del crossfit (' . $capacidadCrossfit . ').'
            );
        }

        $this->cupoMaximo = $cupoMaximo;
        $this->cuposDisponibles = $this->cuposDisponibles ?? $cupoMaximo;
        return $this;
    }

    public function definirCuposDisponibles(?int $cuposDisponibles): self
    {
        if ($cuposDisponibles === null) {
            return $this;
        }

        if ($cuposDisponibles < 0) {
            throw new InvalidArgumentException('Los cupos disponibles no pueden ser negativos.');
        }

        $this->cuposDisponibles = $cuposDisponibles;
        return $this;
    }

    public function asignarEntrenador(int $entrenadorId): self
    {
        if ($entrenadorId <= 0) {
            throw new InvalidArgumentException('Debe asignar un entrenador valido.');
        }

        $this->entrenadorId = $entrenadorId;
        return $this;
    }

    public function construir(): Clase
    {
        $camposFaltantes = [];

        foreach ([
            'dia' => $this->dia,
            'hora' => $this->hora,
            'duracion' => $this->duracion,
            'cupoMaximo' => $this->cupoMaximo,
            'entrenadorId' => $this->entrenadorId,
        ] as $campo => $valor) {
            if ($valor === null) {
                $camposFaltantes[] = $campo;
            }
        }

        if ($camposFaltantes !== []) {
            throw new InvalidArgumentException('Faltan datos obligatorios: ' . implode(', ', $camposFaltantes) . '.');
        }

        $cuposDisponibles = $this->cuposDisponibles ?? $this->cupoMaximo;
        if ($cuposDisponibles > $this->cupoMaximo) {
            throw new InvalidArgumentException('Los cupos disponibles no pueden exceder el cupo maximo.');
        }

        return new Clase(
            $this->id,
            $this->dia,
            $this->hora,
            $this->duracion,
            $this->cupoMaximo,
            $cuposDisponibles,
            $this->entrenadorId
        );
    }
}
