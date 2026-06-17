USE ironclad_box;

INSERT INTO usuarios (id, nombre, cedula, correo, contrasena, rol, estado, fecha_registro) VALUES
(1, 'Admin IronClad', '0706499860', 'admin@ironcladbox.local', '$2y$10$nXGjXD7PCqew472GFbO6Ke8ECPsYSP1peUkZnqkfDc7mqV3N8mciC', 'Administrador', 'Activo', '2026-06-09'),
(2, 'Valeria Rios', '0801308321', 'valeria.rios@ironcladbox.local', '$2y$10$sLKBTfR9LLTQW.qdGIfDXuMEPTS4TXW.dCU7akPY1pz7lcxWxJf2a', 'Entrenador', 'Activo', '2026-06-09'),
(3, 'Daniela Moya', '1725916645', 'daniela.moya@ironcladbox.local', '$2y$10$Mf67PYMVW1Wg5SaUg5Rdvefej14y5gnf4KNmWP85OruMwXRXeo1mi', 'Atleta', 'Activo', '2026-06-09');

INSERT INTO atletas (id, nombre, correo, fecha_registro) VALUES
(1, 'Daniela Moya', 'daniela.moya@ironcladbox.local', '2026-06-09'),
(2, 'Nicolas Perez', 'nicolas.perez@ironcladbox.local', '2026-06-09'),
(3, 'Andrea Vega', 'andrea.vega@ironcladbox.local', '2026-06-09'),
(4, 'Sebastian Flores', 'sebastian.flores@ironcladbox.local', '2026-06-09');

INSERT INTO entrenadores (id, nombre, correo, disponible) VALUES
(1, 'Valeria Rios', 'valeria.rios@ironcladbox.local', 1),
(2, 'Mateo Silva', 'mateo.silva@ironcladbox.local', 1),
(3, 'Camila Torres', 'camila.torres@ironcladbox.local', 1);

INSERT INTO clases (id, dia, hora, duracion, cupo_maximo, cupos_disponibles, entrenador_id) VALUES
(1, '2026-06-18', '06:00:00', 60, 12, 11, 1),
(2, '2026-06-18', '18:00:00', 60, 10, 9, 2),
(3, '2026-06-19', '07:00:00', 60, 12, 12, 3),
(4, '2026-06-20', '09:00:00', 75, 14, 14, 1),
(5, '2026-06-22', '06:00:00', 60, 12, 12, 2),
(6, '2026-06-22', '19:00:00', 60, 10, 10, 3),
(7, '2026-06-23', '07:00:00', 60, 12, 12, 1),
(8, '2026-06-24', '18:30:00', 60, 10, 10, 2),
(9, '2026-06-25', '06:00:00', 45, 8, 8, 3),
(10, '2026-06-26', '17:30:00', 60, 12, 12, 1);

INSERT INTO membresias (id, tipo, precio, fecha_inicio, fecha_vencimiento, estado, id_atleta) VALUES
(1, 'Premium', 55.00, '2026-06-13', '2026-07-13', 'Pagado', 1),
(2, 'Basica', 35.00, '2026-06-14', '2026-07-14', 'Pendiente', 2),
(3, 'Premium', 55.00, '2026-06-13', '2026-07-13', 'Pagado', 3),
(4, 'Basica', 35.00, '2026-05-01', '2026-05-31', 'Vencido', 4);

INSERT INTO reservas (id, id_atleta, id_clase, fecha_reserva, estado) VALUES
(1, 1, 1, '2026-06-17 10:15:00', 'Confirmada'),
(2, 3, 2, '2026-06-17 11:20:00', 'Confirmada');

INSERT INTO progreso_atletas (fecha, tiempo, repeticiones, peso, puntuacion, notas, id_atleta) VALUES
('2026-06-10', 12.50, 80, 40.00, 88.00, 'Buen ritmo en AMRAP.', 1),
('2026-06-12', 10.80, 95, 42.50, 92.00, 'Mejoro transiciones.', 1),
('2026-06-15', NULL, 120, 45.00, 95.00, 'Trabajo de fuerza.', 1),
('2026-06-11', 14.20, 70, 35.00, 81.00, 'Base inicial.', 3),
('2026-06-14', 13.10, 86, 37.50, 87.00, 'Mejor control tecnico.', 3);

INSERT INTO mensajes (contenido, fecha_envio, tipo, id_atleta, id_entrenador) VALUES
('Excelente progreso esta semana. Manten el ritmo y prioriza movilidad.', '2026-06-17 16:00:00', 'Mensaje', 1, 1),
('Recordatorio: traer cuerda y botella de agua para las clases del jueves.', '2026-06-17 17:00:00', 'Anuncio', NULL, 1);
