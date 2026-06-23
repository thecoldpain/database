<?php
/**
 * ================================================================
 * SISTEMA DE GESTIÓN DE PRODUCTOS - ENTREGABLES 1 al 4
 * 
 * Tecnologías: PHP 7+ con sqlsrv + SQL Server
 * Base de datos: productsDB
 * 
 * Características:
 *   ✅ CRUD completo (Entregable 1)
 *   ✅ Filtros con procedimiento almacenado (Entregable 2)
 *   ✅ Categorías, FK y vista (Entregable 3)
 *   ✅ Login, roles y auditoría (Entregable 4)
 * ================================================================
 */

// ---------- CONFIGURACIÓN DE SESIÓN ----------
session_start();

// ---------- CONEXIÓN A SQL SERVER ----------
$serverName = "localhost"; // o "TU_SERVIDOR\SQLEXPRESS"
$connectionOptions = array(
    "Database" => "productsDB",
    "Uid" => "tu_usuario",        // CAMBIA ESTO
    "PWD" => "tu_contraseña"      // CAMBIA ESTO
);
$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    die("❌ Error de conexión: " . print_r(sqlsrv_errors(), true));
}

// ---------- VARIABLES GLOBALES ----------
$mensaje = '';
$tipoMensaje = 'success';
$usuarioLogueado = $_SESSION['usuario'] ?? null;
$rolUsuario = $_SESSION['rol'] ?? null;

// ---------- FUNCIONES AUXILIARES ----------

/**
 * Registra una operación en la tabla de auditoría
 */
function registrarAuditoria($conn, $productoID, $nombreProducto, $operacion, $usuario, $detalles = null) {
    $sql = "INSERT INTO AuditoriaProductos (ProductoID, NombreProducto, Operacion, Usuario, Detalles)
            VALUES (?, ?, ?, ?, ?)";
    $params = array($productoID, $nombreProducto, $operacion, $usuario, $detalles);
    $stmt = sqlsrv_query($conn, $sql, $params);
    return $stmt !== false;
}

/**
 * Obtiene todas las categorías para poblar selects
 */
function obtenerCategorias($conn) {
    $categorias = [];
    $sql = "SELECT ID, Nombre FROM Categorias ORDER BY Nombre";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $categorias[] = $row;
        }
        sqlsrv_free_stmt($stmt);
    }
    return $categorias;
}

/**
 * Verifica si el usuario tiene permiso de administrador
 */
function esAdministrador() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'Administrador';
}

// ---------- PROCESAR ACCIONES ----------

// --- 1. LOGIN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'login') {
    $nombreUsuario = trim($_POST['nombre_usuario']);
    $contrasena = trim($_POST['contrasena']);
    
    if (empty($nombreUsuario) || empty($contrasena)) {
        $mensaje = "❌ Complete todos los campos.";
        $tipoMensaje = "error";
    } else {
        $sql = "SELECT ID, NombreUsuario, Contraseña, Rol FROM Usuarios WHERE NombreUsuario = ?";
        $stmt = sqlsrv_query($conn, $sql, array($nombreUsuario));
        if ($stmt === false) {
            $mensaje = "❌ Error en login: " . print_r(sqlsrv_errors(), true);
            $tipoMensaje = "error";
        } else {
            $usuario = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if ($usuario && $usuario['Contraseña'] === $contrasena) {
                $_SESSION['usuario_id'] = $usuario['ID'];
                $_SESSION['usuario'] = $usuario['NombreUsuario'];
                $_SESSION['rol'] = $usuario['Rol'];
                $mensaje = "✅ Bienvenido, " . $usuario['NombreUsuario'] . " (" . $usuario['Rol'] . ")";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $mensaje = "❌ Usuario o contraseña incorrectos.";
                $tipoMensaje = "error";
            }
            sqlsrv_free_stmt($stmt);
        }
    }
}

