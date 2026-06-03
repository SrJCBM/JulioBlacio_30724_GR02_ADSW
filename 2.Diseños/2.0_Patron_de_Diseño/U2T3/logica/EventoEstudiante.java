package U2T3.logica;

import U2T3.modelo.Estudiante;

public class EventoEstudiante {
    private final TipoEventoEstudiante tipo;
    private final Estudiante estudiante;
    private final String mensaje;

    public EventoEstudiante(TipoEventoEstudiante tipo, Estudiante estudiante, String mensaje) {
        this.tipo = tipo;
        this.estudiante = estudiante;
        this.mensaje = mensaje;
    }

    public TipoEventoEstudiante getTipo() {
        return tipo;
    }

    public Estudiante getEstudiante() {
        return estudiante;
    }

    public String getMensaje() {
        return mensaje;
    }

    @Override
    public String toString() {
        return "[" + tipo + "] " + estudiante + " - " + mensaje;
    }
}
