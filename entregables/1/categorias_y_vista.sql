CREATE TABLE Categorias (
 ID INT IDENTITY(1,1)PRIMARY KEY,
 Nombre NVARCHAR(50) NOT NULL);

 INSERT INTO Categorias (Nombre) VALUES ('Electronica'),('Hogar'),('Juguetes'),('Ropa'),('Deportes'),('Libros');
 
 ALTER TABLE Productos ADD CategoriaID INT NULL;
 ALTER TABLE Productos ADD CONSTRAINT FK_products_Categorias
 FOREIGN KEY (CategoriaID) REFERENCES Categorias(ID);

 CREATE VIEW vw_Productosconcategoria AS
 SELECT
 p.IDU,
 p.Nombre,
 p.Precio,
 p.Stock,
 p.fechacreacion,
 p.CategoriaID,
 c.Nombre AS Categorianombre
 FROM productos p
 LEFT JOIN Categorias c ON p.CategoriaID = c.ID;
