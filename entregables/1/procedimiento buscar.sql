CREATE PROCEDURE sp_BuscarProductos
@Nombre NVARCHAR(100)= NULL,
@PrecioMin FLOAT = NULL,
@PrecioMax FLOAT = NULL,
@CategoriaID INT = NULL
AS
BEGIN
	SELECT IDU,Nombre,Precio,Stock,FechaCreacion,CategoriaID,CategoriaNombre
	FROM vw_Productosconcategoria
	WHERE (@Nombre IS NULL OR Nombre LIKE '%' + @Nombre + '%')
	AND (@PrecioMin IS NULL OR Precio >= @PrecioMin)
	AND (@PrecioMax IS NULL OR Precio <= @PrecioMax)
ORDER BY Nombre;
END;