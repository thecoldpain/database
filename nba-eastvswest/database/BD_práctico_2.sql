CREATE DATABASE IF NOT EXISTS UniversidadDB;
USE UniversidadDB;

-- Tabla Departamentos
CREATE TABLE Departamentos (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Nombre VARCHAR(100) NOT NULL,
    Ubicacion VARCHAR(100)
);

-- Tabla Profesores
CREATE TABLE Profesores (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Nombre VARCHAR(100) NOT NULL,
    Apellido VARCHAR(100) NOT NULL,
    Email VARCHAR(100) UNIQUE,
    DepartamentoId INT,
    FOREIGN KEY (DepartamentoId) REFERENCES Departamentos(Id)
);

-- Tabla Estudiantes
CREATE TABLE Estudiantes (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Carnet VARCHAR(20) UNIQUE NOT NULL,
    Nombre VARCHAR(100) NOT NULL,
    Apellido VARCHAR(100) NOT NULL,
    FechaNacimiento DATE,
    Email VARCHAR(100) UNIQUE,
    FechaInscripcion DATE DEFAULT (CURRENT_DATE)
);

-- Tabla Cursos
CREATE TABLE Cursos (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Codigo VARCHAR(10) UNIQUE NOT NULL,
    Nombre VARCHAR(100) NOT NULL,
    Creditos INT CHECK (Creditos > 0),
    DepartamentoId INT,
    FOREIGN KEY (DepartamentoId) REFERENCES Departamentos(Id)
);

-- Tabla Asignaciones
CREATE TABLE Asignaciones (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    ProfesorId INT,
    CursoId INT,
    Anio INT NOT NULL,
    Semestre INT CHECK (Semestre IN (1,2)),
    UNIQUE (ProfesorId, CursoId, Anio, Semestre),
    FOREIGN KEY (ProfesorId) REFERENCES Profesores(Id),
    FOREIGN KEY (CursoId) REFERENCES Cursos(Id)
);

-- Tabla Matriculas
CREATE TABLE Matriculas (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    EstudianteId INT,
    CursoId INT,
    Anio INT NOT NULL,
    Semestre INT CHECK (Semestre IN (1,2)),
    NotaFinal DECIMAL(5,2) CHECK (NotaFinal BETWEEN 0 AND 100),
    FechaMatricula DATE DEFAULT (CURRENT_DATE),
    UNIQUE (EstudianteId, CursoId, Anio, Semestre),
    FOREIGN KEY (EstudianteId) REFERENCES Estudiantes(Id),
    FOREIGN KEY (CursoId) REFERENCES Cursos(Id)
);

-- Tabla Users (para el sistema web)
CREATE TABLE Users (
    UserID INT PRIMARY KEY AUTO_INCREMENT,
    FullName VARCHAR(100),
    Email VARCHAR(100) UNIQUE,
    PasswordHash VARCHAR(255),
    IsAdmin TINYINT DEFAULT 0
);

-- ============ DATOS ============

INSERT INTO Departamentos (Nombre, Ubicacion) VALUES
('Ingeniería de Sistemas', 'Edificio A - Piso 3'),
('Matemáticas', 'Edificio B - Piso 1'),
('Física', 'Edificio B - Piso 2'),
('Administración', 'Edificio C - Piso 1');

INSERT INTO Profesores (Nombre, Apellido, Email, DepartamentoId) VALUES
('Carlos', 'Gómez', 'carlos.gomez@universidad.edu', 1),
('María', 'Rodríguez', 'maria.rodriguez@universidad.edu', 1),
('Juan', 'Pérez', 'juan.perez@universidad.edu', 2),
('Ana', 'Martínez', 'ana.martinez@universidad.edu', 3),
('Luis', 'Fernández', 'luis.fernandez@universidad.edu', 4);

INSERT INTO Estudiantes (Carnet, Nombre, Apellido, FechaNacimiento, Email) VALUES
('20240001', 'Andrea', 'López', '2000-05-15', 'andrea.lopez@estudiante.edu'),
('20240002', 'Bruno', 'Díaz', '2001-08-22', 'bruno.diaz@estudiante.edu'),
('20240003', 'Carla', 'Sánchez', '1999-11-10', 'carla.sanchez@estudiante.edu'),
('20240004', 'Daniel', 'Morales', '2000-02-28', 'daniel.morales@estudiante.edu'),
('20240005', 'Elena', 'Castro', '2001-07-19', 'elena.castro@estudiante.edu'),
('20240006', 'Fernando', 'Ramos', '2000-12-01', 'fernando.ramos@estudiante.edu'),
('20240007', 'Gabriela', 'Ortiz', '1999-09-30', 'gabriela.ortiz@estudiante.edu'),
('20240008', 'Héctor', 'Vargas', '2001-03-14', 'hector.vargas@estudiante.edu');

INSERT INTO Cursos (Codigo, Nombre, Creditos, DepartamentoId) VALUES
('IS101', 'Programación I', 4, 1),
('IS201', 'Bases de Datos', 4, 1),
('IS301', 'Redes de Computadoras', 3, 1),
('MAT101', 'Cálculo I', 5, 2),
('MAT201', 'Álgebra Lineal', 4, 2),
('FIS101', 'Física I', 4, 3),
('ADM101', 'Introducción a la Administración', 3, 4);

INSERT INTO Asignaciones (ProfesorId, CursoId, Anio, Semestre) VALUES
(1, 1, 2025, 1),
(1, 2, 2025, 1),
(2, 3, 2025, 1),
(3, 4, 2025, 1),
(3, 5, 2025, 1),
(4, 6, 2025, 1),
(5, 7, 2025, 1);

INSERT INTO Matriculas (EstudianteId, CursoId, Anio, Semestre, NotaFinal) VALUES
(1, 1, 2025, 1, 85.5),
(1, 2, 2025, 1, 90.0),
(1, 4, 2025, 1, 78.0),
(2, 1, 2025, 1, 92.0),
(2, 3, 2025, 1, 88.5),
(2, 6, 2025, 1, 75.0),
(3, 2, 2025, 1, 95.0),
(3, 4, 2025, 1, 82.0),
(3, 5, 2025, 1, 88.0),
(4, 1, 2025, 1, 70.0),
(4, 2, 2025, 1, 85.0),
(4, 7, 2025, 1, 91.0),
(5, 3, 2025, 1, 94.0),
(5, 5, 2025, 1, 86.5),
(5, 6, 2025, 1, 89.0),
(6, 1, 2025, 1, 65.0),
(6, 4, 2025, 1, 70.0),
(6, 7, 2025, 1, 78.0),
(7, 2, 2025, 1, 98.0),
(7, 3, 2025, 1, 92.0),
(7, 5, 2025, 1, 95.0),
(8, 1, 2025, 1, 55.0),
(8, 6, 2025, 1, 60.0),
(8, 7, 2025, 1, 68.0);