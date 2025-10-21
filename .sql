-- ==============================
-- CLIENTES
-- ==============================
CREATE TABLE CLIENTES (
    id_cliente INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50),
    apellido VARCHAR(50),
    email VARCHAR(100),
    telefono VARCHAR(20),
    direccion VARCHAR(100),
    ciudad VARCHAR(50),
    provincia VARCHAR(50),
    dni VARCHAR(20)
);

-- ==============================
-- AGENTES
-- ==============================
CREATE TABLE AGENTES (
    id_agente INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50),
    apellido VARCHAR(50),
    email VARCHAR(100),
    telefono VARCHAR(20),
    matricula VARCHAR(20),
    fecha_ingreso DATE
);

-- ==============================
-- PROPIEDADES
-- ==============================
CREATE TABLE PROPIEDADES (
    id_propiedad INT PRIMARY KEY AUTO_INCREMENT,
    id_agente INT,
    titulo VARCHAR(100),
    descripcion TEXT,
    tipo VARCHAR(50),
    direccion VARCHAR(100),
    ciudad VARCHAR(50),
    provincia VARCHAR(50),
    precio DECIMAL(12,2),
    superficie DECIMAL(10,2),
    ambientes INT,
    banos INT,
    dormitorios INT,
    antiguedad INT,
    estado VARCHAR(50),
    FOREIGN KEY (id_agente) REFERENCES AGENTES(id_agente)
);

-- ==============================
-- IMAGENES
-- ==============================
CREATE TABLE IMAGENES (
    id_imagen INT PRIMARY KEY AUTO_INCREMENT,
    id_propiedad INT,
    url VARCHAR(255),
    descripcion VARCHAR(100),
    principal BOOLEAN,
    FOREIGN KEY (id_propiedad) REFERENCES PROPIEDADES(id_propiedad)
);

-- ==============================
-- CARACTERISTICAS
-- ==============================
CREATE TABLE CARACTERISTICAS (
    id_caracteristica INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50)
);

-- ==============================
-- PROPIEDAD_CARACTERISTICA
-- ==============================
CREATE TABLE PROPIEDAD_CARACTERISTICA (
    id_propiedad INT,
    id_caracteristica INT,
    valor VARCHAR(50),
    PRIMARY KEY(id_propiedad,id_caracteristica),
    FOREIGN KEY (id_propiedad) REFERENCES PROPIEDADES(id_propiedad),
    FOREIGN KEY (id_caracteristica) REFERENCES CARACTERISTICAS(id_caracteristica)
);

-- ==============================
-- ETIQUETAS
-- ==============================
CREATE TABLE ETIQUETAS (
    id_etiqueta INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50)
);

-- ==============================
-- PROPIEDAD_ETIQUETA
-- ==============================
CREATE TABLE PROPIEDAD_ETIQUETA (
    id_propiedad INT,
    id_etiqueta INT,
    PRIMARY KEY(id_propiedad,id_etiqueta),
    FOREIGN KEY (id_propiedad) REFERENCES PROPIEDADES(id_propiedad),
    FOREIGN KEY (id_etiqueta) REFERENCES ETIQUETAS(id_etiqueta)
);

-- ==============================
-- TRANSACCIONES
-- ==============================
CREATE TABLE TRANSACCIONES (
    id_transaccion INT PRIMARY KEY AUTO_INCREMENT,
    id_propiedad INT,
    id_cliente INT,
    tipo VARCHAR(50),
    monto DECIMAL(12,2),
    fecha_inicio DATE,
    estado VARCHAR(50),
    FOREIGN KEY (id_propiedad) REFERENCES PROPIEDADES(id_propiedad),
    FOREIGN KEY (id_cliente) REFERENCES CLIENTES(id_cliente)
);

