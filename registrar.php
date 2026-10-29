<?php
// registrar.php - Guarda un INGRESO o EGRESO del empleado logueado
require "config.php";
requerir_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: panel.php");
    exit;
}

$tipo = $_POST["tipo"] ?? "";
if (!in_array($tipo, ["INGRESO", "EGRESO"], true)) {
    flash("error", "Tipo de registro inválido.");
    header("Location: panel.php");
    exit;
}

$empleado_id = $_SESSION["empleado_id"];
$ultimo = ultimo_movimiento($conexion, $empleado_id);

// No se puede fichar dos veces seguidas lo mismo, ni egresar sin haber ingresado
if ($ultimo && $ultimo["tipo"] === $tipo) {
    flash("error", "Ya registraste un $tipo. Primero tenés que registrar el " .
        ($tipo === "INGRESO" ? "EGRESO" : "INGRESO") . ".");
} elseif (!$ultimo && $tipo === "EGRESO") {
    flash("error", "No podés registrar un EGRESO sin un INGRESO previo.");
} else {
    $stmt = $conexion->prepare("INSERT INTO movimientos (empleado_id, tipo) VALUES (?, ?)");
    $stmt->bind_param("is", $empleado_id, $tipo);
    $stmt->execute();
    flash("ok", "$tipo registrado correctamente a las " . date("H:i") . ".");
}

header("Location: panel.php");
exit;
?>
