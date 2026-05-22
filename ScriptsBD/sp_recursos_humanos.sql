USE [PlanillaCR]
GO

-- ==========================================================================
-- 1. STORED PROCEDURE: INSERTAR NUEVO EMPLEADO (SP_EMPLEADOINSERTAR)
-- ==========================================================================
IF OBJECT_ID('sp_EmpleadoInsertar') IS NOT NULL DROP PROCEDURE sp_EmpleadoInsertar
GO

CREATE PROCEDURE sp_EmpleadoInsertar
    @CodEmpresa              INT,
    @CodSucursal             INT,
    @CodDepartamento         INT,
    @CodPuesto               INT,
    @CodHorario              INT,
    @Identificacion          VARCHAR(20),
    @Nombre                  VARCHAR(150),
    @Apellido1               VARCHAR(150),
    @Apellido2               VARCHAR(150),
    @FechaNacimiento         DATE,
    @FechaIngreso            DATE,
    @Telefono                VARCHAR(50),
    @Correo                  VARCHAR(150),
    @Direccion               VARCHAR(500),
    @TipoSalario             VARCHAR(20),
    @SalarioBase             DECIMAL(18,2),
    @Banco                   VARCHAR(100),
    @CuentaIBAN              VARCHAR(100),
    @UsuarioAccion           VARCHAR(100)
AS
BEGIN
    SET NOCOUNT ON;
    SET LANGUAGE Spanish;
    
    BEGIN TRY
        BEGIN TRANSACTION;
        
        -- 1. Validar que la identificación sea única para empleados activos
        IF EXISTS (SELECT 1 FROM Empleado WHERE Identificacion = @Identificacion AND Estado = 'ACTIVO')
        BEGIN
            THROW 50001, 'Error: Ya existe un empleado activo registrado con esta identificación.', 1;
        END

        -- 2. Validar que el correo sea único para empleados activos
        IF EXISTS (SELECT 1 FROM Empleado WHERE Correo = @Correo AND Estado = 'ACTIVO')
        BEGIN
            THROW 50002, 'Error: Ya existe un empleado activo registrado con este correo electrónico.', 1;
        END

        -- 3. Insertar empleado
        INSERT INTO Empleado (
            CodEmpresa, CodSucursal, CodDepartamento, CodPuesto, CodHorario,
            Identificacion, Nombre, Apellido1, Apellido2,
            FechaNacimiento, FechaIngreso, FechaSalida, Telefono, Correo, Direccion,
            TipoSalario, SalarioBase, Banco, CuentaIBAN,
            Estado, FechaCreacion, UsuarioCreacion
        )
        VALUES (
            @CodEmpresa, @CodSucursal, @CodDepartamento, @CodPuesto, @CodHorario,
            @Identificacion, @Nombre, @Apellido1, @Apellido2,
            @FechaNacimiento, @FechaIngreso, NULL, @Telefono, @Correo, @Direccion,
            @TipoSalario, @SalarioBase, @Banco, @CuentaIBAN,
            'ACTIVO', GETDATE(), @UsuarioAccion
        );

        DECLARE @NuevoCodEmpleado INT = SCOPE_IDENTITY();
        DECLARE @NombreCompleto VARCHAR(300) = @Nombre + ' ' + @Apellido1 + ISNULL(' ' + @Apellido2, '');

        -- 4. Registrar auditoría en la Bitácora
        INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle)
        VALUES (
            @UsuarioAccion, 
            'CREACIÓN EMPLEADO', 
            'Se registró con éxito al empleado: ' + @NombreCompleto + ' (ID: ' + @Identificacion + ', Código: ' + CAST(@NuevoCodEmpleado AS VARCHAR(10)) + ').'
        );

        COMMIT TRANSACTION;

        -- Retornar resultado exitoso
        SELECT 
            1 AS Success, 
            'Empleado creado exitosamente con el código ' + CAST(@NuevoCodEmpleado AS VARCHAR(10)) + '.' AS Message,
            @NuevoCodEmpleado AS CodEmpleado;

    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;

        -- Retornar error controlado
        SELECT 
            0 AS Success, 
            ERROR_MESSAGE() AS Message,
            0 AS CodEmpleado;
    END CATCH
END
GO

-- ==========================================================================
-- 2. STORED PROCEDURE: ACTUALIZAR EMPLEADO EXISTENTE (SP_EMPLEADOACTUALIZAR)
-- ==========================================================================
IF OBJECT_ID('sp_EmpleadoActualizar') IS NOT NULL DROP PROCEDURE sp_EmpleadoActualizar
GO

CREATE PROCEDURE sp_EmpleadoActualizar
    @CodEmpleado             INT,
    @CodEmpresa              INT,
    @CodSucursal             INT,
    @CodDepartamento         INT,
    @CodPuesto               INT,
    @CodHorario              INT,
    @Identificacion          VARCHAR(20),
    @Nombre                  VARCHAR(150),
    @Apellido1               VARCHAR(150),
    @Apellido2               VARCHAR(150),
    @FechaNacimiento         DATE,
    @FechaIngreso            DATE,
    @Telefono                VARCHAR(50),
    @Correo                  VARCHAR(150),
    @Direccion               VARCHAR(500),
    @TipoSalario             VARCHAR(20),
    @SalarioBase             DECIMAL(18,2),
    @Banco                   VARCHAR(100),
    @CuentaIBAN              VARCHAR(100),
    @Estado                  VARCHAR(20),
    @UsuarioAccion           VARCHAR(100)
