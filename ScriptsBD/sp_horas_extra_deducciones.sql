USE [PlanillaCR]
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

/*==========================================================================
 1. FUNCIÓN AUXILIAR DE CÁLCULO LEGAL: fn_ObtenerSalarioHora
==========================================================================*/
IF OBJECT_ID('dbo.fn_ObtenerSalarioHora') IS NOT NULL DROP FUNCTION dbo.fn_ObtenerSalarioHora
GO

CREATE FUNCTION dbo.fn_ObtenerSalarioHora
(
    @CodEmpleado INT
)
RETURNS DECIMAL(18,2)
AS
BEGIN
    DECLARE @SalarioBase DECIMAL(18,2)
    DECLARE @TipoSalario VARCHAR(20)
    DECLARE @HorasDia DECIMAL(18,2)
    DECLARE @SalarioHora DECIMAL(18,2) = 0

    SELECT 
        @SalarioBase = e.SalarioBase,
        @TipoSalario = e.TipoSalario,
        @HorasDia = h.HorasDia
    FROM Empleado e
    INNER JOIN Horario h ON e.CodHorario = h.CodHorario
    WHERE e.CodEmpleado = @CodEmpleado

    -- Fallback si no hay horas definidas
    IF @HorasDia IS NULL OR @HorasDia = 0
        SET @HorasDia = 8.00

    IF @TipoSalario = 'HORA'
    BEGIN
        SET @SalarioHora = @SalarioBase
    END
    ELSE IF @TipoSalario = 'MENSUAL'
    BEGIN
        -- Normativa de Costa Rica: Empleados mensuales tienen divisor legal de 30 días
        SET @SalarioHora = (@SalarioBase / 30.00) / @HorasDia
    END
    ELSE IF @TipoSalario = 'QUINCENAL'
    BEGIN
        -- Salario quincenal se divide habitualmente sobre la mitad del mes (15 días)
        SET @SalarioHora = (@SalarioBase / 15.00) / @HorasDia
    END
    ELSE IF @TipoSalario = 'SEMANAL'
    BEGIN
        -- Salario semanal habitualmente se divide sobre 6 días laborales diarios
        SET @SalarioHora = (@SalarioBase / 6.00) / @HorasDia
    END
    ELSE
    BEGIN
        -- Default fallback para otros tipos
        SET @SalarioHora = (@SalarioBase / 30.00) / @HorasDia
    END

    RETURN ROUND(@SalarioHora, 2)
END
GO

/*==========================================================================
 2. CREACIÓN DE LA TABLA: HoraExtra
==========================================================================*/
IF OBJECT_ID('dbo.HoraExtra') IS NOT NULL DROP TABLE dbo.HoraExtra
GO

CREATE TABLE dbo.HoraExtra (
    CodHoraExtra            INT IDENTITY(1,1) PRIMARY KEY,
    CodEmpresa              INT NOT NULL,
    CodEmpleado             INT NOT NULL,
    Fecha                   DATE NOT NULL,
    CantidadHoras           DECIMAL(18,2) NOT NULL,
    FactorMultiplicador     DECIMAL(18,2) NOT NULL DEFAULT 1.50, -- 1.50 para extra ordinaria, 2.00 para extra doble (feriados)
    MontoCalculado          DECIMAL(18,2) NOT NULL, -- Calculado automáticamente en backend de BD
    Observacion             VARCHAR(500) NULL,
    Estado                  VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE', -- PENDIENTE, PROCESADO, ANULADO

    -- Campos de Auditoría Estándar
    FechaCreacion           DATETIME DEFAULT GETDATE(),
    UsuarioCreacion         VARCHAR(100) DEFAULT 'SISTEMA',
    FechaModificacion       DATETIME NULL,
    UsuarioModificacion     VARCHAR(100) NULL,

    FOREIGN KEY (CodEmpresa) REFERENCES Empresa(CodEmpresa),
    FOREIGN KEY (CodEmpleado) REFERENCES Empleado(CodEmpleado)
)
GO

-- Índice optimizado para búsquedas por empleado en nómina
IF EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_HoraExtra_Empleado_Fecha')
    DROP INDEX IX_HoraExtra_Empleado_Fecha ON HoraExtra
GO
CREATE INDEX IX_HoraExtra_Empleado_Fecha ON HoraExtra (CodEmpleado, Fecha)
GO

/*==========================================================================
 3. CREACIÓN DE LA TABLA: DeduccionEmpleado
==========================================================================*/
IF OBJECT_ID('dbo.DeduccionEmpleado') IS NOT NULL DROP TABLE dbo.DeduccionEmpleado
GO