// --- 2. LOGOUT ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- 3. CREAR PRODUCTO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    if (!esAdministrador()) {
        $mensaje = "❌ No tiene permiso para crear productos.";
        $tipoMensaje = "error";
    } else {
        $nombre = trim($_POST['nombre']);
        $precio = floatval($_POST['precio']);
        $stock = intval($_POST['stock']);
        $categoriaID = !empty($_POST['categoria_id']) ? intval($_POST['categoria_id']) : null;
        
        if (empty($nombre) || $precio <= 0 || $stock < 0) {
            $mensaje = "❌ Todos los campos son obligatorios (precio > 0, stock >= 0).";
            $tipoMensaje = "error";
        } else {
            $sql = "INSERT INTO products (Nombre, Precio, Stock, CategoriaID, UsuarioCreacion, UsuarioModificacion)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $params = array($nombre, $precio, $stock, $categoriaID, $_SESSION['usuario'], $_SESSION['usuario']);
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) {
                $mensaje = "❌ Error al crear: " . print_r(sqlsrv_errors(), true);
                $tipoMensaje = "error";
            } else {
                $nuevoID = sqlsrv_insert_id($stmt) ?? 0;
                registrarAuditoria($conn, $nuevoID, $nombre, 'INSERT', $_SESSION['usuario']);
                $mensaje = "✅ Producto '$nombre' creado correctamente.";
                sqlsrv_free_stmt($stmt);
            }
        }
    }
}

// --- 4. EDITAR PRODUCTO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    if (!esAdministrador()) {
        $mensaje = "❌ No tiene permiso para editar productos.";
        $tipoMensaje = "error";
    } else {
        $id = intval($_POST['id']);
        $nombre = trim($_POST['nombre']);
        $precio = floatval($_POST['precio']);
        $stock = intval($_POST['stock']);
        $categoriaID = !empty($_POST['categoria_id']) ? intval($_POST['categoria_id']) : null;
        
        if (empty($nombre) || $precio <= 0 || $stock < 0 || $id <= 0) {
            $mensaje = "❌ Datos inválidos para la edición.";
            $tipoMensaje = "error";
        } else {
            // Obtener nombre anterior para auditoría
            $sqlOld = "SELECT Nombre FROM products WHERE ID = ?";
            $stmtOld = sqlsrv_query($conn, $sqlOld, array($id));
            $nombreAnterior = 'desconocido';
            if ($stmtOld) {
                $row = sqlsrv_fetch_array($stmtOld, SQLSRV_FETCH_ASSOC);
                if ($row) $nombreAnterior = $row['Nombre'];
                sqlsrv_free_stmt($stmtOld);
            }
            
            $sql = "UPDATE products 
                    SET Nombre = ?, Precio = ?, Stock = ?, CategoriaID = ?, UsuarioModificacion = ?
                    WHERE ID = ?";
            $params = array($nombre, $precio, $stock, $categoriaID, $_SESSION['usuario'], $id);
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) {
                $mensaje = "❌ Error al actualizar: " . print_r(sqlsrv_errors(), true);
                $tipoMensaje = "error";
            } else {
                $detalles = "Nombre: '$nombreAnterior' → '$nombre'";
                registrarAuditoria($conn, $id, $nombre, 'UPDATE', $_SESSION['usuario'], $detalles);
                $mensaje = "✅ Producto '$nombre' actualizado correctamente.";
                sqlsrv_free_stmt($stmt);
            }
        }
    }
}

// --- 5. ELIMINAR PRODUCTO ---
if (isset($_GET['eliminar']) && esAdministrador()) {
    $id = intval($_GET['eliminar']);
    // Obtener datos antes de eliminar
    $sqlSel = "SELECT Nombre FROM products WHERE ID = ?";
    $stmtSel = sqlsrv_query($conn, $sqlSel, array($id));
    if ($stmtSel) {
        $row = sqlsrv_fetch_array($stmtSel, SQLSRV_FETCH_ASSOC);
        if ($row) {
            $nombre = $row['Nombre'];
            registrarAuditoria($conn, $id, $nombre, 'DELETE', $_SESSION['usuario']);
            sqlsrv_free_stmt($stmtSel);
            
            $sqlDel = "DELETE FROM products WHERE ID = ?";
            $stmtDel = sqlsrv_query($conn, $sqlDel, array($id));
            if ($stmtDel === false) {
                $mensaje = "❌ Error al eliminar: " . print_r(sqlsrv_errors(), true);
                $tipoMensaje = "error";
            } else {
                $mensaje = "✅ Producto '$nombre' eliminado correctamente.";
                sqlsrv_free_stmt($stmtDel);
            }
        } else {
            $mensaje = "❌ Producto no encontrado.";
            $tipoMensaje = "error";
        }
    }
}

