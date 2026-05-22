<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * CONTROLADOR AJAX DE CATÁLOGOS ADMINISTRATIVOS (AJAX/CATALOGOS.PHP)
 */
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validar seguridad de sesión activa
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Acceso denegado: Sesión no activa."]);
    exit;
}

require_once '../config/conexion.php';

$action = isset($_GET['action']) ? trim($_GET['action']) : (isset($_POST['action']) ? trim($_POST['action']) : '');
$usuarioAccion = $_SESSION['username'];

switch ($action) {

    // ==========================================================================
    // 1. DEPARTAMENTOS
    // ==========================================================================
    
    case 'list_departments':
        try {
            $stmt = $pdo->query("SELECT d.CodDepartamento, d.CodEmpresa, e.Nombre AS Empresa, d.Descripcion, d.Estado 
                                 FROM Departamento d 
                                 INNER JOIN Empresa e ON d.CodEmpresa = e.CodEmpresa 
                                 ORDER BY d.CodDepartamento DESC");
            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $departments]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Error al listar departamentos: " . $e->getMessage()]);
        }
        break;

    case 'get_department':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "ID de departamento inválido."]);
            exit;
        }
        try {
            $stmt = $pdo->prepare("SELECT * FROM Departamento WHERE CodDepartamento = :id");
            $stmt->execute(['id' => $id]);
            $dept = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($dept) {
                echo json_encode(["success" => true, "data" => $dept]);
            } else {
                echo json_encode(["success" => false, "message" => "Departamento no encontrado."]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Error al recuperar departamento: " . $e->getMessage()]);
        }
        break;

    case 'save_department':
        $cod_departamento = isset($_POST['cod_departamento']) ? intval($_POST['cod_departamento']) : 0;
        $cod_empresa = isset($_POST['cod_empresa']) ? intval($_POST['cod_empresa']) : 0;
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $estado = isset($_POST['estado']) ? trim($_POST['estado']) : 'ACTIVO';

        if ($cod_empresa <= 0 || empty($descripcion)) {
            echo json_encode(["success" => false, "message" => "Por favor, asigne una empresa y digite una descripción para el departamento."]);
            exit;
        }

        try {
            if ($cod_departamento === 0) {
                // INSERT
                $stmt = $pdo->prepare("INSERT INTO Departamento (CodEmpresa, Descripcion, Estado, UsuarioCreacion) 
                                     VALUES (:cod_empresa, :descripcion, 'ACTIVO', :usuario)");
                $stmt->execute([
                    'cod_empresa' => $cod_empresa,
                    'descripcion' => $descripcion,
                    'usuario' => $usuarioAccion
                ]);
                
                // Bitácora
                $stmtBit = $pdo->prepare("INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) 
                                         VALUES (:usuario, 'CREAR DEPARTAMENTO', :detalle)");
                $stmtBit->execute([
                    'usuario' => $usuarioAccion,
                    'detalle' => "Se creó el departamento '$descripcion' de forma asíncrona."
                ]);

                echo json_encode(["success" => true, "message" => "Departamento creado exitosamente."]);
            } else {
                // UPDATE
                $stmt = $pdo->prepare("UPDATE Departamento 
                                     SET CodEmpresa = :cod_empresa, Descripcion = :descripcion, Estado = :estado,
                                         FechaModificacion = GETDATE(), UsuarioModificacion = :usuario 
                                     WHERE CodDepartamento = :id");
                $stmt->execute([
                    'cod_empresa' => $cod_empresa,
                    'descripcion' => $descripcion,
                    'estado' => $estado,
                    'usuario' => $usuarioAccion,
                    'id' => $cod_departamento
                ]);

                // Bitácora
                $stmtBit = $pdo->prepare("INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) 
                                         VALUES (:usuario, 'MODIFICAR DEPARTAMENTO', :detalle)");
                $stmtBit->execute([
                    'usuario' => $usuarioAccion,
                    'detalle' => "Se modificó el departamento ID $cod_departamento ('$descripcion')."
                ]);

                echo json_encode(["success" => true, "message" => "Departamento actualizado exitosamente."]);
            }
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Fallo al guardar departamento: " . $e->getMessage()]);
        }
        break;

    case 'delete_department':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "ID de departamento inválido."]);
            exit;
        }
        try {
            $stmt = $pdo->prepare("UPDATE Departamento 
                                 SET Estado = 'INACTIVO', FechaModificacion = GETDATE(), UsuarioModificacion = :usuario 
                                 WHERE CodDepartamento = :id");
            $stmt->execute([
                'usuario' => $usuarioAccion,
                'id' => $id
            ]);

            // Bitácora
            $stmtBit = $pdo->prepare("INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) 
                                     VALUES (:usuario, 'INACTIVAR DEPARTAMENTO', :detalle)");
            $stmtBit->execute([
                'usuario' => $usuarioAccion,
                'detalle' => "Baja lógica aplicada al departamento ID $id."
            ]);

            echo json_encode(["success" => true, "message" => "Departamento inactivado correctamente."]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al inactivar departamento: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 2. PUESTOS
    // ==========================================================================

    case 'list_positions':
        try {
            $stmt = $pdo->query("SELECT p.CodPuesto, p.CodEmpresa, e.Nombre AS Empresa, p.Descripcion, p.Estado 
                                 FROM Puesto p 
                                 INNER JOIN Empresa e ON p.CodEmpresa = e.CodEmpresa 
                                 ORDER BY p.CodPuesto DESC");
            $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $positions]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Error al listar puestos: " . $e->getMessage()]);
        }
        break;

    case 'get_position':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "ID de puesto inválido."]);
            exit;
        }
        try {
            $stmt = $pdo->prepare("SELECT * FROM Puesto WHERE CodPuesto = :id");
            $stmt->execute(['id' => $id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($post) {
                echo json_encode(["success" => true, "data" => $post]);
            } else {
                echo json_encode(["success" => false, "message" => "Puesto no encontrado."]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Error al recuperar puesto: " . $e->getMessage()]);
        }
        break;

    case 'save_position':
        $cod_puesto = isset($_POST['cod_puesto']) ? intval($_POST['cod_puesto']) : 0;
        $cod_empresa = isset($_POST['cod_empresa']) ? intval($_POST['cod_empresa']) : 0;
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $estado = isset($_POST['estado']) ? trim($_POST['estado']) : 'ACTIVO';

        if ($cod_empresa <= 0 || empty($descripcion)) {
            echo json_encode(["success" => false, "message" => "Por favor, asigne una empresa y digite una descripción para el puesto."]);
            exit;
        }

        try {
            if ($cod_puesto === 0) {
                // INSERT
                $stmt = $pdo->prepare("INSERT INTO Puesto (CodEmpresa, Descripcion, Estado, UsuarioCreacion) 
                                     VALUES (:cod_empresa, :descripcion, 'ACTIVO', :usuario)");
                $stmt->execute([
                    'cod_empresa' => $cod_empresa,
                    'descripcion' => $descripcion,
                    'usuario' => $usuarioAccion
                ]);
                
                // Bitácora
                $stmtBit = $pdo->prepare("INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) 
                                         VALUES (:usuario, 'CREAR PUESTO', :detalle)");
                $stmtBit->execute([
                    'usuario' => $usuarioAccion,
                    'detalle' => "Se creó el puesto '$descripcion' de forma asíncrona."
                ]);

                echo json_encode(["success" => true, "message" => "Puesto creado exitosamente."]);
            } else {
                // UPDATE
                $stmt = $pdo->prepare("UPDATE Puesto 
                                     SET CodEmpresa = :cod_empresa, Descripcion = :descripcion, Estado = :estado,
                                         FechaModificacion = GETDATE(), UsuarioModificacion = :usuario 
                                     WHERE CodPuesto = :id");
                $stmt->execute([
                    'cod_empresa' => $cod_empresa,
                    'descripcion' => $descripcion,
                    'estado' => $estado,
                    'usuario' => $usuarioAccion,
                    'id' => $cod_puesto
                ]);

                // Bitácora
                $stmtBit = $pdo->prepare("INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) 
                                         VALUES (:usuario, 'MODIFICAR PUESTO', :detalle)");
                $stmtBit->execute([
                    'usuario' => $usuarioAccion,
                    'detalle' => "Se modificó el puesto ID $cod_puesto ('$descripcion')."
                ]);

                echo json_encode(["success" => true, "message" => "Puesto actualizado exitosamente."]);
            }
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Fallo al guardar puesto: " . $e->getMessage()]);
        }
        break;

    case 'delete_position':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "ID de puesto inválido."]);
            exit;
        }
        try {
            $stmt = $pdo->prepare("UPDATE Puesto 
                                 SET Estado = 'INACTIVO', FechaModificacion = GETDATE(), UsuarioModificacion = :usuario 
                                 WHERE CodPuesto = :id");
            $stmt->execute([
                'usuario' => $usuarioAccion,
                'id' => $id
            ]);

            // Bitácora
            $stmtBit = $pdo->prepare("INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) 
                                     VALUES (:usuario, 'INACTIVAR PUESTO', :detalle)");
            $stmtBit->execute([
                'usuario' => $usuarioAccion,
                'detalle' => "Baja lógica aplicada al puesto ID $id."
            ]);

            echo json_encode(["success" => true, "message" => "Puesto inactivado correctamente."]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al inactivar puesto: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 3. HORARIOS
    // ==========================================================================

    case 'list_schedules':
        try {
            $stmt = $pdo->query("SELECT h.CodHorario, h.CodEmpresa, e.Nombre AS Empresa, h.Descripcion, 
                                        CAST(h.HoraEntrada AS VARCHAR(5)) AS HoraEntrada, 
                                        CAST(h.HoraSalida AS VARCHAR(5)) AS HoraSalida, 
                                        h.HorasDia, h.Estado 
                                 FROM Horario h 
                                 INNER JOIN Empresa e ON h.CodEmpresa = e.CodEmpresa 
                                 ORDER BY h.CodHorario DESC");
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $schedules]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Error al listar horarios: " . $e->getMessage()]);
        }
        break;

    case 'get_schedule':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "ID de horario inválido."]);
            exit;
        }
        try {
            $stmt = $pdo->prepare("SELECT CodHorario, CodEmpresa, Descripcion, 
                                          CAST(HoraEntrada AS VARCHAR(5)) AS HoraEntrada, 
                                          CAST(HoraSalida AS VARCHAR(5)) AS HoraSalida, 
                                          HorasDia, Estado 
                                   FROM Horario WHERE CodHorario = :id");
            $stmt->execute(['id' => $id]);
            $sch = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($sch) {
                echo json_encode(["success" => true, "data" => $sch]);
            } else {
                echo json_encode(["success" => false, "message" => "Horario no encontrado."]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Error al recuperar horario: " . $e->getMessage()]);
        }
        break;

    case 'save_schedule':
        $cod_horario = isset($_POST['cod_horario']) ? intval($_POST['cod_horario']) : 0;
        $cod_empresa = isset($_POST['cod_empresa']) ? intval($_POST['cod_empresa']) : 0;
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $hora_entrada = isset($_POST['hora_entrada']) ? trim($_POST['hora_entrada']) : '';
        $hora_salida = isset($_POST['hora_salida']) ? trim($_POST['hora_salida']) : '';
        $horas_dia = isset($_POST['horas_dia']) ? floatval($_POST['horas_dia']) : 0.00;
        $estado = isset($_POST['estado']) ? trim($_POST['estado']) : 'ACTIVO';

        if ($cod_empresa <= 0 || empty($descripcion) || empty($hora_entrada) || empty($hora_salida) || $horas_dia <= 0) {
            echo json_encode(["success" => false, "message" => "Por favor, asigne una empresa, una descripción, horas válidas de entrada/salida y las horas diarias calculadas."]);
            exit;
        }

        try {
            if ($cod_horario === 0) {
                // INSERT
                $stmt = $pdo->prepare("INSERT INTO Horario (CodEmpresa, Descripcion, HoraEntrada, HoraSalida, HorasDia, Estado, UsuarioCreacion) 
                                     VALUES (:cod_empresa, :descripcion, :hora_entrada, :hora_salida, :horas_dia, 'ACTIVO', :usuario)");
                $stmt->execute([
                    'cod_empresa' => $cod_empresa,
                    'descripcion' => $descripcion,
                    'hora_entrada' => $hora_entrada,
                    'hora_salida' => $hora_salida,
                    'horas_dia' => $horas_dia,
                    'usuario' => $usuarioAccion
                ]);
                
                // Bitácora
                $stmtBit = $pdo->prepare("INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) 
                                         VALUES (:usuario, 'CREAR HORARIO', :detalle)");
                $stmtBit->execute([
                    'usuario' => $usuarioAccion,
                    'detalle' => "Se creó el horario '$descripcion' ($hora_entrada - $hora_salida, $horas_dia horas/día)."
                ]);

                echo json_encode(["success" => true, "message" => "Horario creado exitosamente."]);
            } else {
                // UPDATE
                $stmt = $pdo->prepare("UPDATE Horario 
                                     SET CodEmpresa = :cod_empresa, Descripcion = :descripcion, 
                                         HoraEntrada = :hora_entrada, HoraSalida = :hora_salida, HorasDia = :horas_dia, Estado = :estado,
                                         FechaModificacion = GETDATE(), UsuarioModificacion = :usuario 
                                     WHERE CodHorario = :id");
                $stmt->execute([
                    'cod_empresa' => $cod_empresa,
                    'descripcion' => $descripcion,
                    'hora_entrada' => $hora_entrada,
                    'hora_salida' => $hora_salida,
                    'horas_dia' => $horas_dia,
                    'estado' => $estado,
                    'usuario' => $usuarioAccion,
                    'id' => $cod_horario
                ]);

                // Bitácora
                $stmtBit = $pdo->prepare("INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) 
                                         VALUES (:usuario, 'MODIFICAR HORARIO', :detalle)");
                $stmtBit->execute([
                    'usuario' => $usuarioAccion,
                    'detalle' => "Se modificó el horario ID $cod_horario ('$descripcion')."
                ]);

                echo json_encode(["success" => true, "message" => "Horario actualizado exitosamente."]);
            }
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Fallo al guardar horario: " . $e->getMessage()]);
        }
        break;

    case 'delete_schedule':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "ID de horario inválido."]);
            exit;
        }
        try {
            $stmt = $pdo->prepare("UPDATE Horario 
                                 SET Estado = 'INACTIVO', FechaModificacion = GETDATE(), UsuarioModificacion = :usuario 
                                 WHERE CodHorario = :id");
            $stmt->execute([
                'usuario' => $usuarioAccion,
                'id' => $id
            ]);

            // Bitácora
            $stmtBit = $pdo->prepare("INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) 
                                     VALUES (:usuario, 'INACTIVAR HORARIO', :detalle)");
            $stmtBit->execute([
                'usuario' => $usuarioAccion,
                'detalle' => "Baja lógica aplicada al horario ID $id."
            ]);

            echo json_encode(["success" => true, "message" => "Horario inactivado correctamente."]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al inactivar horario: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // ACCIÓN NO ENCONTRADA
    // ==========================================================================
    default:
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Acción administrativa de catálogos no soportada."]);
        break;
}
