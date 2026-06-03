package U2T3.logica;

import U2T3.datos.RepositorioEstudiante;
import U2T3.modelo.Estudiante;
import java.util.List;

/**
 * CRUD base: ejecuta persistencia en memoria sin reglas adicionales.
 * Los comportamientos extra se agregan con Decorator.
 */
public class CrudEstudianteBase implements ServicioCrudEstudiante {
    private final RepositorioEstudiante repo;

    public CrudEstudianteBase(RepositorioEstudiante repo) {
        this.repo = repo;
    }

    @Override
    public String agregar(Estudiante estudiante) {
        repo.guardar(estudiante);
        return "Agregado exitosamente: " + estudiante.getNombre();
    }

    @Override
    public String actualizar(Estudiante estudiante) {
        repo.actualizar(estudiante);
        return "Actualizado exitosamente: ID " + estudiante.getId();
    }

    @Override
    public String eliminar(int id) {
        Estudiante estudiante = repo.buscarPorId(id);
        repo.eliminar(id);
        return "Eliminado exitosamente: " + estudiante.getNombre() + " (ID " + id + ")";
    }

    @Override
    public Estudiante buscarPorId(int id) {
        return repo.buscarPorId(id);
    }

    @Override
    public List<Estudiante> listarTodos() {
        return repo.listarTodos();
    }
}

