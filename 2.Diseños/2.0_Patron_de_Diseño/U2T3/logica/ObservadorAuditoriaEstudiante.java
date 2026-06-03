package U2T3.logica;

import java.util.ArrayList;
import java.util.List;

public class ObservadorAuditoriaEstudiante implements ObservadorEstudiante {
    private final List<String> bitacora = new ArrayList<>();

    @Override
    public void actualizar(EventoEstudiante evento) {
        bitacora.add("AUDITORIA " + evento);
    }

    public List<String> getBitacora() {
        return new ArrayList<>(bitacora);
    }
}
