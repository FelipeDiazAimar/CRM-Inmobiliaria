<?php
// ============================================================
// MODO DEMO sin MySQL (AGENTE C-1)
// Seeds realistas El Salvador (colonias, USD). Columnas compatibles
// con .sql: CLIENTES / AGENTES / PROPIEDADES / INTERACCIONES / CITAS / TRANSACCIONES.
// Contrato EXACTO (otros 9 agentes lo usan):
//   demo_activo(): bool
//   demo_datos(string $entidad): array
//   demo_guardar(string $entidad, array $item): array
//   demo_escribir(string $entidad, array $todos): void
// Entidades válidas: clientes, agentes, propiedades, interacciones, citas, transacciones.
// En demo NADA exige MySQL; en real todo intacto.
// ============================================================

if (!function_exists('demo_activo')) {
    require_once __DIR__ . '/config.php';
}

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

if (!function_exists('demo__init')) {
    function demo__init(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        if (!isset($_SESSION['demo']) || !is_array($_SESSION['demo'])) {
            $_SESSION['demo'] = [];
        }
    }
}

if (!function_exists('demo__id_campo')) {
    function demo__id_campo(string $entidad): ?string
    {
        $map = [
            'clientes'      => 'id_cliente',
            'agentes'       => 'id_agente',
            'propiedades'   => 'id_propiedad',
            'interacciones' => 'id_interaccion',
            'citas'         => 'id_cita',
            'transacciones' => 'id_transaccion',
        ];
        $e = strtolower(trim($entidad));
        return $map[$e] ?? null;
    }
}

