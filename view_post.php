<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}

$stmt = mysqli_prepare($conn, 'SELECT p.title, p.slug, p.content, p.created_at, u.username FROM posts p LEFT JOIN users u ON u.id = p.author_id WHERE p.id = ? LIMIT 1');
if (!$stmt) {
    header('Location: dashboard.php');
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$post = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$post) {
    header('Location: dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Ver entrada</title>
  <link rel="stylesheet" href="styles/inline-extracted.css">
</head>
<body>
  <div class="admin-wrap">
    <header class="admin-header">
      <div class="brand">PIME <span>menorca</span></div>
      <div class="user-info"><a href="dashboard.php">Volver al panel</a></div>
    </header>
    <main class="admin-panel">
      <h1><?php echo e($post['title']); ?></h1>
      <div class="list-meta">
        <strong>Slug:</strong> <?php echo e($post['slug']); ?> | 
        <strong>Fecha:</strong> <?php echo e(date('d/m/Y H:i', strtotime($post['created_at']))); ?> |
        <strong>Autor:</strong> <?php echo e($post['username'] ?: 'sistema'); ?>
      </div>
      <div class="panel-body" style="padding:16px; line-height:1.6;">
        <?php echo nl2br(e($post['content'])); ?>
      </div>
      <p style="margin-top:14px;">
        <a class="btn" href="dashboard.php">Volver</a>
        <a class="btn btn-primary" href="edit_post.php?id=<?php echo $id; ?>">Editar</a>
      </p>
    </main>
  </div>
</body>
</html>