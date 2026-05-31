<?php
require_once __DIR__ . '/../config.php';

$username = 'root';
$password = '';
$host = 'localhost';
$database = 'proyecto_db';


$conn = mysqli_connect($host, $username, $password, $database);
if ($conn) {
    mysqli_set_charset($conn, 'utf8');
} else {
    header('Content-Type: application/json', true, 500);
    echo json_encode(['error' => 'DB connection failed', 'detail' => mysqli_connect_error()]);
    exit;
}