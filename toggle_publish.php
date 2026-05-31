<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}

$stmt = mysqli_prepare($conn, 'SELECT published FROM posts WHERE id = ? LIMIT 1');
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $current);
    $found = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($found) {
        $newVal = $current ? 0 : 1;
        $upd = mysqli_prepare($conn, 'UPDATE posts SET published = ? WHERE id = ?');
        if ($upd) {
            mysqli_stmt_bind_param($upd, 'ii', $newVal, $id);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
        }
    }
}

$back = 'dashboard.php';
$qs = $_SERVER['HTTP_REFERER'] ?? '';
if ($qs && strpos($qs, 'dashboard.php') !== false) {
    $parsed = parse_url($qs);
    $back = 'dashboard.php' . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
}
header('Location: ' . $back);
exit;
