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
  <link rel="stylesheet" href="styles/app.css">
</head>
<body>
  <div class="max-w-245 mx-auto mt-4.5 mb-6 border border-[#b9d0b6] bg-[#fffffff7] shadow-[0_4px_18px_rgba(25,54,28,0.08)] overflow-hidden max-md:mx-2">
    <header class="admin-header">
      <div class="font-bold tracking-[0.2px]">PIME <span class="font-normal opacity-90">menorca</span></div>
      <div><a href="dashboard.php">Volver al panel</a></div>
    </header>
    <main class="px-4.5 pt-3.5 pb-4.5 bg-white">
      <h1 class="mt-0.5 mb-3.5 text-lg font-bold text-brand"><?php echo e($post['title']); ?></h1>
      <div class="px-3 py-2.5 text-[#555] text-[13px]">
        <strong>Slug:</strong> <?php echo e($post['slug']); ?> |
        <strong>Fecha:</strong> <?php echo e(date('d/m/Y H:i', strtotime($post['created_at']))); ?> |
        <strong>Autor:</strong> <?php echo e($post['username'] ?: 'sistema'); ?>
      </div>
      <div class="border border-[#d9e3d6] p-4 leading-[1.6]">
        <?php echo nl2br(e($post['content'])); ?>
      </div>
      <p class="mt-3.5">
        <a class="btn" href="dashboard.php">Volver</a>
        <a class="btn btn-primary" href="edit_post.php?id=<?php echo $id; ?>">Editar</a>
      </p>
    </main>
  </div>
</body>
</html>
