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

    $email    = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($email === "" || $password === "") {
        $_SESSION["login_error"] = "Correo y contraseña son obligatorios.";
        header("Location: loginnba.html");
        exit;
    }

    // Buscar usuario por correo
    $stmt = mysqli_prepare($conn, 
        "SELECT UserID, Email, PasswordHash, IsAdmin FROM Users WHERE Email = ?"
    );
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user   = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($user && password_verify($password, $user["PasswordHash"])) {
        $_SESSION["user_id"]    = $user["UserID"];
        $_SESSION["user_email"] = $user["Email"];
        $_SESSION["is_admin"]   = ($user["IsAdmin"] == 1);
        header("Location: web_nba-conferences.html");
        exit;
    } else {
        $_SESSION["login_error"] = "Correo o contraseña incorrectos.";
        header("Location: loginnba.html");
        exit;
    }
}

mysqli_close($conn);
header("Location: loginnba.html");
?>