<?php
require 'conexion.php';
session_start();

$usuario = null;
$mostrarPregunta = false;
$pregunta = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"] ?? null;
    $respuesta = $_POST["respuesta"] ?? null;
    $nuevaPass = $_POST["nueva_password"] ?? null;

    $usuario = $database->usuarios->findOne(['email' => $email]);

    if (!$usuario) {
        $error = "No se encontró un usuario con ese email.";
    } else {
        $pregunta = $usuario["pregunta_seguridad"] ?? null;

        if (isset($respuesta)) {
            // Validar respuesta
            if (password_verify($respuesta, $usuario["respuesta_seguridad"])) {
                $nuevoHash = password_hash($nuevaPass, PASSWORD_DEFAULT);
                $result = $database->usuarios->updateOne(
                    ['email' => $email],
                    ['$set' => ['password' => $nuevoHash]]
                );

                $mensaje = "Contraseña actualizada correctamente.";
            } else {
                $error = "La respuesta de seguridad no es correcta.";
                $mostrarPregunta = true;
            }
        } else {
            // Mostrar la pregunta de seguridad
            $mostrarPregunta = true;
        }
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
                    <input type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($email ?? '') ?>">
                </div>

                <?php if ($mostrarPregunta && isset($pregunta)): ?>
                    <p><strong><?= htmlspecialchars($pregunta) ?></strong></p>
                    <input type="text" name="respuesta" placeholder="Tu respuesta" required>
                    <input type="password" name="nueva_password" placeholder="Nueva contraseña" required>
                <?php endif; ?>

                <button type="submit">Actualizar Contraseña</button>
            </form>

        </div>
    </div>
</body>

</html>