package U2T3.logica;

import U2T3.modelo.Estudiante;
import java.util.List;

public interface ServicioCrudEstudiante {
    String agregar(Estudiante estudiante);
    String actualizar(Estudiante estudiante);
    String eliminar(int id);
    Estudiante buscarPorId(int id);
    List<Estudiante> listarTodos();
}

