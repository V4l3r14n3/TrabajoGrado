<?php
require 'conexion.php';
require 'vendor/autoload.php';

use MongoDB\BSON\UTCDateTime;

session_start();
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["aceptar_consentimiento"])) {
    if ($_POST["password"] !== $_POST["confirm_password"]) {
        $error = "Las contraseñas no coinciden.";
    } elseif (strlen($_POST["password"]) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres.";
    } else {
        $nombre = $_POST["nombre"];
        $email = $_POST["email"];
        $password = password_hash($_POST["password"], PASSWORD_BCRYPT);
        $tipo_usuario = $_POST["tipo_usuario"];

        $coleccion = $database->usuarios;
        $nuevoUsuario = [
            "nombre" => $nombre,
            "email" => $email,
            "password" => $password,
            "tipo_usuario" => $tipo_usuario,
            "fecha_registro" => new UTCDateTime()
        ];

        if ($tipo_usuario == "voluntario") {
            $nuevoUsuario["intereses"] = $_POST["intereses"] ?? [];
            $nuevoUsuario["habilidades"] = $_POST["habilidades"] ?? "";
            $nuevoUsuario["disponibilidad"] = $_POST["disponibilidad"] ?? "";
        } elseif ($tipo_usuario == "organizacion") {
            if (!empty($_POST["organizacion"]) && !empty($_POST["descripcion"])) {
                $nuevoUsuario["organizacion"] = $_POST["organizacion"];
                $nuevoUsuario["descripcion"] = $_POST["descripcion"];
                $nuevoUsuario["verificado"] = false;
                $nuevoUsuario["link_verificacion"] = "verificar.php?token=" . bin2hex(random_bytes(16));
            } else {
                $error = "Por favor completa todos los campos requeridos para organizaciones.";
            }
        }

        if (empty($error)) {
            try {
                $resultado = $coleccion->insertOne($nuevoUsuario);
                if ($resultado->getInsertedCount() === 1) {
                    // Notificar a todos los administradores
                    $admins = $database->usuarios->find(['tipo_usuario' => 'admin']);

                    $mensajeNotificacion = "🆕 Se ha creado una nueva cuenta de tipo '{$tipo_usuario}' con el nombre: {$nombre}.";

                    foreach ($admins as $admin) {
                        $database->notificaciones->insertOne([
                            'id_usuario' => $admin['_id'],
                            'tipo' => 'nuevo_registro',
                            'mensaje' => $mensajeNotificacion,
                            'fecha' => new UTCDateTime(),
                            'leido' => false
                        ]);
                    }
                    header("Location: login.php?registro_exitoso=1");
                    exit();
                } else {
                    $error = "Error al insertar el usuario en la base de datos.";
                }
            } catch (Exception $e) {
                $error = "Excepción: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <link rel="stylesheet" href="css/registros.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            padding: 30px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.6);
        }

        .modal-content {
            background-color: #fff;
            margin: auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
        }

        .modal h3 {
            margin-top: 0;
        }

        .modal-footer {
            text-align: right;
            margin-top: 20px;
        }

        #password-match {
            font-size: 14px;
            margin-top: 5px;
            display: block;
        }
    </style>
</head>

<body>
    <div class="registro-container">
        <div class="avatar"><i class="fas fa-user-plus"></i></div>
        <h2>Crear cuenta</h2>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="registro.php" id="formRegistro">
            <div class="input-container">
                <i class="fas fa-user"></i>
                <input type="text" name="nombre" placeholder="Nombre completo" required>
            </div>
            <div class="input-container">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Correo electrónico" required>
            </div>
            <div class="input-container">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Contraseña" required>
            </div>
            <div class="input-container">
                <i class="fas fa-lock"></i>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirmar contraseña" required>
                <span id="password-match"></span>
            </div>
            <div class="select-container">
                <i class="fas fa-users"></i>
                <select id="tipo_usuario" name="tipo_usuario" required>
                    <option value="" disabled selected>Selecciona tu tipo de usuario</option>
                    <option value="voluntario">Voluntario</option>
                    <option value="organizacion">Organización</option>
                </select>
            </div>

            <!-- VOLUNTARIO -->
            <div id="voluntario-fields">
                <div class="input-container">
                    <i class="fas fa-heart"></i>
                    <label class="checkbox-label">Intereses:</label>
                    <div class="checkbox-options">
                        <label><input type="checkbox" name="intereses[]" value="educacion"> Educación</label>
                        <label><input type="checkbox" name="intereses[]" value="medio_ambiente"> Medio Ambiente</label>
                        <label><input type="checkbox" name="intereses[]" value="animal"> Animal</label>
                        <label><input type="checkbox" name="intereses[]" value="salud"> Salud</label>
                        <label><input type="checkbox" name="intereses[]" value="social"> Trabajo Social</label>
                        <label><input type="checkbox" name="intereses[]" value="tecnologia"> Tecnología</label>
                    </div>
                </div>
                <div class="input-container">
                    <i class="fas fa-tools"></i>
                    <input type="text" name="habilidades" placeholder="Habilidades">
                </div>
                <div class="select-container">
                    <i class="fas fa-calendar-alt"></i>
                    <select name="disponibilidad">
                        <option value="" disabled selected>Selecciona tu disponibilidad</option>
                        <option value="mañana">Mañana</option>
                        <option value="tarde">Tarde</option>
                        <option value="noche">Noche</option>
                    </select>
                </div>
            </div>

            <!-- ORGANIZACIÓN -->
            <div id="organizacion-fields">
                <div class="input-container">
                    <i class="fas fa-building"></i>
                    <input type="text" name="organizacion" placeholder="Nombre de la organización">
                </div>
                <div class="input-container">
                    <i class="fas fa-align-left"></i>
                    <input type="text" name="descripcion" placeholder="Descripción de la organización">
                </div>
            </div>

            <input type="hidden" name="aceptar_consentimiento" value="1">
            <button type="button" class="registro-button" onclick="mostrarConsentimiento()">REGISTRARSE</button>
        </form>
        <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
        <p><a href="index.php" style="color: #007bff;">⟵ Volver al inicio</a></p>
    </div>

    <!-- Modal del consentimiento -->
    <div id="modalConsentimiento" class="modal">
        <div class="modal-content">
            <h3>📝 Consentimiento Informado para Participantes</h3>
            <p><strong>¡Bienvenido/a!</strong><br><br>
                Antes de continuar, te invitamos a leer cuidadosamente este consentimiento informado. Tu participación en esta plataforma implica el tratamiento de tus datos personales y tu vinculación con organizaciones sin ánimo de lucro.</p>

            <ol>
                <li><strong>Finalidad del uso de tus datos personales:</strong><br>
                    Al registrarte en esta plataforma, autorizas el uso de tu información personal para:<br>
                    - Gestionar tu perfil como voluntario/a.<br>
                    - Postularte a oportunidades ofrecidas por organizaciones.<br>
                    - Facilitar comunicación con organizaciones.<br>
                    - Generar estadísticas de participación.
                </li>
                <li><strong>Confidencialidad y protección de datos:</strong><br>
                    Cumplimos con la Ley 1581 de 2012. Tus datos serán confidenciales y seguros.
                </li>
                <li><strong>Tus derechos:</strong><br>
                    - Acceder, modificar o eliminar tu información.<br>
                    - Retirar tu consentimiento.<br>
                    - Consultar escribiéndonos a [correo de contacto].
                </li>
                <li><strong>Participación voluntaria:</strong><br>
                    Puedes retirarte en cualquier momento.
                </li>
                <li><strong>Responsabilidades del voluntario:</strong><br>
                    - Actuar con responsabilidad.<br>
                    - Proveer información veraz.<br>
                    - Cumplir con los lineamientos de las organizaciones.
                </li>
                <li><strong>Uso responsable de la plataforma:</strong><br>
                    Las organizaciones usarán tus datos únicamente con fines de voluntariado.
                </li>
            </ol>

            <div style="margin-top: 10px;">
                <label><input type="checkbox" id="aceptarCheck"> ✅ Declaro que he leído, comprendido y acepto los términos del consentimiento informado.</label>
                <p id="mensaje-error" style="color: red; display: none; margin-top: 5px;"></p>
            </div>
            <div class="modal-footer">
                <button onclick="cerrarModal()" class="registro-button" style="background-color: #aaa;">Cancelar</button>
                <button onclick="enviarFormulario()" class="registro-button">Aceptar y Crear Cuenta</button>
            </div>


            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const tipoUsuario = document.getElementById("tipo_usuario");
                    const voluntarioFields = document.getElementById("voluntario-fields");
                    const organizacionFields = document.getElementById("organizacion-fields");

                    function toggleFields() {
                        const tipo = tipoUsuario.value;
                        voluntarioFields.classList.toggle("active", tipo === "voluntario");
                        organizacionFields.classList.toggle("active", tipo === "organizacion");
                    }

                    tipoUsuario.addEventListener("change", toggleFields);
                    toggleFields();

                    const passwordInput = document.getElementById("password");
                    const confirmPasswordInput = document.getElementById("confirm_password");
                    const passwordMatchText = document.getElementById("password-match");

                    function verificarCoincidencia() {
                        const passwordVal = passwordInput.value;
                        const confirmVal = confirmPasswordInput.value;

                        if (passwordVal.length > 0 && passwordVal.length < 8) {
                            passwordMatchText.textContent = "❌ La contraseña debe tener al menos 8 caracteres.";
                            passwordMatchText.style.color = "red";
                        } else if (confirmVal && passwordVal !== confirmVal) {
                            passwordMatchText.textContent = "❌ Las contraseñas no coinciden.";
                            passwordMatchText.style.color = "red";
                        } else if (confirmVal && passwordVal === confirmVal) {
                            passwordMatchText.textContent = "✅ Las contraseñas coinciden.";
                            passwordMatchText.style.color = "green";
                        } else {
                            passwordMatchText.textContent = "";
                        }
                    }

                    passwordInput.addEventListener('input', verificarCoincidencia);
                    confirmPasswordInput.addEventListener('input', verificarCoincidencia);
                });

                function mostrarConsentimiento() {
                    const tipoUsuario = document.getElementById("tipo_usuario").value;
                    if (tipoUsuario === "") {
                        alert("Por favor selecciona tu tipo de usuario antes de continuar.");
                        return;
                    }
                    document.getElementById("modalConsentimiento").style.display = "block";
                }

                function cerrarModal() {
                    document.getElementById("modalConsentimiento").style.display = "none";
                }

                function enviarFormulario() {
                    const check = document.getElementById("aceptarCheck");
                    const mensajeError = document.getElementById("mensaje-error");

                    const password = document.getElementById("password").value;
                    const confirmPassword = document.getElementById("confirm_password").value;

                    if (password.length < 8) {
                        mensajeError.style.display = "block";
                        mensajeError.textContent = "La contraseña debe tener al menos 8 caracteres.";
                        return;
                    }

                    if (password !== confirmPassword) {
                        mensajeError.style.display = "block";
                        mensajeError.textContent = "❌ Las contraseñas no coinciden.";
                        return;
                    }

                    if (check.checked) {
                        mensajeError.style.display = "none";
                        mensajeError.textContent = "";
                        document.getElementById("formRegistro").submit();
                    } else {
                        mensajeError.style.display = "block";
                        mensajeError.textContent = "Debes aceptar los términos del consentimiento informado para continuar.";
                    }
                }
            </script>

</body>

</html>