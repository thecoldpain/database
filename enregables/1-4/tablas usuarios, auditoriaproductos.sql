 create table Usuarios (
 ID INT IDENTITY(1,1) PRIMARY KEY,
 NombreUsuario NVARCHAR(50) UNIQUE NOT NULL,
 Contraseña NVARCHAR(255) NOT NULL,
 Rol NVARCHAR(20) NOT NULL,
fechacreacion DATETIME DEFAULT GETDATE()
);
go

insert into Usuarios (NombreUsuario, Contraseña, Rol) VALUES
('admin','admin123','Administrador'),('consultor','consultor123','Consultor');
GO

alter table productos add UsuarioCreacion NVARCHAR(50) NULL;
ALTER TABLE productos ADD UsuarioModificacion NVARCHAR(50) NULL;
GO

create table Auditoriaproductos (
	ID INT IDENTITY(1,1) PRIMARY KEY, ProductoID INT, NombreProducto NVARCHAR(100),Operacion NVARCHAR(10),Usuario NVARCHAR(50),Fechahora DATETIME DEFAULT GETDATE(), Detalles NVARCHAR(MAX) NULL );
	GO