package U2T3.logica;

import U2T3.modelo.Estudiante;
import java.util.List;
import java.util.stream.Collectors;

public class BusquedaPorIdStrategy implements EstrategiaBusquedaEstudiante {
    @Override
    public List<Estudiante> buscar(List<Estudiante> estudiantes, String criterio) {
        int id = Integer.parseInt(criterio.trim());
        return estudiantes.stream()
                .filter(estudiante -> estudiante.getId() == id)
                .collect(Collectors.toList());
    }

    @Override
    public String getNombre() {
        return "Busqueda por ID";
    }
}