-- ==============================
-- CITAS
-- ==============================
CREATE TABLE CITAS (
    id_cita INT PRIMARY KEY AUTO_INCREMENT,
    id_agente INT,
    id_cliente INT,
    id_propiedad INT,
    fecha DATETIME,
    estado VARCHAR(50),
    notas TEXT,
    FOREIGN KEY (id_agente) REFERENCES AGENTES(id_agente),
    FOREIGN KEY (id_cliente) REFERENCES CLIENTES(id_cliente),
    FOREIGN KEY (id_propiedad) REFERENCES PROPIEDADES(id_propiedad)
);

-- ==============================
-- INTERACCIONES
-- ==============================
CREATE TABLE INTERACCIONES (
    id_interaccion INT PRIMARY KEY AUTO_INCREMENT,
    id_cliente INT,
    id_agente INT,
    id_propiedad INT,
    fecha_interaccion DATETIME,
    medio VARCHAR(50),
    descripcion TEXT,
    FOREIGN KEY (id_cliente) REFERENCES CLIENTES(id_cliente),
    FOREIGN KEY (id_agente) REFERENCES AGENTES(id_agente),
    FOREIGN KEY (id_propiedad) REFERENCES PROPIEDADES(id_propiedad)
);


-- ==============================
-- CLIENTES (10 registros)
-- ==============================
INSERT INTO CLIENTES (nombre, apellido, email, telefono, direccion, ciudad, provincia, dni) VALUES
('Juan','Pérez','juanp@mail.com','3411234561','Calle Falsa 123','Rosario','Santa Fe','12345678'),
('María','Gómez','mariag@mail.com','3411234562','Av. Siempre Viva 742','Rosario','Santa Fe','87654321'),
('Pedro','Santos','pedros@mail.com','3411234563','Calle Sol 45','Rosario','Santa Fe','23456789'),
('Ana','López','anal@mail.com','3411234564','Av. Luna 12','Rosario','Santa Fe','34567890'),
('Sofía','Vega','sofiav@mail.com','3411234565','Calle Estrella 78','Rosario','Santa Fe','45678901'),
('Diego','Molina','diegom@mail.com','3411234566','Av. Libertad 22','Rosario','Santa Fe','56789012'),
('Lucía','Torres','luciatorres@mail.com','3411234567','Calle Río 56','Rosario','Santa Fe','67890123'),
('Martín','Rojas','martinr@mail.com','3411234568','Calle Mar 89','Rosario','Santa Fe','78901234'),
('Laura','Fernández','lauraf@mail.com','3411234569','Av. Tierra 5','Rosario','Santa Fe','89012345'),
('Carlos','Ramírez','carlosr@mail.com','3411234570','Calle Cielo 100','Rosario','Santa Fe','90123456');

-- ==============================
-- AGENTES (10 registros)
-- ==============================
INSERT INTO AGENTES (nombre, apellido, email, telefono, matricula, fecha_ingreso) VALUES
('Carlos','Ramírez','carlosr@mail.com','3411234563','AG001','2018-03-15'),
('Laura','Fernández','lauraf@mail.com','3411234564','AG002','2020-06-01'),
('Lucía','Torres','luciatorres@mail.com','3411234565','AG003','2015-09-20'),
('Martín','Rojas','martinr@mail.com','3411234566','AG004','2022-01-10'),
('Carlos','Ramírez','carlosr2@mail.com','3411234567','AG005','2017-11-05'),
('Laura','Fernández','lauraf2@mail.com','3411234568','AG006','2019-04-18'),
('Lucía','Torres','luciatorres2@mail.com','3411234569','AG007','2013-07-30'),
('Martín','Rojas','martinr2@mail.com','3411234570','AG008','2024-02-12'),
('Carlos','Ramírez','carlosr3@mail.com','3411234571','AG009','2016-08-25'),
('Laura','Fernández','lauraf3@mail.com','3411234572','AG010','2021-12-05');

