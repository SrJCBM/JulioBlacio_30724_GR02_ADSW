<?php

class Mensaje
{
    private ?int $id;
    private string $contenido;
    private string $fechaEnvio;
    private string $tipo;
    private ?int $idAtleta;
    private ?int $idEntrenador;

    public function __construct(
        ?int $id,
        string $contenido,
        string $fechaEnvio,
        string $tipo,
        ?int $idAtleta,
        ?int $idEntrenador
    ) {
        $this->id = $id;
        $this->contenido = $contenido;
        $this->fechaEnvio = $fechaEnvio;
        $this->tipo = $tipo;
        $this->idAtleta = $idAtleta;
        $this->idEntrenador = $idEntrenador;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenido(): string
    {
        return $this->contenido;
    }

    public function getFechaEnvio(): string
    {
        return $this->fechaEnvio;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function getIdAtleta(): ?int
    {
        return $this->idAtleta;
    }

    public function getIdEntrenador(): ?int
    {
        return $this->idEntrenador;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'contenido' => $this->contenido,
            'fechaEnvio' => $this->fechaEnvio,
            'tipo' => $this->tipo,
            'idAtleta' => $this->idAtleta,
            'idEntrenador' => $this->idEntrenador,
        ];
    }

    public static function fromArray(array $fila): self
    {
        return new self(
            isset($fila['id']) ? (int) $fila['id'] : null,
            (string) $fila['contenido'],
            (string) ($fila['fecha_envio'] ?? $fila['fechaEnvio']),
            (string) $fila['tipo'],
            isset($fila['id_atleta']) ? (int) $fila['id_atleta'] : ($fila['idAtleta'] ?? null),
            isset($fila['id_entrenador']) ? (int) $fila['id_entrenador'] : ($fila['idEntrenador'] ?? null)
        );
    }
}
