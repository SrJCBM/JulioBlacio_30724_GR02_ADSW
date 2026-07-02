<?php

require_once __DIR__ . '/../models/Usuario.php';

class UsuarioBuilder
{
    private const ROLES_VALIDOS = ['Administrador', 'Entrenador', 'Atleta'];
    private const ESTADOS_VALIDOS = ['Activo', 'Inactivo'];

    private ?int $id = null;
    private ?string $nombre = null;
    private ?string $cedula = null;
    private ?string $correo = null;
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

        if (strlen($nombre) > 120) {
            throw new InvalidArgumentException('El nombre no puede superar 120 caracteres.');
        }

        $this->nombre = $nombre;
        return $this;
    }

    public function configurarCedula(string $cedula): self
    {
        $cedula = preg_replace('/\D+/', '', $cedula);
        if (!$this->cedulaEcuatorianaValida($cedula)) {
            throw new InvalidArgumentException('La cedula ecuatoriana no es valida.');
        }

        $this->cedula = $cedula;
        return $this;
    }

    public function configurarCorreo(string $correo): self
    {
        $correo = strtolower(trim($correo));
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El correo no tiene un formato valido.');
        }

        $this->correo = $correo;
        return $this;
    }

    public function definirContrasena(string $contrasena): self
    {
        if (strlen($contrasena) < 8) {
            throw new InvalidArgumentException('La contrasena debe tener al menos 8 caracteres.');
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
            'cedula' => $this->cedula,
            'correo' => $this->correo,
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
            $this->cedula,
            $this->correo,
            $this->contrasena,
            $this->rol,
            $this->estado,
            $this->fechaRegistro
        );
    }

    private function cedulaEcuatorianaValida(string $cedula): bool
    {
        if (!preg_match('/^\d{10}$/', $cedula)) {
            return false;
        }

        $provincia = (int) substr($cedula, 0, 2);
        $tercerDigito = (int) $cedula[2];
        if ($provincia < 1 || $provincia > 24 || $tercerDigito > 5) {
            return false;
        }

        $suma = 0;
        for ($i = 0; $i < 9; $i++) {
            $digito = (int) $cedula[$i];
            if ($i % 2 === 0) {
                $digito *= 2;
                if ($digito > 9) {
                    $digito -= 9;
                }
            }
            $suma += $digito;
        }

        $verificador = (10 - ($suma % 10)) % 10;
        return $verificador === (int) $cedula[9];
    }
}