-- ==============================
-- PROPIEDADES (10 registros)
-- ==============================
INSERT INTO PROPIEDADES (id_agente, titulo, descripcion, tipo, direccion, ciudad, provincia, precio, superficie, ambientes, banos, dormitorios, antiguedad, estado) VALUES
(1,'Casa con pileta','Hermosa casa con jardín y pileta','casa','Calle Luna 45','Rosario','Santa Fe',250000,150,5,3,3,10,'disponible'),
(2,'Departamento céntrico','Departamento en pleno centro','departamento','Av. Córdoba 200','Rosario','Santa Fe',120000,80,3,2,2,5,'disponible'),
(3,'Terreno zona sur','Terreno baldío listo para construcción','terreno','Calle Sol 12','Rosario','Santa Fe',60000,200,0,0,0,0,'disponible'),
(4,'Oficina moderna','Oficina con gran iluminación natural','oficina','Calle Estrella 101','Rosario','Santa Fe',95000,90,2,1,0,3,'disponible'),
(1,'Casa de lujo','Casa de lujo con pileta y cochera doble','casa','Calle Mar 77','Rosario','Santa Fe',500000,300,8,4,5,2,'disponible'),
(2,'Departamento económico','Ideal para estudiantes','departamento','Calle Sol 25','Rosario','Santa Fe',80000,50,2,1,1,10,'disponible'),
(3,'Galpón industrial','Galpón con gran altura','galpón','Av. Industria 500','Rosario','Santa Fe',150000,400,1,0,0,15,'disponible'),
(4,'Local comercial','Local en zona de alto tránsito','local','Calle Comercio 10','Rosario','Santa Fe',90000,60,2,1,0,8,'disponible'),
(1,'Casa antigua reciclada','Casa con estilo antiguo reciclada','casa','Calle Vieja 3','Rosario','Santa Fe',180000,120,4,2,3,30,'disponible'),
(2,'Departamento con balcón','Departamento moderno con balcón amplio','departamento','Av. Libertad 88','Rosario','Santa Fe',140000,70,3,2,2,5,'disponible');

-- ==============================
-- IMAGENES (10 registros)
-- ==============================
INSERT INTO IMAGENES (id_propiedad, url, descripcion, principal) VALUES
(1,'/imagenes/casa1_1.jpg','Fachada principal',1),
(1,'/imagenes/casa1_2.jpg','Piscina',0),
(2,'/imagenes/depto1_1.jpg','Living comedor',1),
(2,'/imagenes/depto1_2.jpg','Cocina',0),
(3,'/imagenes/terreno1_1.jpg','Vista aérea',1),
(4,'/imagenes/oficina1_1.jpg','Interior oficina',1),
(5,'/imagenes/casa2_1.jpg','Casa de lujo',1),
(6,'/imagenes/depto2_1.jpg','Departamento económico',1),
(7,'/imagenes/galpon1_1.jpg','Galpón industrial',1),
(8,'/imagenes/local1_1.jpg','Local comercial',1);

-- ==============================
-- CARACTERISTICAS (10 registros)
-- ==============================
INSERT INTO CARACTERISTICAS (nombre) VALUES
('Pileta'),('Cochera'),('Balcón'),('Jardín'),('Reciclada'),('Vista al río'),('Aire acondicionado'),('Amoblada'),('Seguridad 24hs'),('Ascensor');

-- ==============================
-- PROPIEDAD_CARACTERISTICA (10 registros)
-- ==============================
INSERT INTO PROPIEDAD_CARACTERISTICA (id_propiedad, id_caracteristica, valor) VALUES
(1,1,'Sí'),(1,2,'Sí'),(1,4,'Sí'),
(2,3,'Sí'),
(5,1,'Sí'),(5,2,'Sí'),
(9,5,'Sí'),
(10,3,'Sí'),
(7,8,'Sí'),
(4,7,'Sí');

-- ==============================
-- ETIQUETAS (10 registros)
-- ==============================
INSERT INTO ETIQUETAS (nombre) VALUES
('Lujo'),('Oportunidad'),('Céntrica'),('Económica'),('Reciclada'),('Industrial'),('Frente al río'),('Moderna'),('Con vista'),('Exclusiva');

