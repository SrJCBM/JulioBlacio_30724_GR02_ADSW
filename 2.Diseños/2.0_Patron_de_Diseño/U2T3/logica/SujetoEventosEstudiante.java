package U2T3.logica;

public interface SujetoEventosEstudiante {
    void agregarObservador(ObservadorEstudiante observador);
    void quitarObservador(ObservadorEstudiante observador);
    void notificarObservadores(EventoEstudiante evento);
}
