<?php
// config.php
date_default_timezone_set("America/Argentina/Buenos_Aires");

$host = getenv("DB_HOST") ?: "localhost";
$user = getenv("DB_USER") ?: "root";
$pass = getenv("DB_PASS") ?: "";
$db   = "control_empleados";
$port = (int)(getenv("DB_PORT") ?: 3306);

$conexion = new mysqli($host, $user, $pass, $db, $port);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");
$conexion->query("SET time_zone = '-03:00'");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------- Funciones comunes ----------------

// Escapa texto para mostrar en HTML
function e($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, "UTF-8");
}

// Guarda un mensaje para mostrar en la próxima página
function flash($tipo, $mensaje) {
    $_SESSION["flash"] = ["tipo" => $tipo, "mensaje" => $mensaje];
}

// Muestra (y borra) el mensaje guardado
function mostrar_flash() {
    if (!empty($_SESSION["flash"])) {
        $f = $_SESSION["flash"];
        unset($_SESSION["flash"]);
        echo '<div class="alerta alerta-' . e($f["tipo"]) . '">' . e($f["mensaje"]) . '</div>';
    }
}

// Exige sesión iniciada (y opcionalmente un rol)
function requerir_login($rol = null) {
    if (empty($_SESSION["empleado_id"])) {
        header("Location: login.php");
        exit;
    }
    if ($rol !== null && $_SESSION["rol"] !== $rol) {
        header("Location: panel.php");
        exit;
    }
}

// Devuelve el último movimiento de un empleado o null
function ultimo_movimiento($conexion, $empleado_id) {
    $stmt = $conexion->prepare(
        "SELECT tipo, fecha_hora FROM movimientos
         WHERE empleado_id = ? ORDER BY fecha_hora DESC, id DESC LIMIT 1"
    );
    $stmt->bind_param("i", $empleado_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?>
