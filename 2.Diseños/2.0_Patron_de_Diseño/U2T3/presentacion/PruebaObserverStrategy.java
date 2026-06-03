package U2T3.presentacion;

import U2T3.logica.BusquedaPorIdStrategy;
import U2T3.logica.BusquedaPorNombreStrategy;
import U2T3.logica.ControlEstudiante;
import U2T3.logica.ObservadorAuditoriaEstudiante;
import U2T3.logica.ObservadorNotificacionEstudiante;

public class PruebaObserverStrategy {
    public static void main(String[] args) {
        ControlEstudiante control = new ControlEstudiante();
        ObservadorAuditoriaEstudiante auditoria = new ObservadorAuditoriaEstudiante();
        ObservadorNotificacionEstudiante notificacion = new ObservadorNotificacionEstudiante();

        control.agregarObservador(auditoria);
        control.agregarObservador(notificacion);

        System.out.println("=== Observer: eventos del CRUD ===");
        System.out.println(control.agregarDesdeEntradaExterna(1, "Ana Torres", 20));
        System.out.println(notificacion.getUltimaNotificacion());
        System.out.println(control.agregarDesdeEntradaExterna(2, "Luis Perez", 17));
        System.out.println(notificacion.getUltimaNotificacion());
        System.out.println(control.actualizarEstudiante(1, "Ana Torres Actualizada", 21));
        System.out.println(notificacion.getUltimaNotificacion());
        System.out.println(control.eliminarEstudiante(2));
        System.out.println(control.eliminarEstudiante(1));
        System.out.println(notificacion.getUltimaNotificacion());

        System.out.println("\n=== Bitacora de auditoria ===");
        auditoria.getBitacora().forEach(System.out::println);

        System.out.println("\n=== Strategy: busqueda dinamica ===");
        control.agregarDesdeEntradaExterna(3, "Carlos Andrade", 22);
        control.agregarDesdeEntradaExterna(4, "Carolina Mena", 23);

        control.cambiarEstrategiaBusqueda(new BusquedaPorIdStrategy());
        System.out.println(control.getNombreEstrategiaBusqueda() + " criterio=3");
        control.buscarEstudiantes("3").forEach(System.out::println);

        control.cambiarEstrategiaBusqueda(new BusquedaPorNombreStrategy());
        System.out.println(control.getNombreEstrategiaBusqueda() + " criterio=Caro");
        control.buscarEstudiantes("Caro").forEach(System.out::println);
    }
}
