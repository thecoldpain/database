-- ============================================
-- 1. CREAR LA BASE DE DATOS
-- ============================================
CREATE DATABASE UniversidadDB;
GO

USE UniversidadDB;
GO

-- ============================================
-- 2. CREAR LAS TABLAS (ESTRUCTURA - DDL)
-- ============================================

-- Tabla de Departamentos
CREATE TABLE Departamentos (
    Id INT PRIMARY KEY IDENTITY(1,1),
    Nombre VARCHAR(100) NOT NULL,
    Ubicacion VARCHAR(100)
);

-- Tabla de Profesores
CREATE TABLE Profesores (
    Id INT PRIMARY KEY IDENTITY(1,1),
    Nombre VARCHAR(100) NOT NULL,
    Apellido VARCHAR(100) NOT NULL,
    Email VARCHAR(100) UNIQUE,
    DepartamentoId INT FOREIGN KEY REFERENCES Departamentos(Id)
);

-- Tabla de Estudiantes
CREATE TABLE Estudiantes (
    Id INT PRIMARY KEY IDENTITY(1,1),
    Carnet VARCHAR(20) UNIQUE NOT NULL,
    Nombre VARCHAR(100) NOT NULL,
    Apellido VARCHAR(100) NOT NULL,
    FechaNacimiento DATE,
    Email VARCHAR(100) UNIQUE,
    FechaInscripcion DATE DEFAULT GETDATE()
);

-- Tabla de Cursos
CREATE TABLE Cursos (
    Id INT PRIMARY KEY IDENTITY(1,1),
    Codigo VARCHAR(10) UNIQUE NOT NULL,
    Nombre VARCHAR(100) NOT NULL,
    Creditos INT CHECK (Creditos > 0),
    DepartamentoId INT FOREIGN KEY REFERENCES Departamentos(Id)
);

-- Tabla de Asignaciones (Profesor - Curso)
CREATE TABLE Asignaciones (
    Id INT PRIMARY KEY IDENTITY(1,1),
    ProfesorId INT FOREIGN KEY REFERENCES Profesores(Id),
    CursoId INT FOREIGN KEY REFERENCES Cursos(Id),
    Anio INT NOT NULL,
    Semestre INT CHECK (Semestre IN (1,2)),
    UNIQUE (ProfesorId, CursoId, Anio, Semestre)
);

-- Tabla de Matriculas (Estudiante - Curso)
CREATE TABLE Matriculas (
    Id INT PRIMARY KEY IDENTITY(1,1),
    EstudianteId INT FOREIGN KEY REFERENCES Estudiantes(Id),
    CursoId INT FOREIGN KEY REFERENCES Cursos(Id),
    Anio INT NOT NULL,
    Semestre INT CHECK (Semestre IN (1,2)),
    NotaFinal DECIMAL(5,2) CHECK (NotaFinal BETWEEN 0 AND 100),
    FechaMatricula DATE DEFAULT GETDATE(),
    UNIQUE (EstudianteId, CursoId, Anio, Semestre)
);

-- ============================================
-- 3. INSERTAR DATOS (DML - INSERT)
-- ============================================

-- Insertar Departamentos
INSERT INTO Departamentos (Nombre, Ubicacion) VALUES
('Ingeniería de Sistemas', 'Edificio A - Piso 3'),
('Matemáticas', 'Edificio B - Piso 1'),
('Física', 'Edificio B - Piso 2'),
('Administración', 'Edificio C - Piso 1');

-- Insertar Profesores
INSERT INTO Profesores (Nombre, Apellido, Email, DepartamentoId) VALUES
('Carlos', 'Gómez', 'carlos.gomez@universidad.edu', 1),
('María', 'Rodríguez', 'maria.rodriguez@universidad.edu', 1),
('Juan', 'Pérez', 'juan.perez@universidad.edu', 2),
('Ana', 'Martínez', 'ana.martinez@universidad.edu', 3),
('Luis', 'Fernández', 'luis.fernandez@universidad.edu', 4);

