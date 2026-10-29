<?php
// logout.php - Cierra la sesión y vuelve al login
session_start();
$_SESSION = [];
session_destroy();
header("Location: login.php");
exit;
?>
