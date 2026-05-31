<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
requireLogin();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard - Admin</title>
  <link rel="stylesheet" href="styles/inline-extracted.css">
</head>
<body>
  <div class="admin-wrap">
    <header class="admin-header">
      <div class="brand">PIME <span>menorca</span></div>
      <div class="user-info">Bienvenido, <?php echo e($_SESSION['username'] ?? ''); ?> — <a href="logout.php">Salir</a></div>
    </header>

    <main class="admin-panel">
      <h1>Noticias</h1>

      <form class="panel-controls" method="get" action="dashboard.php" id="searchForm">
        <div class="left-controls">
          <a class="btn btn-primary" href="new_post.php">Nuevo</a>
          <input class="toolbar-input" type="date" id="from_date" name="from_date" value="<?php echo e($_GET['from_date'] ?? ''); ?>">
          <input class="toolbar-input" type="date" id="to_date" name="to_date" value="<?php echo e($_GET['to_date'] ?? ''); ?>">
          <select class="toolbar-select" name="user_filter">
            <option>-- Filtro x Usuario --</option>
          </select>
          <select class="toolbar-select" name="published_filter">
            <option>-- Filtro x Publicada --</option>
          </select>
        </div>
        <div class="right-controls">
          <label class="search-label">Buscar: <input class="search-input" id="q" name="q" value="<?php echo e($_GET['q'] ?? ''); ?>"></label>
          <button class="btn" type="submit">Filtrar</button>
        </div>
      </form>

      <?php
      // Conexión y consulta básica con paginación
      $perPage = 17;
      $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
      $offset = ($page - 1) * $perPage;
      $q = isset($_GET['q']) ? trim($_GET['q']) : '';

      $like = "%" . $q . "%";

      // Conteo total
      $countSql = "SELECT COUNT(*) AS cnt FROM posts WHERE title LIKE ?";
      $stmtc = mysqli_prepare($conn, $countSql);
      mysqli_stmt_bind_param($stmtc, 's', $like);
      mysqli_stmt_execute($stmtc);
      mysqli_stmt_bind_result($stmtc, $totalCount);
      mysqli_stmt_fetch($stmtc);
      mysqli_stmt_close($stmtc);

      $sql = "SELECT p.id, p.title, p.created_at, u.username, p.published FROM posts p LEFT JOIN users u ON u.id = p.author_id WHERE p.title LIKE ? ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
      $stmt = mysqli_prepare($conn, $sql);
      mysqli_stmt_bind_param($stmt, 'sii', $like, $perPage, $offset);
      mysqli_stmt_execute($stmt);
      mysqli_stmt_bind_result($stmt, $id, $title, $created_at, $username, $published);
      ?>

      <div class="panel-body">
        <table class="list-table">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Titular</th>
              <th>Usuario</th>
              <th>Publicada</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
          <?php while (mysqli_stmt_fetch($stmt)): ?>
            <tr>
              <td><?php echo e(date('d/m/Y', strtotime($created_at))); ?></td>
              <td class="title-col"><?php echo e($title); ?></td>
              <td><?php echo e($username ?: 'sistema'); ?></td>
              <td><?php echo $published ? 'Sí' : 'No'; ?></td>
              <td class="actions">
                <img class="icon" src="./assets/edit.svg" onclick="window.location.href='edit_post.php?id=<?php echo $id; ?>'" alt="Editar">
                <img class="icon" src="./assets/view.svg" onclick="window.location.href='view_post.php?id=<?php echo $id; ?>'" alt="Ver">
                <img class="icon<?php echo $published ? ' icon--published' : ' icon--unpublished'; ?>" src="./assets/publish.svg" onclick="window.location.href='toggle_publish.php?id=<?php echo $id; ?>'" title="<?php echo $published ? 'Despublicar' : 'Publicar'; ?>" alt="Publicar">
                <img class="icon" src="./assets/delete.svg" onclick="if(confirm('¿Borrar este registro?')) { window.location.href='delete_post.php?id=<?php echo $id; ?>'; }" alt="Borrar">
              </td>
            </tr>
          <?php endwhile; mysqli_stmt_close($stmt); ?>
          </tbody>
        </table>

        <div class="list-meta">
          Mostrando registros del <?php echo $offset + 1; ?> al <?php echo min($offset + $perPage, $totalCount); ?> de un total de <?php echo $totalCount; ?> registros
        </div>

        <div class="pagination">
          <?php
          $totalPages = max(1, (int)ceil($totalCount / $perPage));
          $start = max(1, $page - 2);
          $end = min($totalPages, $page + 2);
          if ($page > 1) echo '<a href="?page=' . ($page - 1) . '" class="page">Anterior</a>';
          for ($p = $start; $p <= $end; $p++) {
              if ($p === $page) echo '<span class="page current">' . $p . '</span>';
              else echo '<a class="page" href="?page=' . $p . '">' . $p . '</a>';
          }
          if ($page < $totalPages) echo '<a href="?page=' . ($page + 1) . '" class="page">Siguiente</a>';
          ?>
        </div>

      </div>

    </main>
  </div>
</body>
</html>