CREATE TABLE dbo.DeduccionEmpleado (
    CodDeduccionEmpleado    INT IDENTITY(1,1) PRIMARY KEY,
    CodEmpresa              INT NOT NULL,
    CodEmpleado             INT NOT NULL,
    CodRubro                INT NOT NULL, -- FK a RubroPlanilla para clasificar la deducción
    Descripcion             VARCHAR(150) NOT NULL,
    TipoDeduccion           VARCHAR(20) NOT NULL, -- MONTO o PORCENTAJE
    Monto                   DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    Porcentaje              DECIMAL(18,4) NOT NULL DEFAULT 0.00,
    MontoTotalOriginal      DECIMAL(18,2) NULL, -- Principal del préstamo (si aplica)
    SaldoRestante           DECIMAL(18,2) NULL, -- Saldo que se amortiza automáticamente
    Estado                  VARCHAR(20) NOT NULL DEFAULT 'ACTIVO', -- ACTIVO, INACTIVO

    -- Campos de Auditoría Estándar
    FechaCreacion           DATETIME DEFAULT GETDATE(),
    UsuarioCreacion         VARCHAR(100) DEFAULT 'SISTEMA',
    FechaModificacion       DATETIME NULL,
    UsuarioModificacion     VARCHAR(100) NULL,

    FOREIGN KEY (CodEmpresa) REFERENCES Empresa(CodEmpresa),
    FOREIGN KEY (CodEmpleado) REFERENCES Empleado(CodEmpleado),
    FOREIGN KEY (CodRubro) REFERENCES RubroPlanilla(CodRubro)
)
GO

-- Índice de optimización
IF EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_DeduccionEmpleado_Empleado')
    DROP INDEX IX_DeduccionEmpleado_Empleado ON DeduccionEmpleado
GO
CREATE INDEX IX_DeduccionEmpleado_Empleado ON DeduccionEmpleado (CodEmpleado)
GO

/*==========================================================================
 4. STORED PROCEDURES DE NEGOCIO: CRUD HORAS EXTRA
==========================================================================*/

-- 4.1. SP_HORAEXTRAINSERTAR
IF OBJECT_ID('dbo.sp_HoraExtraInsertar') IS NOT NULL DROP PROCEDURE dbo.sp_HoraExtraInsertar
GO

CREATE PROCEDURE dbo.sp_HoraExtraInsertar
    @CodEmpresa              INT,
    @CodEmpleado             INT,
    @Fecha                   DATE,
    @CantidadHoras           DECIMAL(18,2),
    @FactorMultiplicador     DECIMAL(18,2),
    @Observacion             VARCHAR(500),
    @UsuarioAccion           VARCHAR(100)
AS
BEGIN
    SET NOCOUNT ON;
    SET LANGUAGE Spanish;
    BEGIN TRY
        BEGIN TRANSACTION;

        -- 1. Validar que el empleado exista y esté activo
        IF NOT EXISTS (SELECT 1 FROM Empleado WHERE CodEmpleado = @CodEmpleado AND Estado = 'ACTIVO')
        BEGIN
            THROW 50001, 'Error: El colaborador especificado no existe o está registrado como INACTIVO.', 1;
        END

        -- 2. Calcular salario por hora usando la función legal y obtener el monto
        DECLARE @SalarioHora DECIMAL(18,2) = dbo.fn_ObtenerSalarioHora(@CodEmpleado);
        DECLARE @MontoCalculado DECIMAL(18,2) = ROUND(@CantidadHoras * @FactorMultiplicador * @SalarioHora, 2);

        -- 3. Insertar registro
        INSERT INTO HoraExtra (
            CodEmpresa, CodEmpleado, Fecha, CantidadHoras, FactorMultiplicador, MontoCalculado, Observacion, Estado, UsuarioCreacion
        )
        VALUES (
            @CodEmpresa, @CodEmpleado, @Fecha, @CantidadHoras, @FactorMultiplicador, @MontoCalculado, @Observacion, 'PENDIENTE', @UsuarioAccion
        );

        DECLARE @NuevoCod INT = SCOPE_IDENTITY();
        DECLARE @NombreEmpleado VARCHAR(250);
        SELECT @NombreEmpleado = Nombre + ' ' + Apellido1 FROM Empleado WHERE CodEmpleado = @CodEmpleado;

        -- 4. Auditoría en Bitácora
        INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle)
        VALUES (
            @UsuarioAccion, 
            'CREACIÓN HORA EXTRA', 
            'Se reportaron ' + CAST(@CantidadHoras AS VARCHAR(10)) + ' horas extra (multiplicador x' + CAST(@FactorMultiplicador AS VARCHAR(5)) + ') con un valor de ₡' + CAST(@MontoCalculado AS VARCHAR(20)) + ' para: ' + @NombreEmpleado + ' (Código registro: ' + CAST(@NuevoCod AS VARCHAR(10)) + ').'
        );

        COMMIT TRANSACTION;

        SELECT 
            1 AS Success, 
            'Horas extra reportadas y precalculadas correctamente.' AS Message,
            @NuevoCod AS CodHoraExtra;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
        SELECT 
            0 AS Success, 
            ERROR_MESSAGE() AS Message,
            0 AS CodHoraExtra;
    END CATCH
