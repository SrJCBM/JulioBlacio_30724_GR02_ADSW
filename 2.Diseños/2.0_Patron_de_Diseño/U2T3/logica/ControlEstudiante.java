package U2T3.logica;

import U2T3.datos.RepositorioEstudiante;
import U2T3.datos.EstudianteExterno;
import U2T3.modelo.Estudiante;
import java.util.ArrayList;
import java.util.List;

/**
 * CAPA: Logica de Negocio
 * Responsabilidad: coordina el CRUD entre Presentacion y Datos.
 * Patrones aplicados:
 * Adapter: convierte datos externos codigo/nombreCompleto/anios al modelo interno Estudiante.
 * Decorator: agrega validacion, reglas academicas y auditoria al CRUD base.
 * Observer: notifica eventos del CRUD a observadores registrados.
 * Strategy: permite cambiar dinamicamente el algoritmo de busqueda.
 */
public class ControlEstudiante implements SujetoEventosEstudiante {
    private final ServicioCrudEstudiante servicioCrud;
    private final AdaptadorEntradaEstudiante adaptadorEntrada;
    private final List<ObservadorEstudiante> observadores = new ArrayList<>();
    private EstrategiaBusquedaEstudiante estrategiaBusqueda;

    public ControlEstudiante() {
        RepositorioEstudiante repo = new RepositorioEstudiante();
        ServicioCrudEstudiante base = new CrudEstudianteBase(repo);
        ServicioCrudEstudiante validado = new ValidacionCrudEstudianteDecorator(base);
        this.servicioCrud = new AuditoriaCrudEstudianteDecorator(validado);
        this.adaptadorEntrada = new AdaptadorEstudianteExterno();
        this.estrategiaBusqueda = new BusquedaPorIdStrategy();
    }

    // RF-01: CREATE usando Adapter para transformar la entrada externa al modelo interno.
    public String agregarDesdeEntradaExterna(int codigo, String nombreCompleto, int anios) {
        try {
            EstudianteExterno entrada = new EstudianteExterno(codigo, nombreCompleto, anios);
            Estudiante estudiante = adaptadorEntrada.adaptar(entrada);
            String resultado = servicioCrud.agregar(estudiante);
            if (esOperacionExitosa(resultado)) {
                notificarObservadores(new EventoEstudiante(
                        TipoEventoEstudiante.REGISTRADO,
                        estudiante,
                        resultado
                ));
            }
            return resultado;
        } catch (IllegalArgumentException ex) {
            return "Error: " + ex.getMessage();
        }
    }

    // RF-04: READ
    public List<Estudiante> listarTodos() {
        return servicioCrud.listarTodos();
    }

    // RF-02: UPDATE usando la cadena Decorator del CRUD.
    public String actualizarEstudiante(int id, String nuevoNombre, int nuevaEdad) {
        Estudiante estudiante = new Estudiante(id, nuevoNombre, nuevaEdad);
        String resultado = servicioCrud.actualizar(estudiante);
        if (esOperacionExitosa(resultado)) {
            notificarObservadores(new EventoEstudiante(
                    TipoEventoEstudiante.ACTUALIZADO,
                    estudiante,
                    resultado
            ));
        }
        return resultado;
    }

    // RF-03: DELETE usando la cadena Decorator del CRUD.
    public String eliminarEstudiante(int id) {
        Estudiante estudiante = servicioCrud.buscarPorId(id);
        String resultado = servicioCrud.eliminar(id);
        if (esOperacionExitosa(resultado) && estudiante != null) {
            notificarObservadores(new EventoEstudiante(
                    TipoEventoEstudiante.ELIMINADO,
                    estudiante,
                    resultado
            ));
        }
        return resultado;
    }

    public void cambiarEstrategiaBusqueda(EstrategiaBusquedaEstudiante estrategiaBusqueda) {
        if (estrategiaBusqueda == null) {
            throw new IllegalArgumentException("La estrategia de busqueda no puede ser nula.");
        }
        this.estrategiaBusqueda = estrategiaBusqueda;
    }

    public String getNombreEstrategiaBusqueda() {
        return estrategiaBusqueda.getNombre();
    }

    public List<Estudiante> buscarEstudiantes(String criterio) {
        return estrategiaBusqueda.buscar(listarTodos(), criterio);
    }

    @Override
    public void agregarObservador(ObservadorEstudiante observador) {
        if (observador != null && !observadores.contains(observador)) {
            observadores.add(observador);
        }
    }

    @Override
    public void quitarObservador(ObservadorEstudiante observador) {
        observadores.remove(observador);
    }

    @Override
    public void notificarObservadores(EventoEstudiante evento) {
        for (ObservadorEstudiante observador : observadores) {
            observador.actualizar(evento);
        }
    }

    private boolean esOperacionExitosa(String resultado) {
        return resultado != null && !resultado.startsWith("Error:");
    }
}

