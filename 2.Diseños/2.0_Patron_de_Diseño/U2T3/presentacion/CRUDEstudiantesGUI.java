package U2T3.presentacion;

import U2T3.logica.BusquedaPorIdStrategy;
import U2T3.logica.BusquedaPorNombreStrategy;
import U2T3.logica.ControlEstudiante;
import U2T3.logica.ObservadorNotificacionEstudiante;
import U2T3.modelo.Estudiante;

import javax.swing.*;
import java.awt.*;
import java.awt.event.ActionEvent;
import java.util.List;

/**
 * CAPA: Presentacion
 * Responsabilidad: interfaz grafica para que el Administrador Academico
 * interactue con el CRUD de estudiantes.
 */
public class CRUDEstudiantesGUI extends JFrame {
    private final ControlEstudiante control;
    private final ObservadorNotificacionEstudiante observadorNotificacion;

    private final JTextField txtId = new JTextField(10);
    private final JTextField txtNombre = new JTextField(20);
    private final JTextField txtEdad = new JTextField(5);
    private final JComboBox<String> cmbEstrategia = new JComboBox<>(new String[]{"ID", "Nombre"});
    private final JTextArea areaSalida = new JTextArea(12, 40);

    public CRUDEstudiantesGUI() {
        this.control = new ControlEstudiante();
        this.observadorNotificacion = new ObservadorNotificacionEstudiante();
        this.control.agregarObservador(observadorNotificacion);

        setTitle("CRUD Estudiantes - Observer y Strategy");
        setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        initLayout();
        pack();
        setLocationRelativeTo(null);
    }

    private void initLayout() {
        JPanel panel = new JPanel(new BorderLayout(8, 8));

        JPanel form = new JPanel(new FlowLayout(FlowLayout.LEFT));
        form.add(new JLabel("Codigo:")); form.add(txtId);
        form.add(new JLabel("Nombre completo:")); form.add(txtNombre);
        form.add(new JLabel("Anios:")); form.add(txtEdad);
        form.add(new JLabel("Buscar por:")); form.add(cmbEstrategia);

        JPanel botones = new JPanel(new FlowLayout(FlowLayout.LEFT));
        JButton btnAgregarAdapter = new JButton("Agregar externo (Adapter)");
        JButton btnActualizar = new JButton("Actualizar");
        JButton btnEliminar = new JButton("Eliminar");
        JButton btnListar = new JButton("Listar Todos");
        JButton btnBuscar = new JButton("Buscar (Strategy)");

        botones.add(btnAgregarAdapter);
        botones.add(btnActualizar);
        botones.add(btnEliminar);
        botones.add(btnListar);
        botones.add(btnBuscar);

        areaSalida.setEditable(false);
        JScrollPane scroll = new JScrollPane(areaSalida);

        panel.add(form, BorderLayout.NORTH);
        panel.add(botones, BorderLayout.CENTER);
        panel.add(scroll, BorderLayout.SOUTH);

        setContentPane(panel);

        btnAgregarAdapter.addActionListener(this::onAgregarAdapter);
        btnActualizar.addActionListener(this::onActualizar);
        btnEliminar.addActionListener(this::onEliminar);
        btnListar.addActionListener(e -> actualizarLista());
        btnBuscar.addActionListener(this::onBuscar);
    }

    private void onAgregarAdapter(ActionEvent e) {
        try {
            int id = Integer.parseInt(txtId.getText().trim());
            String nombre = txtNombre.getText().trim();
            int edad = Integer.parseInt(txtEdad.getText().trim());
            String res = control.agregarDesdeEntradaExterna(id, nombre, edad);
            mostrarMensaje(res);
            actualizarLista();
        } catch (NumberFormatException ex) {
            mostrarMensaje("ID y Edad deben ser numeros enteros.");
        }
    }

    private void onActualizar(ActionEvent e) {
        try {
            int id = Integer.parseInt(txtId.getText().trim());
            String nombre = txtNombre.getText().trim();
            int edad = Integer.parseInt(txtEdad.getText().trim());
            String res = control.actualizarEstudiante(id, nombre, edad);
            mostrarMensaje(res);
            actualizarLista();
        } catch (NumberFormatException ex) {
            mostrarMensaje("ID y Edad deben ser numeros enteros.");
        }
    }

    private void onEliminar(ActionEvent e) {
        try {
            int id = Integer.parseInt(txtId.getText().trim());
            String res = control.eliminarEstudiante(id);
            mostrarMensaje(res);
            actualizarLista();
        } catch (NumberFormatException ex) {
            mostrarMensaje("ID debe ser un numero entero.");
        }
    }

    private void onBuscar(ActionEvent e) {
        String opcion = (String) cmbEstrategia.getSelectedItem();
        String criterio = "Nombre".equals(opcion) ? txtNombre.getText().trim() : txtId.getText().trim();

        if ("Nombre".equals(opcion)) {
            control.cambiarEstrategiaBusqueda(new BusquedaPorNombreStrategy());
        } else {
            control.cambiarEstrategiaBusqueda(new BusquedaPorIdStrategy());
        }

        try {
            List<Estudiante> resultados = control.buscarEstudiantes(criterio);
            StringBuilder sb = new StringBuilder("--- RESULTADOS: ")
                    .append(control.getNombreEstrategiaBusqueda())
                    .append(" ---\n");
            if (resultados.isEmpty()) {
                sb.append("(Sin coincidencias)");
            } else {
                for (Estudiante estudiante : resultados) {
                    sb.append(estudiante).append('\n');
                }
            }
            areaSalida.setText(sb.toString());
        } catch (NumberFormatException ex) {
            mostrarMensaje("Para buscar por ID ingrese un numero entero en Codigo.");
        }
    }

    private void actualizarLista() {
        List<Estudiante> lista = control.listarTodos();
        StringBuilder sb = new StringBuilder();
        sb.append("--- LISTA DE ESTUDIANTES ---\n");
        if (lista.isEmpty()) {
            sb.append("(No hay estudiantes registrados)");
        } else {
            for (Estudiante est : lista) sb.append(est).append('\n');
        }
        areaSalida.setText(sb.toString());
    }

    private void mostrarMensaje(String msg) {
        String notificacion = observadorNotificacion.getUltimaNotificacion();
        if (notificacion != null && !notificacion.isEmpty() && !msg.startsWith("Error:")) {
            JOptionPane.showMessageDialog(this, msg + "\n" + notificacion);
        } else {
            JOptionPane.showMessageDialog(this, msg);
        }
    }

    public static void main(String[] args) {
        SwingUtilities.invokeLater(() -> {
            CRUDEstudiantesGUI gui = new CRUDEstudiantesGUI();
            gui.setVisible(true);
        });
    }
}