END
GO

-- 4.2. SP_HORAEXTRAACTUALIZAR
IF OBJECT_ID('dbo.sp_HoraExtraActualizar') IS NOT NULL DROP PROCEDURE dbo.sp_HoraExtraActualizar
GO

CREATE PROCEDURE dbo.sp_HoraExtraActualizar
    @CodHoraExtra            INT,
    @Fecha                   DATE,
    @CantidadHoras           DECIMAL(18,2),
    @FactorMultiplicador     DECIMAL(18,2),
    @Observacion             VARCHAR(500),
    @Estado                  VARCHAR(20),
    @UsuarioAccion           VARCHAR(100)
AS
BEGIN
    SET NOCOUNT ON;
    SET LANGUAGE Spanish;
    BEGIN TRY
        BEGIN TRANSACTION;

        -- 1. Validar que el registro exista
        DECLARE @CodEmpleado INT;
        DECLARE @EstadoActual VARCHAR(20);

        SELECT @CodEmpleado = CodEmpleado, @EstadoActual = Estado 
        FROM HoraExtra 
        WHERE CodHoraExtra = @CodHoraExtra;

        IF @CodEmpleado IS NULL
        BEGIN
            THROW 50002, 'Error: El código del reporte de horas extra especificado no existe.', 1;
        END

        -- 2. Validar que no haya sido procesado
        IF @EstadoActual = 'PROCESADO'
        BEGIN
            THROW 50003, 'Error: No se pueden realizar modificaciones sobre registros de horas extra ya liquidados en planilla.', 1;
        END

        -- 3. Calcular nuevo monto
        DECLARE @SalarioHora DECIMAL(18,2) = dbo.fn_ObtenerSalarioHora(@CodEmpleado);
        DECLARE @MontoCalculado DECIMAL(18,2) = ROUND(@CantidadHoras * @FactorMultiplicador * @SalarioHora, 2);

        -- 4. Actualizar
        UPDATE HoraExtra
        SET 
            Fecha = @Fecha,
            CantidadHoras = @CantidadHoras,
            FactorMultiplicador = @FactorMultiplicador,
            MontoCalculado = @MontoCalculado,
            Observacion = @Observacion,
            Estado = @Estado,
            FechaModificacion = GETDATE(),
            UsuarioModificacion = @UsuarioAccion
        WHERE CodHoraExtra = @CodHoraExtra;

        -- 5. Auditoría
        INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle)
        VALUES (
            @UsuarioAccion, 
            'MODIFICACIÓN HORA EXTRA', 
            'Se modificó el reporte de horas extra ID ' + CAST(@CodHoraExtra AS VARCHAR(10)) + '. Nuevo total: ' + CAST(@CantidadHoras AS VARCHAR(10)) + ' horas, valor ₡' + CAST(@MontoCalculado AS VARCHAR(20)) + '.'
        );

        COMMIT TRANSACTION;

        SELECT 
            1 AS Success, 
            'Reporte de horas extra actualizado exitosamente.' AS Message,
            @CodHoraExtra AS CodHoraExtra;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
        SELECT 
            0 AS Success, 
            ERROR_MESSAGE() AS Message,
            @CodHoraExtra AS CodHoraExtra;
    END CATCH
END
GO

-- 4.3. SP_HORAEXTRAELIMINAR
IF OBJECT_ID('dbo.sp_HoraExtraEliminar') IS NOT NULL DROP PROCEDURE dbo.sp_HoraExtraEliminar
GO

CREATE PROCEDURE dbo.sp_HoraExtraEliminar
    @CodHoraExtra            INT,
    @UsuarioAccion           VARCHAR(100)
