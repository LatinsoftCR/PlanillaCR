-- APLICAR PLANILLA (CIERRE DEFINITIVO CON ACUMULACIÓN DE VACACIONES L.C.R.)
ALTER PROCEDURE sp_AplicarPlanilla
(
    @CodPlanilla INT,
    @Usuario VARCHAR(100)
)
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRY
        BEGIN TRANSACTION;

        -- 1. Marcar planilla como aplicada
        UPDATE Planilla
        SET Estado = 'APLICADA',
            FechaModificacion = GETDATE(),
            UsuarioModificacion = @Usuario
        WHERE CodPlanilla = @CodPlanilla;

        -- 2. Determinar el tipo de planilla y los días a ganar por este ciclo
        DECLARE @CodTipoPlanilla INT;
        DECLARE @DiasEarned DECIMAL(18, 4);

        SELECT @CodTipoPlanilla = CodTipoPlanilla 
        FROM Planilla 
        WHERE CodPlanilla = @CodPlanilla;

        SET @DiasEarned = CASE 
            WHEN @CodTipoPlanilla = 1 THEN 12.0 / 52.0  -- Semanal (12 días al año)
            WHEN @CodTipoPlanilla = 2 THEN 15.0 / 24.0  -- Quincenal (15 días al año)
            WHEN @CodTipoPlanilla = 3 THEN 15.0 / 12.0  -- Mensual (15 días al año)
            ELSE 12.0 / 52.0                            -- Destajo / Otros por defecto
        END;

        -- 3. Cursor para iterar sobre todos los empleados calculados
        DECLARE @CodEmpleado INT;
        DECLARE @FechaIngreso DATE;

        DECLARE curEmpleados CURSOR FOR
        SELECT DISTINCT pd.CodEmpleado, e.FechaIngreso
        FROM PlanillaDetalle pd
        INNER JOIN Empleado e ON pd.CodEmpleado = e.CodEmpleado
        WHERE pd.CodPlanilla = @CodPlanilla;

        OPEN curEmpleados;
        FETCH NEXT FROM curEmpleados INTO @CodEmpleado, @FechaIngreso;

        WHILE @@FETCH_STATUS = 0
        BEGIN
            -- Determinar el periodo anual actual del empleado según su FechaIngreso
            DECLARE @YearsWorked INT = DATEDIFF(year, @FechaIngreso, GETDATE());
            DECLARE @PeriodoInicio DATE = DATEADD(year, @YearsWorked, @FechaIngreso);
            DECLARE @PeriodoFin DATE = DATEADD(year, @YearsWorked + 1, @FechaIngreso);

            -- Si el periodoInicio es mayor al día de hoy, retroceder un año
            IF @PeriodoInicio > GETDATE()
            BEGIN
                SET @YearsWorked = @YearsWorked - 1;
                SET @PeriodoInicio = DATEADD(year, @YearsWorked, @FechaIngreso);
                SET @PeriodoFin = DATEADD(year, @YearsWorked + 1, @FechaIngreso);
            END

            -- Verificar si ya existe un registro de vacaciones activo para este empleado
            IF EXISTS (SELECT 1 FROM Vacaciones WHERE CodEmpleado = @CodEmpleado AND Estado = 'ACTIVO')
            BEGIN
                -- Actualizar el acumulado y saldo del registro de vacaciones activo
                UPDATE Vacaciones
                SET DiasGanados = DiasGanados + @DiasEarned,
                    Saldo = Saldo + @DiasEarned,
                    FechaModificacion = GETDATE(),
                    UsuarioModificacion = @Usuario
                WHERE CodEmpleado = @CodEmpleado AND Estado = 'ACTIVO';
            END
            ELSE
            BEGIN
                -- Crear un registro nuevo
                INSERT INTO Vacaciones (CodEmpleado, PeriodoInicio, PeriodoFin, DiasGanados, DiasTomados, Saldo, Estado, UsuarioCreacion)
                VALUES (@CodEmpleado, @PeriodoInicio, @PeriodoFin, @DiasEarned, 0.00, @DiasEarned, 'ACTIVO', @Usuario);
            END

            FETCH NEXT FROM curEmpleados INTO @CodEmpleado, @FechaIngreso;
        END

        CLOSE curEmpleados;
        DEALLOCATE curEmpleados;

        -- 4. Registrar en la Bitácora
        INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle)
        VALUES (@Usuario, 'APLICAR PLANILLA', 'Planilla ID: ' + CAST(@CodPlanilla AS VARCHAR(10)) + ' ha sido aplicada y cerrada. Se acumularon vacaciones de forma proporcional (' + CAST(ROUND(@DiasEarned, 4) AS VARCHAR(15)) + ' dias por colaborador).');

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;
        DECLARE @ErrorMessage NVARCHAR(4000) = ERROR_MESSAGE();
        RAISERROR (@ErrorMessage, 16, 1);
    END CATCH
END
GO
