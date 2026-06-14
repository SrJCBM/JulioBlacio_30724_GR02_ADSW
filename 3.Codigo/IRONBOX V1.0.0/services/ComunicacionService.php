<?php

require_once __DIR__ . '/../models/Mensaje.php';
require_once __DIR__ . '/../dao/ComunicacionDAO.php';

class ComunicacionService
{
    private const TIPOS_VALIDOS = ['Mensaje', 'Anuncio'];

    private ComunicacionDAO $comunicacionDAO;

    public function __construct(?ComunicacionDAO $comunicacionDAO = null)
    {
        $this->comunicacionDAO = $comunicacionDAO ?? new ComunicacionDAO();
    }

    public function listarAtletas(): array
    {
        return $this->comunicacionDAO->listarAtletas();
    }

    public function listarEntrenadores(): array
    {
        return $this->comunicacionDAO->listarEntrenadores();
    }

    public function enviar(array $datos): Mensaje
    {
        $datos = $this->normalizarDatosEnvio($datos);

        if (!$this->comunicacionDAO->entrenadorExiste($datos['idEntrenador'])) {
            throw new DomainException('El entrenador remitente no existe.');
        }

        if ($datos['tipo'] === 'Mensaje' && !$this->comunicacionDAO->atletaExiste((int) $datos['idAtleta'])) {
            throw new DomainException('El atleta destinatario no existe.');
        }

        $mensaje = new Mensaje(
            null,
            $datos['contenido'],
            date('Y-m-d H:i:s'),
            $datos['tipo'],
            $datos['tipo'] === 'Anuncio' ? null : (int) $datos['idAtleta'],
            $datos['idEntrenador']
        );

        return $this->comunicacionDAO->crear($mensaje);
    }

    public function listarRecibidos(int $idAtleta): array
    {
        if ($idAtleta <= 0 || !$this->comunicacionDAO->atletaExiste($idAtleta)) {
            throw new InvalidArgumentException('Debe seleccionar un atleta valido.');
        }

        return $this->comunicacionDAO->listarRecibidosPorAtleta($idAtleta);
    }

    public function listarHistorial(?int $idEntrenador = null): array
    {
        if ($idEntrenador !== null && $idEntrenador > 0 && !$this->comunicacionDAO->entrenadorExiste($idEntrenador)) {
            throw new InvalidArgumentException('Debe seleccionar un entrenador valido.');
        }

        return $this->comunicacionDAO->listarHistorialEntrenador($idEntrenador && $idEntrenador > 0 ? $idEntrenador : null);
    }

    private function normalizarDatosEnvio(array $datos): array
    {
        $tipo = trim($datos['tipo'] ?? 'Mensaje');
        $contenido = trim($datos['contenido'] ?? '');
        $idEntrenador = (int) ($datos['idEntrenador'] ?? $datos['id_entrenador'] ?? 0);
        $idAtleta = $datos['idAtleta'] ?? $datos['id_atleta'] ?? null;

        if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
            throw new InvalidArgumentException('El tipo debe ser Mensaje o Anuncio.');
        }

        if ($contenido === '') {
            throw new InvalidArgumentException('El contenido del mensaje es obligatorio.');
        }

        if (strlen($contenido) > 1000) {
            throw new InvalidArgumentException('El mensaje no puede superar 1000 caracteres.');
        }

        if ($idEntrenador <= 0) {
            throw new InvalidArgumentException('Debe seleccionar un entrenador remitente.');
        }

        if ($tipo === 'Mensaje' && (int) $idAtleta <= 0) {
            throw new InvalidArgumentException('Debe seleccionar un atleta destinatario.');
        }

        return [
            'tipo' => $tipo,
            'contenido' => $contenido,
            'idEntrenador' => $idEntrenador,
            'idAtleta' => $idAtleta !== null ? (int) $idAtleta : null,
        ];
    }
}