AS
BEGIN
    SET NOCOUNT ON;
    SET LANGUAGE Spanish;
    BEGIN TRY
        BEGIN TRANSACTION;

        DECLARE @EstadoActual VARCHAR(20);
        SELECT @EstadoActual = Estado FROM HoraExtra WHERE CodHoraExtra = @CodHoraExtra;

        IF @EstadoActual IS NULL
        BEGIN
            THROW 50004, 'Error: El registro de horas extra especificado no existe.', 1;
        END

        IF @EstadoActual = 'PROCESADO'
        BEGIN
            THROW 50005, 'Error: No se permite anular ni eliminar registros de horas extra ya liquidados en planilla.', 1;
        END

        -- Baja lógica cambiando estado a ANULADO
        UPDATE HoraExtra
        SET Estado = 'ANULADO',
            FechaModificacion = GETDATE(),
            UsuarioModificacion = @UsuarioAccion
        WHERE CodHoraExtra = @CodHoraExtra;

        -- Registrar en bitácora
        INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle)
        VALUES (@UsuarioAccion, 'ANULACIÓN HORA EXTRA', 'Se anuló el reporte de horas extra con ID ' + CAST(@CodHoraExtra AS VARCHAR(10)) + '.');

        COMMIT TRANSACTION;

        SELECT 
            1 AS Success, 
            'Reporte de horas extra anulado correctamente.' AS Message;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
        SELECT 
            0 AS Success, 
            ERROR_MESSAGE() AS Message;
    END CATCH
END
GO

/*==========================================================================
 5. STORED PROCEDURES DE NEGOCIO: CRUD DEDUCCIONES PROGRAMADAS
==========================================================================*/

-- 5.1. SP_DEDUCCIONEMPLEADOINSERTAR
IF OBJECT_ID('dbo.sp_DeduccionEmpleadoInsertar') IS NOT NULL DROP PROCEDURE dbo.sp_DeduccionEmpleadoInsertar
GO

CREATE PROCEDURE dbo.sp_DeduccionEmpleadoInsertar
    @CodEmpresa              INT,
    @CodEmpleado             INT,
    @CodRubro                INT,
    @Descripcion             VARCHAR(150),
    @TipoDeduccion           VARCHAR(20),
    @Monto                   DECIMAL(18,2),
    @Porcentaje              DECIMAL(18,4),
    @MontoTotalOriginal      DECIMAL(18,2),
    @UsuarioAccion           VARCHAR(100)
AS
BEGIN
    SET NOCOUNT ON;
    SET LANGUAGE Spanish;
    BEGIN TRY
        BEGIN TRANSACTION;

        -- 1. Validar que el empleado exista y esté activo
        IF NOT EXISTS (SELECT 1 FROM Empleado WHERE CodEmpleado = @CodEmpleado AND Estado = 'ACTIVO')
        BEGIN
            THROW 50006, 'Error: El colaborador no existe o no se encuentra activo.', 1;
        END

        -- 2. Insertar
        INSERT INTO DeduccionEmpleado (
            CodEmpresa, CodEmpleado, CodRubro, Descripcion, TipoDeduccion, Monto, Porcentaje, MontoTotalOriginal, SaldoRestante, Estado, UsuarioCreacion
        )
        VALUES (
            @CodEmpresa, @CodEmpleado, @CodRubro, @Descripcion, @TipoDeduccion, @Monto, @Porcentaje, @MontoTotalOriginal, @MontoTotalOriginal, 'ACTIVO', @UsuarioAccion
        );

        DECLARE @NuevoCod INT = SCOPE_IDENTITY();
        DECLARE @NombreEmpleado VARCHAR(250);
        SELECT @NombreEmpleado = Nombre + ' ' + Apellido1 FROM Empleado WHERE CodEmpleado = @CodEmpleado;

        -- 3. Auditoría
        INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle)
        VALUES (
            @UsuarioAccion, 
            'CREACIÓN DEDUCCIÓN PROGRAMADA', 
            'Se programó la deducción (' + @Descripcion + ') tipo ' + @TipoDeduccion + ' para ' + @NombreEmpleado + ' (Código: ' + CAST(@NuevoCod AS VARCHAR(10)) + '). Principal original: ₡' + ISNULL(CAST(@MontoTotalOriginal AS VARCHAR(20)), 'N/A') + '.'
        );

        COMMIT TRANSACTION;

        SELECT 
            1 AS Success, 
            'Deducción programada correctamente para el colaborador.' AS Message,
            @NuevoCod AS CodDeduccionEmpleado;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
        SELECT 
            0 AS Success, 
            ERROR_MESSAGE() AS Message,
            0 AS CodDeduccionEmpleado;
    END CATCH
END
GO

-- 5.2. SP_DEDUCCIONEMPLEADOACTUALIZAR
IF OBJECT_ID('dbo.sp_DeduccionEmpleadoActualizar') IS NOT NULL DROP PROCEDURE dbo.sp_DeduccionEmpleadoActualizar
GO

