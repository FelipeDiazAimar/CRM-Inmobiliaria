-- =====================================================
-- CRM Inmobiliaria — Seed de demo: El Salvador (SV)
-- Autor: AGENTE C-9. Requiere el schema de `.sql` ya
-- aplicado. Datos de personas y propiedades IDÉNTICOS a
-- los seeds PHP del agente C-1 (pages/demo_data.php) para
-- consistencia demo; solo se agregan filas extra donde
-- este seed exige más volumen (interacciones 10→14,
-- citas 6→10) más las tablas que C-1 no cubre
-- (IMAGENES, CARACTERISTICAS, ETIQUETAS, pivotes,
-- IMAGENES_PERFILES).
-- Contenido: 4 agentes, 8 clientes, 12 propiedades,
-- 12 imágenes, 14 propiedad_caracteristica,
-- 14 propiedad_etiqueta, 6 transacciones, 10 citas,
-- 14 interacciones (kanban: 4/4/3/3), 12 perfiles.
-- Gente canónica (C-1):
--   Agentes: Rodrigo Henríquez, Alejandra Campos,
--     Diego Fuentes, Marcela Rivas.
--   Clientes: Gabriela Mendoza, José Hernández,
--     Carmen Aguilar, Roberto Castillo, Lucía Morales,
--     Miguel Rivas, Elena Portillo, David Bonilla.
-- Charset: utf8mb4 (acentos y eñes OK).
-- =====================================================
USE `crm-inmobiliaria`;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE IMAGENES;
TRUNCATE TABLE IMAGENES_PERFILES;
TRUNCATE TABLE PROPIEDAD_CARACTERISTICA;
TRUNCATE TABLE PROPIEDAD_ETIQUETA;
TRUNCATE TABLE TRANSACCIONES;
TRUNCATE TABLE CITAS;
TRUNCATE TABLE INTERACCIONES;
TRUNCATE TABLE PROPIEDADES;
TRUNCATE TABLE CARACTERISTICAS;
TRUNCATE TABLE ETIQUETAS;
TRUNCATE TABLE CLIENTES;
TRUNCATE TABLE AGENTES;
SET FOREIGN_KEY_CHECKS = 1;

-- ==============================
-- AGENTES (4 registros, idénticos a C-1)
-- ==============================
INSERT INTO AGENTES
    (id_agente, nombre, apellido, email, telefono, matricula, fecha_ingreso)
VALUES
    (1, 'Rodrigo', 'Henríquez', 'rodrigo.henriquez@crm.sv', '+503 7890-1111', 'AG-ES001', '2019-03-15'),
    (2, 'Alejandra', 'Campos', 'alejandra.campos@crm.sv', '+503 7890-2222', 'AG-ES002', '2021-06-01'),
    (3, 'Diego', 'Fuentes', 'diego.fuentes@crm.sv', '+503 7890-3333', 'AG-ES003', '2022-01-10'),
    (4, 'Marcela', 'Rivas', 'marcela.rivas@crm.sv', '+503 7890-4444', 'AG-ES004', '2023-02-12');

-- ==============================
-- CLIENTES (8 registros, idénticos a C-1)
-- ==============================
INSERT INTO CLIENTES
    (id_cliente, nombre, apellido, email, telefono, direccion, ciudad, provincia, dni)
