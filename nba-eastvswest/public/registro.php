<?php
/* registro.php
 * Recibe los datos del formulario de registro, crea un nuevo usuario en la tabla Users
 * de tu base de datos MSSQL y redirige al login tras un registro exitoso.
 *
 * Requisitos:
 *   - Extensión sqlsrv instalada y habilitada en PHP.
 *   - Tabla Users con al menos las columnas:
 *         UserID (INT, IDENTITY PK)
 *         FullName (VARCHAR)
 *         Email  (VARCHAR, único)
 *         PasswordHash (VARCHAR)   ← almacena el hash generado por password_hash()
 *         IsAdmin  (TINYINT/BIT, default 0)
 */

session_start(); // opcional, por si quieres usar mensajes flash

/* --------------------  CONFIGURACIÓN DE CONEXIÓN  -------------------- */
$serverName = "TU_SERVIDOR_MSSQL";      // Ej: "localhost" o IP del servidor
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

/* --------------------  PROCESAR POST  -------------------- */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Recibir y sanitizar datos del formulario
    $fullName = trim($_POST["fullname"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $confirm  = trim($_POST["confirmPassword"] ?? "");
    $terms    = isset($_POST["terms"]) ? true : false;

    // Validaciones básicas del lado del servidor
    $errors = [];

    if ($fullName === "") {
        $errors[] = "El nombre completo es obligatorio.";
    }
    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Ingresa un correo electrónico válido.";
    }
    if ($password === "" || strlen($password) < 6) {
        $errors[] = "La contraseña debe tener al menos 6 caracteres.";
    }
    if ($password !== $confirm) {
        $errors[] = "Las contraseñas no coinciden.";
    }
    if (!$terms) {
        $errors[] = "Debes aceptar los términos y condiciones.";
    }

    // Si hay errores, volver al formulario con mensaje
    if (!empty($errors)) {
        $_SESSION["register_errors"] = $errors;
        $_SESSION["register_old"]    = ["fullname"=>$fullName, "email"=>$email];
        header("Location: register.html");
        exit;
    }

    // Verificar si el correo ya existe
    $checkSql = "SELECT UserID FROM Users WHERE Email = ?";
    $checkParams = array($email);
    $checkStmt   = sqlsrv_query($conn, $checkSql, $checkParams);
    if ($checkStmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($checkStmt);

    if ($row) {
        $_SESSION["register_errors"] = ["El correo ya está registrado."];
        header("Location: register.html");
        exit;
    }

    // Generar hash seguro de la contraseña
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar nuevo usuario
    $insertSql = "INSERT INTO Users (FullName, Email, PasswordHash, IsAdmin)
                  VALUES (?, ?, ?, 0)";
    $insertParams = array($fullName, $email, $passwordHash);
    $insertStmt   = sqlsrv_query($conn, $insertSql, $insertParams);

    if ($insertStmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    sqlsrv_free_stmt($insertStmt);

    // Opcional: guardar mensaje de éxito en sesión para mostrarlo en login
    $_SESSION["register_success"] = "Registro exitoso. Ahora puedes iniciar sesión.";
    header("Location: loginnba.html");
    exit;
}

// Si alguien accede directamente a este archivo sin POST, redirigir al formulario
header("Location: registro.html");
exit;
?>