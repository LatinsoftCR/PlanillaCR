<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * CONTROLADOR AJAX PARA CONFIGURACIÓN Y PARÁMETROS LEGALES (CONFIGURACION.PHP)
 */
header('Content-Type: application/json; charset=utf-8');

// Prevenir acceso directo que no sea POST o GET controlado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Acceso no autorizado. Inicie sesión."]);
    exit;
}

require_once '../config/conexion.php';

$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';

if (empty($action)) {
    echo json_encode(["success" => false, "message" => "Acción de configuración no especificada."]);
    exit;
}

$usuario_accion = $_SESSION['username'];

switch ($action) {

    // ==========================================================================
    // 1. OBTENER PARÁMETROS CCSS ACTIVOS (OBRERO & PATRONO)
    // ==========================================================================
    case 'get_active_ccss':
        try {
            $stmt = $pdo->prepare("
                SELECT TOP 1 * 
                FROM ParametroCCSS 
                WHERE Estado = 'ACTIVO' 
                ORDER BY VigenciaDesde DESC
            ");
            $stmt->execute();
            $data = $stmt->fetch();

            if ($data) {
                echo json_encode(["success" => true, "data" => $data]);
            } else {
                // Fallback por si no hay registros activos
                echo json_encode([
                    "success" => true,
                    "data" => [
                        "SEMObrero" => 5.5000,
                        "IVMObrero" => 4.1700,
                        "BancoPopular" => 1.0000,
                        "SEMPatrono" => 9.2500,
                        "IVMPatrono" => 5.4200,
                        "BancoPopularPatrono" => 0.5000,
                        "FODESAF" => 5.0000,
                        "IMAS" => 0.5000,
                        "INA" => 1.5000,
                        "FCL" => 1.5000,
                        "ROP" => 1.5000,
                        "INS" => 1.5000,
                        "VigenciaDesde" => date('Y-01-01'),
                        "VigenciaHasta" => date('Y-12-31')
                    ]
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al obtener parámetros CCSS: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 2. GUARDAR NUEVA VIGENCIA DE CCSS & CARGAS SOCIALES (HISTÓRICO)
    // ==========================================================================
    case 'save_ccss':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(["success" => false, "message" => "Método no permitido."]);
            exit;
        }

        $vigencia_desde = isset($_POST['vigencia_desde']) ? trim($_POST['vigencia_desde']) : '';
        $vigencia_hasta = isset($_POST['vigencia_hasta']) ? trim($_POST['vigencia_hasta']) : '2099-12-31';

        $sem_obrero = isset($_POST['sem_obrero']) ? floatval($_POST['sem_obrero']) : 0.0;
        $ivm_obrero = isset($_POST['ivm_obrero']) ? floatval($_POST['ivm_obrero']) : 0.0;
        $bp_obrero = isset($_POST['bp_obrero']) ? floatval($_POST['bp_obrero']) : 0.0;

        $sem_patrono = isset($_POST['sem_patrono']) ? floatval($_POST['sem_patrono']) : 0.0;
        $ivm_patrono = isset($_POST['ivm_patrono']) ? floatval($_POST['ivm_patrono']) : 0.0;
        $bp_patrono = isset($_POST['bp_patrono']) ? floatval($_POST['bp_patrono']) : 0.0;

        $fodesaf = isset($_POST['fodesaf']) ? floatval($_POST['fodesaf']) : 0.0;
        $imas = isset($_POST['imas']) ? floatval($_POST['imas']) : 0.0;
        $ina = isset($_POST['ina']) ? floatval($_POST['ina']) : 0.0;
        $fcl = isset($_POST['fcl']) ? floatval($_POST['fcl']) : 0.0;
        $rop = isset($_POST['rop']) ? floatval($_POST['rop']) : 0.0;
        $ins = isset($_POST['ins']) ? floatval($_POST['ins']) : 0.0;

        if (empty($vigencia_desde)) {
            echo json_encode(["success" => false, "message" => "La fecha de inicio de vigencia es obligatoria."]);
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                EXEC sp_ParametroCCSSInsertar
                    @VigenciaDesde = :vigencia_desde,
                    @VigenciaHasta = :vigencia_hasta,
                    @SEMObrero = :sem_obrero,
                    @IVMObrero = :ivm_obrero,
                    @BancoPopular = :bp_obrero,
                    @SEMPatrono = :sem_patrono,
                    @IVMPatrono = :ivm_patrono,
                    @BancoPopularPatrono = :bp_patrono,
                    @FODESAF = :fodesaf,
                    @IMAS = :imas,
                    @INA = :ina,
                    @FCL = :fcl,
                    @ROP = :rop,
                    @INS = :ins,
                    @Usuario = :usuario
            ");

            $stmt->execute([
                'vigencia_desde' => $vigencia_desde,
                'vigencia_hasta' => $vigencia_hasta,
                'sem_obrero' => $sem_obrero,
                'ivm_obrero' => $ivm_obrero,
                'bp_obrero' => $bp_obrero,
                'sem_patrono' => $sem_patrono,
                'ivm_patrono' => $ivm_patrono,
                'bp_patrono' => $bp_patrono,
                'fodesaf' => $fodesaf,
                'imas' => $imas,
                'ina' => $ina,
                'fcl' => $fcl,
                'rop' => $rop,
                'ins' => $ins,
                'usuario' => $usuario_accion
            ]);

            echo json_encode(["success" => true, "message" => "Nueva vigencia de parámetros legales de Costa Rica guardada correctamente."]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al guardar parámetros de cargas sociales: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 3. LISTAR HISTORIAL DE CCSS & CARGAS SOCIALES
    // ==========================================================================
    case 'list_ccss_history':
        try {
            $stmt = $pdo->prepare("
                SELECT * 
                FROM ParametroCCSS 
                ORDER BY VigenciaDesde DESC
            ");
            $stmt->execute();
            $data = $stmt->fetchAll();

            echo json_encode(["success" => true, "data" => $data]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al obtener historial: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 4. LISTAR TRAMOS DE IMPUESTO SOBRE LA RENTA
    // ==========================================================================
    case 'list_renta':
        try {
            $stmt = $pdo->prepare("
                SELECT * 
                FROM TablaRenta 
                ORDER BY DesdeMonto ASC
            ");
            $stmt->execute();
            $data = $stmt->fetchAll();

            echo json_encode(["success" => true, "data" => $data]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al obtener tramos de renta: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 5. OBTENER DETALLE DE TRAMO DE RENTA INDIVIDUAL
    // ==========================================================================
    case 'get_renta':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        try {
            $stmt = $pdo->prepare("SELECT * FROM TablaRenta WHERE Codigo = :id");
            $stmt->execute(['id' => $id]);
            $data = $stmt->fetch();

            if ($data) {
                echo json_encode(["success" => true, "data" => $data]);
            } else {
                echo json_encode(["success" => false, "message" => "Tramo de impuesto no encontrado."]);
            }
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error en base de datos: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 6. GUARDAR / EDITAR TRAMO DE IMPUESTO SOBRE LA RENTA
    // ==========================================================================
    case 'save_renta':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(["success" => false, "message" => "Método no permitido."]);
            exit;
        }

        $codigo = isset($_POST['codigo_renta']) ? intval($_POST['codigo_renta']) : 0;
        $desde = isset($_POST['desde_monto']) ? floatval($_POST['desde_monto']) : 0.0;
        $hasta = isset($_POST['hasta_monto']) ? floatval($_POST['hasta_monto']) : 999999999.0;
        $porcentaje = isset($_POST['porcentaje_renta']) ? floatval($_POST['porcentaje_renta']) : 0.0;
        $exceso = isset($_POST['exceso_renta']) ? floatval($_POST['exceso_renta']) : 0.0;
        $base = isset($_POST['base_renta']) ? floatval($_POST['base_renta']) : 0.0;

        try {
            $stmt = $pdo->prepare("
                EXEC sp_TablaRentaBracketGuardar
                    @Codigo = :codigo,
                    @DesdeMonto = :desde,
                    @HastaMonto = :hasta,
                    @Porcentaje = :porcentaje,
                    @Exceso = :exceso,
                    @Base = :base,
                    @Usuario = :usuario
            ");

            $stmt->execute([
                'codigo' => $codigo,
                'desde' => $desde,
                'hasta' => $hasta,
                'porcentaje' => $porcentaje,
                'exceso' => $exceso,
                'base' => $base,
                'usuario' => $usuario_accion
            ]);

            echo json_encode(["success" => true, "message" => "Tramo de Impuesto sobre la Renta guardado exitosamente."]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al guardar tramo de renta: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 7. ELIMINAR TRAMO DE IMPUESTO SOBRE LA RENTA
    // ==========================================================================
    case 'delete_renta':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(["success" => false, "message" => "Método no permitido."]);
            exit;
        }

        $codigo = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($codigo <= 0) {
            echo json_encode(["success" => false, "message" => "Código de tramo inválido."]);
            exit;
        }

        try {
            $stmt = $pdo->prepare("EXEC sp_TablaRentaBracketEliminar @Codigo = :codigo, @Usuario = :usuario");
            $stmt->execute([
                'codigo' => $codigo,
                'usuario' => $usuario_accion
            ]);

            echo json_encode(["success" => true, "message" => "Tramo de renta eliminado de forma permanente."]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al eliminar tramo: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 8. OBTENER CONFIGURACIONES GENERALES DEL SISTEMA
    // ==========================================================================
    case 'get_general':
        try {
            $stmt = $pdo->prepare("SELECT Clave, Valor FROM ConfiguracionSistema WHERE Estado = 'ACTIVO'");
            $stmt->execute();
            $rows = $stmt->fetchAll();

            $config = [];
            foreach ($rows as $r) {
                $config[$r['Clave']] = $r['Valor'];
            }

            echo json_encode(["success" => true, "data" => $config]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al obtener configuraciones generales: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 9. GUARDAR CONFIGURACIONES GENERALES
    // ==========================================================================
    case 'save_general':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(["success" => false, "message" => "Método no permitido."]);
            exit;
        }

        $horas_semana = isset($_POST['horas_semana']) ? trim($_POST['horas_semana']) : '48';
        $decimales = isset($_POST['decimales_planilla']) ? trim($_POST['decimales_planilla']) : '2';
        $moneda = isset($_POST['moneda_sistema']) ? trim($_POST['moneda_sistema']) : 'CRC';

        try {
            $pdo->beginTransaction();

            $settings = [
                'HORAS_SEMANA' => $horas_semana,
                'DECIMALES_PLANILLA' => $decimales,
                'MONEDA' => $moneda
            ];

            foreach ($settings as $key => $val) {
                // Verificar si existe la clave
                $stmt_chk = $pdo->prepare("SELECT COUNT(*) AS total FROM ConfiguracionSistema WHERE Clave = :key");
                $stmt_chk->execute(['key' => $key]);
                $row = $stmt_chk->fetch();

                if ($row['total'] > 0) {
                    $stmt_upd = $pdo->prepare("
                        UPDATE ConfiguracionSistema
                        SET Valor = :val,
                            FechaModificacion = GETDATE(),
                            UsuarioModificacion = :usuario
                        WHERE Clave = :key
                    ");
                    $stmt_upd->execute(['val' => $val, 'usuario' => $usuario_accion, 'key' => $key]);
                } else {
                    $stmt_ins = $pdo->prepare("
                        INSERT INTO ConfiguracionSistema (Clave, Valor, Estado, UsuarioCreacion)
                        VALUES (:key, :val, 'ACTIVO', :usuario)
                    ");
                    $stmt_ins->execute(['key' => $key, 'val' => $val, 'usuario' => $usuario_accion]);
                }
            }

            // Registrar en bitácora
            $stmt_log = $pdo->prepare("
                INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) 
                VALUES (:usuario, 'CONFIGURACION GENERAL', 'Actualización de parámetros del sistema: Horas Semana=' + :horas + ', Decimales=' + :decimales + ', Moneda=' + :moneda)
            ");
            $stmt_log->execute([
                'usuario' => $usuario_accion,
                'horas' => $horas_semana,
                'decimales' => $decimales,
                'moneda' => $moneda
            ]);

            $pdo->commit();
            echo json_encode(["success" => true, "message" => "Parámetros generales del ERP actualizados con éxito."]);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(["success" => false, "message" => "Fallo al actualizar configuraciones: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 10. LISTAR RUBROS DE PLANILLA Y SUS CUENTAS CONTABLES
    // ==========================================================================
    case 'list_rubros':
        try {
            $stmt = $pdo->prepare("
                SELECT CodRubro, Codigo, Descripcion, Tipo, CuentaContable 
                FROM RubroPlanilla 
                WHERE Estado = 'ACTIVO' 
                ORDER BY Tipo DESC, Prioridad ASC, Codigo ASC
            ");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(["success" => true, "data" => $data]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al obtener rubros: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 11. GUARDAR CUENTA CONTABLE DE UN RUBRO DE PLANILLA
    // ==========================================================================
    case 'save_rubro_cuenta':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(["success" => false, "message" => "Método no permitido."]);
            exit;
        }

        $cod_rubro = isset($_POST['cod_rubro']) ? intval($_POST['cod_rubro']) : 0;
        $cuenta_contable = isset($_POST['cuenta_contable']) ? trim($_POST['cuenta_contable']) : '';

        if ($cod_rubro <= 0) {
            echo json_encode(["success" => false, "message" => "Código de rubro no válido."]);
            exit;
        }

        try {
            // Obtener datos del rubro para bitácora
            $stmt_rub = $pdo->prepare("SELECT Codigo, Descripcion FROM RubroPlanilla WHERE CodRubro = :cod_rubro");
            $stmt_rub->execute(['cod_rubro' => $cod_rubro]);
            $rubro = $stmt_rub->fetch(PDO::FETCH_ASSOC);

            if (!$rubro) {
                echo json_encode(["success" => false, "message" => "El rubro especificado no existe."]);
                exit;
            }

            // Actualizar cuenta contable
            $stmt = $pdo->prepare("
                UPDATE RubroPlanilla
                SET CuentaContable = :cuenta_contable,
                    FechaModificacion = GETDATE(),
                    UsuarioModificacion = :usuario
                WHERE CodRubro = :cod_rubro
            ");
            $stmt->execute([
                'cuenta_contable' => !empty($cuenta_contable) ? $cuenta_contable : null,
                'usuario' => $usuario_accion,
                'cod_rubro' => $cod_rubro
            ]);

            // Registrar en bitácora
            $stmt_log = $pdo->prepare("
                INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) 
                VALUES (:usuario, 'CONFIGURACION CONTABLE', 'Asignación de cuenta contable: Rubro=' + :rubro_cod + ' (' + :rubro_desc + ') -> Cuenta=' + :cuenta)
            ");
            $stmt_log->execute([
                'usuario' => $usuario_accion,
                'rubro_cod' => $rubro['Codigo'],
                'rubro_desc' => $rubro['Descripcion'],
                'cuenta' => !empty($cuenta_contable) ? $cuenta_contable : 'NINGUNA'
            ]);

            echo json_encode(["success" => true, "message" => "Cuenta contable asignada correctamente al rubro: " . $rubro['Descripcion'] . "."]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al guardar cuenta contable: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 12. OBTENER DETALLE DE UN RUBRO POR ID
    // ==========================================================================
    case 'get_rubro':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "Código de rubro inválido."]);
            exit;
        }
        try {
            $stmt = $pdo->prepare("SELECT * FROM RubroPlanilla WHERE CodRubro = :id");
            $stmt->execute(['id' => $id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($record) {
                echo json_encode(["success" => true, "data" => $record]);
            } else {
                echo json_encode(["success" => false, "message" => "Rubro no encontrado."]);
            }
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error de base de datos: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 13. CREAR / EDITAR RUBRO DE PLANILLA (CATÁLOGO MAESTRO)
    // ==========================================================================
    case 'save_rubro':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(["success" => false, "message" => "Método no permitido."]);
            exit;
        }
        $cod_rubro = isset($_POST['cod_rubro']) ? intval($_POST['cod_rubro']) : 0;
        $codigo = isset($_POST['codigo']) ? strtoupper(trim($_POST['codigo'])) : '';
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : 'DEVENGO';
        $prioridad = isset($_POST['prioridad']) ? intval($_POST['prioridad']) : 0;
        
        $afecta_ccss = isset($_POST['afecta_ccss']) ? 1 : 0;
        $afecta_renta = isset($_POST['afecta_renta']) ? 1 : 0;
        $afecta_vacaciones = isset($_POST['afecta_vacaciones']) ? 1 : 0;
        $afecta_aguinaldo = isset($_POST['afecta_aguinaldo']) ? 1 : 0;
        $cuenta_contable = isset($_POST['cuenta_contable']) ? trim($_POST['cuenta_contable']) : '';
        $estado = isset($_POST['estado']) ? trim($_POST['estado']) : 'ACTIVO';

        if (empty($codigo) || empty($descripcion) || empty($tipo)) {
            echo json_encode(["success" => false, "message" => "Código, Descripción y Tipo son requeridos."]);
            exit;
        }

        try {
            if ($cod_rubro === 0) {
                // Verificar si código ya existe
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM RubroPlanilla WHERE Codigo = :codigo");
                $stmtCheck->execute(['codigo' => $codigo]);
                if ($stmtCheck->fetchColumn() > 0) {
                    echo json_encode(["success" => false, "message" => "El código de rubro '{$codigo}' ya existe en el sistema."]);
                    exit;
                }

                // INSERTAR
                $stmt = $pdo->prepare("
                    INSERT INTO RubroPlanilla (Codigo, Descripcion, Tipo, Prioridad, AfectaCCSS, AfectaRenta, AfectaVacaciones, AfectaAguinaldo, CuentaContable, Estado, UsuarioCreacion)
                    VALUES (:codigo, :descripcion, :tipo, :prioridad, :afecta_ccss, :afecta_renta, :afecta_vacaciones, :afecta_aguinaldo, :cuenta_contable, :estado, :usuario)
                ");
                $stmt->execute([
                    'codigo' => $codigo,
                    'descripcion' => $descripcion,
                    'tipo' => $tipo,
                    'prioridad' => $prioridad,
                    'afecta_ccss' => $afecta_ccss,
                    'afecta_renta' => $afecta_renta,
                    'afecta_vacaciones' => $afecta_vacaciones,
                    'afecta_aguinaldo' => $afecta_aguinaldo,
                    'cuenta_contable' => !empty($cuenta_contable) ? $cuenta_contable : null,
                    'estado' => $estado,
                    'usuario' => $usuario_accion
                ]);

                // Registrar en bitácora
                $stmt_log = $pdo->prepare("
                    INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) 
                    VALUES (:usuario, 'CATALOGO RUBROS', 'Creado nuevo rubro: ' + :codigo + ' - ' + :descripcion)
                ");
                $stmt_log->execute(['usuario' => $usuario_accion, 'codigo' => $codigo, 'descripcion' => $descripcion]);

                echo json_encode(["success" => true, "message" => "Rubro creado con éxito en el catálogo maestro."]);
            } else {
                // ACTUALIZAR
                $stmt = $pdo->prepare("
                    UPDATE RubroPlanilla
                    SET Descripcion = :descripcion,
                        Tipo = :tipo,
                        Prioridad = :prioridad,
                        AfectaCCSS = :afecta_ccss,
                        AfectaRenta = :afecta_renta,
                        AfectaVacaciones = :afecta_vacaciones,
                        AfectaAguinaldo = :afecta_aguinaldo,
                        CuentaContable = :cuenta_contable,
                        Estado = :estado,
                        FechaModificacion = GETDATE(),
                        UsuarioModificacion = :usuario
                    WHERE CodRubro = :cod_rubro
                ");
                $stmt->execute([
                    'descripcion' => $descripcion,
                    'tipo' => $tipo,
                    'prioridad' => $prioridad,
                    'afecta_ccss' => $afecta_ccss,
                    'afecta_renta' => $afecta_renta,
                    'afecta_vacaciones' => $afecta_vacaciones,
                    'afecta_aguinaldo' => $afecta_aguinaldo,
                    'cuenta_contable' => !empty($cuenta_contable) ? $cuenta_contable : null,
                    'estado' => $estado,
                    'usuario' => $usuario_accion,
                    'cod_rubro' => $cod_rubro
                ]);

                // Registrar en bitácora
                $stmt_log = $pdo->prepare("
                    INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) 
                    VALUES (:usuario, 'CATALOGO RUBROS', 'Actualizado rubro ID: ' + CAST(:cod_rubro AS VARCHAR(10)) + ' (' + :descripcion + ')')
                ");
                $stmt_log->execute(['usuario' => $usuario_accion, 'cod_rubro' => $cod_rubro, 'descripcion' => $descripcion]);

                echo json_encode(["success" => true, "message" => "Rubro actualizado con éxito."]);
            }
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al guardar rubro: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 14. INACTIVAR RUBRO DE PLANILLA (LÓGICO)
    // ==========================================================================
    case 'delete_rubro':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "Código de rubro no válido."]);
            exit;
        }
        try {
            // Verificar si es un rubro del sistema
            $stmtCheck = $pdo->prepare("SELECT Codigo, Descripcion FROM RubroPlanilla WHERE CodRubro = :id");
            $stmtCheck->execute(['id' => $id]);
            $rubro = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($rubro && in_array($rubro['Codigo'], ['SAL', 'HEX', 'DES', 'CCSS', 'REN', 'BP'])) {
                echo json_encode(["success" => false, "message" => "No se puede inactivar el rubro de sistema '{$rubro['Descripcion']}' ya que es crítico para el cálculo legal."]);
                exit;
            }

            $stmt = $pdo->prepare("
                UPDATE RubroPlanilla
                SET Estado = 'INACTIVO',
                    FechaModificacion = GETDATE(),
                    UsuarioModificacion = :usuario
                WHERE CodRubro = :id
            ");
            $stmt->execute([
                'id' => $id,
                'usuario' => $usuario_accion
            ]);

            // Registrar en bitácora
            $stmt_log = $pdo->prepare("
                INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) 
                VALUES (:usuario, 'CATALOGO RUBROS', 'Inactivado rubro ID: ' + CAST(:id AS VARCHAR(10)))
            ");
            $stmt_log->execute(['usuario' => $usuario_accion, 'id' => $id]);

            echo json_encode(["success" => true, "message" => "Rubro inactivado con éxito del catálogo maestro."]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al inactivar rubro: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 15. LISTAR EMPRESAS
    // ==========================================================================
    case 'list_empresas':
        try {
            $stmt = $pdo->prepare("
                SELECT CodEmpresa, Nombre, NombreComercial, CedulaJuridica, Telefono, Correo, Direccion, CodigoCCSS, CodigoINS, Moneda, Estado, Logo
                FROM Empresa
                ORDER BY NombreComercial ASC, Nombre ASC
            ");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(["success" => true, "data" => $data]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al obtener empresas: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 16. OBTENER DETALLE DE UNA EMPRESA POR ID
    // ==========================================================================
    case 'get_empresa':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "Código de empresa inválido."]);
            exit;
        }
        try {
            $stmt = $pdo->prepare("SELECT * FROM Empresa WHERE CodEmpresa = :id");
            $stmt->execute(['id' => $id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($record) {
                echo json_encode(["success" => true, "data" => $record]);
            } else {
                echo json_encode(["success" => false, "message" => "Empresa no encontrada."]);
            }
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error de base de datos: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 17. CREAR / EDITAR EMPRESA
    // ==========================================================================
    case 'save_empresa':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(["success" => false, "message" => "Método no permitido."]);
            exit;
        }
        $cod_empresa = isset($_POST['cod_empresa']) ? intval($_POST['cod_empresa']) : 0;
        $nombre = trim($_POST['nombre']);
        $nombre_comercial = isset($_POST['nombre_comercial']) ? trim($_POST['nombre_comercial']) : '';
        $cedula_juridica = trim($_POST['cedula_juridica']);
        $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
        $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
        $direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : '';
        $codigo_ccss = isset($_POST['codigo_ccss']) ? trim($_POST['codigo_ccss']) : '';
        $codigo_ins = isset($_POST['codigo_ins']) ? trim($_POST['codigo_ins']) : '';
        $moneda = isset($_POST['moneda']) ? trim($_POST['moneda']) : 'CRC';
        $estado = isset($_POST['estado']) ? trim($_POST['estado']) : 'ACTIVO';
        $logo = isset($_POST['logo']) ? trim($_POST['logo']) : '';

        if (empty($nombre) || empty($cedula_juridica)) {
            echo json_encode(["success" => false, "message" => "La Razón Social y Cédula Jurídica son requeridas."]);
            exit;
        }

        try {
            if ($cod_empresa === 0) {
                // Verificar si cédula jurídica ya existe
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM Empresa WHERE CedulaJuridica = :cedula_juridica");
                $stmtCheck->execute(['cedula_juridica' => $cedula_juridica]);
                if ($stmtCheck->fetchColumn() > 0) {
                    echo json_encode(["success" => false, "message" => "La Cédula Jurídica '{$cedula_juridica}' ya está registrada en el sistema."]);
                    exit;
                }

                // INSERTAR
                $stmt = $pdo->prepare("
                    INSERT INTO Empresa (Nombre, NombreComercial, CedulaJuridica, Telefono, Correo, Direccion, CodigoCCSS, CodigoINS, Moneda, Estado, Logo, UsuarioCreacion)
                    VALUES (:nombre, :nombre_comercial, :cedula_juridica, :telefono, :correo, :direccion, :codigo_ccss, :codigo_ins, :moneda, :estado, :logo, :usuario)
                ");
                $stmt->execute([
                    'nombre' => $nombre,
                    'nombre_comercial' => !empty($nombre_comercial) ? $nombre_comercial : null,
                    'cedula_juridica' => $cedula_juridica,
                    'telefono' => !empty($telefono) ? $telefono : null,
                    'correo' => !empty($correo) ? $correo : null,
                    'direccion' => !empty($direccion) ? $direccion : null,
                    'codigo_ccss' => !empty($codigo_ccss) ? $codigo_ccss : null,
                    'codigo_ins' => !empty($codigo_ins) ? $codigo_ins : null,
                    'moneda' => $moneda,
                    'estado' => $estado,
                    'logo' => !empty($logo) ? $logo : null,
                    'usuario' => $usuario_accion
                ]);

                // Registrar en bitácora
                $stmt_log = $pdo->prepare("
                    INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) 
                    VALUES (:usuario, 'CONFIGURACION EMPRESAS', 'Creada nueva empresa: ' + :nombre + ' (' + :cedula + ')')
                ");
                $stmt_log->execute(['usuario' => $usuario_accion, 'nombre' => $nombre, 'cedula' => $cedula_juridica]);

                echo json_encode(["success" => true, "message" => "Empresa registrada con éxito."]);
            } else {
                // ACTUALIZAR
                $stmt = $pdo->prepare("
                    UPDATE Empresa
                    SET Nombre = :nombre,
                        NombreComercial = :nombre_comercial,
                        CedulaJuridica = :cedula_juridica,
                        Telefono = :telefono,
                        Correo = :correo,
                        Direccion = :direccion,
                        CodigoCCSS = :codigo_ccss,
                        CodigoINS = :codigo_ins,
                        Moneda = :moneda,
                        Estado = :estado,
                        Logo = :logo,
                        FechaModificacion = GETDATE(),
                        UsuarioModificacion = :usuario
                    WHERE CodEmpresa = :cod_empresa
                ");
                $stmt->execute([
                    'nombre' => $nombre,
                    'nombre_comercial' => !empty($nombre_comercial) ? $nombre_comercial : null,
                    'cedula_juridica' => $cedula_juridica,
                    'telefono' => !empty($telefono) ? $telefono : null,
                    'correo' => !empty($correo) ? $correo : null,
                    'direccion' => !empty($direccion) ? $direccion : null,
                    'codigo_ccss' => !empty($codigo_ccss) ? $codigo_ccss : null,
                    'codigo_ins' => !empty($codigo_ins) ? $codigo_ins : null,
                    'moneda' => $moneda,
                    'estado' => $estado,
                    'logo' => !empty($logo) ? $logo : null,
                    'usuario' => $usuario_accion,
                    'cod_empresa' => $cod_empresa
                ]);

                // Registrar en bitácora
                $stmt_log = $pdo->prepare("
                    INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) 
                    VALUES (:usuario, 'CONFIGURACION EMPRESAS', 'Actualizada empresa ID: ' + CAST(:cod_empresa AS VARCHAR(10)) + ' (' + :nombre + ')')
                ");
                $stmt_log->execute(['usuario' => $usuario_accion, 'cod_empresa' => $cod_empresa, 'nombre' => $nombre]);

                echo json_encode(["success" => true, "message" => "Empresa actualizada con éxito."]);
            }
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al guardar empresa: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 18. INACTIVAR EMPRESA (LÓGICO)
    // ==========================================================================
    case 'delete_empresa':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "Código de empresa no válido."]);
            exit;
        }
        try {
            $stmt = $pdo->prepare("
                UPDATE Empresa
                SET Estado = 'INACTIVO',
                    FechaModificacion = GETDATE(),
                    UsuarioModificacion = :usuario
                WHERE CodEmpresa = :id
            ");
            $stmt->execute([
                'id' => $id,
                'usuario' => $usuario_accion
            ]);

            // Registrar en bitácora
            $stmt_log = $pdo->prepare("
                INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) 
                VALUES (:usuario, 'CONFIGURACION EMPRESAS', 'Inactivada empresa ID: ' + CAST(:id AS VARCHAR(10)))
            ");
            $stmt_log->execute(['usuario' => $usuario_accion, 'id' => $id]);

            echo json_encode(["success" => true, "message" => "Empresa inactivada con éxito."]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al inactivar empresa: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 19. ACTIVAR EMPRESA (LÓGICO)
    // ==========================================================================
    case 'activate_empresa':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "Código de empresa no válido."]);
            exit;
        }
        try {
            $stmt = $pdo->prepare("
                UPDATE Empresa
                SET Estado = 'ACTIVO',
                    FechaModificacion = GETDATE(),
                    UsuarioModificacion = :usuario
                WHERE CodEmpresa = :id
            ");
            $stmt->execute([
                'id' => $id,
                'usuario' => $usuario_accion
            ]);

            // Registrar en bitácora
            $stmt_log = $pdo->prepare("
                INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) 
                VALUES (:usuario, 'CONFIGURACION EMPRESAS', 'Activada empresa ID: ' + CAST(:id AS VARCHAR(10)))
            ");
            $stmt_log->execute(['usuario' => $usuario_accion, 'id' => $id]);

            echo json_encode(["success" => true, "message" => "Empresa activada con éxito."]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al activar empresa: " . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(["success" => false, "message" => "Acción no reconocida."]);
        break;
}