VALUES
    (1, 'Gabriela', 'Mendoza', 'gaby.mendoza@gmail.com', '+503 7123-4561', 'Col. Escalón, Calle El Mirador #4215', 'San Salvador', 'San Salvador', '04123456-7'),
    (2, 'José', 'Hernández', 'jose.hernandez.sv@gmail.com', '+503 7689-1234', 'Col. San Benito, Av. La Capilla #233', 'San Salvador', 'San Salvador', '03876543-2'),
    (3, 'Carmen', 'Aguilar', 'carmen.aguilar@yahoo.com', '+503 7734-8890', 'Col. Miramonte, Calle Motocross #18', 'San Salvador', 'San Salvador', '05234567-1'),
    (4, 'Roberto', 'Castillo', 'roberto.castillo@hotmail.com', '+503 7012-6678', 'Res. Santa Elena, Senda Los Eucaliptos #7', 'Antiguo Cuscatlán', 'La Libertad', '02987654-3'),
    (5, 'Lucía', 'Morales', 'lucia.morales.sv@gmail.com', '+503 7498-2210', 'Col. Santa Mónica, Av. Jerusalén #45', 'Santa Tecla', 'La Libertad', '06112233-4'),
    (6, 'Miguel', 'Rivas', 'miguel.rivas12@gmail.com', '+503 7856-9034', 'Col. Ciudad Satélite, Pje. Oriente #12', 'Soyapango', 'San Salvador', '03345678-9'),
    (7, 'Elena', 'Portillo', 'elena.portillo@gmail.com', '+503 7911-3456', 'Col. San José, 2a Calle Poniente #30', 'Santa Ana', 'Santa Ana', '01456789-0'),
    (8, 'David', 'Bonilla', 'david.bonilla.sv@gmail.com', '+503 7555-7891', 'Col. La Presita, Av. Roosevelt #210', 'San Miguel', 'San Miguel', '02765432-5');

-- ==============================
-- PROPIEDADES (12 registros, idénticos a C-1)
-- ==============================
INSERT INTO PROPIEDADES
    (id_propiedad, id_agente, titulo, descripcion, tipo, direccion, ciudad, provincia, precio, superficie, ambientes, banos, dormitorios, antiguedad, estado)
VALUES
    (1, 1, 'Casa familiar Col. Escalón', 'Casa de dos plantas con jardín y cochera doble, cerca de Paseo General Escalón.', 'casa', 'Col. Escalón, Calle El Mirador #4215', 'San Salvador', 'San Salvador', 285000.00, 220.00, 6, 3, 4, 8, 'disponible'),
    (2, 2, 'Casa campestre Santa Tecla', 'Casa con terraza y vista al volcán, ideal para familia.', 'casa', 'Col. Santa Mónica, Av. Jerusalén #45', 'Santa Tecla', 'La Libertad', 195000.00, 180.00, 5, 2, 3, 5, 'disponible'),
    (3, 1, 'Apartamento Torre San Benito', 'Apartamento moderno piso 8 con balcón y seguridad 24h.', 'departamento', 'Col. San Benito, Av. La Capilla #233', 'San Salvador', 'San Salvador', 135000.00, 95.00, 3, 2, 2, 3, 'disponible'),
    (4, 3, 'Apartamento Antiguo Cuscatlán', 'Apartamento acogedor cerca de Multiplaza, ideal primera vivienda.', 'departamento', 'Res. Santa Elena, Senda Los Eucaliptos #7', 'Antiguo Cuscatlán', 'La Libertad', 98000.00, 72.00, 3, 1, 2, 6, 'reservada'),
    (5, 2, 'Lote Bosques de Santa Elena', 'Lote plano listo para construir, acceso privado y portón.', 'terreno', 'Bosques de Santa Elena, Pol. G #14', 'Antiguo Cuscatlán', 'La Libertad', 75000.00, 350.00, 0, 0, 0, 0, 'disponible'),
    (6, 4, 'Casa Col. Miramonte', 'Casa remodelada con cuarto de servicio y cisterna.', 'casa', 'Col. Miramonte, Calle Motocross #18', 'San Salvador', 'San Salvador', 165000.00, 150.00, 5, 2, 3, 12, 'disponible'),
    (7, 3, 'Apartamento Santa Ana centro', 'Apartamento céntrico a 5 min del parque Libertad.', 'departamento', 'Col. San José, 2a Calle Poniente #30', 'Santa Ana', 'Santa Ana', 62000.00, 68.00, 2, 1, 2, 10, 'disponible'),
    (8, 4, 'Casa San Miguel con patio', 'Casa amplia con patio y local anexo para negocio.', 'casa', 'Col. La Presita, Av. Roosevelt #210', 'San Miguel', 'San Miguel', 118000.00, 200.00, 5, 2, 3, 15, 'alquilada'),
    (9, 1, 'Lote Altos de La Toma', 'Lote con vista panorámica, ideal proyecto familiar.', 'terreno', 'Altos de La Toma, Lot. #22', 'Soyapango', 'San Salvador', 42000.00, 280.00, 0, 0, 0, 0, 'disponible'),
    (10, 2, 'Casa Los Sueños Soyapango', 'Casa de una planta, recién pintada, todos los servicios.', 'casa', 'Col. Ciudad Satélite, Pje. Oriente #12', 'Soyapango', 'San Salvador', 89500.00, 120.00, 4, 2, 3, 20, 'vendida'),
    (11, 3, 'Apartamento Merliot ejecutivo', 'Apartamento ejecutivo con gym y cowork incluido.', 'departamento', 'Ciudad Merliot, Calle Chiltiupán #800', 'Santa Tecla', 'La Libertad', 112000.00, 85.00, 3, 2, 2, 2, 'disponible'),
    (12, 4, 'Lote Corinto Zaragoza', 'Lote rústico con acceso a calle principal, agua y luz.', 'terreno', 'Finca Corinto, Lot. #5', 'Zaragoza', 'La Libertad', 38500.00, 500.00, 0, 0, 0, 0, 'disponible');

