<?php
/* loginnba.php
 * Procesa el formulario de login (loginnba.html) y crea una sesión de usuario.
 * Requiere que la extensión sqlsrv esté instalada y configurada en PHP.
 */

session_start(); // Inicia la sesión (guarda datos en el servidor)

// Configuración de conexión a MSSQL – AJUSTA ESTOS VALORES
$serverName = "192.168.1.32";      // Ej: "localhost" o una IP
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

// Si el formulario se envió mediante POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Recibir y sanitizar los datos del formulario
    $email    = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    // Validación básica del lado del servidor
    if ($email === "" || $password === "") {
        $_SESSION["login_error"] = "Correo y contraseña son obligatorios.";
        header("Location: loginnba.html");
        exit;
    }

    // Consulta para obtener el usuario por correo
    $sql = "SELECT UserID, Email, PasswordHash, IsAdmin
            FROM Users
            WHERE Email = ?";
    $params = array($email);
    $stmt   = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    // Obtener el registro (debería ser una única fila)
    $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);

    if ($user && password_verify($password, $user["PasswordHash"])) {
        // Credenciales correctas – crear variables de sesión
        $_SESSION["user_id"]   = $user["UserID"];
        $_SESSION["user_email"]= $user["Email"];
        $_SESSION["is_admin"]  = ($user["IsAdmin"] == 1); // true/false

        // Redirigir a la página principal (o a un dashboard si lo prefieres)
        header("Location: web_nba-conferences.html");
        exit;
    } else {
        // Credenciales inválidas
        $_SESSION["login_error"] = "Correo o contraseña incorrectos.";
        header("Location: loginnba.html");
        exit;
    }
}

// Si alguien accede directamente a este archivo sin POST, redirigir al formulario
header("Location: loginnba.html");
exit;
?>