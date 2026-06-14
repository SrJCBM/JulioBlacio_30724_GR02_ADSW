<?php

class RegistroProgreso
{
    private ?int $id;
    private string $fecha;
    private ?float $tiempo;
    private ?int $repeticiones;
    private ?float $peso;
    private float $puntuacion;
    private string $notas;
    private int $idAtleta;

    public function __construct(
        ?int $id,
        string $fecha,
        ?float $tiempo,
        ?int $repeticiones,
        ?float $peso,
        float $puntuacion,
        string $notas,
        int $idAtleta
    ) {
        $this->id = $id;
        $this->fecha = $fecha;
        $this->tiempo = $tiempo;
        $this->repeticiones = $repeticiones;
        $this->peso = $peso;
        $this->puntuacion = $puntuacion;
        $this->notas = $notas;
        $this->idAtleta = $idAtleta;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFecha(): string
    {
        return $this->fecha;
    }

    public function getTiempo(): ?float
    {
        return $this->tiempo;
    }

    public function getRepeticiones(): ?int
    {
        return $this->repeticiones;
    }

    public function getPeso(): ?float
    {
        return $this->peso;
    }

    public function getPuntuacion(): float
    {
        return $this->puntuacion;
    }

    public function getNotas(): string
    {
        return $this->notas;
    }

    public function getIdAtleta(): int
    {
        return $this->idAtleta;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'fecha' => $this->fecha,
            'tiempo' => $this->tiempo,
            'repeticiones' => $this->repeticiones,
            'peso' => $this->peso,
            'puntuacion' => $this->puntuacion,
            'notas' => $this->notas,
            'idAtleta' => $this->idAtleta,
        ];
    }

    public static function fromArray(array $fila): self
    {
        return new self(
            isset($fila['id']) ? (int) $fila['id'] : null,
            (string) $fila['fecha'],
            $fila['tiempo'] !== null ? (float) $fila['tiempo'] : null,
            $fila['repeticiones'] !== null ? (int) $fila['repeticiones'] : null,
            $fila['peso'] !== null ? (float) $fila['peso'] : null,
            (float) $fila['puntuacion'],
            (string) ($fila['notas'] ?? ''),
            (int) ($fila['id_atleta'] ?? $fila['idAtleta'])
        );
    }
}
