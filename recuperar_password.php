<?php
require 'conexion.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $nuevoPassword = password_hash($_POST["nueva_password"], PASSWORD_DEFAULT);

    $resultado = $database->usuarios->updateOne(
        ['email' => $email],
        ['$set' => ['password' => $nuevoPassword]]
    );

    if ($resultado->getModifiedCount() > 0) {
        $mensaje = "Contraseña actualizada con éxito. Ya puedes iniciar sesión.";
    } else {
        $error = "No se encontró un usuario con ese email.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="css/recuperar_pass.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

</head>
<body>
    <div class="recover-container">
        <div class="recover-box">
            <h2>Recuperar Contraseña</h2>

            <?php if (isset($mensaje)) echo "<p class='success-message'>$mensaje</p>"; ?>
            <?php if (isset($error)) echo "<p class='error-message'>$error</p>"; ?>

            <form method="POST">
                <div class="input-container">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email" required>
                </div>

                <div class="input-container">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="nueva_password" placeholder="Nueva Contraseña" required>
                </div>

                <button type="submit" class="recover-button">Actualizar Contraseña</button>
                <button type="button" class="back-login-button" onclick="window.location.href='login.php'">Volver al Login</button>
            </form>
        </div>
    </div>
</body>
</html>
