<?php
require "config.php";

// Si ya está logueado, va directo a su pantalla
if (!empty($_SESSION["empleado_id"])) {
    header("Location: " . ($_SESSION["rol"] === "admin" ? "admin.php" : "panel.php"));
    exit;
}

$error = "";
$dni = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $dni   = trim($_POST["dni"] ?? "");
    $clave = $_POST["clave"] ?? "";

    if (!preg_match('/^\d{7,10}$/', $dni) || $clave === "") {
        $error = "Ingresá un DNI válido (solo números) y tu clave.";
    } else {
        $stmt = $conexion->prepare(
            "SELECT id, nombre, apellido, clave, rol, activo FROM empleados WHERE dni = ?"
        );
        $stmt->bind_param("s", $dni);
        $stmt->execute();
        $empleado = $stmt->get_result()->fetch_assoc();

        if (!$empleado || !password_verify($clave, $empleado["clave"])) {
            $error = "DNI o clave incorrectos.";
        } elseif (!$empleado["activo"]) {
            $error = "Tu usuario está desactivado. Consultá con el administrador.";
        } else {
            session_regenerate_id(true);
            $_SESSION["empleado_id"] = $empleado["id"];
            $_SESSION["nombre"]      = $empleado["nombre"] . " " . $empleado["apellido"];
            $_SESSION["rol"]         = $empleado["rol"];
            header("Location: " . ($empleado["rol"] === "admin" ? "admin.php" : "panel.php"));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- CSS -->
    <link rel="stylesheet" href="css/styles.css">
    <!-- TIPOGRAFIA -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:ital,wght@0,100..700;1,100..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <title>FICHAR - Sistema de Fichadas</title>
</head>
<body>
    <main>
        <div class="card">
            <h1>Fich<span>AR</span></h1>
            <div id="reloj" class="reloj"></div>
            <div class="card-texts">
                <h2 class="card-titulo">Fichar jornada</h2>
                <h3 class="card-info">Ingresá tu DNI y tu clave para registrar tu Ingreso - Egreso.</h3>
            </div>

            <?php mostrar_flash(); ?>
            <?php if ($error): ?>
                <div class="alerta alerta-error"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="frm-fichaje">
                <form action="login.php" method="post">
                    <div class="frm-content">
                        <div class="frm-lbls">
                            <label for="dni">DNI</label>
                            <input type="text" id="dni" name="dni" placeholder="Ingrese su DNI" inputmode="numeric" pattern="\d{7,10}" value="<?= e($dni) ?>" required autofocus>

                            <label for="clave">Clave</label>
                            <input type="password" id="clave" name="clave" placeholder="Ingrese su clave" required>
                        </div>

                        <div class="frm-btn"><button type="submit" class="btn_registrar">Ingresar</button></div>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <script src="js/reloj.js"></script>
</body>
</html>
