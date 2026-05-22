<?php
require_once __DIR__ . '/../config/conexion.php';

try {
    $sql = "
    IF OBJECT_ID('VacacionesDetalle') IS NOT NULL DROP TABLE VacacionesDetalle;
    
    CREATE TABLE VacacionesDetalle (
        Codigo                  INT IDENTITY(1,1) PRIMARY KEY,
        CodEmpleado             INT NOT NULL,
        FechaInicio             DATE NULL,
        FechaFin                DATE NULL,
        DiasSolicitados         DECIMAL(18,2) NOT NULL,
        TipoMovimiento          VARCHAR(50) NOT NULL, -- 'DISFRUTE', 'PAGO', 'AJUSTE'
        Observaciones           VARCHAR(500) NULL,
        MontoPagado             DECIMAL(18,2) DEFAULT 0.00,
        
        -- Campos de Auditoría Estándar
        Estado                  VARCHAR(20) DEFAULT 'ACTIVO',
        FechaCreacion           DATETIME DEFAULT GETDATE(),
        UsuarioCreacion         VARCHAR(100) DEFAULT 'SISTEMA',
        FechaModificacion       DATETIME NULL,
        UsuarioModificacion     VARCHAR(100) NULL,

        FOREIGN KEY (CodEmpleado) REFERENCES Empleado(CodEmpleado)
    );
    ";
    
    $pdo->exec($sql);
    echo "¡Tabla VacacionesDetalle creada con éxito!\n";
} catch (Exception $e) {
    echo "Error al crear la tabla: " . $e->getMessage() . "\n";
}