CREATE PROCEDURE dbo.sp_DeduccionEmpleadoActualizar
    @CodDeduccionEmpleado    INT,
    @CodRubro                INT,
    @Descripcion             VARCHAR(150),
    @TipoDeduccion           VARCHAR(20),
    @Monto                   DECIMAL(18,2),
    @Porcentaje              DECIMAL(18,4),
    @MontoTotalOriginal      DECIMAL(18,2),
    @SaldoRestante           DECIMAL(18,2),
    @Estado                  VARCHAR(20),
    @UsuarioAccion           VARCHAR(100)
AS
BEGIN
    SET NOCOUNT ON;
    SET LANGUAGE Spanish;
    BEGIN TRY
        BEGIN TRANSACTION;

        -- 1. Validar existencia
        IF NOT EXISTS (SELECT 1 FROM DeduccionEmpleado WHERE CodDeduccionEmpleado = @CodDeduccionEmpleado)
        BEGIN
            THROW 50007, 'Error: El código de la deducción especificado no existe.', 1;
        END

        -- 2. Actualizar
        UPDATE DeduccionEmpleado
        SET 
            CodRubro = @CodRubro,
            Descripcion = @Descripcion,
            TipoDeduccion = @TipoDeduccion,
            Monto = @Monto,
            Porcentaje = @Porcentaje,
            MontoTotalOriginal = @MontoTotalOriginal,
            SaldoRestante = @SaldoRestante,
            Estado = @Estado,
            FechaModificacion = GETDATE(),
            UsuarioModificacion = @UsuarioAccion
        WHERE CodDeduccionEmpleado = @CodDeduccionEmpleado;

        -- 3. Auditoría
        INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle)
        VALUES (
            @UsuarioAccion, 
            'MODIFICACIÓN DEDUCCIÓN PROGRAMADA', 
            'Se actualizó la deducción programada ID ' + CAST(@CodDeduccionEmpleado AS VARCHAR(10)) + ' (' + @Descripcion + '). Saldo restante actual: ₡' + ISNULL(CAST(@SaldoRestante AS VARCHAR(20)), '0') + '.'
        );

        COMMIT TRANSACTION;

        SELECT 
            1 AS Success, 
            'Deducción programada modificada exitosamente.' AS Message,
            @CodDeduccionEmpleado AS CodDeduccionEmpleado;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
        SELECT 
            0 AS Success, 
            ERROR_MESSAGE() AS Message,
            @CodDeduccionEmpleado AS CodDeduccionEmpleado;
    END CATCH
END
GO

-- 5.3. SP_DEDUCCIONEMPLEADOELIMINAR
IF OBJECT_ID('dbo.sp_DeduccionEmpleadoEliminar') IS NOT NULL DROP PROCEDURE dbo.sp_DeduccionEmpleadoEliminar
GO

CREATE PROCEDURE dbo.sp_DeduccionEmpleadoEliminar
    @CodDeduccionEmpleado    INT,
    @UsuarioAccion           VARCHAR(100)
AS
BEGIN
    SET NOCOUNT ON;
    SET LANGUAGE Spanish;
    BEGIN TRY
        BEGIN TRANSACTION;

        -- 1. Validar existencia
        IF NOT EXISTS (SELECT 1 FROM DeduccionEmpleado WHERE CodDeduccionEmpleado = @CodDeduccionEmpleado)
        BEGIN
            THROW 50008, 'Error: La deducción programada especificada no existe.', 1;
        END

        -- 2. Baja lógica (cambiar estado a INACTIVO)
        UPDATE DeduccionEmpleado
        SET Estado = 'INACTIVO',
            FechaModificacion = GETDATE(),
            UsuarioModificacion = @UsuarioAccion
        WHERE CodDeduccionEmpleado = @CodDeduccionEmpleado;

        -- 3. Auditoría
        INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle)
        VALUES (@UsuarioAccion, 'INACTIVAR DEDUCCIÓN PROGRAMADA', 'Se inactivó (baja lógica) la deducción programada ID ' + CAST(@CodDeduccionEmpleado AS VARCHAR(10)) + '.');

        COMMIT TRANSACTION;

        SELECT 
            1 AS Success, 
            'Deducción programada inactivada correctamente.' AS Message;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
        SELECT 
            0 AS Success, 
            ERROR_MESSAGE() AS Message;
    END CATCH
END
GO

PRINT 'SCRIPT DE BACKEND SQL SERVER DE HORAS EXTRA Y DEDUCCIONES COMPILADO CON ÉXITO.'
GO
