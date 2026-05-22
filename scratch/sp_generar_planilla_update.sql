USE [PlanillaCR]
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

IF OBJECT_ID('sp_GenerarPlanilla') IS NOT NULL DROP PROCEDURE sp_GenerarPlanilla
GO

CREATE PROCEDURE sp_GenerarPlanilla
(
    @CodEmpresa INT,
    @CodTipoPlanilla INT,
    @FechaInicio DATE,
    @FechaFin DATE,
    @FechaPago DATE,
    @Usuario VARCHAR(100)
)
AS
BEGIN
    SET NOCOUNT ON;
    SET LANGUAGE Spanish;
    BEGIN TRY
        BEGIN TRANSACTION;

        DECLARE @CodPlanilla INT

        -- 1. Insertar cabecera de la Planilla
        INSERT INTO Planilla (CodEmpresa, CodTipoPlanilla, FechaInicio, FechaFin, FechaPago, Estado, UsuarioCreacion)
        VALUES (@CodEmpresa, @CodTipoPlanilla, @FechaInicio, @FechaFin, @FechaPago, 'ABIERTA', @Usuario)

        SET @CodPlanilla = SCOPE_IDENTITY()

        -- 2. Cargar salarios base de empleados activos de forma automática
        INSERT INTO PlanillaDetalle (CodPlanilla, CodEmpleado, CodRubro, Cantidad, Monto, BaseCalculo, Porcentaje, FormulaAplicada, UsuarioCreacion)
        SELECT 
            @CodPlanilla,
            e.CodEmpleado,
            r.CodRubro,
            1.00,
            e.SalarioBase,
            e.SalarioBase,
            0.00,
            'SALARIO BASE MANUAL',
            @Usuario
        FROM Empleado e
        CROSS JOIN RubroPlanilla r
        WHERE e.CodEmpresa = @CodEmpresa 
          AND e.Estado = 'ACTIVO'
          AND r.Codigo = 'SAL'

        -- 3. Cargar acumulación de Destajos pendientes en este rango de fechas
        INSERT INTO PlanillaDetalle (CodPlanilla, CodEmpleado, CodRubro, Cantidad, Monto, BaseCalculo, Porcentaje, FormulaAplicada, UsuarioCreacion)
        SELECT
            @CodPlanilla,
            e.CodEmpleado,
            r.CodRubro,
            ISNULL(SUM(d.Cantidad), 0),
            ISNULL(SUM(d.Monto), 0),
            ISNULL(SUM(d.Monto), 0),
            0.00,
            'DESTAJOS PROCESADOS',
            @Usuario
        FROM Empleado e
        INNER JOIN RubroPlanilla r ON r.Codigo = 'DES'
        INNER JOIN Destajo d ON e.CodEmpleado = d.CodEmpleado
        WHERE e.CodEmpresa = @CodEmpresa 
          AND e.Estado = 'ACTIVO'
          AND d.Estado = 'PENDIENTE'
          AND d.Fecha BETWEEN @FechaInicio AND @FechaFin
        GROUP BY e.CodEmpleado, r.CodRubro

        -- Actualizar estado de destajos a Procesados
        UPDATE Destajo
        SET Estado = 'PROCESADO',
            FechaModificacion = GETDATE(),
            UsuarioModificacion = @Usuario
        WHERE Estado = 'PENDIENTE'
          AND Fecha BETWEEN @FechaInicio AND @FechaFin
          AND CodEmpleado IN (SELECT CodEmpleado FROM Empleado WHERE CodEmpresa = @CodEmpresa)

        -- 4. Cargar acumulación de Horas Extra pendientes en este rango de fechas
        INSERT INTO PlanillaDetalle (CodPlanilla, CodEmpleado, CodRubro, Cantidad, Monto, BaseCalculo, Porcentaje, FormulaAplicada, UsuarioCreacion)
        SELECT
            @CodPlanilla,
            e.CodEmpleado,
            r.CodRubro,
            ISNULL(SUM(he.CantidadHoras), 0),
            ISNULL(SUM(he.MontoCalculado), 0),
            ISNULL(SUM(he.MontoCalculado), 0),
            0.00,
            'HORAS EXTRA PROCESADAS',
            @Usuario
        FROM Empleado e
        INNER JOIN RubroPlanilla r ON r.Codigo = 'HEX'
        INNER JOIN HoraExtra he ON e.CodEmpleado = he.CodEmpleado
        WHERE e.CodEmpresa = @CodEmpresa 
          AND e.Estado = 'ACTIVO'
          AND he.Estado = 'PENDIENTE'
          AND he.Fecha BETWEEN @FechaInicio AND @FechaFin
        GROUP BY e.CodEmpleado, r.CodRubro

        -- Actualizar estado de horas extra a Procesados
        UPDATE HoraExtra
        SET Estado = 'PROCESADO',
            FechaModificacion = GETDATE(),
            UsuarioModificacion = @Usuario
        WHERE Estado = 'PENDIENTE'
          AND Fecha BETWEEN @FechaInicio AND @FechaFin
          AND CodEmpleado IN (SELECT CodEmpleado FROM Empleado WHERE CodEmpresa = @CodEmpresa)

        -- 5. Cargar deducciones programadas/voluntarias activas de los empleados
        -- Creamos tabla temporal para calcular montos exactos amortizados
        IF OBJECT_ID('tempdb..#DeduccionesCalculadas') IS NOT NULL DROP TABLE #DeduccionesCalculadas
        
        CREATE TABLE #DeduccionesCalculadas (
            CodDeduccionEmpleado INT,
            CodEmpleado INT,
            CodRubro INT,
            BaseCalculo DECIMAL(18,2),
            Porcentaje DECIMAL(18,4),
            MontoDeducir DECIMAL(18,2)
        )

        INSERT INTO #DeduccionesCalculadas (CodDeduccionEmpleado, CodEmpleado, CodRubro, BaseCalculo, Porcentaje, MontoDeducir)
        SELECT 
            de.CodDeduccionEmpleado,
            de.CodEmpleado,
            de.CodRubro,
            e.SalarioBase,
            de.Porcentaje,
            ROUND(
                CASE 
                    -- Si tiene saldo restante y es menor al monto programado, se deduce solo el saldo
                    WHEN de.SaldoRestante IS NOT NULL AND de.TipoDeduccion = 'MONTO' AND de.Monto > de.SaldoRestante THEN de.SaldoRestante
                    WHEN de.SaldoRestante IS NOT NULL AND de.TipoDeduccion = 'PORCENTAJE' AND (e.SalarioBase * (de.Porcentaje / 100.00)) > de.SaldoRestante THEN de.SaldoRestante
                    -- Si no, se deduce lo programado
                    WHEN de.TipoDeduccion = 'MONTO' THEN de.Monto
                    ELSE e.SalarioBase * (de.Porcentaje / 100.00)
                END, 2
            )
        FROM DeduccionEmpleado de
        INNER JOIN Empleado e ON de.CodEmpleado = e.CodEmpleado
        WHERE e.CodEmpresa = @CodEmpresa
          AND e.Estado = 'ACTIVO'
          AND de.Estado = 'ACTIVO'

        -- Insertar en PlanillaDetalle
        INSERT INTO PlanillaDetalle (CodPlanilla, CodEmpleado, CodRubro, Cantidad, Monto, BaseCalculo, Porcentaje, FormulaAplicada, UsuarioCreacion)
        SELECT
            @CodPlanilla,
            dc.CodEmpleado,
            dc.CodRubro,
            1.00,
            dc.MontoDeducir,
            dc.BaseCalculo,
            dc.Porcentaje,
            'DEDUCCIÓN PROGRAMADA APLICADA',
            @Usuario
        FROM #DeduccionesCalculadas dc
        WHERE dc.MontoDeducir > 0

        -- Amortizar Saldos en DeduccionEmpleado
        UPDATE de
        SET de.SaldoRestante = de.SaldoRestante - dc.MontoDeducir,
            de.Estado = CASE WHEN (de.SaldoRestante - dc.MontoDeducir) <= 0 THEN 'INACTIVO' ELSE 'ACTIVO' END,
            de.FechaModificacion = GETDATE(),
            de.UsuarioModificacion = @Usuario
        FROM DeduccionEmpleado de
        INNER JOIN #DeduccionesCalculadas dc ON de.CodDeduccionEmpleado = dc.CodDeduccionEmpleado
        WHERE de.SaldoRestante IS NOT NULL

        DROP TABLE #DeduccionesCalculadas

        -- 6. Bitácora de Auditoría
        INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle)
        VALUES (@Usuario, 'GENERAR PLANILLA', 'Planilla ID: ' + CAST(@CodPlanilla AS VARCHAR(10)) + ' generada con éxito, incluyendo salarios base, destajos, horas extra y deducciones programadas.')

        COMMIT TRANSACTION;
        SELECT @CodPlanilla AS CodPlanilla;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;
        
        DECLARE @ErrorMessage NVARCHAR(4000) = ERROR_MESSAGE()
        DECLARE @ErrorSeverity INT = ERROR_SEVERITY()
        DECLARE @ErrorState INT = ERROR_STATE()
        RAISERROR (@ErrorMessage, @ErrorSeverity, @ErrorState)
    END CATCH
END
GO