-- ==============================
-- IMAGENES (12 registros)
-- NOTA: columna `imagen` (BLOB) queda NULL a propósito:
-- el código PHP ya contempla fallback a imagen local
-- cuando el blob es NULL (pages/propiedades.php).
-- ==============================
INSERT INTO IMAGENES
    (id_imagen, id_propiedad, url, imagen, descripcion, principal)
VALUES
    (1, 1, '/image/Inmueble/sv-01.jpg', NULL, 'Fachada casa en Escalón', 1),
    (2, 2, '/image/Inmueble/sv-02.jpg', NULL, 'Terraza casa Santa Tecla', 1),
    (3, 3, '/image/Inmueble/sv-03.jpg', NULL, 'Balcón Torre San Benito', 1),
    (4, 4, '/image/Inmueble/sv-04.jpg', NULL, 'Sala aparta Cuscatlán', 1),
    (5, 5, '/image/Inmueble/sv-05.jpg', NULL, 'Lote Bosques Santa Elena', 1),
    (6, 6, '/image/Inmueble/sv-06.jpg', NULL, 'Frente casa Miramonte', 1),
    (7, 7, '/image/Inmueble/sv-07.jpg', NULL, 'Edificio Santa Ana centro', 1),
    (8, 8, '/image/Inmueble/sv-08.jpg', NULL, 'Patio casa San Miguel', 1),
    (9, 9, '/image/Inmueble/sv-09.jpg', NULL, 'Vista lote La Toma', 1),
    (10, 10, '/image/Inmueble/sv-10.jpg', NULL, 'Frente casa Soyapango', 1),
    (11, 11, '/image/Inmueble/sv-11.jpg', NULL, 'Amenidades Merliot', 1),
    (12, 12, '/image/Inmueble/sv-12.jpg', NULL, 'Acceso lote Corinto', 1);

-- ==============================
-- CARACTERISTICAS (10 registros, mismos ids que .sql;
-- id 6 adaptado a SV: vista panorámica del lote La Toma)
-- ==============================
INSERT INTO CARACTERISTICAS
    (id_caracteristica, nombre)
VALUES
    (1, 'Pileta'),
    (2, 'Cochera'),
    (3, 'Balcón'),
    (4, 'Jardín'),
    (5, 'Reciclada'),
    (6, 'Vista panorámica'),
    (7, 'Aire acondicionado'),
    (8, 'Amoblada'),
    (9, 'Seguridad 24hs'),
    (10, 'Ascensor');