-- ==============================
-- PROPIEDAD_ETIQUETA (10 registros)
-- ==============================
INSERT INTO PROPIEDAD_ETIQUETA (id_propiedad, id_etiqueta) VALUES
(1,1),(1,3),
(2,2),(2,3),
(5,1),(6,4),
(7,6),(9,5),
(10,1),(10,3);

-- ==============================
-- TRANSACCIONES (10 registros)
-- ==============================
INSERT INTO TRANSACCIONES (id_propiedad, id_cliente, tipo, monto, fecha_inicio, estado) VALUES
(1,1,'venta',250000,'2025-10-01','activa'),
(2,2,'alquiler',120000,'2025-10-02','activa'),
(3,3,'venta',60000,'2025-10-03','activa'),
(4,4,'alquiler',95000,'2025-10-04','activa'),
(5,5,'venta',500000,'2025-10-05','activa'),
(6,6,'alquiler',80000,'2025-10-06','activa'),
(7,7,'venta',150000,'2025-10-07','activa'),
(8,8,'alquiler',90000,'2025-10-08','activa'),
(9,9,'venta',180000,'2025-10-09','activa'),
(10,10,'alquiler',140000,'2025-10-10','activa');

-- ==============================
-- CITAS (10 registros)
-- ==============================
INSERT INTO CITAS (id_agente, id_cliente, id_propiedad, fecha, estado, notas) VALUES
(1,1,1,'2025-10-03 10:00:00','pendiente','Visita inicial'),
(2,2,2,'2025-10-03 11:00:00','pendiente','Visita inicial'),
(3,3,3,'2025-10-04 09:00:00','pendiente','Visita al terreno'),
(4,4,4,'2025-10-04 14:00:00','pendiente','Visita oficina'),
(1,5,5,'2025-10-05 10:30:00','pendiente','Visita casa lujo'),
(2,6,6,'2025-10-05 12:00:00','pendiente','Visita depto económico'),
(3,7,7,'2025-10-06 09:30:00','pendiente','Visita galpón'),
(4,8,8,'2025-10-06 11:00:00','pendiente','Visita local'),
(1,9,9,'2025-10-07 10:00:00','pendiente','Visita casa reciclada'),
(2,10,10,'2025-10-07 12:00:00','pendiente','Visita depto balcón');

-- ==============================
-- INTERACCIONES (15 registros)
-- ==============================
INSERT INTO INTERACCIONES (id_cliente, id_agente, id_propiedad, fecha_interaccion, medio, descripcion) VALUES
(1,1,1,'2025-10-01 10:00:00','Teléfono','Llamada inicial'),
(2,2,2,'2025-10-01 11:00:00','Email','Envió listado'),
(3,3,3,'2025-10-02 09:00:00','WhatsApp','Pregunta sobre terreno'),
(4,4,4,'2025-10-02 10:00:00','Visita','Visita oficina'),
(5,1,5,'2025-10-03 11:00:00','Teléfono','Consulta financiación'),
(6,2,6,'2025-10-03 12:00:00','Email','Envió contrato'),
(7,3,7,'2025-10-04 09:30:00','WhatsApp','Pregunta sobre galpón'),
(8,4,8,'2025-10-04 10:30:00','Teléfono','Confirma visita'),
(9,1,9,'2025-10-05 11:00:00','Visita','Visita casa reciclada'),
(10,2,10,'2025-10-05 12:00:00','WhatsApp','Pregunta depto balcón'),
(1,1,1,'2025-10-06 10:00:00','Email','Envió listado actualizado'),
(2,2,2,'2025-10-06 11:00:00','Teléfono','Seguimiento cliente'),
(3,3,3,'2025-10-07 09:00:00','WhatsApp','Solicita más información'),
(4,4,4,'2025-10-07 10:00:00','Email','Envío propuesta'),
(5,1,5,'2025-10-08 10:30:00','Teléfono','Seguimiento compra');  