AS
BEGIN
    SET NOCOUNT ON;
    SET LANGUAGE Spanish;

    BEGIN TRY
        BEGIN TRANSACTION;

        -- 1. Validar que el empleado exista
        IF NOT EXISTS (SELECT 1 FROM Empleado WHERE CodEmpleado = @CodEmpleado)
        BEGIN
            THROW 50003, 'Error: El código de empleado especificado no existe en el sistema.', 1;
        END

        -- 2. Validar duplicado de identificación en otros empleados activos
        IF EXISTS (SELECT 1 FROM Empleado WHERE Identificacion = @Identificacion AND CodEmpleado <> @CodEmpleado AND Estado = 'ACTIVO')
        BEGIN
            THROW 50004, 'Error: Ya existe otro empleado activo registrado con esta identificación.', 1;
        END

        -- 3. Validar duplicado de correo en otros empleados activos
        IF EXISTS (SELECT 1 FROM Empleado WHERE Correo = @Correo AND CodEmpleado <> @CodEmpleado AND Estado = 'ACTIVO')
        BEGIN
            THROW 50005, 'Error: Ya existe otro empleado activo registrado con este correo electrónico.', 1;
        END

        -- 4. Actualizar empleado
        UPDATE Empleado
        SET 
            CodEmpresa = @CodEmpresa,
            CodSucursal = @CodSucursal,
            CodDepartamento = @CodDepartamento,
            CodPuesto = @CodPuesto,
            CodHorario = @CodHorario,
            Identificacion = @Identificacion,
            Nombre = @Nombre,
            Apellido1 = @Apellido1,
            Apellido2 = @Apellido2,
            FechaNacimiento = @FechaNacimiento,
            FechaIngreso = @FechaIngreso,
            Telefono = @Telefono,
            Correo = @Correo,
            Direccion = @Direccion,
            TipoSalario = @TipoSalario,
            SalarioBase = @SalarioBase,
            Banco = @Banco,
            CuentaIBAN = @CuentaIBAN,
            Estado = @Estado,
            FechaModificacion = GETDATE(),
            UsuarioModificacion = @UsuarioAccion
        WHERE CodEmpleado = @CodEmpleado;

        DECLARE @NombreCompleto VARCHAR(300) = @Nombre + ' ' + @Apellido1 + ISNULL(' ' + @Apellido2, '');

        -- 5. Registrar auditoría en la Bitácora
        INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle)
        VALUES (
            @UsuarioAccion, 
            'MODIFICACIÓN EMPLEADO', 
            'Se actualizaron los datos del empleado: ' + @NombreCompleto + ' (Código: ' + CAST(@CodEmpleado AS VARCHAR(10)) + ').'
        );

        COMMIT TRANSACTION;

        SELECT 
            1 AS Success, 
            'Datos del empleado actualizados exitosamente.' AS Message,
            @CodEmpleado AS CodEmpleado;

    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;

        SELECT 
            0 AS Success, 
            ERROR_MESSAGE() AS Message,
            @CodEmpleado AS CodEmpleado;
    END CATCH
END
GO

-- ==========================================================================
-- 3. STORED PROCEDURE: INACTIVAR / BAJA LÓGICA DE EMPLEADO (SP_EMPLEADOINACTIVAR)
-- ==========================================================================
IF OBJECT_ID('sp_EmpleadoInactivar') IS NOT NULL DROP PROCEDURE sp_EmpleadoInactivar
GO

CREATE PROCEDURE sp_EmpleadoInactivar
    @CodEmpleado             INT,
    @FechaSalida             DATE,
    @UsuarioAccion           VARCHAR(100)
AS
BEGIN
    SET NOCOUNT ON;
    SET LANGUAGE Spanish;

    BEGIN TRY
        BEGIN TRANSACTION;

        -- 1. Validar existencia del empleado
        IF NOT EXISTS (SELECT 1 FROM Empleado WHERE CodEmpleado = @CodEmpleado)
        BEGIN
            THROW 50006, 'Error: El código de empleado especificado no existe.', 1;
        END

        DECLARE @NombreCompleto VARCHAR(300);
        DECLARE @Identificacion VARCHAR(20);

        SELECT 
            @NombreCompleto = Nombre + ' ' + Apellido1 + ISNULL(' ' + Apellido2, ''),
            @Identificacion = Identificacion
        FROM Empleado
        WHERE CodEmpleado = @CodEmpleado;

        -- 2. Modificar estado a INACTIVO
        UPDATE Empleado
        SET 
            Estado = 'INACTIVO',
            FechaSalida = @FechaSalida,
            FechaModificacion = GETDATE(),
            UsuarioModificacion = @UsuarioAccion
        WHERE CodEmpleado = @CodEmpleado;

        -- 3. Registrar auditoría en la Bitácora
        INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle)
        VALUES (
            @UsuarioAccion, 
            'BAJA EMPLEADO', 
            'Se inactivo (baja lógica) al empleado: ' + @NombreCompleto + ' (ID: ' + @Identificacion + ', Código: ' + CAST(@CodEmpleado AS VARCHAR(10)) + ') con fecha de salida: ' + CAST(@FechaSalida AS VARCHAR(10)) + '.'
        );

        COMMIT TRANSACTION;

        SELECT 
            1 AS Success, 
            'El empleado ha sido inhabilitado (INACTIVO) en el sistema.' AS Message,
            @CodEmpleado AS CodEmpleado;

    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;

        SELECT 
            0 AS Success, 
            ERROR_MESSAGE() AS Message,
            @CodEmpleado AS CodEmpleado;
    END CATCH
END
GO

PRINT 'STORED PROCEDURES DE RECURSOS HUMANOS COMPILADOS CON ÉXITO.'
GO