-- ==============================
-- PROPIEDAD_CARACTERISTICA (14 registros)
-- ==============================
INSERT INTO PROPIEDAD_CARACTERISTICA
    (id_propiedad, id_caracteristica, valor)
VALUES
    (1, 2, 'Sí'),
    (1, 4, 'Sí'),
    (2, 4, 'Sí'),
    (2, 7, 'Sí'),
    (3, 3, 'Sí'),
    (3, 9, 'Sí'),
    (4, 8, 'Sí'),
    (6, 5, 'Sí'),
    (6, 4, 'Sí'),
    (8, 4, 'Sí'),
    (8, 2, 'Sí'),
    (9, 6, 'Sí'),
    (11, 10, 'Sí'),
    (11, 7, 'Sí');

-- ==============================
-- ETIQUETAS (10 registros, mismos ids que .sql;
-- id 7 adaptado a SV)
-- ==============================
INSERT INTO ETIQUETAS
    (id_etiqueta, nombre)
VALUES
    (1, 'Lujo'),
    (2, 'Oportunidad'),
    (3, 'Céntrica'),
    (4, 'Económica'),
    (5, 'Reciclada'),
    (6, 'Industrial'),
    (7, 'Panorámica'),
    (8, 'Moderna'),
    (9, 'Con vista'),
    (10, 'Exclusiva');

-- ==============================
-- PROPIEDAD_ETIQUETA (14 registros)
-- ==============================
INSERT INTO PROPIEDAD_ETIQUETA
    (id_propiedad, id_etiqueta)
VALUES
    (1, 1),
    (1, 3),
    (2, 9),
    (2, 10),
    (3, 2),
    (3, 8),
    (4, 4),
    (5, 2),
    (6, 5),
    (8, 4),
    (9, 7),
    (10, 4),
    (11, 8),
    (11, 10);

-- ==============================
-- TRANSACCIONES (6 registros, idénticos a C-1;
-- coherentes con estados: prop 10 vendida, 8 alquilada,
-- 4 reservada. En alquileres el monto es canon mensual USD)
-- ==============================
INSERT INTO TRANSACCIONES
    (id_transaccion, id_propiedad, id_cliente, tipo, monto, fecha_inicio, estado)
VALUES
    (1, 10, 6, 'venta', 89500.00, '2026-09-20', 'cerrada'),
    (2, 8, 8, 'alquiler', 550.00, '2026-10-01', 'activa'),
    (3, 4, 4, 'venta', 98000.00, '2026-10-05', 'pendiente'),
    (4, 3, 2, 'alquiler', 750.00, '2026-10-03', 'activa'),
    (5, 5, 3, 'venta', 75000.00, '2026-10-06', 'pendiente'),
    (6, 2, 5, 'venta', 195000.00, '2026-10-07', 'activa');

-- ==============================
-- CITAS (10 registros: 6 idénticas a C-1 + 4 extra)
-- ==============================
INSERT INTO CITAS
    (id_cita, id_agente, id_cliente, id_propiedad, fecha, estado, notas)
VALUES
    (1, 1, 1, 1, '2026-10-13 10:00:00', 'confirmada', 'Visita casa Escalón con familia'),
    (2, 1, 2, 3, '2026-10-13 15:00:00', 'pendiente', 'Tour apartamento San Benito'),
    (3, 2, 5, 2, '2026-10-14 09:30:00', 'pendiente', 'Visita casa Santa Tecla'),
    (4, 3, 4, 4, '2026-10-14 14:00:00', 'confirmada', 'Segunda visita con asesor bancario'),
    (5, 3, 7, 7, '2026-10-11 11:00:00', 'realizada', 'Visita Santa Ana, oferta verbal'),
    (6, 4, 8, 8, '2026-10-12 16:00:00', 'pendiente', 'Firma precontrato alquiler San Miguel'),
    (7, 2, 3, 6, '2026-10-15 10:00:00', 'pendiente', 'Visita casa Miramonte con familia'),
    (8, 4, 6, 9, '2026-10-15 16:00:00', 'pendiente', 'Recorrido lote La Toma'),
    (9, 1, 5, 2, '2026-10-16 09:00:00', 'confirmada', 'Visita casa Santa Tecla'),
    (10, 3, 8, 12, '2026-10-16 12:00:00', 'pendiente', 'Medición lote Corinto');

