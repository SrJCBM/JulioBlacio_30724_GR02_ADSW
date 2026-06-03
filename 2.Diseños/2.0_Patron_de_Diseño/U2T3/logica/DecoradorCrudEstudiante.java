package U2T3.logica;

import U2T3.modelo.Estudiante;
import java.util.List;

public abstract class DecoradorCrudEstudiante implements ServicioCrudEstudiante {
    protected final ServicioCrudEstudiante servicio;

    protected DecoradorCrudEstudiante(ServicioCrudEstudiante servicio) {
        this.servicio = servicio;
    }

    @Override
    public String agregar(Estudiante estudiante) {
        return servicio.agregar(estudiante);
    }

    @Override
    public String actualizar(Estudiante estudiante) {
        return servicio.actualizar(estudiante);
    }

    @Override
    public String eliminar(int id) {
        return servicio.eliminar(id);
    }

    @Override
    public Estudiante buscarPorId(int id) {
        return servicio.buscarPorId(id);
    }

    @Override
    public List<Estudiante> listarTodos() {
        return servicio.listarTodos();
    }
}

