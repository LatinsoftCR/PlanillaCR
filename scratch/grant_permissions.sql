USE [master]
GO
-- 1. Crear el Login de Windows para Apache si no existe en SQL Server
IF NOT EXISTS (SELECT * FROM sys.server_principals WHERE name = 'NT AUTHORITY\SYSTEM')
BEGIN
    CREATE LOGIN [NT AUTHORITY\SYSTEM] FROM WINDOWS WITH DEFAULT_DATABASE=[PlanillaCR]
END
GO

USE [PlanillaCR]
GO
-- 2. Crear el usuario de base de datos asociado al Login
IF NOT EXISTS (SELECT * FROM sys.database_principals WHERE name = 'NT AUTHORITY\SYSTEM')
BEGIN
    CREATE USER [NT AUTHORITY\SYSTEM] FOR LOGIN [NT AUTHORITY\SYSTEM]
END
GO

-- 3. Conceder rol de db_owner para que pueda realizar SELECT, INSERT, UPDATE, DELETE e invocar Stored Procedures
ALTER ROLE [db_owner] ADD MEMBER [NT AUTHORITY\SYSTEM]
GO

PRINT 'SISTEMA PLANILLACR: PERMISOS CONCEDIDOS EXITOSAMENTE A NT AUTHORITY\SYSTEM.'
GO
