package U2T3.logica;

import U2T3.datos.EstudianteExterno;
import U2T3.modelo.Estudiante;

public interface AdaptadorEntradaEstudiante {
    Estudiante adaptar(EstudianteExterno entrada);
}

