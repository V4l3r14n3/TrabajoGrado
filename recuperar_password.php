<?php
require 'conexion.php';
session_start();

$usuario = null;
$mostrarPregunta = false;
$pregunta = "";
$email = "";
$mensaje = "";
$error = "";

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
            if (password_verify($respuesta, $usuario["respuesta_seguridad"])) {
                $nuevoHash = password_hash($nuevaPass, PASSWORD_DEFAULT);
                $result = $database->usuarios->updateOne(
                    ['email' => $email],
                    ['$set' => ['password' => $nuevoHash]]
                );

                $mensaje = "Contraseña actualizada correctamente.";
                $mostrarPregunta = false;
            } else {
                $error = "La respuesta de seguridad no es correcta.";
                $mostrarPregunta = true;
            }
        } else {
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
    <link rel="stylesheet" href="css/recuperar_password.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body>

    <?php if ($mensaje): ?>
        <div class="toast success show"><?= $mensaje ?></div>
        <script>
            setTimeout(() => {
                document.querySelector('.toast.success').classList.remove('show');
                window.location.href = "login.php";
            }, 4000);
        </script>
    <?php elseif ($error): ?>
        <div class="toast error show"><?= $error ?></div>
        <script>
            setTimeout(() => {
                document.querySelector('.toast.error').classList.remove('show');
            }, 4000);
        </script>
    <?php endif; ?>

    <div class="recover-container">
        <div class="recover-box">
            <h2>Recuperar Contraseña</h2>

            <form method="POST">
                <div class="input-container">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($email) ?>">
                </div>

                <?php if ($mostrarPregunta && $pregunta): ?>
                    <p><strong><?= htmlspecialchars($pregunta) ?></strong></p>
                    <div class="input-container">
                        <i class="fas fa-question-circle"></i>
                        <input type="text" name="respuesta" placeholder="Tu respuesta" required>
                    </div>
                    <div class="input-container">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="nueva_password" placeholder="Nueva contraseña" required>
                    </div>
                <?php endif; ?>

                <button type="submit" class="recover-button">Actualizar Contraseña</button>

                <?php if ($mensaje): ?>
                    <button type="button" class="back-login-button" onclick="window.location.href='login.php'">Volver al Login</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>

</html>
