<?php

class Membresia
{
    private ?int $id;
    private string $tipo;
    private float $precio;
    private string $fechaInicio;
    private string $fechaVencimiento;
    private string $estado;
    private int $idAtleta;

    public function __construct(
        ?int $id,
        string $tipo,
        float $precio,
        string $fechaInicio,
        string $fechaVencimiento,
        string $estado,
        int $idAtleta
    ) {
        $this->id = $id;
        $this->tipo = $tipo;
        $this->precio = $precio;
        $this->fechaInicio = $fechaInicio;
        $this->fechaVencimiento = $fechaVencimiento;
        $this->estado = $estado;
        $this->idAtleta = $idAtleta;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function getPrecio(): float
    {
        return $this->precio;
    }

    public function getFechaInicio(): string
    {
        return $this->fechaInicio;
    }

    public function getFechaVencimiento(): string
    {
        return $this->fechaVencimiento;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getIdAtleta(): int
    {
        return $this->idAtleta;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'precio' => $this->precio,
            'fechaInicio' => $this->fechaInicio,
            'fechaVencimiento' => $this->fechaVencimiento,
            'estado' => $this->estado,
            'idAtleta' => $this->idAtleta,
        ];
    }

    public static function fromArray(array $fila): self
    {
        return new self(
            isset($fila['id']) ? (int) $fila['id'] : null,
            (string) $fila['tipo'],
            (float) $fila['precio'],
            (string) ($fila['fecha_inicio'] ?? $fila['fechaInicio']),
            (string) ($fila['fecha_vencimiento'] ?? $fila['fechaVencimiento']),
            (string) $fila['estado'],
            (int) ($fila['id_atleta'] ?? $fila['idAtleta'])
        );
    }
}
