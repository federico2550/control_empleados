<?php
require "config.php";
requerir_login("admin");

$seccion = $_GET["seccion"] ?? "movimientos";
if (!in_array($seccion, ["registrar", "movimientos", "empleados"], true)) {
    $seccion = "movimientos";
}

// ---------------- Acciones (POST) ----------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";

    if ($accion === "crear") {
        $dni      = trim($_POST["dni"] ?? "");
        $nombre   = trim($_POST["nombre"] ?? "");
        $apellido = trim($_POST["apellido"] ?? "");
        $clave    = $_POST["clave"] ?? "";
        $rol      = ($_POST["rol"] ?? "") === "admin" ? "admin" : "empleado";

        if (!preg_match('/^\d{7,10}$/', $dni)) {
            flash("error", "El DNI debe tener entre 7 y 10 números.");
        } elseif ($nombre === "" || $apellido === "") {
            flash("error", "Nombre y apellido son obligatorios.");
        } elseif (strlen($clave) < 4) {
            flash("error", "La clave debe tener al menos 4 caracteres.");
        } else {
            $stmt = $conexion->prepare("SELECT id FROM empleados WHERE dni = ?");
            $stmt->bind_param("s", $dni);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                flash("error", "Ya existe un empleado con el DNI $dni.");
            } else {
                $hash = password_hash($clave, PASSWORD_DEFAULT);
                $stmt = $conexion->prepare(
                    "INSERT INTO empleados (dni, nombre, apellido, clave, rol) VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("sssss", $dni, $nombre, $apellido, $hash, $rol);
                $stmt->execute();
                flash("ok", "Empleado $nombre $apellido registrado correctamente.");
                header("Location: admin.php?seccion=empleados");
                exit;
            }
        }
        header("Location: admin.php?seccion=registrar");
        exit;
    }

    if ($accion === "toggle") {
        $id = (int)($_POST["id"] ?? 0);
        if ($id === (int)$_SESSION["empleado_id"]) {
            flash("error", "No podés desactivar tu propio usuario.");
        } else {
            $stmt = $conexion->prepare("UPDATE empleados SET activo = 1 - activo WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            flash("ok", "Estado del empleado actualizado.");
        }
        header("Location: admin.php?seccion=empleados");
        exit;
    }

    if ($accion === "clave") {
        $id    = (int)($_POST["id"] ?? 0);
        $clave = $_POST["clave"] ?? "";
        if (strlen($clave) < 4) {
            flash("error", "La clave debe tener al menos 4 caracteres.");
        } else {
            $hash = password_hash($clave, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("UPDATE empleados SET clave = ? WHERE id = ?");
            $stmt->bind_param("si", $hash, $id);
            $stmt->execute();
            flash("ok", "Clave actualizada.");
        }
        header("Location: admin.php?seccion=empleados");
        exit;
    }
}

// ---------------- Datos para cada sección ----------------
if ($seccion === "movimientos") {
    $f_dni   = trim($_GET["dni"] ?? "");
    $f_desde = $_GET["desde"] ?? date("Y-m-01");
    $f_hasta = $_GET["hasta"] ?? date("Y-m-d");

    $sql = "SELECT m.tipo, m.fecha_hora, e.id AS empleado_id, e.dni, e.nombre, e.apellido
            FROM movimientos m JOIN empleados e ON e.id = m.empleado_id
            WHERE m.fecha_hora >= ? AND m.fecha_hora < DATE_ADD(?, INTERVAL 1 DAY)";
    $tipos  = "ss";
    $params = [$f_desde, $f_hasta];
    if ($f_dni !== "") {
        $sql .= " AND e.dni LIKE ?";
        $tipos .= "s";
        $params[] = "%$f_dni%";
    }
    $sql .= " ORDER BY m.fecha_hora DESC, m.id DESC";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($tipos, ...$params);
    $stmt->execute();
    $movimientos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Horas trabajadas: se empareja cada INGRESO con el EGRESO siguiente del mismo empleado
    $resumen = [];
    $abiertos = [];
    foreach (array_reverse($movimientos) as $m) {
        $id = $m["empleado_id"];
        if (!isset($resumen[$id])) {
            $resumen[$id] = ["nombre" => $m["apellido"] . ", " . $m["nombre"], "dni" => $m["dni"], "segundos" => 0, "jornadas" => 0];
        }
        if ($m["tipo"] === "INGRESO") {
            $abiertos[$id] = strtotime($m["fecha_hora"]);
        } elseif (isset($abiertos[$id])) {
            $resumen[$id]["segundos"] += strtotime($m["fecha_hora"]) - $abiertos[$id];
            $resumen[$id]["jornadas"]++;
            unset($abiertos[$id]);
        }
    }
    uasort($resumen, fn($a, $b) => strcmp($a["nombre"], $b["nombre"]));
}

if ($seccion === "empleados") {
    $empleados = $conexion->query(
        "SELECT e.id, e.dni, e.nombre, e.apellido, e.rol, e.activo,
                (SELECT tipo FROM movimientos WHERE empleado_id = e.id ORDER BY fecha_hora DESC, id DESC LIMIT 1) AS ultimo
         FROM empleados e ORDER BY e.apellido, e.nombre"
    )->fetch_all(MYSQLI_ASSOC);
}

function formato_horas($segundos) {
    return sprintf("%d:%02d", intdiv($segundos, 3600), intdiv($segundos % 3600, 60));
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

    <title>Administrador - FICHAR</title>
</head>
<body class="admin">
    <header>
        <div class="logo">Fich<span>AR</span></div>
        <nav class="items">
            <ul>
                <li><a href="admin.php?seccion=registrar" class="<?= $seccion === "registrar" ? "activo" : "" ?>">Registrar</a></li>
                <li><a href="admin.php?seccion=movimientos" class="<?= $seccion === "movimientos" ? "activo" : "" ?>">Movimientos</a></li>
                <li><a href="admin.php?seccion=empleados" class="<?= $seccion === "empleados" ? "activo" : "" ?>">Empleados</a></li>
                <li><a href="logout.php">Salir</a></li>
            </ul>
        </nav>
    </header>

    <section class="contenido">
        <?php mostrar_flash(); ?>

        <?php if ($seccion === "registrar"): ?>
            <div class="card">
                <div class="card-texts">
                    <h2 class="card-titulo">Registrar empleado</h2>
                    <h3 class="card-info">Cargá los datos del nuevo empleado. Va a fichar con su DNI y esta clave.</h3>
                </div>
                <form action="admin.php?seccion=registrar" method="post">
                    <input type="hidden" name="accion" value="crear">
                    <div class="frm-lbls">
                        <label for="dni">DNI</label>
                        <input type="text" id="dni" name="dni" inputmode="numeric" pattern="\d{7,10}" placeholder="Solo números" required>

                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" name="nombre" maxlength="60" required>

                        <label for="apellido">Apellido</label>
                        <input type="text" id="apellido" name="apellido" maxlength="60" required>

                        <label for="clave">Clave</label>
                        <input type="password" id="clave" name="clave" minlength="4" required>

                        <label for="rol">Rol</label>
                        <select id="rol" name="rol">
                            <option value="empleado">Empleado</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div class="frm-btn"><button type="submit" class="btn_registrar">Registrar</button></div>
                </form>
            </div>

        <?php elseif ($seccion === "movimientos"): ?>
            <div class="panel-admin">
                <h2 class="card-titulo">Movimientos</h2>

                <form class="filtros" method="get" action="admin.php">
                    <input type="hidden" name="seccion" value="movimientos">
                    <div>
                        <label for="f_dni">DNI</label>
                        <input type="text" id="f_dni" name="dni" value="<?= e($f_dni) ?>" placeholder="Todos">
                    </div>
                    <div>
                        <label for="f_desde">Desde</label>
                        <input type="date" id="f_desde" name="desde" value="<?= e($f_desde) ?>">
                    </div>
                    <div>
                        <label for="f_hasta">Hasta</label>
                        <input type="date" id="f_hasta" name="hasta" value="<?= e($f_hasta) ?>">
                    </div>
                    <button type="submit" class="btn_registrar">Filtrar</button>
                </form>

                <?php if ($resumen): ?>
                    <h3 class="subtitulo">Horas trabajadas en el período</h3>
                    <div class="tabla-wrap">
                        <table>
                            <thead><tr><th>Empleado</th><th>DNI</th><th>Jornadas</th><th>Horas</th></tr></thead>
                            <tbody>
                            <?php foreach ($resumen as $r): ?>
                                <tr>
                                    <td><?= e($r["nombre"]) ?></td>
                                    <td><?= e($r["dni"]) ?></td>
                                    <td><?= $r["jornadas"] ?></td>
                                    <td><?= formato_horas($r["segundos"]) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <h3 class="subtitulo">Detalle (<?= count($movimientos) ?>)</h3>
                <div class="tabla-wrap">
                    <table>
                        <thead><tr><th>Fecha</th><th>Hora</th><th>Empleado</th><th>DNI</th><th>Tipo</th></tr></thead>
                        <tbody>
                        <?php if (!$movimientos): ?>
                            <tr><td colspan="5" class="vacio">No hay movimientos para este filtro.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($movimientos as $m): ?>
                            <tr>
                                <td><?= date("d/m/Y", strtotime($m["fecha_hora"])) ?></td>
                                <td><?= date("H:i:s", strtotime($m["fecha_hora"])) ?></td>
                                <td><?= e($m["apellido"] . ", " . $m["nombre"]) ?></td>
                                <td><?= e($m["dni"]) ?></td>
                                <td><span class="badge badge-<?= strtolower($m["tipo"]) ?>"><?= e($m["tipo"]) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php else: ?>
            <div class="panel-admin">
                <h2 class="card-titulo">Empleados</h2>
                <div class="tabla-wrap">
                    <table>
                        <thead><tr><th>Empleado</th><th>DNI</th><th>Rol</th><th>Estado</th><th>Situación</th><th>Acciones</th></tr></thead>
                        <tbody>
                        <?php foreach ($empleados as $emp): ?>
                            <tr class="<?= $emp["activo"] ? "" : "inactivo" ?>">
                                <td><?= e($emp["apellido"] . ", " . $emp["nombre"]) ?></td>
                                <td><?= e($emp["dni"]) ?></td>
                                <td><?= $emp["rol"] === "admin" ? "Admin" : "Empleado" ?></td>
                                <td><?= $emp["activo"] ? "Activo" : "Inactivo" ?></td>
                                <td>
                                    <?php if ($emp["ultimo"] === "INGRESO"): ?>
                                        <span class="badge badge-ingreso">Trabajando</span>
                                    <?php else: ?>
                                        <span class="badge">Fuera</span>
                                    <?php endif; ?>
                                </td>
                                <td class="acciones">
                                    <form method="post" action="admin.php?seccion=empleados">
                                        <input type="hidden" name="accion" value="toggle">
                                        <input type="hidden" name="id" value="<?= $emp["id"] ?>">
                                        <button type="submit" class="btn-chico"><?= $emp["activo"] ? "Desactivar" : "Activar" ?></button>
                                    </form>
                                    <form method="post" action="admin.php?seccion=empleados" class="frm-clave">
                                        <input type="hidden" name="accion" value="clave">
                                        <input type="hidden" name="id" value="<?= $emp["id"] ?>">
                                        <input type="password" name="clave" placeholder="Nueva clave" minlength="4" required>
                                        <button type="submit" class="btn-chico">Cambiar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </section>
</body>
</html>
