<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../includes/functions.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if ($slug === '') {
    http_response_code(400);
    echo json_encode(['error' => 'slug requerido']);
    exit;
}

$stmt = mysqli_prepare($conn,
    'SELECT p.id, p.title, p.subtitle, p.slug, p.content, p.created_at, u.username
     FROM posts p
     LEFT JOIN users u ON u.id = p.author_id
     WHERE p.slug = ? AND p.published = 1 LIMIT 1'
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Query error']);
    exit;
}

mysqli_stmt_bind_param($stmt, 's', $slug);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$post = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$post) {
    http_response_code(404);
    echo json_encode(['error' => 'No encontrada']);
    exit;
}

$IMAGE_BASE = 'http://localhost/admin/uploads/posts/';
$images = getPostImages((int)$post['id']);
$imagenes = array_map(fn($img) => $IMAGE_BASE . $img['filename'], $images);

$ts = strtotime($post['created_at']);
$mesesLargo = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$m = (int)date('n', $ts);

echo json_encode([
    'id'       => (int)$post['id'],
    'titulo'   => $post['title'],
    'extracto' => $post['subtitle'] ?? '',
    'contenido'=> $post['content'] ?? '',
    'autor'    => $post['username'] ? 'Por ' . $post['username'] : 'Por Redacción',
    'fecha'    => date('j', $ts) . ' de ' . $mesesLargo[$m] . ' de ' . date('Y', $ts),
    'slug'     => $post['slug'],
    'imagenes' => $imagenes,
], JSON_UNESCAPED_UNICODE);
