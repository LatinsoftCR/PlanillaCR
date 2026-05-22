USE [PlanillaCR]
GO

-- 1. Saneamiento de Departamentos
UPDATE Departamento SET Descripcion = 'Tecnología e Informática' WHERE CodDepartamento = 1 OR Descripcion LIKE '%Tecnolog%';
UPDATE Departamento SET Descripcion = 'Administración y Finanzas' WHERE CodDepartamento = 2 OR Descripcion LIKE '%Administrac%';
UPDATE Departamento SET Descripcion = 'Operaciones y Logística' WHERE CodDepartamento = 3 OR Descripcion LIKE '%Logis%';
UPDATE Departamento SET Descripcion = 'Ventas y Mercadeo' WHERE CodDepartamento = 4;
UPDATE Departamento SET Descripcion = 'Soporte Técnico' WHERE CodDepartamento = 5 OR Descripcion LIKE '%Soporte%';

-- 2. Saneamiento de Puestos
UPDATE Puesto SET Descripcion = 'Administrador del Sistema' WHERE CodPuesto = 1;
UPDATE Puesto SET Descripcion = 'Gerente de Recursos Humanos' WHERE CodPuesto = 2;
UPDATE Puesto SET Descripcion = 'Desarrollador de Software' WHERE CodPuesto = 3;
UPDATE Puesto SET Descripcion = 'Ejecutivo de Ventas' WHERE CodPuesto = 4;
UPDATE Puesto SET Descripcion = 'Analista de Planillas' WHERE CodPuesto = 5;

-- 3. Saneamiento de Horarios
UPDATE Horario SET Descripcion = 'Jornada Diurna Estándar' WHERE CodHorario = 1 OR Descripcion LIKE '%Est%ndar%';
UPDATE Horario SET Descripcion = 'Jornada Nocturna Estándar' WHERE CodHorario = 2;
UPDATE Horario SET Descripcion = 'Jornada Mixta' WHERE CodHorario = 3;

PRINT 'SANEAMIENTO DE TILDES EN CATÁLOGOS COMPLETADO CON ÉXITO.'
GO
