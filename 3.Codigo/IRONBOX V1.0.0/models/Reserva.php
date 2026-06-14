<?php

class Reserva
{
    private ?int $id;
    private int $idAtleta;
    private int $idClase;
    private string $fechaReserva;
    private string $estado;

    public function __construct(
        ?int $id,
        int $idAtleta,
        int $idClase,
        string $fechaReserva,
        string $estado
    ) {
        $this->id = $id;
        $this->idAtleta = $idAtleta;
        $this->idClase = $idClase;
        $this->fechaReserva = $fechaReserva;
        $this->estado = $estado;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdAtleta(): int
    {
        return $this->idAtleta;
    }

    public function getIdClase(): int
    {
        return $this->idClase;
    }

    public function getFechaReserva(): string
    {
        return $this->fechaReserva;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'idAtleta' => $this->idAtleta,
            'idClase' => $this->idClase,
            'fechaReserva' => $this->fechaReserva,
            'estado' => $this->estado,
        ];
    }

    public static function fromArray(array $fila): self
    {
        return new self(
            isset($fila['id']) ? (int) $fila['id'] : null,
            (int) ($fila['id_atleta'] ?? $fila['idAtleta']),
            (int) ($fila['id_clase'] ?? $fila['idClase']),
            (string) ($fila['fecha_reserva'] ?? $fila['fechaReserva']),
            (string) $fila['estado']
        );
    }
}
