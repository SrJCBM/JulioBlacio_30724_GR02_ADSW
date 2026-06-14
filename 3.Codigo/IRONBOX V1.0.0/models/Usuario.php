<?php

class Usuario
{
    private ?int $id;
    private string $nombre;
    private string $email;
    private string $contrasena;
    private string $rol;
    private string $estado;
    private string $fechaRegistro;

    public function __construct(
        ?int $id,
        string $nombre,
        string $email,
        string $contrasena,
        string $rol,
        string $estado,
        string $fechaRegistro
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->email = $email;
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

    public function getEmail(): string
    {
        return $this->email;
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
            'email' => $this->email,
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
            (string) $fila['email'],
            (string) ($fila['contrasena'] ?? $fila['contraseña'] ?? ''),
            (string) $fila['rol'],
            (string) $fila['estado'],
            (string) ($fila['fecha_registro'] ?? $fila['fechaRegistro'])
        );
    }
}