if (!function_exists('demo__seed')) {
    /**
     * @return array<int,array<string,mixed>>
     */
    function demo__seed(string $entidad): array
    {
        $e = strtolower(trim($entidad));
        switch ($e) {
            case 'clientes':
                return [
                    ['id_cliente' => 1, 'nombre' => 'Gabriela', 'apellido' => 'Mendoza', 'email' => 'gaby.mendoza@gmail.com', 'telefono' => '+503 7123-4561', 'direccion' => 'Col. Escalón, Calle El Mirador #4215', 'ciudad' => 'San Salvador', 'provincia' => 'San Salvador', 'dni' => '04123456-7'],
                    ['id_cliente' => 2, 'nombre' => 'José', 'apellido' => 'Hernández', 'email' => 'jose.hernandez.sv@gmail.com', 'telefono' => '+503 7689-1234', 'direccion' => 'Col. San Benito, Av. La Capilla #233', 'ciudad' => 'San Salvador', 'provincia' => 'San Salvador', 'dni' => '03876543-2'],
                    ['id_cliente' => 3, 'nombre' => 'Carmen', 'apellido' => 'Aguilar', 'email' => 'carmen.aguilar@yahoo.com', 'telefono' => '+503 7734-8890', 'direccion' => 'Col. Miramonte, Calle Motocross #18', 'ciudad' => 'San Salvador', 'provincia' => 'San Salvador', 'dni' => '05234567-1'],
                    ['id_cliente' => 4, 'nombre' => 'Roberto', 'apellido' => 'Castillo', 'email' => 'roberto.castillo@hotmail.com', 'telefono' => '+503 7012-6678', 'direccion' => 'Res. Santa Elena, Senda Los Eucaliptos #7', 'ciudad' => 'Antiguo Cuscatlán', 'provincia' => 'La Libertad', 'dni' => '02987654-3'],
                    ['id_cliente' => 5, 'nombre' => 'Lucía', 'apellido' => 'Morales', 'email' => 'lucia.morales.sv@gmail.com', 'telefono' => '+503 7498-2210', 'direccion' => 'Col. Santa Mónica, Av. Jerusalén #45', 'ciudad' => 'Santa Tecla', 'provincia' => 'La Libertad', 'dni' => '06112233-4'],
                    ['id_cliente' => 6, 'nombre' => 'Miguel', 'apellido' => 'Rivas', 'email' => 'miguel.rivas12@gmail.com', 'telefono' => '+503 7856-9034', 'direccion' => 'Col. Ciudad Satélite, Pje. Oriente #12', 'ciudad' => 'Soyapango', 'provincia' => 'San Salvador', 'dni' => '03345678-9'],
                    ['id_cliente' => 7, 'nombre' => 'Elena', 'apellido' => 'Portillo', 'email' => 'elena.portillo@gmail.com', 'telefono' => '+503 7911-3456', 'direccion' => 'Col. San José, 2a Calle Poniente #30', 'ciudad' => 'Santa Ana', 'provincia' => 'Santa Ana', 'dni' => '01456789-0'],
                    ['id_cliente' => 8, 'nombre' => 'David', 'apellido' => 'Bonilla', 'email' => 'david.bonilla.sv@gmail.com', 'telefono' => '+503 7555-7891', 'direccion' => 'Col. La Presita, Av. Roosevelt #210', 'ciudad' => 'San Miguel', 'provincia' => 'San Miguel', 'dni' => '02765432-5'],
                ];
            case 'agentes':
                return [
                    ['id_agente' => 1, 'nombre' => 'Rodrigo', 'apellido' => 'Henríquez', 'email' => 'rodrigo.henriquez@crm.sv', 'telefono' => '+503 7890-1111', 'matricula' => 'AG-ES001', 'fecha_ingreso' => '2019-03-15'],
                    ['id_agente' => 2, 'nombre' => 'Alejandra', 'apellido' => 'Campos', 'email' => 'alejandra.campos@crm.sv', 'telefono' => '+503 7890-2222', 'matricula' => 'AG-ES002', 'fecha_ingreso' => '2021-06-01'],
                    ['id_agente' => 3, 'nombre' => 'Diego', 'apellido' => 'Fuentes', 'email' => 'diego.fuentes@crm.sv', 'telefono' => '+503 7890-3333', 'matricula' => 'AG-ES003', 'fecha_ingreso' => '2022-01-10'],
                    ['id_agente' => 4, 'nombre' => 'Marcela', 'apellido' => 'Rivas', 'email' => 'marcela.rivas@crm.sv', 'telefono' => '+503 7890-4444', 'matricula' => 'AG-ES004', 'fecha_ingreso' => '2023-02-12'],
                ];
            case 'propiedades':
                return [
                    ['id_propiedad' => 1, 'id_agente' => 1, 'titulo' => 'Casa familiar Col. Escalón', 'descripcion' => 'Casa de dos plantas con jardín y cochera doble, cerca de Paseo General Escalón.', 'tipo' => 'casa', 'direccion' => 'Col. Escalón, Calle El Mirador #4215', 'ciudad' => 'San Salvador', 'provincia' => 'San Salvador', 'precio' => 285000.00, 'superficie' => 220.00, 'ambientes' => 6, 'banos' => 3, 'dormitorios' => 4, 'antiguedad' => 8, 'estado' => 'disponible'],
                    ['id_propiedad' => 2, 'id_agente' => 2, 'titulo' => 'Casa campestre Santa Tecla', 'descripcion' => 'Casa con terraza y vista al volcán, ideal para familia.', 'tipo' => 'casa', 'direccion' => 'Col. Santa Mónica, Av. Jerusalén #45', 'ciudad' => 'Santa Tecla', 'provincia' => 'La Libertad', 'precio' => 195000.00, 'superficie' => 180.00, 'ambientes' => 5, 'banos' => 2, 'dormitorios' => 3, 'antiguedad' => 5, 'estado' => 'disponible'],
                    ['id_propiedad' => 3, 'id_agente' => 1, 'titulo' => 'Apartamento Torre San Benito', 'descripcion' => 'Apartamento moderno piso 8 con balcón y seguridad 24h.', 'tipo' => 'departamento', 'direccion' => 'Col. San Benito, Av. La Capilla #233', 'ciudad' => 'San Salvador', 'provincia' => 'San Salvador', 'precio' => 135000.00, 'superficie' => 95.00, 'ambientes' => 3, 'banos' => 2, 'dormitorios' => 2, 'antiguedad' => 3, 'estado' => 'disponible'],
                    ['id_propiedad' => 4, 'id_agente' => 3, 'titulo' => 'Apartamento Antiguo Cuscatlán', 'descripcion' => 'Apartamento acogedor cerca de Multiplaza, ideal primera vivienda.', 'tipo' => 'departamento', 'direccion' => 'Res. Santa Elena, Senda Los Eucaliptos #7', 'ciudad' => 'Antiguo Cuscatlán', 'provincia' => 'La Libertad', 'precio' => 98000.00, 'superficie' => 72.00, 'ambientes' => 3, 'banos' => 1, 'dormitorios' => 2, 'antiguedad' => 6, 'estado' => 'reservada'],
                    ['id_propiedad' => 5, 'id_agente' => 2, 'titulo' => 'Lote Bosques de Santa Elena', 'descripcion' => 'Lote plano listo para construir, acceso privado y portón.', 'tipo' => 'terreno', 'direccion' => 'Bosques de Santa Elena, Pol. G #14', 'ciudad' => 'Antiguo Cuscatlán', 'provincia' => 'La Libertad', 'precio' => 75000.00, 'superficie' => 350.00, 'ambientes' => 0, 'banos' => 0, 'dormitorios' => 0, 'antiguedad' => 0, 'estado' => 'disponible'],
                    ['id_propiedad' => 6, 'id_agente' => 4, 'titulo' => 'Casa Col. Miramonte', 'descripcion' => 'Casa remodelada con cuarto de servicio y cisterna.', 'tipo' => 'casa', 'direccion' => 'Col. Miramonte, Calle Motocross #18', 'ciudad' => 'San Salvador', 'provincia' => 'San Salvador', 'precio' => 165000.00, 'superficie' => 150.00, 'ambientes' => 5, 'banos' => 2, 'dormitorios' => 3, 'antiguedad' => 12, 'estado' => 'disponible'],
                    ['id_propiedad' => 7, 'id_agente' => 3, 'titulo' => 'Apartamento Santa Ana centro', 'descripcion' => 'Apartamento céntrico a 5 min del parque Libertad.', 'tipo' => 'departamento', 'direccion' => 'Col. San José, 2a Calle Poniente #30', 'ciudad' => 'Santa Ana', 'provincia' => 'Santa Ana', 'precio' => 62000.00, 'superficie' => 68.00, 'ambientes' => 2, 'banos' => 1, 'dormitorios' => 2, 'antiguedad' => 10, 'estado' => 'disponible'],
                    ['id_propiedad' => 8, 'id_agente' => 4, 'titulo' => 'Casa San Miguel con patio', 'descripcion' => 'Casa amplia con patio y local anexo para negocio.', 'tipo' => 'casa', 'direccion' => 'Col. La Presita, Av. Roosevelt #210', 'ciudad' => 'San Miguel', 'provincia' => 'San Miguel', 'precio' => 118000.00, 'superficie' => 200.00, 'ambientes' => 5, 'banos' => 2, 'dormitorios' => 3, 'antiguedad' => 15, 'estado' => 'alquilada'],
                    ['id_propiedad' => 9, 'id_agente' => 1, 'titulo' => 'Lote Altos de La Toma', 'descripcion' => 'Lote con vista panorámica, ideal proyecto familiar.', 'tipo' => 'terreno', 'direccion' => 'Altos de La Toma, Lot. #22', 'ciudad' => 'Soyapango', 'provincia' => 'San Salvador', 'precio' => 42000.00, 'superficie' => 280.00, 'ambientes' => 0, 'banos' => 0, 'dormitorios' => 0, 'antiguedad' => 0, 'estado' => 'disponible'],
                    ['id_propiedad' => 10, 'id_agente' => 2, 'titulo' => 'Casa Los Sueños Soyapango', 'descripcion' => 'Casa de una planta, recién pintada, todos los servicios.', 'tipo' => 'casa', 'direccion' => 'Col. Ciudad Satélite, Pje. Oriente #12', 'ciudad' => 'Soyapango', 'provincia' => 'San Salvador', 'precio' => 89500.00, 'superficie' => 120.00, 'ambientes' => 4, 'banos' => 2, 'dormitorios' => 3, 'antiguedad' => 20, 'estado' => 'vendida'],
                    ['id_propiedad' => 11, 'id_agente' => 3, 'titulo' => 'Apartamento Merliot ejecutivo', 'descripcion' => 'Apartamento ejecutivo con gym y cowork incluido.', 'tipo' => 'departamento', 'direccion' => 'Ciudad Merliot, Calle Chiltiupán #800', 'ciudad' => 'Santa Tecla', 'provincia' => 'La Libertad', 'precio' => 112000.00, 'superficie' => 85.00, 'ambientes' => 3, 'banos' => 2, 'dormitorios' => 2, 'antiguedad' => 2, 'estado' => 'disponible'],
                    ['id_propiedad' => 12, 'id_agente' => 4, 'titulo' => 'Lote Corinto Zaragoza', 'descripcion' => 'Lote rústico con acceso a calle principal, agua y luz.', 'tipo' => 'terreno', 'direccion' => 'Finca Corinto, Lot. #5', 'ciudad' => 'Zaragoza', 'provincia' => 'La Libertad', 'precio' => 38500.00, 'superficie' => 500.00, 'ambientes' => 0, 'banos' => 0, 'dormitorios' => 0, 'antiguedad' => 0, 'estado' => 'disponible'],
                ];
            case 'interacciones':
                return [
                    ['id_interaccion' => 1, 'id_cliente' => 1, 'id_agente' => 1, 'id_propiedad' => 1, 'titulo' => 'Consulta casa Escalón', 'descripcion' => 'Cliente pide precio final y formas de pago en USD.', 'estado_kanban' => 'nuevo', 'prioridad' => 'alta', 'medio' => 'WhatsApp', 'proxima_accion' => 'Enviar dossier y estructura de pago', 'fecha_proxima_accion' => '2026-10-14', 'fecha_interaccion' => '2026-10-08 10:00:00', 'posicion' => 1, 'created_at' => '2026-10-08 10:00:00', 'updated_at' => '2026-10-08 10:00:00'],
                    ['id_interaccion' => 2, 'id_cliente' => 2, 'id_agente' => 1, 'id_propiedad' => 3, 'titulo' => 'Tour Torre San Benito', 'descripcion' => 'Solicita visita fin de semana al apartamento.', 'estado_kanban' => 'nuevo', 'prioridad' => 'media', 'medio' => 'Teléfono', 'proxima_accion' => 'Agendar visita sábado', 'fecha_proxima_accion' => '2026-10-13', 'fecha_interaccion' => '2026-10-08 11:30:00', 'posicion' => 2, 'created_at' => '2026-10-08 11:30:00', 'updated_at' => '2026-10-08 11:30:00'],
                    ['id_interaccion' => 3, 'id_cliente' => 5, 'id_agente' => 2, 'id_propiedad' => 2, 'titulo' => 'Interés casa Santa Tecla', 'descripcion' => 'Lead de Facebook, pide fotos del patio.', 'estado_kanban' => 'nuevo', 'prioridad' => 'media', 'medio' => 'Facebook', 'proxima_accion' => 'Enviar fotos y video', 'fecha_proxima_accion' => '2026-10-12', 'fecha_interaccion' => '2026-10-09 09:00:00', 'posicion' => 3, 'created_at' => '2026-10-09 09:00:00', 'updated_at' => '2026-10-09 09:00:00'],
                    ['id_interaccion' => 4, 'id_cliente' => 4, 'id_agente' => 3, 'id_propiedad' => 4, 'titulo' => 'Seguimiento aparta Cuscatlán', 'descripcion' => 'Cliente ya visitó, evalúa crédito hipotecario.', 'estado_kanban' => 'seguimiento', 'prioridad' => 'alta', 'medio' => 'Visita', 'proxima_accion' => 'Enviar simulación bancaria', 'fecha_proxima_accion' => '2026-10-15', 'fecha_interaccion' => '2026-10-07 14:00:00', 'posicion' => 1, 'created_at' => '2026-10-07 14:00:00', 'updated_at' => '2026-10-07 14:00:00'],
                    ['id_interaccion' => 5, 'id_cliente' => 3, 'id_agente' => 4, 'id_propiedad' => 6, 'titulo' => 'Segunda visita Miramonte', 'descripcion' => 'Pide llevar a su familia a segunda visita.', 'estado_kanban' => 'seguimiento', 'prioridad' => 'media', 'medio' => 'WhatsApp', 'proxima_accion' => 'Confirmar visita familiar', 'fecha_proxima_accion' => '2026-10-14', 'fecha_interaccion' => '2026-10-06 16:00:00', 'posicion' => 2, 'created_at' => '2026-10-06 16:00:00', 'updated_at' => '2026-10-06 16:00:00'],
                    ['id_interaccion' => 6, 'id_cliente' => 6, 'id_agente' => 2, 'id_propiedad' => 9, 'titulo' => 'Duda lote La Toma', 'descripcion' => 'Pregunta por acceso a agua y escrituración.', 'estado_kanban' => 'seguimiento', 'prioridad' => 'baja', 'medio' => 'Email', 'proxima_accion' => 'Enviar copia de escritura madre', 'fecha_proxima_accion' => '2026-10-16', 'fecha_interaccion' => '2026-10-05 12:00:00', 'posicion' => 3, 'created_at' => '2026-10-05 12:00:00', 'updated_at' => '2026-10-05 12:00:00'],
                    ['id_interaccion' => 7, 'id_cliente' => 7, 'id_agente' => 3, 'id_propiedad' => 7, 'titulo' => 'Oferta apartamento Santa Ana', 'descripcion' => 'Cliente oferta USD 58,000 de contado.', 'estado_kanban' => 'negociacion', 'prioridad' => 'alta', 'medio' => 'Teléfono', 'proxima_accion' => 'Presentar oferta al propietario', 'fecha_proxima_accion' => '2026-10-12', 'fecha_interaccion' => '2026-10-04 09:30:00', 'posicion' => 1, 'created_at' => '2026-10-04 09:30:00', 'updated_at' => '2026-10-04 09:30:00'],
                    ['id_interaccion' => 8, 'id_cliente' => 8, 'id_agente' => 4, 'id_propiedad' => 8, 'titulo' => 'Negociación alquiler San Miguel', 'descripcion' => 'Negocian depósito y plazo a 2 años.', 'estado_kanban' => 'negociacion', 'prioridad' => 'media', 'medio' => 'Visita', 'proxima_accion' => 'Redactar contrato de alquiler', 'fecha_proxima_accion' => '2026-10-13', 'fecha_interaccion' => '2026-10-03 10:30:00', 'posicion' => 2, 'created_at' => '2026-10-03 10:30:00', 'updated_at' => '2026-10-03 10:30:00'],
                    ['id_interaccion' => 9, 'id_cliente' => 6, 'id_agente' => 2, 'id_propiedad' => 10, 'titulo' => 'Cierre casa Soyapango', 'descripcion' => 'Venta cerrada, pendiente firma en notaría.', 'estado_kanban' => 'cerrado', 'prioridad' => 'media', 'medio' => 'Email', 'proxima_accion' => 'Coordinar firma', 'fecha_proxima_accion' => '2026-10-11', 'fecha_interaccion' => '2026-10-02 11:00:00', 'posicion' => 1, 'created_at' => '2026-10-02 11:00:00', 'updated_at' => '2026-10-02 11:00:00'],
                    ['id_interaccion' => 10, 'id_cliente' => 1, 'id_agente' => 1, 'id_propiedad' => 11, 'titulo' => 'Postventa Merliot', 'descripcion' => 'Cliente desistió, se archiva con encuesta.', 'estado_kanban' => 'cerrado', 'prioridad' => 'baja', 'medio' => 'WhatsApp', 'proxima_accion' => 'Enviar encuesta de satisfacción', 'fecha_proxima_accion' => '2026-10-10', 'fecha_interaccion' => '2026-10-01 12:00:00', 'posicion' => 2, 'created_at' => '2026-10-01 12:00:00', 'updated_at' => '2026-10-01 12:00:00'],
                ];
            case 'citas':
                return [
                    ['id_cita' => 1, 'id_agente' => 1, 'id_cliente' => 1, 'id_propiedad' => 1, 'fecha' => '2026-10-13 10:00:00', 'estado' => 'confirmada', 'notas' => 'Visita casa Escalón con familia'],
                    ['id_cita' => 2, 'id_agente' => 1, 'id_cliente' => 2, 'id_propiedad' => 3, 'fecha' => '2026-10-13 15:00:00', 'estado' => 'pendiente', 'notas' => 'Tour apartamento San Benito'],
                    ['id_cita' => 3, 'id_agente' => 2, 'id_cliente' => 5, 'id_propiedad' => 2, 'fecha' => '2026-10-14 09:30:00', 'estado' => 'pendiente', 'notas' => 'Visita casa Santa Tecla'],
                    ['id_cita' => 4, 'id_agente' => 3, 'id_cliente' => 4, 'id_propiedad' => 4, 'fecha' => '2026-10-14 14:00:00', 'estado' => 'confirmada', 'notas' => 'Segunda visita con asesor bancario'],
                    ['id_cita' => 5, 'id_agente' => 3, 'id_cliente' => 7, 'id_propiedad' => 7, 'fecha' => '2026-10-11 11:00:00', 'estado' => 'realizada', 'notas' => 'Visita Santa Ana, oferta verbal'],
                    ['id_cita' => 6, 'id_agente' => 4, 'id_cliente' => 8, 'id_propiedad' => 8, 'fecha' => '2026-10-12 16:00:00', 'estado' => 'pendiente', 'notas' => 'Firma precontrato alquiler San Miguel'],
                ];
            case 'transacciones':
                return [
                    ['id_transaccion' => 1, 'id_propiedad' => 10, 'id_cliente' => 6, 'tipo' => 'venta', 'monto' => 89500.00, 'fecha_inicio' => '2026-09-20', 'estado' => 'cerrada'],
                    ['id_transaccion' => 2, 'id_propiedad' => 8, 'id_cliente' => 8, 'tipo' => 'alquiler', 'monto' => 550.00, 'fecha_inicio' => '2026-10-01', 'estado' => 'activa'],
                    ['id_transaccion' => 3, 'id_propiedad' => 4, 'id_cliente' => 4, 'tipo' => 'venta', 'monto' => 98000.00, 'fecha_inicio' => '2026-10-05', 'estado' => 'pendiente'],
                    ['id_transaccion' => 4, 'id_propiedad' => 3, 'id_cliente' => 2, 'tipo' => 'alquiler', 'monto' => 750.00, 'fecha_inicio' => '2026-10-03', 'estado' => 'activa'],
                    ['id_transaccion' => 5, 'id_propiedad' => 5, 'id_cliente' => 3, 'tipo' => 'venta', 'monto' => 75000.00, 'fecha_inicio' => '2026-10-06', 'estado' => 'pendiente'],
                    ['id_transaccion' => 6, 'id_propiedad' => 2, 'id_cliente' => 5, 'tipo' => 'venta', 'monto' => 195000.00, 'fecha_inicio' => '2026-10-07', 'estado' => 'activa'],
                ];
            default:
                return [];
        }
    }
}

