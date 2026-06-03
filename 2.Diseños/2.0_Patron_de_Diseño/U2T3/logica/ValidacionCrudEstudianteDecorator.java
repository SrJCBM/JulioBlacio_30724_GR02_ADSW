package U2T3.logica;

import U2T3.modelo.Estudiante;

/**
 * Decorator: agrega validaciones y reglas academicas al CRUD base.
 */
public class ValidacionCrudEstudianteDecorator extends DecoradorCrudEstudiante {
    public ValidacionCrudEstudianteDecorator(ServicioCrudEstudiante servicio) {
        super(servicio);
    }

    @Override
    public String agregar(Estudiante estudiante) {
        String error = validarEstudiante(estudiante);
        if (error != null) return error;
        if (buscarPorId(estudiante.getId()) != null) {
            return "Error: Ya existe un estudiante con el ID " + estudiante.getId() + ".";
        }
        return super.agregar(estudiante);
    }

    @Override
    public String actualizar(Estudiante estudiante) {
        if (buscarPorId(estudiante.getId()) == null) {
            return "Error: No existe un estudiante con el ID " + estudiante.getId() + ".";
        }
        String error = validarEstudiante(estudiante);
        if (error != null) return "Error: Datos invalidos para la actualizacion.";
        return super.actualizar(estudiante);
    }

    @Override
    public String eliminar(int id) {
        Estudiante estudiante = buscarPorId(id);
        if (estudiante == null) {
            return "Error: No existe un estudiante con el ID " + id + ".";
        }
        if (estudiante.getEdad() < 18) {
            return "Error: Regla academica - no se puede eliminar a '"
                    + estudiante.getNombre() + "' porque es menor de 18 anios (Edad: "
                    + estudiante.getEdad() + ").";
        }
        return super.eliminar(id);
    }

    private String validarEstudiante(Estudiante estudiante) {
        if (estudiante == null || estudiante.getId() <= 0 || estudiante.getEdad() <= 0
                || estudiante.getNombre() == null || estudiante.getNombre().trim().isEmpty()) {
            return "Error: Datos invalidos. ID y Edad deben ser mayores a 0, Nombre no puede estar vacio.";
        }
        return null;
    }
}

