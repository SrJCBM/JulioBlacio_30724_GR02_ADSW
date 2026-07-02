<?php

require_once __DIR__ . '/../builders/ReporteBuilder.php';
require_once __DIR__ . '/../dao/ReporteDAO.php';

class ReporteService
{
    private ReporteDAO $reporteDAO;

    public function __construct(?ReporteDAO $reporteDAO = null)
    {
        $this->reporteDAO = $reporteDAO ?? new ReporteDAO();
    }

    public function generar(array $filtros, bool $persistir = true): Reporte
    {
        $filtros = $this->normalizarFiltros($filtros);
        $filas = $filtros['tipo'] === 'Finanzas'
            ? $this->reporteDAO->consultarFinanzas($filtros['fechaInicio'], $filtros['fechaFin'])
            : $this->reporteDAO->consultarAsistencia($filtros['fechaInicio'], $filtros['fechaFin']);

        $reporte = (new ReporteBuilder())
            ->conTipo($filtros['tipo'])
            ->conRangoFechas($filtros['fechaInicio'], $filtros['fechaFin'])
            ->conFilas($filas)
            ->formatearDatos()
            ->construir();

        return $persistir ? $this->reporteDAO->guardar($reporte) : $reporte;
    }

    public function generarCsv(array $filtros): string
    {
        $reporte = $this->generar($filtros, false);
        $datos = $reporte->getDatos();
        $columnas = $datos['columnas'];
        $filas = $datos['filas'];

        $archivo = fopen('php://temp', 'r+');
        fputcsv($archivo, $columnas);

        foreach ($filas as $fila) {
            fputcsv($archivo, array_map(
                fn (string $columna): string => (string) ($fila[$columna] ?? ''),
                $columnas
            ));
        }

        rewind($archivo);
        $csv = stream_get_contents($archivo);
        fclose($archivo);

        return $csv ?: '';
    }

    public function prepararPdf(array $filtros): array
    {
        $reporte = $this->generar($filtros, false);

        return [
            'message' => 'Exportacion PDF preparada. En el prototipo se recomienda imprimir la vista desde el navegador.',
            'reporte' => $reporte->toArray(),
        ];
    }

    private function normalizarFiltros(array $filtros): array
    {
        $fechaFin = $filtros['fechaFin'] ?? $filtros['fecha_fin'] ?? date('Y-m-d');
        $fechaInicio = $filtros['fechaInicio'] ?? $filtros['fecha_inicio'] ?? date('Y-m-01');

        foreach (['fechaInicio' => $fechaInicio, 'fechaFin' => $fechaFin] as $campo => $valor) {
            $fecha = DateTime::createFromFormat('Y-m-d', (string) $valor);
            if (!$fecha || $fecha->format('Y-m-d') !== $valor) {
                throw new InvalidArgumentException('El campo ' . $campo . ' debe tener formato YYYY-MM-DD.');
            }
        }

        if ($fechaInicio > $fechaFin) {
            throw new InvalidArgumentException('La fecha de inicio no puede ser posterior a la fecha fin.');
        }

        return [
            'tipo' => $filtros['tipo'] ?? 'Finanzas',
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
        ];
    }
}