if (!function_exists('demo_datos')) {
    /**
     * Devuelve todos los registros demo de una entidad.
     * Lee de $_SESSION si ya hay cambios, si no usa el seed.
     */
    function demo_datos(string $entidad): array
    {
        demo__init();
        $e = strtolower(trim($entidad));
        if (demo__id_campo($e) === null) {
            return [];
        }
        if (!isset($_SESSION['demo'][$e]) || !is_array($_SESSION['demo'][$e])) {
            $_SESSION['demo'][$e] = demo__seed($e);
        }
        return array_values($_SESSION['demo'][$e]);
    }
}

if (!function_exists('demo_guardar')) {
    /**
     * Agrega un registro a la entidad (persiste en $_SESSION).
     * Auto-asigna id correlativo si no viene en $item. Devuelve el item guardado.
     */
    function demo_guardar(string $entidad, array $item): array
    {
        demo__init();
        $e = strtolower(trim($entidad));
        $idCampo = demo__id_campo($e);
        if ($idCampo === null) {
            return $item;
        }
        $todos = demo_datos($e);
        if (!isset($item[$idCampo]) || (int)$item[$idCampo] <= 0) {
            $max = 0;
            foreach ($todos as $row) {
                if (isset($row[$idCampo]) && (int)$row[$idCampo] > $max) {
                    $max = (int)$row[$idCampo];
                }
            }
            $item[$idCampo] = $max + 1;
        }
        $todos[] = $item;
        $_SESSION['demo'][$e] = array_values($todos);
        return $item;
    }
}

if (!function_exists('demo_escribir')) {
    /**
     * Reemplaza toda la colección de una entidad (persiste en $_SESSION).
     */
    function demo_escribir(string $entidad, array $todos): void
    {
        demo__init();
        $e = strtolower(trim($entidad));
        if (demo__id_campo($e) === null) {
            return;
        }
        $_SESSION['demo'][$e] = array_values($todos);
    }
}
