<?php

require_once __DIR__ . '/../models/Usuario.php';

class UsuarioBuilder
{
    private const ROLES_VALIDOS = ['Administrador', 'Entrenador', 'Atleta'];
    private const ESTADOS_VALIDOS = ['Activo', 'Inactivo'];

    private ?int $id = null;
    private ?string $nombre = null;
    private ?string $email = null;
    private ?string $contrasena = null;
    private ?string $rol = null;
    private ?string $estado = null;
    private ?string $fechaRegistro = null;

    public function conId(?int $id): self
    {
        if ($id !== null && $id <= 0) {
            throw new InvalidArgumentException('El id del usuario no es valido.');
        }

        $this->id = $id;
        return $this;
    }

    public function configurarNombre(string $nombre): self
    {
        $nombre = trim($nombre);
        if (strlen($nombre) < 3) {
            throw new InvalidArgumentException('El nombre debe tener al menos 3 caracteres.');
        }

        $this->nombre = $nombre;
        return $this;
    }

    public function configurarEmail(string $email): self
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El email no tiene un formato valido.');
        }

        $this->email = $email;
        return $this;
    }

    public function definirContrasena(string $contrasena): self
    {
        if (strlen($contrasena) < 6) {
            throw new InvalidArgumentException('La contrasena debe tener al menos 6 caracteres.');
        }

        $this->contrasena = password_hash($contrasena, PASSWORD_DEFAULT);
        return $this;
    }

    public function usarContrasenaHash(string $hash): self
    {
        if (trim($hash) === '') {
            throw new InvalidArgumentException('La contrasena almacenada no es valida.');
        }

        $this->contrasena = $hash;
        return $this;
    }

    public function asignarRol(string $rol): self
    {
        $rol = trim($rol);
        if (!in_array($rol, self::ROLES_VALIDOS, true)) {
            throw new InvalidArgumentException('El rol debe ser Administrador, Entrenador o Atleta.');
        }

        $this->rol = $rol;
        return $this;
    }

    public function definirEstado(string $estado): self
    {
        $estado = trim($estado);
        if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
            throw new InvalidArgumentException('El estado debe ser Activo o Inactivo.');
        }

        $this->estado = $estado;
        return $this;
    }

    public function definirFechaRegistro(?string $fechaRegistro = null): self
    {
        $fechaRegistro = $fechaRegistro ?: date('Y-m-d');
        $fecha = DateTime::createFromFormat('Y-m-d', $fechaRegistro);
        if (!$fecha || $fecha->format('Y-m-d') !== $fechaRegistro) {
            throw new InvalidArgumentException('La fecha de registro debe tener formato YYYY-MM-DD.');
        }

        $this->fechaRegistro = $fechaRegistro;
        return $this;
    }

    public function construir(): Usuario
    {
        $this->estado = $this->estado ?? 'Activo';
        $this->fechaRegistro = $this->fechaRegistro ?? date('Y-m-d');

        $camposFaltantes = [];
        foreach ([
            'nombre' => $this->nombre,
            'email' => $this->email,
            'contrasena' => $this->contrasena,
            'rol' => $this->rol,
            'estado' => $this->estado,
            'fechaRegistro' => $this->fechaRegistro,
        ] as $campo => $valor) {
            if ($valor === null || $valor === '') {
                $camposFaltantes[] = $campo;
            }
        }

        if ($camposFaltantes !== []) {
            throw new InvalidArgumentException('Faltan datos obligatorios: ' . implode(', ', $camposFaltantes) . '.');
        }

        return new Usuario(
            $this->id,
            $this->nombre,
            $this->email,
            $this->contrasena,
            $this->rol,
            $this->estado,
            $this->fechaRegistro
        );
    }
}
