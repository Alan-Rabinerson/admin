<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../includes/functions.php';

$limit = isset($_GET['limit']) ? max(1, min(20, (int)$_GET['limit'])) : 5;

$sql = 'SELECT p.id, p.title, p.subtitle, p.slug, p.created_at, u.username
        FROM posts p
        LEFT JOIN users u ON u.id = p.author_id
        ORDER BY p.created_at DESC
        LIMIT ?';

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode(['error' => 'Query error']);
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $limit);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$IMAGE_BASE = 'http://localhost/admin/uploads/posts/';

$noticias = [];
while ($row = mysqli_fetch_assoc($result)) {
    $images = getPostImages((int)$row['id']);
    $imagen = count($images) > 0 ? $IMAGE_BASE . $images[0]['filename'] : null;

    $ts = strtotime($row['created_at']);
    $meses = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $mesesLargo = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $m = (int)date('n', $ts);

    $noticias[] = [
        'id'         => (int)$row['id'],
        'titulo'     => $row['title'],
        'extracto'   => $row['subtitle'] ?? '',
        'autor'      => $row['username'] ? 'Por ' . $row['username'] : 'Por Redacción',
        'fecha'      => date('j', $ts) . ' de ' . $mesesLargo[$m] . ' de ' . date('Y', $ts),
        'fechaCorta' => date('j', $ts) . ' ' . $meses[$m] . ' ' . date('Y', $ts),
        'imagen'     => $imagen,
        'slug'       => $row['slug'],
    ];
}

mysqli_stmt_close($stmt);
echo json_encode($noticias, JSON_UNESCAPED_UNICODE);
