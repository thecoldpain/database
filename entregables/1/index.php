<?php
// ============================================================
// ENTREGABLE 1: CRUD básico de productos (PHP + PDO + MariaDB)
// ============================================================

// ---------- CONFIGURACIÓN DE CONEXIÓN PDO ----------
$host = 'localhost';
$dbname = 'productsDB';      // <--- NOMBRE CORRECTO
$user = 'tu_usuario';        // CAMBIA por tu usuario de MariaDB
$password = 'tu_contraseña'; // CAMBIA por tu contraseña

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}

// Variables para mensajes
$mensaje = "";
$tipoMensaje = "success";

// ---------- ELIMINAR ----------
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    try {
        // Obtener nombre para el mensaje
        $stmt = $pdo->prepare("SELECT Nombre FROM productos WHERE ID = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $nombre = $row['Nombre'];
            // Eliminar
            $stmtDel = $pdo->prepare("DELETE FROM productos WHERE ID = ?");
            $stmtDel->execute([$id]);
            $mensaje = "✅ Producto '$nombre' eliminado correctamente.";
        } else {
            $mensaje = "❌ Producto no encontrado.";
            $tipoMensaje = "error";
        }
    } catch (PDOException $e) {
        $mensaje = "❌ Error al eliminar: " . $e->getMessage();
        $tipoMensaje = "error";
    }
}

// ---------- CREAR (INSERT) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    $nombre = trim($_POST['nombre']);
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);

    if (empty($nombre) || $precio <= 0 || $stock < 0) {
        $mensaje = "❌ Todos los campos son obligatorios (precio > 0, stock >= 0).";
        $tipoMensaje = "error";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO productos (Nombre, Precio, Stock) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, $precio, $stock]);
            $mensaje = "✅ Producto '$nombre' creado correctamente.";
        } catch (PDOException $e) {
            $mensaje = "❌ Error al crear: " . $e->getMessage();
            $tipoMensaje = "error";
        }
    }
}

// ---------- EDITAR (UPDATE) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    $id = intval($_POST['id']);
    $nombre = trim($_POST['nombre']);
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);

    if (empty($nombre) || $precio <= 0 || $stock < 0 || $id <= 0) {
        $mensaje = "❌ Datos inválidos para la edición.";
        $tipoMensaje = "error";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE productos SET Nombre = ?, Precio = ?, Stock = ? WHERE ID = ?");
            $stmt->execute([$nombre, $precio, $stock, $id]);
            $mensaje = "✅ Producto '$nombre' actualizado correctamente.";
        } catch (PDOException $e) {
            $mensaje = "❌ Error al actualizar: " . $e->getMessage();
            $tipoMensaje = "error";
        }
    }
}

// ---------- LISTAR PRODUCTOS ----------
$productos = [];
try {
    $stmt = $pdo->query("SELECT ID, Nombre, Precio, Stock, FechaCreacion FROM productos ORDER BY Nombre");
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Formatear fecha para mostrar
    foreach ($productos as &$p) {
        $p['FechaCreacion'] = date('d/m/Y H:i', strtotime($p['FechaCreacion']));
    }
} catch (PDOException $e) {
    $mensaje = "❌ Error al obtener productos: " . $e->getMessage();
    $tipoMensaje = "error";
}

// ---------- OBTENER DATOS PARA EDITAR (si se pidió) ----------
$productoEditar = null;
if (isset($_GET['editar'])) {
    $idEditar = intval($_GET['editar']);
    try {
        $stmt = $pdo->prepare("SELECT ID, Nombre, Precio, Stock FROM productos WHERE ID = ?");
        $stmt->execute([$idEditar]);
        $productoEditar = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $mensaje = "❌ Error al cargar producto para editar: " . $e->getMessage();
        $tipoMensaje = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Productos - Entregable 1</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f4f4f4; }
        .container { max-width: 1100px; margin: auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { text-align: center; color: #333; }
        .mensaje { padding: 12px; border-radius: 4px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-section { background: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 30px; }
        .form-section input, .form-section button { padding: 8px; margin: 5px 5px 5px 0; border-radius: 4px; border: 1px solid #ccc; }
        .form-section button { background: #007bff; color: white; border: none; cursor: pointer; padding: 8px 15px; }
        .form-section button:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table, th, td { border: 1px solid #ddd; }
        th { background: #f8f9fa; padding: 10px; text-align: left; }
        td { padding: 10px; }
        .acciones a { padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 0.9em; }
        .btn-editar { background: #ffc107; color: #333; }
        .btn-eliminar { background: #dc3545; color: white; }
        .btn-cancelar { background: #6c757d; color: white; padding: 8px 15px; border-radius: 4px; text-decoration: none; }
        .editar-form { background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ffeeba; }
        .sin-productos { text-align: center; padding: 20px; font-style: italic; color: #888; }
    </style>
</head>
<body>
<div class="container">
    <h1>📦 Gestión de Productos</h1>

    <?php if (!empty($mensaje)): ?>
        <div class="mensaje <?php echo $tipoMensaje; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>

    <!-- FORMULARIO DE EDICIÓN (si se seleccionó un producto) -->
    <?php if ($productoEditar): ?>
    <div class="editar-form">
        <h2>✏️ Editar Producto</h2>
        <form method="POST">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" value="<?php echo $productoEditar['ID']; ?>">
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($productoEditar['Nombre']); ?>" required>
            <input type="number" step="0.01" name="precio" value="<?php echo htmlspecialchars($productoEditar['Precio']); ?>" required min="0.01">
            <input type="number" name="stock" value="<?php echo htmlspecialchars($productoEditar['Stock']); ?>" required min="0">
            <button type="submit">Actualizar</button>
            <a href="index.php" class="btn-cancelar">Cancelar</a>
        </form>
    </div>
    <?php endif; ?>

    <!-- FORMULARIO DE CREACIÓN -->
    <div class="form-section">
        <h2>➕ Nuevo Producto</h2>
        <form method="POST">
            <input type="hidden" name="accion" value="crear">
            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="number" step="0.01" name="precio" placeholder="Precio" required min="0.01">
            <input type="number" name="stock" placeholder="Stock" required min="0">
            <button type="submit">Guardar</button>
        </form>
    </div>

    <!-- TABLA DE PRODUCTOS -->
    <h2>📋 Lista de Productos</h2>
    <?php if (count($productos) > 0): ?>
    <table>
        <thead><tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Stock</th><th>Fecha Creación</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($productos as $p): ?>
            <tr>
                <td><?php echo $p['ID']; ?></td>
                <td><?php echo htmlspecialchars($p['Nombre']); ?></td>
                <td>$ <?php echo number_format($p['Precio'], 2); ?></td>
                <td><?php echo $p['Stock']; ?></td>
                <td><?php echo htmlspecialchars($p['FechaCreacion']); ?></td>
                <td class="acciones">
                    <a href="index.php?editar=<?php echo $p['ID']; ?>" class="btn-editar">✏️ Editar</a>
                    <a href="index.php?eliminar=<?php echo $p['ID']; ?>" class="btn-eliminar" onclick="return confirm('¿Eliminar este producto?')">🗑️ Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div class="sin-productos">No hay productos registrados.</div>
    <?php endif; ?>
</div>
</body>
</html>