<?php
require "config.php";
requerir_login();

$ultimo = ultimo_movimiento($conexion, $_SESSION["empleado_id"]);
// Si el último fue INGRESO, lo siguiente es EGRESO (y viceversa)
$proximo = ($ultimo && $ultimo["tipo"] === "INGRESO") ? "EGRESO" : "INGRESO";
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

    <p class="saludo">Hola, <strong><?= e($_SESSION["nombre"]) ?></strong></p>

    <?php mostrar_flash(); ?>

    <div class="ultimo-registro">
        <?php if ($ultimo): ?>
            <p>Tu último registro fue: <strong><?= e($ultimo["tipo"]) ?></strong>
               a las <strong><?= date("H:i", strtotime($ultimo["fecha_hora"])) ?></strong>
               del <strong><?= date("d/m/Y", strtotime($ultimo["fecha_hora"])) ?></strong></p>
        <?php else: ?>
            <p>Todavía no tenés registros.</p>
        <?php endif; ?>
    </div>

    <form action="registrar.php" method="post" class="frm-btn-doble">
        <button type="submit" name="tipo" value="INGRESO" class="btn_ingreso" <?= $proximo !== "INGRESO" ? "disabled" : "" ?>>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                <polyline points="10 17 15 12 10 7"/>
                <line x1="15" y1="12" x2="3" y2="12"/>
            </svg>
            Registrar Ingreso
        </button>

        <button type="submit" name="tipo" value="EGRESO" class="btn_egreso" <?= $proximo !== "EGRESO" ? "disabled" : "" ?>>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Registrar Egreso
        </button>
    </form>

    <a href="logout.php" class="link-salir">Cerrar sesión</a>
</div>
    </main>
    <script src="js/reloj.js"></script>
</body>
</html>
