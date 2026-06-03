package U2T3.logica;

import U2T3.modelo.Estudiante;
import java.util.List;
import java.util.Locale;
import java.util.stream.Collectors;

public class BusquedaPorNombreStrategy implements EstrategiaBusquedaEstudiante {
    @Override
    public List<Estudiante> buscar(List<Estudiante> estudiantes, String criterio) {
        String texto = criterio == null ? "" : criterio.toLowerCase(Locale.ROOT).trim();
        return estudiantes.stream()
                .filter(estudiante -> estudiante.getNombre().toLowerCase(Locale.ROOT).contains(texto))
                .collect(Collectors.toList());
    }

    @Override
    public String getNombre() {
        return "Busqueda por Nombre";
    }
}
