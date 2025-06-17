<?php
require 'conexion.php';
require 'vendor/autoload.php';
session_start();

// Prellenar email desde cookie
$emailGuardado = isset($_COOKIE['email']) ? $_COOKIE['email'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"];

    $coleccion = $database->usuarios;
    $usuario = $coleccion->findOne(["email" => $email]);

    if ($usuario && password_verify($password, $usuario["password"])) {
        $_SESSION["usuario"] = [
            "_id" => (string)$usuario["_id"],
            "nombre" => $usuario["nombre"],
            "email" => $usuario["email"],
            "tipo_usuario" => $usuario["tipo_usuario"],
            "organizacion" => $usuario["organizacion"] ?? null,
        ];

        // Guardar cookie si marcó "Recordarme"
        if (isset($_POST['recordarme'])) {
            setcookie("email", $email, time() + (86400 * 30), "/"); // 30 días
        } else {
            setcookie("email", "", time() - 3600, "/"); // Eliminar cookie
        }

        // Redirigir según verificación y tipo
        if ($usuario["tipo_usuario"] === "organizacion" && isset($usuario["verificado"]) && !$usuario["verificado"]) {
            header("Location: organizacion/verificar.php");
            exit();
        }

        switch ($usuario["tipo_usuario"]) {
            case "voluntario":
                header("Location: voluntario/index.php");
                break;
            case "organizacion":
                header("Location: organizacion/index.php");
                break;
            case "admin":
                header("Location: admin/index.php");
                break;
            default:
                header("Location: index.php");
                break;
        }
        exit();
    } else {
        $error = "Credenciales incorrectas.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body>
    <div class="login-container">
        <div class="avatar">
            <i class="fas fa-user"></i>
        </div>
        <div class="login-box">
            <!-- Mensaje de éxito si el registro fue correcto -->
            <?php if (isset($_GET['registro_exitoso'])): ?>
                <div class="success-message">¡Registro exitoso! Ahora puedes iniciar sesión.</div>
            <?php endif; ?>

            <!-- Mensaje de error si las credenciales son incorrectas -->
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="input-container">
                    <i class="fas fa-user"></i>
                    <input type="email" name="email" placeholder="Email ID" required value="<?php echo htmlspecialchars($emailGuardado); ?>">
                </div>
                <div class="input-container">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="options">
                    <label><input type="checkbox" name="recordarme" <?php if ($emailGuardado) echo 'checked'; ?>> Recordarme</label>
                    <a href="recuperar_password.php">¿Olvidaste tu contraseña?</a>
                </div>
                <button type="submit" class="login-button">LOGIN</button>
            </form>
            <p>¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
            <p><a href="index.php" style="color: #1e3c72;">⟵ Volver al inicio</a></p>
        </div>
    </div>
    <script src="js/login.js"></script>
</body>

</html>