-- Insertar Estudiantes
INSERT INTO Estudiantes (Carnet, Nombre, Apellido, FechaNacimiento, Email) VALUES
('20240001', 'Andrea', 'López', '2000-05-15', 'andrea.lopez@estudiante.edu'),
('20240002', 'Bruno', 'Díaz', '2001-08-22', 'bruno.diaz@estudiante.edu'),
('20240003', 'Carla', 'Sánchez', '1999-11-10', 'carla.sanchez@estudiante.edu'),
('20240004', 'Daniel', 'Morales', '2000-02-28', 'daniel.morales@estudiante.edu'),
('20240005', 'Elena', 'Castro', '2001-07-19', 'elena.castro@estudiante.edu'),
('20240006', 'Fernando', 'Ramos', '2000-12-01', 'fernando.ramos@estudiante.edu'),
('20240007', 'Gabriela', 'Ortiz', '1999-09-30', 'gabriela.ortiz@estudiante.edu'),
('20240008', 'Héctor', 'Vargas', '2001-03-14', 'hector.vargas@estudiante.edu');

-- Insertar Cursos
INSERT INTO Cursos (Codigo, Nombre, Creditos, DepartamentoId) VALUES
('IS101', 'Programación I', 4, 1),
('IS201', 'Bases de Datos', 4, 1),
('IS301', 'Redes de Computadoras', 3, 1),
('MAT101', 'Cálculo I', 5, 2),
('MAT201', 'Álgebra Lineal', 4, 2),
('FIS101', 'Física I', 4, 3),
('ADM101', 'Introducción a la Administración', 3, 4);

-- Insertar Asignaciones (Profesor - Curso)
INSERT INTO Asignaciones (ProfesorId, CursoId, Anio, Semestre) VALUES
(1, 1, 2025, 1),  -- Carlos Gómez enseña Programación I
(1, 2, 2025, 1),  -- Carlos Gómez enseña Bases de Datos
(2, 3, 2025, 1),  -- María Rodríguez enseña Redes
(3, 4, 2025, 1),  -- Juan Pérez enseña Cálculo I
(3, 5, 2025, 1),  -- Juan Pérez enseña Álgebra Lineal
(4, 6, 2025, 1),  -- Ana Martínez enseña Física I
(5, 7, 2025, 1);  -- Luis Fernández enseña Administración

-- Insertar Matrículas (Estudiantes en Cursos)
INSERT INTO Matriculas (EstudianteId, CursoId, Anio, Semestre, NotaFinal) VALUES
-- Estudiante 1 (Andrea)
(1, 1, 2025, 1, 85.5),
(1, 2, 2025, 1, 90.0),
(1, 4, 2025, 1, 78.0),
-- Estudiante 2 (Bruno)
(2, 1, 2025, 1, 92.0),
(2, 3, 2025, 1, 88.5),
(2, 6, 2025, 1, 75.0),
-- Estudiante 3 (Carla)
(3, 2, 2025, 1, 95.0),
(3, 4, 2025, 1, 82.0),
(3, 5, 2025, 1, 88.0),
-- Estudiante 4 (Daniel)
(4, 1, 2025, 1, 70.0),
(4, 2, 2025, 1, 85.0),
(4, 7, 2025, 1, 91.0),
-- Estudiante 5 (Elena)
(5, 3, 2025, 1, 94.0),
(5, 5, 2025, 1, 86.5),
(5, 6, 2025, 1, 89.0),
-- Estudiante 6 (Fernando)
(6, 1, 2025, 1, 65.0),
(6, 4, 2025, 1, 70.0),
(6, 7, 2025, 1, 78.0),
-- Estudiante 7 (Gabriela)
(7, 2, 2025, 1, 98.0),
(7, 3, 2025, 1, 92.0),
(7, 5, 2025, 1, 95.0),
-- Estudiante 8 (Héctor)
(8, 1, 2025, 1, 55.0),
(8, 6, 2025, 1, 60.0),
(8, 7, 2025, 1, 68.0);
