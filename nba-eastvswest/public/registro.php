<?php
session_start();

/* -------------------- CONEXIÓN -------------------- */
$conn = mysqli_connect("localhost", "root", "", "UniversidadDB");
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");

/* -------------------- PROCESAR POST -------------------- */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $fullName = trim($_POST["fullname"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $confirm  = trim($_POST["confirmPassword"] ?? "");
    $terms    = isset($_POST["terms"]) ? true : false;

    $errors = [];

    if ($fullName === "")
        $errors[] = "El nombre completo es obligatorio.";
    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Ingresa un correo electrónico válido.";
    if ($password === "" || strlen($password) < 6)
        $errors[] = "La contraseña debe tener al menos 6 caracteres.";
    if ($password !== $confirm)
        $errors[] = "Las contraseñas no coinciden.";
    if (!$terms)
        $errors[] = "Debes aceptar los términos y condiciones.";

    if (!empty($errors)) {
        $_SESSION["register_errors"] = $errors;
        $_SESSION["register_old"]    = ["fullname" => $fullName, "email" => $email];
        header("Location: registro.html");
        exit;
    }

    // Verificar si el correo ya existe
    $checkStmt = mysqli_prepare($conn, "SELECT UserID FROM Users WHERE Email = ?");
    mysqli_stmt_bind_param($checkStmt, "s", $email);
    mysqli_stmt_execute($checkStmt);
    $result = mysqli_stmt_get_result($checkStmt);
    $row    = mysqli_fetch_assoc($result);
    mysqli_stmt_close($checkStmt);

    if ($row) {
        $_SESSION["register_errors"] = ["El correo ya está registrado."];
        header("Location: registro.html");
        exit;
    }

    // Hash de contraseña e insertar usuario
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $insertStmt = mysqli_prepare($conn, 
        "INSERT INTO Users (FullName, Email, PasswordHash, IsAdmin) VALUES (?, ?, ?, 0)"
    );
    mysqli_stmt_bind_param($insertStmt, "sss", $fullName, $email, $passwordHash);

    if (!mysqli_stmt_execute($insertStmt)) {
        die("Error al registrar: " . mysqli_error($conn));
    }
    mysqli_stmt_close($insertStmt);

    $_SESSION["register_success"] = "Registro exitoso. Ahora puedes iniciar sesión.";
    header("Location: loginnba.html");
    exit;
}

mysqli_close($conn);
?>