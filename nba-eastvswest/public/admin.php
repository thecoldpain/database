<?php
/* admin.php – API JSON para el panel de administración
 * Solo accesible si el usuario tiene rol admin (verificado por sesión).
 * Soporta GET, POST, PUT, DELETE sobre las tablas: equipos, partidos, usuarios.
 */

session_start();
if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    echo json_encode(['error'=>'Acceso denegado']);
    exit;
}

/* ---------- CONFIGURACIÓN DE CONEXIÓN (ajusta) ---------- */
$serverName = "192.168.1.32";      // Ej: "localhost"
$connectionInfo = array(
    "Database" => "TU_BASE_DE_DATOS",
    "UID"      => "TU_USUARIO",
    "PWD"      => "TU_CONTRASEÑA",
    "CharacterSet" => "UTF-8"
);
$conn = sqlsrv_connect($serverName, $connectionInfo);
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

/* ---------- EJECUCIÓN SEGURA ---------- */
function qry($sql, $params = []) {
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    return $stmt;
}

/* ---------- RUTEO ---------- */
$method = $_SERVER['REQUEST_METHOD'];
$table  = $_GET['table'] ?? '';
$id     = $_GET['id'] ?? null;

if (!in_array($table, ['equipos','partidos','usuarios'])) {
    http_response_code(400);
    echo json_encode(['error'=>'Tabla no válida']);
    exit;
}

/* ---------- SELECT ---------- */
if ($method === 'GET') {
    if ($id !== null) {
        $sql = "SELECT * FROM $table WHERE id = ?";
        $stmt = qry($sql, [$id]);
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        if ($row === null) {
            http_response_code(404);
            echo json_encode(['error'=>'No encontrado']);
        } else {
            // Convertir objetos DateTime a string ISO
            foreach ($row as $k=>$v) {
                if ($v instanceof DateTime) {
                    $row[$k] = $v->format('Y-m-d\TH:i:s');
                }
            }
            echo json_encode($row);
        }
    } else {
        $sql = "SELECT * FROM $table ORDER BY id";
        $stmt = qry($sql);
        $out = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            foreach ($row as $k=>$v) {
                if ($v instanceof DateTime) {
                    $row[$k] = $v->format('Y-m-d\TH:i:s');
                }
            }
            $out[] = $row;
        }
        sqlsrv_free_stmt($stmt);
        echo json_encode($out);
    }
    exit;
}

/* ---------- INSERT ---------- */
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error'=>'JSON inválido']);
        exit;
    }

    $cols = $vals = $params = [];
    foreach ($input as $col=>$val) {
        if ($col === 'id') continue;
        $cols[] = "$col";
        $vals[] = "?";
        $params[] = $val;
    }
    $sql = "INSERT INTO $table (".implode(',',$cols).") VALUES (".implode(',',$vals).")";
    $stmt = qry($sql, $params);
    $newId = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_INT);
    sqlsrv_free_stmt($stmt);
    http_response_code(201);
    echo json_encode(['id'=>$newId]);
    exit;
}

/* ---------- UPDATE ---------- */
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE || empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['error'=>'JSON inválido o falta id']);
        exit;
    }
    $id = $input['id'];
    unset($input['id']);

    $sets = $params = [];
    foreach ($input as $col=>$val) {
        $sets[] = "$col = ?";
        $params[] = $val;
    }
    $params[] = $id;
    $sql = "UPDATE $table SET ".implode(',',$sets)." WHERE id = ?";
    $stmt = qry($sql, $params);
    sqlsrv_free_stmt($stmt);
    echo json_encode(['affected'=>sqlsrv_rows_affected($stmt)]);
    exit;
}

/* ---------- DELETE ---------- */
if ($method === 'DELETE') {
    if ($id === null) {
        http_response_code(400);
        echo json_encode(['error'=>'Falta id']);
        exit;
    }
    $sql = "DELETE FROM $table WHERE id = ?";
    $stmt = qry($sql, [$id]);
    sqlsrv_free_stmt($stmt);
    echo json_encode(['affected'=>sqlsrv_rows_affected($stmt)]);
    exit;
}

/* ---------- MÉTODO NO PERMITIDO ---------- */
http_response_code(405);
echo json_encode(['error'=>'Método no permitido']);
?>