// --- 6. BÚSQUEDA CON PROCEDIMIENTO (Entregable 2) ---
$productos = [];
$filtroNombre = isset($_GET['nombre']) ? trim($_GET['nombre']) : '';
$filtroPrecioMin = isset($_GET['precio_min']) && $_GET['precio_min'] !== '' ? floatval($_GET['precio_min']) : null;
$filtroPrecioMax = isset($_GET['precio_max']) && $_GET['precio_max'] !== '' ? floatval($_GET['precio_max']) : null;
$filtroCategoria = isset($_GET['categoria_id']) && $_GET['categoria_id'] !== '' ? intval($_GET['categoria_id']) : null;
$esBusqueda = isset($_GET['buscar']) && $_GET['buscar'] == '1';

if ($esBusqueda) {
    // Usar el procedimiento almacenado
    $sql = "{CALL sp_BuscarProductos(?, ?, ?, ?)}";
    $params = array(
        $filtroNombre ?: null,
        $filtroPrecioMin,
        $filtroPrecioMax,
        $filtroCategoria
    );
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        $mensaje = "❌ Error en búsqueda: " . print_r(sqlsrv_errors(), true);
        $tipoMensaje = "error";
    } else {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $productos[] = $row;
        }
        sqlsrv_free_stmt($stmt);
    }
} else {
    // Mostrar todos usando la vista
    $sql = "SELECT * FROM vw_ProductosConCategoria ORDER BY Nombre";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        $mensaje = "❌ Error al listar productos: " . print_r(sqlsrv_errors(), true);
        $tipoMensaje = "error";
    } else {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $productos[] = $row;
        }
        sqlsrv_free_stmt($stmt);
    }
}

// Formatear fecha para mostrar
foreach ($productos as &$p) {
    if ($p['FechaCreacion'] instanceof DateTime) {
        $p['FechaCreacion'] = $p['FechaCreacion']->format('d/m/Y H:i');
    }
}

// --- 7. OBTENER DATOS PARA EDITAR ---
$productoEditar = null;
if (isset($_GET['editar']) && esAdministrador()) {
    $idEditar = intval($_GET['editar']);
    $sql = "SELECT ID, Nombre, Precio, Stock, CategoriaID FROM products WHERE ID = ?";
    $stmt = sqlsrv_query($conn, $sql, array($idEditar));
    if ($stmt) {
        $productoEditar = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
    }
}

