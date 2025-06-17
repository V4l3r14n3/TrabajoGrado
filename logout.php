session_start();
session_destroy();
$_SESSION = []; // ← limpiar por si acaso
header("Location: index.php");
exit();
