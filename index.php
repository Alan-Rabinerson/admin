<?php
session_start();
// Entrada simple: si está autenticado, ir a dashboard; si no, ir a login
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
header('Location: login.php');
exit;
