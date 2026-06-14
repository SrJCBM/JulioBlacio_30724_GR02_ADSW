<?php

require_once __DIR__ . '/../builders/UsuarioBuilder.php';
require_once __DIR__ . '/../dao/UsuarioDAO.php';

class UsuarioService
{
    private UsuarioDAO $usuarioDAO;

    public function __construct(?UsuarioDAO $usuarioDAO = null)
    {
        $this->usuarioDAO = $usuarioDAO ?? new UsuarioDAO();
    }

    public function listar(): array
    {
        return $this->usuarioDAO->listar();
    }

    public function crear(array $datos): Usuario
    {
        $datos = $this->normalizarDatosCreacion($datos);

        if ($this->usuarioDAO->emailExiste($datos['email'])) {
            throw new DomainException('El email ya esta registrado.');
        }

        $usuario = (new UsuarioBuilder())
            ->configurarNombre($datos['nombre'])
            ->configurarEmail($datos['email'])
            ->definirContrasena($datos['contrasena'])
            ->asignarRol($datos['rol'])
            ->definirEstado($datos['estado'])
            ->definirFechaRegistro($datos['fechaRegistro'])
            ->construir();

        return $this->usuarioDAO->crear($usuario);
    }

    public function editar(int $id, array $datos): Usuario
    {
        $usuarioActual = $this->usuarioDAO->buscarPorId($id);
        if (!$usuarioActual) {
            throw new DomainException('El usuario solicitado no existe.');
        }

        $datos = $this->normalizarDatosEdicion($datos, $usuarioActual);

        if ($this->usuarioDAO->emailExiste($datos['email'], $id)) {
            throw new DomainException('El email ya esta registrado por otro usuario.');
        }

        $builder = (new UsuarioBuilder())
            ->conId($id)
            ->configurarNombre($datos['nombre'])
            ->configurarEmail($datos['email'])
            ->asignarRol($datos['rol'])
            ->definirEstado($datos['estado'])
            ->definirFechaRegistro($datos['fechaRegistro']);

        if ($datos['contrasena'] !== '') {
            $builder->definirContrasena($datos['contrasena']);
        } else {
            $builder->usarContrasenaHash($usuarioActual->getContrasena());
        }

        return $this->usuarioDAO->actualizar($builder->construir());
    }

    public function desactivar(int $id): void
    {
        $usuario = $this->usuarioDAO->buscarPorId($id);
        if (!$usuario) {
            throw new DomainException('El usuario solicitado no existe.');
        }

        if ($usuario->getEstado() === 'Inactivo') {
            throw new DomainException('El usuario ya se encuentra inactivo.');
        }

        if (!$this->usuarioDAO->desactivar($id)) {
            throw new DomainException('No se pudo desactivar el usuario.');
        }
    }

    private function normalizarDatosCreacion(array $datos): array
    {
        $normalizados = [
            'nombre' => $datos['nombre'] ?? '',
            'email' => $datos['email'] ?? '',
            'contrasena' => $datos['contrasena'] ?? $datos['contraseña'] ?? '',
            'rol' => $datos['rol'] ?? '',
            'estado' => $datos['estado'] ?? 'Activo',
            'fechaRegistro' => $datos['fechaRegistro'] ?? $datos['fecha_registro'] ?? date('Y-m-d'),
        ];

        foreach (['nombre', 'email', 'contrasena', 'rol'] as $campo) {
            if ($normalizados[$campo] === '') {
                throw new InvalidArgumentException('El campo ' . $campo . ' es obligatorio.');
            }
        }

        return $normalizados;
    }

    private function normalizarDatosEdicion(array $datos, Usuario $usuarioActual): array
    {
        return [
            'nombre' => $datos['nombre'] ?? $usuarioActual->getNombre(),
            'email' => $datos['email'] ?? $usuarioActual->getEmail(),
            'contrasena' => $datos['contrasena'] ?? $datos['contraseña'] ?? '',
            'rol' => $datos['rol'] ?? $usuarioActual->getRol(),
            'estado' => $datos['estado'] ?? $usuarioActual->getEstado(),
            'fechaRegistro' => $datos['fechaRegistro'] ?? $datos['fecha_registro'] ?? $usuarioActual->getFechaRegistro(),
        ];
    }
}
