<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * SCRIPT DE MIGRACIÓN: AMPLIACIÓN DE TABLA PARAMETROCCSS Y STORED PROCEDURES DE CONFIGURACIÓN
 */
header('Content-Type: text/plain; charset=utf-8');

// Cargar la conexión existente
require_once '../config/conexion.php';

echo "INICIANDO PROCESO DE MIGRACIÓN DE CONFIGURACIÓN LEGAL DE COSTA RICA...\n";
echo "====================================================================\n\n";

try {
    // 1. Ampliar columnas en ParametroCCSS si no existen
    echo "1. Validando estructura de la tabla 'ParametroCCSS'...\n";
    
    $check_columns = [
        'BancoPopularPatrono' => 'DECIMAL(18,4) NULL',
        'FODESAF' => 'DECIMAL(18,4) NULL',
        'IMAS' => 'DECIMAL(18,4) NULL',
        'INA' => 'DECIMAL(18,4) NULL',
        'FCL' => 'DECIMAL(18,4) NULL',
        'ROP' => 'DECIMAL(18,4) NULL'
    ];

    foreach ($check_columns as $col => $type) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total 
            FROM sys.columns 
            WHERE object_id = OBJECT_ID('ParametroCCSS') AND name = :name
        ");
        $stmt->execute(['name' => $col]);
        $row = $stmt->fetch();

        if ($row['total'] == 0) {
            echo "   -> Agregando columna '{$col}' ({$type})...\n";
            $pdo->exec("ALTER TABLE ParametroCCSS ADD {$col} {$type}");
        } else {
            echo "   -> La columna '{$col}' ya existe en 'ParametroCCSS'.\n";
        }
    }

    // 2. Poblar/Actualizar registros preexistentes de ParametroCCSS
    echo "\n2. Actualizando valores preexistentes de cargas patronales...\n";
    $pdo->exec("
        UPDATE ParametroCCSS
        SET 
            BancoPopularPatrono = ISNULL(BancoPopularPatrono, 0.5000),
            FODESAF = ISNULL(FODESAF, 5.0000),
            IMAS = ISNULL(IMAS, 0.5000),
            INA = ISNULL(INA, 1.5000),
            FCL = ISNULL(FCL, 1.5000),
            ROP = ISNULL(ROP, 1.5000)
    ");
    echo "   -> ¡Valores patronales unificados con éxito!\n";

    // 3. Crear Stored Procedures de Configuración
    echo "\n3. Compilando Stored Procedures de Configuración en SQL Server...\n";

    // SP: sp_ParametroCCSSInsertar (Histórico de Cargas)
    echo "   -> Creando 'sp_ParametroCCSSInsertar'...\n";
    $pdo->exec("
        IF OBJECT_ID('sp_ParametroCCSSInsertar') IS NOT NULL 
            DROP PROCEDURE sp_ParametroCCSSInsertar
    ");
    $pdo->exec("
        CREATE PROCEDURE sp_ParametroCCSSInsertar
        (
            @VigenciaDesde DATE,
            @VigenciaHasta DATE,
            @SEMObrero DECIMAL(18,4),
            @IVMObrero DECIMAL(18,4),
            @BancoPopular DECIMAL(18,4),
            @SEMPatrono DECIMAL(18,4),
            @IVMPatrono DECIMAL(18,4),
            @BancoPopularPatrono DECIMAL(18,4),
            @FODESAF DECIMAL(18,4),
            @IMAS DECIMAL(18,4),
            @INA DECIMAL(18,4),
            @FCL DECIMAL(18,4),
            @ROP DECIMAL(18,4),
            @INS DECIMAL(18,4),
            @Usuario VARCHAR(100)
        )
        AS
        BEGIN
            SET NOCOUNT ON;
            BEGIN TRY
                BEGIN TRANSACTION;

                -- Inactivar el registro activo previo
                UPDATE ParametroCCSS
                SET Estado = 'INACTIVO',
                    VigenciaHasta = DATEADD(DAY, -1, @VigenciaDesde),
                    FechaModificacion = GETDATE(),
                    UsuarioModificacion = @Usuario
                WHERE Estado = 'ACTIVO';

                -- Insertar la nueva vigencia
                INSERT INTO ParametroCCSS (
                    VigenciaDesde, VigenciaHasta, 
                    SEMObrero, IVMObrero, BancoPopular,
                    SEMPatrono, IVMPatrono, BancoPopularPatrono,
                    FODESAF, IMAS, INA, FCL, ROP, INS,
                    Estado, UsuarioCreacion
                )
                VALUES (
                    @VigenciaDesde, @VigenciaHasta,
                    @SEMObrero, @IVMObrero, @BancoPopular,
                    @SEMPatrono, @IVMPatrono, @BancoPopularPatrono,
                    @FODESAF, @IMAS, @INA, @FCL, @ROP, @INS,
                    'ACTIVO', @Usuario
                );

                -- Registrar auditoría en la Bitácora (Regla #9)
                INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle)
                VALUES (@Usuario, 'GUARDAR PARÁMETROS CCSS', 'Nueva vigencia legal registrada desde: ' + CAST(@VigenciaDesde AS VARCHAR(10)) + '. Tasas Obrero/Patronal y Fodesaf unificadas.');

                COMMIT TRANSACTION;
            END TRY
            BEGIN CATCH
                IF @@TRANCOUNT > 0
                    ROLLBACK TRANSACTION;
                DECLARE @ErrorMessage NVARCHAR(4000) = ERROR_MESSAGE();
                RAISERROR (@ErrorMessage, 16, 1);
            END CATCH
        END
    ");

    // SP: sp_TablaRentaBracketGuardar
    echo "   -> Creando 'sp_TablaRentaBracketGuardar'...\n";
    $pdo->exec("
        IF OBJECT_ID('sp_TablaRentaBracketGuardar') IS NOT NULL 
            DROP PROCEDURE sp_TablaRentaBracketGuardar
    ");
    $pdo->exec("
        CREATE PROCEDURE sp_TablaRentaBracketGuardar
        (
            @Codigo INT,
            @DesdeMonto DECIMAL(18,2),
            @HastaMonto DECIMAL(18,2),
            @Porcentaje DECIMAL(18,4),
            @Exceso DECIMAL(18,2),
            @Base DECIMAL(18,2),
            @Usuario VARCHAR(100)
        )
        AS
        BEGIN
            SET NOCOUNT ON;
            BEGIN TRY
                BEGIN TRANSACTION;

                IF @Codigo > 0
                BEGIN
                    -- Actualizar tramo existente
                    UPDATE TablaRenta
                    SET DesdeMonto = @DesdeMonto,
                        HastaMonto = @HastaMonto,
                        Porcentaje = @Porcentaje,
                        Exceso = @Exceso,
                        Base = @Base,
                        FechaModificacion = GETDATE(),
                        UsuarioModificacion = @Usuario
                    WHERE Codigo = @Codigo;

                    INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle)
                    VALUES (@Usuario, 'EDITAR TRAMO RENTA', 'Tramo Renta ID: ' + CAST(@Codigo AS VARCHAR(10)) + ' actualizado.');
                END
                ELSE
                BEGIN
                    -- Crear nuevo tramo renta
                    INSERT INTO TablaRenta (
                        VigenciaDesde, VigenciaHasta, DesdeMonto, HastaMonto, 
                        Porcentaje, Exceso, Base, Estado, UsuarioCreacion
                    )
                    VALUES (
                        '2026-01-01', '2026-12-31', @DesdeMonto, @HastaMonto,
                        @Porcentaje, @Exceso, @Base, 'ACTIVO', @Usuario
                    );

                    INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle)
                    VALUES (@Usuario, 'CREAR TRAMO RENTA', 'Nuevo tramo renta agregado con límite inferior: ' + CAST(@DesdeMonto AS VARCHAR(15)));
                END

                COMMIT TRANSACTION;
            END TRY
            BEGIN CATCH
                IF @@TRANCOUNT > 0
                    ROLLBACK TRANSACTION;
                DECLARE @ErrorMessage NVARCHAR(4000) = ERROR_MESSAGE();
                RAISERROR (@ErrorMessage, 16, 1);
            END CATCH
        END
    ");

    // SP: sp_TablaRentaBracketEliminar
    echo "   -> Creando 'sp_TablaRentaBracketEliminar'...\n";
    $pdo->exec("
        IF OBJECT_ID('sp_TablaRentaBracketEliminar') IS NOT NULL 
            DROP PROCEDURE sp_TablaRentaBracketEliminar
    ");
    $pdo->exec("
        CREATE PROCEDURE sp_TablaRentaBracketEliminar
        (
            @Codigo INT,
            @Usuario VARCHAR(100)
        )
        AS
        BEGIN
            SET NOCOUNT ON;
            BEGIN TRY
                BEGIN TRANSACTION;

                -- Eliminar físicamente o inactivar lógicamente
                DELETE FROM TablaRenta WHERE Codigo = @Codigo;

                INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle)
                VALUES (@Usuario, 'ELIMINAR TRAMO RENTA', 'Tramo Renta ID: ' + CAST(@Codigo AS VARCHAR(10)) + ' eliminado.');

                COMMIT TRANSACTION;
            END TRY
            BEGIN CATCH
                IF @@TRANCOUNT > 0
                    ROLLBACK TRANSACTION;
                DECLARE @ErrorMessage NVARCHAR(4000) = ERROR_MESSAGE();
                RAISERROR (@ErrorMessage, 16, 1);
            END CATCH
        END
    ");

    echo "\n====================================================================\n";
    echo "¡PROCESO DE MIGRACIÓN COMPLETADO CON ÉXITO ABSOLUTO!\n";

} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO EN LA MIGRACIÓN:\n" . $e->getMessage() . "\n";
}