-- ==============================
-- INTERACCIONES (14 registros: 10 idénticas a C-1 + 4 extra)
-- Kanban final: nuevo x4, seguimiento x4, negociacion x3, cerrado x3.
-- ==============================
INSERT INTO INTERACCIONES
    (id_interaccion, id_cliente, id_agente, id_propiedad, titulo, descripcion, estado_kanban, prioridad, medio, proxima_accion, fecha_proxima_accion, fecha_interaccion, posicion)
VALUES
    (1, 1, 1, 1, 'Consulta casa Escalón', 'Cliente pide precio final y formas de pago en USD.', 'nuevo', 'alta', 'WhatsApp', 'Enviar dossier y estructura de pago', '2026-10-14', '2026-10-08 10:00:00', 1),
    (2, 2, 1, 3, 'Tour Torre San Benito', 'Solicita visita fin de semana al apartamento.', 'nuevo', 'media', 'Teléfono', 'Agendar visita sábado', '2026-10-13', '2026-10-08 11:30:00', 2),
    (3, 5, 2, 2, 'Interés casa Santa Tecla', 'Lead de Facebook, pide fotos del patio.', 'nuevo', 'media', 'Facebook', 'Enviar fotos y video', '2026-10-12', '2026-10-09 09:00:00', 3),
    (4, 4, 3, 4, 'Seguimiento aparta Cuscatlán', 'Cliente ya visitó, evalúa crédito hipotecario.', 'seguimiento', 'alta', 'Visita', 'Enviar simulación bancaria', '2026-10-15', '2026-10-07 14:00:00', 1),
    (5, 3, 4, 6, 'Segunda visita Miramonte', 'Pide llevar a su familia a segunda visita.', 'seguimiento', 'media', 'WhatsApp', 'Confirmar visita familiar', '2026-10-14', '2026-10-06 16:00:00', 2),
    (6, 6, 2, 9, 'Duda lote La Toma', 'Pregunta por acceso a agua y escrituración.', 'seguimiento', 'baja', 'Email', 'Enviar copia de escritura madre', '2026-10-16', '2026-10-05 12:00:00', 3),
    (7, 7, 3, 7, 'Oferta apartamento Santa Ana', 'Cliente oferta USD 58,000 de contado.', 'negociacion', 'alta', 'Teléfono', 'Presentar oferta al propietario', '2026-10-12', '2026-10-04 09:30:00', 1),
    (8, 8, 4, 8, 'Negociación alquiler San Miguel', 'Negocian depósito y plazo a 2 años.', 'negociacion', 'media', 'Visita', 'Redactar contrato de alquiler', '2026-10-13', '2026-10-03 10:30:00', 2),
    (9, 6, 2, 10, 'Cierre casa Soyapango', 'Venta cerrada, pendiente firma en notaría.', 'cerrado', 'media', 'Email', 'Coordinar firma', '2026-10-11', '2026-10-02 11:00:00', 1),
    (10, 1, 1, 11, 'Postventa Merliot', 'Cliente desistió, se archiva con encuesta.', 'cerrado', 'baja', 'WhatsApp', 'Enviar encuesta de satisfacción', '2026-10-10', '2026-10-01 12:00:00', 2),
    (11, 3, 4, 6, 'Consulta casa Miramonte', 'Carmen pide costo de escrituración e impuestos.', 'nuevo', 'media', 'Email', 'Enviar liquidación de gastos', '2026-10-17', '2026-10-09 15:00:00', 4),
    (12, 8, 4, 8, 'Renovación alquiler San Miguel', 'David evalúa renovar un año más con ajuste.', 'seguimiento', 'media', 'Teléfono', 'Enviar propuesta de renovación', '2026-10-18', '2026-10-09 17:30:00', 4),
    (13, 4, 3, 4, 'Contraoferta aparta Cuscatlán', 'Propietario acepta con ajuste en fecha de entrega.', 'negociacion', 'alta', 'WhatsApp', 'Agendar firma de promesa', '2026-10-16', '2026-10-08 12:00:00', 3),
    (14, 2, 1, 3, 'Alquiler firmado Torre San Benito', 'Contrato de alquiler firmado por un año.', 'cerrado', 'media', 'Visita', 'Archivar expediente en CRM', NULL, '2026-10-10 10:00:00', 3);

