<?php

class Clase
{
    private ?int $id;
    private string $dia;
    private string $hora;
    private int $duracion;
    private int $cupoMaximo;
    private int $cuposDisponibles;
    private int $entrenadorId;

    public function __construct(
        ?int $id,
        string $dia,
        string $hora,
        int $duracion,
        int $cupoMaximo,
        int $cuposDisponibles,
        int $entrenadorId
    ) {
        $this->id = $id;
        $this->dia = $dia;
        $this->hora = $hora;
        $this->duracion = $duracion;
        $this->cupoMaximo = $cupoMaximo;
        $this->cuposDisponibles = $cuposDisponibles;
        $this->entrenadorId = $entrenadorId;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDia(): string
    {
        return $this->dia;
    }

    public function getHora(): string
    {
        return $this->hora;
    }

    public function getDuracion(): int
    {
        return $this->duracion;
    }

    public function getCupoMaximo(): int
    {
        return $this->cupoMaximo;
    }

    public function getCuposDisponibles(): int
    {
        return $this->cuposDisponibles;
    }

    public function getEntrenadorId(): int
    {
        return $this->entrenadorId;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'dia' => $this->dia,
            'hora' => $this->hora,
            'duracion' => $this->duracion,
            'cupoMaximo' => $this->cupoMaximo,
            'cuposDisponibles' => $this->cuposDisponibles,
            'entrenadorId' => $this->entrenadorId,
        ];
    }

    public static function fromArray(array $fila): self
    {
        return new self(
            isset($fila['id']) ? (int) $fila['id'] : null,
            (string) $fila['dia'],
            (string) $fila['hora'],
            (int) $fila['duracion'],
            (int) ($fila['cupo_maximo'] ?? $fila['cupoMaximo']),
            (int) ($fila['cupos_disponibles'] ?? $fila['cuposDisponibles']),
            (int) ($fila['entrenador_id'] ?? $fila['entrenadorId'])
        );
    }
}
