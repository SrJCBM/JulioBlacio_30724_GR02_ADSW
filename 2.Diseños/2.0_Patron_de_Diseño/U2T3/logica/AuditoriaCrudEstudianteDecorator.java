package U2T3.logica;

import U2T3.modelo.Estudiante;

/**
 * Decorator: agrega mensajes de auditoria sin modificar el CRUD base.
 */
public class AuditoriaCrudEstudianteDecorator extends DecoradorCrudEstudiante {
    public AuditoriaCrudEstudianteDecorator(ServicioCrudEstudiante servicio) {
        super(servicio);
    }

    @Override
    public String agregar(Estudiante estudiante) {
        return auditar(super.agregar(estudiante), "CREATE");
    }

    @Override
    public String actualizar(Estudiante estudiante) {
        return auditar(super.actualizar(estudiante), "UPDATE");
    }

    @Override
    public String eliminar(int id) {
        return auditar(super.eliminar(id), "DELETE");
    }

    private String auditar(String resultado, String operacion) {
        if (resultado.startsWith("Error:")) {
            return resultado;
        }
        return resultado + " [Decorator: auditoria " + operacion + "]";
    }
}

