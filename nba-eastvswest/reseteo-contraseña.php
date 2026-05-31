<?php
/* reseteo-contraseña.php
 * Procesa el formulario de "¿Olvidaste tu contraseña?" (reseteo-contraseña.html).
 * 1. Busca el usuario por correo.
 * 2. Genera un token aleatorio, guarda su hash y una fecha de expiración.
 * 3. En un entorno real enviaría el token por correo; aquí lo muestra para testing.
 */

session_start();

// ---------- CONFIGURACIÓN DE CONEXIÓN (ajusta estos valores) ----------
$serverName = "192.168.1.32";      // Ej: "localhost" o IP
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

// ---------- PROCESAR EL FORMULARIO ----------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");

    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION["reset_error"] = "Por favor ingresa un correo válido.";
        header("Location: reseteo-contraseña.html");
        exit;
    }

    // Verificar si el correo existe en la tabla Users
    $checkSql = "SELECT UserID FROM Users WHERE Email = ?";
    $checkParams = array($email);
    $checkStmt = sqlsrv_query($conn, $checkSql, $checkParams);
    if ($checkStmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $user = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($checkStmt);

    if (!$user) {
        // No revelar si el email existe o no (seguridad)
        $_SESSION["reset_success"] = "Si el correo está registrado, recibirás instrucciones para restablecer tu contraseña.";
        header("Location: reseteo-contraseña.html");
        exit;
    }

    $userId = $user["UserID"];

    // Generar token seguro (URL‑safe base64, 32 bytes => 43 chars)
    $token = bin2hex(random_bytes(32)); // 64 caracteres hexadecimales
    $tokenHash = hash('sha256', $token); // Guardamos solo el hash

    // Fecha de expiración: 1 hora desde ahora (ajustable)
    $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

    // Insertar registro en tabla de reseteos (asumimos que existe)
    $insertSql = "INSERT INTO password_resets (UserID, TokenHash, ExpiresAt)
                  VALUES (?, ?, ?)";
    $insertParams = array($userId, $tokenHash, $expires);
    $insertStmt = sqlsrv_query($conn, $insertSql, $insertParams);
    if ($insertStmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($insertStmt);

    // ----- En producción: enviar el token por correo -----
    // Aquí lo mostramos únicamente para poder probar el flujo localmente.
    $_SESSION["reset_token"] = $token; // Sólo para demostración; eliminar en prod
    $_SESSION["reset_success"] = "Se ha generado un token de restablecimiento. Revisa la consola o la variable de sesión para usarlo.";
    header("Location: reseteo-contraseña.html");
    exit;
}

// Acceso directo sin POST
header("Location: reseteo-contraseña.html");
exit;
?>