-- ==============================
-- IMAGENES_PERFILES (12 registros: 8 clientes + 4 agentes C-1)
-- ==============================
INSERT INTO IMAGENES_PERFILES
    (id_imagen_perfil, id_cliente, id_agente, url, descripcion, principal)
VALUES
    (1, 1, NULL, '/image/Icono/sv-cliente1.png', 'Foto de perfil de Gabriela Mendoza', 1),
    (2, 2, NULL, '/image/Icono/sv-cliente2.png', 'Foto de perfil de José Hernández', 1),
    (3, 3, NULL, '/image/Icono/sv-cliente3.png', 'Foto de perfil de Carmen Aguilar', 1),
    (4, 4, NULL, '/image/Icono/sv-cliente4.png', 'Foto de perfil de Roberto Castillo', 1),
    (5, 5, NULL, '/image/Icono/sv-cliente5.png', 'Foto de perfil de Lucía Morales', 1),
    (6, 6, NULL, '/image/Icono/sv-cliente6.png', 'Foto de perfil de Miguel Rivas', 1),
    (7, 7, NULL, '/image/Icono/sv-cliente7.png', 'Foto de perfil de Elena Portillo', 1),
    (8, 8, NULL, '/image/Icono/sv-cliente8.png', 'Foto de perfil de David Bonilla', 1),
    (9, NULL, 1, '/image/Icono/sv-agente1.png', 'Foto de perfil de Rodrigo Henríquez', 1),
    (10, NULL, 2, '/image/Icono/sv-agente2.png', 'Foto de perfil de Alejandra Campos', 1),
    (11, NULL, 3, '/image/Icono/sv-agente3.png', 'Foto de perfil de Diego Fuentes', 1),
    (12, NULL, 4, '/image/Icono/sv-agente4.png', 'Foto de perfil de Marcela Rivas', 1);

-- ==============================
-- Verificación post-seed (conteos esperados:
-- 4/8/12/12/10/14/10/14/6/10/14/12)
-- ==============================
SELECT 'AGENTES' AS tabla, COUNT(*) AS n FROM AGENTES UNION ALL
SELECT 'CLIENTES', COUNT(*) FROM CLIENTES UNION ALL
SELECT 'PROPIEDADES', COUNT(*) FROM PROPIEDADES UNION ALL
SELECT 'IMAGENES', COUNT(*) FROM IMAGENES UNION ALL
SELECT 'CARACTERISTICAS', COUNT(*) FROM CARACTERISTICAS UNION ALL
SELECT 'PROPIEDAD_CARACTERISTICA', COUNT(*) FROM PROPIEDAD_CARACTERISTICA UNION ALL
SELECT 'ETIQUETAS', COUNT(*) FROM ETIQUETAS UNION ALL
SELECT 'PROPIEDAD_ETIQUETA', COUNT(*) FROM PROPIEDAD_ETIQUETA UNION ALL
SELECT 'TRANSACCIONES', COUNT(*) FROM TRANSACCIONES UNION ALL
SELECT 'CITAS', COUNT(*) FROM CITAS UNION ALL
SELECT 'INTERACCIONES', COUNT(*) FROM INTERACCIONES UNION ALL
SELECT 'IMAGENES_PERFILES', COUNT(*) FROM IMAGENES_PERFILES;