// Obtener categorías para selects
$categorias = obtenerCategorias($conn);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Entregables 1-4</title>
    <style>
        /* ====== ESTILOS BÁSICOS ====== */
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 20px; background: #f0f2f5; }
        .container { max-width: 1200px; margin: auto; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #333; }
        .text-center { text-align: center; }
        .mensaje { padding: 12px 15px; border-radius: 5px; margin-bottom: 20px; font-weight: 500; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* ====== CABECERA ====== */
        .header { display: flex; justify-content: space-between; align-items: center; background: #e9ecef; padding: 10px 20px; border-radius: 5px; margin-bottom: 20px; }
        .user-info { font-weight: bold; }
        .user-info span { color: #007bff; }
        .btn-logout { background: #dc3545; color: white; padding: 6px 15px; border-radius: 4px; text-decoration: none; font-size: 0.9em; }
        .btn-logout:hover { background: #c82333; }

        /* ====== FORMULARIOS ====== */
        .form-section, .editar-form, .login-form, .filtros-form {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            border: 1px solid #dee2e6;
        }
        .form-section input, .form-section select, .form-section button,
        .editar-form input, .editar-form select, .editar-form button,
        .filtros-form input, .filtros-form select, .filtros-form button {
            padding: 8px 12px;
            margin: 5px 5px 5px 0;
            border-radius: 4px;
            border: 1px solid #ced4da;
            font-size: 0.95em;
        }
        .form-section button, .editar-form button, .filtros-form button {
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            padding: 8px 18px;
        }
        .form-section button:hover, .editar-form button:hover, .filtros-form button:hover {
            background: #0056b3;
        }
        .btn-cancelar { background: #6c757d; color: white; padding: 8px 15px; border-radius: 4px; text-decoration: none; }
        .btn-cancelar:hover { background: #5a6268; }

        /* ====== TABLA ====== */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid #ddd; }
        th { background: #e9ecef; padding: 10px; text-align: left; }
        td { padding: 10px; vertical-align: middle; }
        .acciones a { padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 0.85em; display: inline-block; }
        .btn-editar { background: #ffc107; color: #333; }
        .btn-eliminar { background: #dc3545; color: white; }
        .btn-editar:hover { background: #e0a800; }
        .btn-eliminar:hover { background: #c82333; }
        .sin-productos { text-align: center; padding: 30px; color: #888; font-style: italic; }
        .text-muted { color: #6c757d; }
        .badge-consulta { background: #17a2b8; color: white; padding: 2px 10px; border-radius: 12px; font-size: 0.75em; }

        /* ====== RESPONSIVE ====== */
        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: flex-start; gap: 10px; }
            table, th, td { font-size: 0.85em; }
            .form-section input, .form-section select { width: 100%; margin: 5px 0; }
        }
    </style>
</head>
<body>
<div class="container">

    <!-- ============================================================ -->
    <!-- 1. ENCABEZADO -->
    <!-- ============================================================ -->
    <div class="header">
        <div>
            <strong>📦 Sistema de Productos</strong>
            <?php if ($usuarioLogueado): ?>
                <span style="margin-left: 20px; font-size: 0.9em;">
                    👤 <span><?php echo htmlspecialchars($usuarioLogueado); ?></span>
                    (<?php echo htmlspecialchars($rolUsuario); ?>)
                </span>
            <?php endif; ?>
        </div>
        <div>
            <?php if ($usuarioLogueado): ?>
                <a href="?logout=1" class="btn-logout">🚪 Cerrar sesión</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 2. MENSAJES -->
    <!-- ============================================================ -->
    <?php if (!empty($mensaje)): ?>
        <div class="mensaje <?php echo $tipoMensaje; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- 3. LOGIN (si no hay sesión)                                  -->
    <!-- ============================================================ -->
    <?php if (!$usuarioLogueado): ?>
    <div class="login-form" style="max-width: 400px; margin: 40px auto;">
        <h2>🔐 Iniciar sesión</h2>
        <form method="POST">
            <input type="hidden" name="accion" value="login">
            <div style="margin-bottom: 10px;">
                <label>Usuario:</label>
                <input type="text" name="nombre_usuario" placeholder="Ej: admin" required style="width: 100%;">
            </div>
            <div style="margin-bottom: 10px;">
                <label>Contraseña:</label>
                <input type="password" name="contrasena" placeholder="Contraseña" required style="width: 100%;">
            </div>
            <button type="submit" style="width: 100%;">Ingresar</button>
            <div style="margin-top: 10px; font-size: 0.85em; color: #6c757d;">
                <p>Usuarios de prueba: <br>
                <strong>admin</strong> / admin123 (Administrador)<br>
                <strong>consultor</strong> / consultor123 (Consultor)</p>
            </div>
        </form>
    </div>
    <?php else: ?>

    <!-- ============================================================ -->
    <!-- 4. FILTROS (Entregable 2)                                    -->
    <!-- ============================================================ -->
    <div class="filtros-form">
        <h2>🔍 Buscar productos</h2>
        <form method="GET">
            <input type="hidden" name="buscar" value="1">
            <input type="text" name="nombre" placeholder="Nombre (contenga...)" value="<?php echo htmlspecialchars($filtroNombre); ?>">
            <input type="number" step="0.01" name="precio_min" placeholder="Precio mínimo" value="<?php echo $filtroPrecioMin !== null ? htmlspecialchars($filtroPrecioMin) : ''; ?>">
            <input type="number" step="0.01" name="precio_max" placeholder="Precio máximo" value="<?php echo $filtroPrecioMax !== null ? htmlspecialchars($filtroPrecioMax) : ''; ?>">
            <select name="categoria_id">
                <option value="">-- Todas las categorías --</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo $cat['ID']; ?>" <?php echo ($filtroCategoria == $cat['ID']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['Nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">🔎 Buscar</button>
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn-cancelar">🔄 Limpiar</a>
            <?php if ($esBusqueda): ?>
                <span class="badge-consulta">Resultados de búsqueda</span>
            <?php endif; ?>
        </form>
    </div>

    <!-- ============================================================ -->
    <!-- 5. CREAR (solo Administrador)                               -->
    <!-- ============================================================ -->
    <?php if (esAdministrador()): ?>
    <div class="form-section">
        <h2>➕ Nuevo Producto</h2>
        <form method="POST">
            <input type="hidden" name="accion" value="crear">
            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="number" step="0.01" name="precio" placeholder="Precio" required min="0.01">
            <input type="number" name="stock" placeholder="Stock" required min="0">
            <select name="categoria_id">
                <option value="">-- Sin categoría --</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo $cat['ID']; ?>"><?php echo htmlspecialchars($cat['Nombre']); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">💾 Guardar</button>
        </form>
    </div>
    <?php else: ?>
        <div class="form-section" style="background: #fff3cd; border-color: #ffeeba;">
            <p style="margin: 5px 0; color: #856404;">ℹ️ Modo consultor: solo puede visualizar productos.</p>
        </div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- 6. EDITAR (solo Administrador)                              -->
    <!-- ============================================================ -->
    <?php if ($productoEditar && esAdministrador()): ?>
    <div class="editar-form">
        <h2>✏️ Editar Producto</h2>
        <form method="POST">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" value="<?php echo $productoEditar['ID']; ?>">
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($productoEditar['Nombre']); ?>" required>
            <input type="number" step="0.01" name="precio" value="<?php echo htmlspecialchars($productoEditar['Precio']); ?>" required min="0.01">
            <input type="number" name="stock" value="<?php echo htmlspecialchars($productoEditar['Stock']); ?>" required min="0">
            <select name="categoria_id">
                <option value="">-- Sin categoría --</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo $cat['ID']; ?>" <?php echo ($productoEditar['CategoriaID'] == $cat['ID']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['Nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">💾 Actualizar</button>
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn-cancelar">Cancelar</a>
        </form>
    </div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- 7. TABLA DE PRODUCTOS                                        -->
    <!-- ============================================================ -->
    <h2>📋 Lista de Productos</h2>
    <?php if (count($productos) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Categoría</th>
                <th>Fecha Creación</th>
                <?php if (esAdministrador()): ?>
                <th>Acciones</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($productos as $p): ?>
            <tr>
                <td><?php echo $p['ID']; ?></td>
                <td><?php echo htmlspecialchars($p['Nombre']); ?></td>
                <td>$ <?php echo number_format($p['Precio'], 2); ?></td>
                <td><?php echo $p['Stock']; ?></td>
                <td><?php echo $p['CategoriaNombre'] ?? '<span class="text-muted">Sin categoría</span>'; ?></td>
                <td><?php echo htmlspecialchars($p['FechaCreacion']); ?></td>
                <?php if (esAdministrador()): ?>
                <td class="acciones">
                    <a href="?editar=<?php echo $p['ID']; ?>" class="btn-editar">✏️ Editar</a>
                    <a href="?eliminar=<?php echo $p['ID']; ?>" class="btn-eliminar" onclick="return confirm('¿Eliminar este producto?')">🗑️ Eliminar</a>
                </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div class="sin-productos">
            📭 No se encontraron productos que coincidan con los filtros seleccionados.
            <?php if ($esBusqueda): ?>
                <br><small>Prueba con otros criterios o <a href="<?php echo $_SERVER['PHP_SELF']; ?>">limpia los filtros</a>.</small>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- 8. PIE DE PÁGINA                                             -->
    <!-- ============================================================ -->
    <div style="margin-top: 30px; font-size: 0.8em; color: #6c757d; border-top: 1px solid #dee2e6; padding-top: 15px; text-align: center;">
        <p>
            📌 Entregables 1 al 4 - Sistema de Gestión de Productos<br>
            🔹 CRUD básico | 🔹 Filtros con SP | 🔹 Categorías y vista | 🔹 Login y auditoría<br>
            🖥️ PHP + sqlsrv + SQL Server - <?php echo date('Y'); ?>
        </p>
    </div>

    <?php endif; // Fin de usuario logueado ?>
</div>
</body>
</html>
