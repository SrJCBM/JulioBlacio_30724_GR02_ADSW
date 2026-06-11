<?php

class Reporte
{
    private ?int $id;
    private string $tipo;
    private array $datos;
    private string $fechaGeneracion;

    public function __construct(?int $id, string $tipo, array $datos, string $fechaGeneracion)
    {
        $this->id = $id;
        $this->tipo = $tipo;
        $this->datos = $datos;
        $this->fechaGeneracion = $fechaGeneracion;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function getDatos(): array
    {
        return $this->datos;
    }

    public function getFechaGeneracion(): string
    {
        return $this->fechaGeneracion;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'datos' => $this->datos,
            'fechaGeneracion' => $this->fechaGeneracion,
        ];
    }

    public static function fromArray(array $fila): self
    {
        $datos = $fila['datos'];
        if (is_string($datos)) {
            $datos = json_decode($datos, true) ?: [];
        }

        return new self(
            isset($fila['id']) ? (int) $fila['id'] : null,
            (string) $fila['tipo'],
            $datos,
            (string) ($fila['fecha_generacion'] ?? $fila['fechaGeneracion'])
        );
    }
}
