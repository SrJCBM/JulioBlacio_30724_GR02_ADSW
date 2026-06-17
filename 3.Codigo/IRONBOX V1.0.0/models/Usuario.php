<?php

class Usuario
{
    private ?int $id;
    private string $nombre;
    private string $cedula;
    private string $correo;
    private string $contrasena;
    private string $rol;
    private string $estado;
    private string $fechaRegistro;

    public function __construct(
        ?int $id,
        string $nombre,
        string $cedula,
        string $correo,
        string $contrasena,
        string $rol,
        string $estado,
        string $fechaRegistro
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->cedula = $cedula;
        $this->correo = $correo;
        $this->contrasena = $contrasena;
        $this->rol = $rol;
        $this->estado = $estado;
        $this->fechaRegistro = $fechaRegistro;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getCedula(): string
    {
        return $this->cedula;
    }

    public function getCorreo(): string
    {
        return $this->correo;
    }

    public function getContrasena(): string
    {
        return $this->contrasena;
    }

    public function getRol(): string
    {
        return $this->rol;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getFechaRegistro(): string
    {
        return $this->fechaRegistro;
    }

    public function toArray(bool $incluirContrasena = false): array
    {
        $usuario = [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'cedula' => $this->cedula,
            'correo' => $this->correo,
            'rol' => $this->rol,
            'estado' => $this->estado,
            'fechaRegistro' => $this->fechaRegistro,
        ];

        if ($incluirContrasena) {
            $usuario['contrasena'] = $this->contrasena;
        }

        return $usuario;
    }

    public static function fromArray(array $fila): self
    {
        return new self(
            isset($fila['id']) ? (int) $fila['id'] : null,
            (string) $fila['nombre'],
            (string) ($fila['cedula'] ?? ''),
            (string) ($fila['correo'] ?? $fila['email']),
            (string) ($fila['contrasena'] ?? $fila['contraseña'] ?? ''),
            (string) $fila['rol'],
            (string) $fila['estado'],
            (string) ($fila['fecha_registro'] ?? $fila['fechaRegistro'])
        );
    }
}
