package U2T3.logica;

import U2T3.modelo.Estudiante;
import java.util.List;

public interface EstrategiaBusquedaEstudiante {
    List<Estudiante> buscar(List<Estudiante> estudiantes, String criterio);
    String getNombre();
}
