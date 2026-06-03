package U2T3.datos;

/**
 * Simula un registro recibido desde una fuente externa, por ejemplo CSV o JSON.
 * La fuente no usa los mismos nombres del modelo interno del sistema.
 */
public class EstudianteExterno {
    private final int codigo;
    private final String nombreCompleto;
    private final int anios;

    public EstudianteExterno(int codigo, String nombreCompleto, int anios) {
        this.codigo = codigo;
        this.nombreCompleto = nombreCompleto;
        this.anios = anios;
    }

    public int getCodigo() {
        return codigo;
    }

    public String getNombreCompleto() {
        return nombreCompleto;
    }

    public int getAnios() {
        return anios;
    }
}

