package U2T3.logica;

public class ObservadorNotificacionEstudiante implements ObservadorEstudiante {
    private String ultimaNotificacion = "";

    @Override
    public void actualizar(EventoEstudiante evento) {
        ultimaNotificacion = "NOTIFICACION: evento " + evento.getTipo()
                + " para estudiante ID " + evento.getEstudiante().getId();
    }

    public String getUltimaNotificacion() {
        return ultimaNotificacion;
    }
}
