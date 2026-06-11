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
  <link rel="stylesheet" href="styles/app.css">
</head>
<body>
  <div class="max-w-245 mx-auto mt-4.5 mb-6 border border-[#b9d0b6] bg-[#fffffff7] shadow-[0_4px_18px_rgba(25,54,28,0.08)] overflow-hidden max-md:mx-2">
    <header class="admin-header">
      <div class="font-bold tracking-[0.2px]">PIME <span class="font-normal opacity-90">menorca</span></div>
      <div>Bienvenido, <?php echo e($_SESSION['username'] ?? ''); ?> — <a href="logout.php">Salir</a></div>
    </header>

    <main class="px-4.5 pt-3.5 pb-4.5 bg-white">
      <h1 class="mt-0.5 mb-3.5 text-lg font-bold text-brand">Noticias</h1>

      <?php
      // Leer filtros
      $q          = trim($_GET['q'] ?? '');
      $fromDate   = trim($_GET['from_date'] ?? '');
      $toDate     = trim($_GET['to_date'] ?? '');
      $userFilter = isset($_GET['user_filter']) && $_GET['user_filter'] !== '' ? (int)$_GET['user_filter'] : null;
      $pubFilter  = isset($_GET['published_filter']) && $_GET['published_filter'] !== '' ? (int)$_GET['published_filter'] : null;

      // Cargar usuarios para el select
      $usersResult = mysqli_query($conn, "SELECT id, username FROM users ORDER BY username ASC");
      $usersList = [];
      while ($row = mysqli_fetch_assoc($usersResult)) {
          $usersList[] = $row;
      }
      ?>

      <form class="flex justify-between items-center mb-3 gap-2.5 w-full max-md:flex-col max-md:items-start" method="get" action="dashboard.php" id="searchForm">
        <div class="flex items-center gap-2 flex-wrap max-md:w-full">
          <a class="btn btn-primary" href="new_post.php">Nuevo</a>
          <input class="w-auto min-w-[120px] h-7 px-2 py-1 border border-[#cfd8cf] bg-white text-[#243024] text-sm max-md:min-w-0 max-md:w-full" type="date" id="from_date" name="from_date" value="<?php echo e($fromDate); ?>">
          <input class="w-auto min-w-[120px] h-7 px-2 py-1 border border-[#cfd8cf] bg-white text-[#243024] text-sm max-md:min-w-0 max-md:w-full" type="date" id="to_date" name="to_date" value="<?php echo e($toDate); ?>">
          <select class="w-auto min-w-[120px] h-7 px-2 py-1 border border-[#cfd8cf] bg-white text-[#243024] text-sm max-md:min-w-0 max-md:w-full" name="user_filter">
            <option value="">-- Filtro x Usuario --</option>
            <?php foreach ($usersList as $u): ?>
              <option value="<?php echo (int)$u['id']; ?>"<?php echo ($userFilter === (int)$u['id']) ? ' selected' : ''; ?>><?php echo e($u['username']); ?></option>
            <?php endforeach; ?>
          </select>
          <select class="w-auto min-w-[120px] h-7 px-2 py-1 border border-[#cfd8cf] bg-white text-[#243024] text-sm max-md:min-w-0 max-md:w-full" name="published_filter">
            <option value="">-- Filtro x Publicada --</option>
            <option value="1"<?php echo ($pubFilter === 1) ? ' selected' : ''; ?>>Publicada</option>
            <option value="0"<?php echo ($pubFilter === 0) ? ' selected' : ''; ?>>No publicada</option>
          </select>
        </div>
        <div class="flex items-center gap-2 flex-wrap max-md:w-full">
          <label class="inline-flex items-center gap-2 text-[#324132] text-sm">Buscar: <input class="w-auto min-w-[120px] h-7 px-2 py-1 border border-[#cfd8cf] bg-white text-[#243024] text-sm max-md:min-w-0 max-md:w-full" id="q" name="q" value="<?php echo e($q); ?>"></label>
          <button class="btn" type="submit">Filtrar</button>
        </div>
      </form>

      <?php
      $perPage = 17;
      $page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
      $offset  = ($page - 1) * $perPage;

      // Construir WHERE dinámico
      $where  = ['1=1'];
      $types  = '';
      $params = [];

      if ($q !== '') {
          $like = '%' . $q . '%';
          $where[] = 'p.title LIKE ?';
          $types  .= 's';
          $params[] = &$like;
      }
      if ($fromDate !== '') {
          $where[] = 'DATE(p.created_at) >= ?';
          $types  .= 's';
          $params[] = &$fromDate;
      }
      if ($toDate !== '') {
          $where[] = 'DATE(p.created_at) <= ?';
          $types  .= 's';
          $params[] = &$toDate;
      }
      if ($userFilter !== null) {
          $where[] = 'p.author_id = ?';
          $types  .= 'i';
          $params[] = &$userFilter;
      }
      if ($pubFilter !== null) {
          $where[] = 'p.published = ?';
          $types  .= 'i';
          $params[] = &$pubFilter;
      }

      $whereClause = implode(' AND ', $where);

      // Conteo total
      $stmtc = mysqli_prepare($conn, "SELECT COUNT(*) FROM posts p WHERE $whereClause");
      if ($types !== '') {
          mysqli_stmt_bind_param($stmtc, $types, ...$params);
      }
      mysqli_stmt_execute($stmtc);
      mysqli_stmt_bind_result($stmtc, $totalCount);
      mysqli_stmt_fetch($stmtc);
      mysqli_stmt_close($stmtc);

      // Consulta de datos
      $offsetRef  = $offset;
      $perPageRef = $perPage;
      $dataTypes  = $types . 'ii';
      $dataParams = $params;
      $dataParams[] = &$perPageRef;
      $dataParams[] = &$offsetRef;

      $stmt = mysqli_prepare($conn, "SELECT p.id, p.title, p.created_at, u.username, p.published FROM posts p LEFT JOIN users u ON u.id = p.author_id WHERE $whereClause ORDER BY p.created_at DESC LIMIT ? OFFSET ?");
      mysqli_stmt_bind_param($stmt, $dataTypes, ...$dataParams);
      mysqli_stmt_execute($stmt);
      mysqli_stmt_bind_result($stmt, $id, $title, $created_at, $username, $published);

      // Parámetros de filtro para paginación
      $filterQuery = http_build_query(array_filter([
          'q'                => $q,
          'from_date'        => $fromDate,
          'to_date'          => $toDate,
          'user_filter'      => $userFilter !== null ? $userFilter : '',
          'published_filter' => $pubFilter  !== null ? $pubFilter  : '',
      ], fn($v) => $v !== '' && $v !== null));
      $filterQuery = $filterQuery ? '&' . $filterQuery : '';
      ?>

      <div class="border border-[#d9e3d6]">
        <table class="w-full border-collapse">
          <thead>
            <tr>
              <th class="bg-[#f8faf7] px-3 py-2.5 text-left border-b border-[#dbe4d8] text-[#26422d] text-sm font-semibold">Fecha</th>
              <th class="bg-[#f8faf7] px-3 py-2.5 text-left border-b border-[#dbe4d8] text-[#26422d] text-sm font-semibold">Titular</th>
              <th class="bg-[#f8faf7] px-3 py-2.5 text-left border-b border-[#dbe4d8] text-[#26422d] text-sm font-semibold">Usuario</th>
              <th class="bg-[#f8faf7] px-3 py-2.5 text-left border-b border-[#dbe4d8] text-[#26422d] text-sm font-semibold">Publicada</th>
              <th class="bg-[#f8faf7] px-3 py-2.5 text-left border-b border-[#dbe4d8] text-[#26422d] text-sm font-semibold">Acción</th>
            </tr>
          </thead>
          <tbody>
          <?php while (mysqli_stmt_fetch($stmt)): ?>
            <tr class="hover:bg-[#f8fcf6]">
              <td class="px-3 py-2.5 border-t border-[#edf2eb] align-middle text-sm"><?php echo e(date('d/m/Y', strtotime($created_at))); ?></td>
              <td class="px-3 py-2.5 border-t border-[#edf2eb] align-middle text-[#333] text-sm"><?php echo e($title); ?></td>
              <td class="px-3 py-2.5 border-t border-[#edf2eb] align-middle text-sm"><?php echo e($username ?: 'sistema'); ?></td>
              <td class="px-3 py-2.5 border-t border-[#edf2eb] align-middle text-sm"><?php echo $published ? 'Sí' : 'No'; ?></td>
              <td class="px-3 py-2.5 border-t border-[#edf2eb] align-middle w-[136px] text-center">
                <img class="icon" src="./assets/edit.svg" onclick="window.location.href='edit_post.php?id=<?php echo $id; ?>'" alt="Editar">
                <img class="icon" src="./assets/view.svg" onclick="window.location.href='view_post.php?id=<?php echo $id; ?>'" alt="Ver">
                <img class="icon<?php echo $published ? ' icon--published' : ' icon--unpublished'; ?>" src="./assets/publish.svg" onclick="window.location.href='toggle_publish.php?id=<?php echo $id; ?>'" title="<?php echo $published ? 'Despublicar' : 'Publicar'; ?>" alt="Publicar">
                <img class="icon" src="./assets/delete.svg" onclick="confirmDelete('delete_post.php?id=<?php echo $id; ?>')" alt="Borrar">
              </td>
            </tr>
          <?php endwhile; mysqli_stmt_close($stmt); ?>
          </tbody>
        </table>

        <div class="px-3 py-2.5 text-[#555] text-[13px]">
          Mostrando registros del <?php echo $offset + 1; ?> al <?php echo min($offset + $perPage, $totalCount); ?> de un total de <?php echo $totalCount; ?> registros
        </div>

        <div class="px-3 py-2.5 text-right">
          <?php
          $totalPages = max(1, (int)ceil($totalCount / $perPage));
          $start = max(1, $page - 2);
          $end = min($totalPages, $page + 2);
          if ($page > 1) echo '<a href="?page=' . ($page - 1) . $filterQuery . '" class="page">Anterior</a>';
          for ($p = $start; $p <= $end; $p++) {
              if ($p === $page) echo '<span class="page current">' . $p . '</span>';
              else echo '<a class="page" href="?page=' . $p . $filterQuery . '">' . $p . '</a>';
          }
          if ($page < $totalPages) echo '<a href="?page=' . ($page + 1) . $filterQuery . '" class="page">Siguiente</a>';
          ?>
        </div>

      </div>

    </main>
  </div>

  <!-- Modal confirmación borrar -->
  <div id="deleteModal" style="display:none;position:fixed;inset:0;z-index:1000;align-items:center;justify-content:center;">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,0.45);" onclick="closeDeleteModal()"></div>
    <div style="position:relative;background:#fff;border:1px solid #b9d0b6;box-shadow:0 8px 32px rgba(25,54,28,0.18);padding:28px 32px;min-width:300px;max-width:380px;border-radius:2px;text-align:center;">
      <p style="margin:0 0 20px;font-size:15px;color:#26422d;font-weight:600;">¿Borrar este registro?</p>
      <p style="margin:0 0 24px;font-size:13px;color:#666;">Esta acción no se puede deshacer.</p>
      <div style="display:flex;gap:10px;justify-content:center;">
        <button class="btn" onclick="closeDeleteModal()">Cancelar</button>
        <button class="btn" id="deleteConfirmBtn" style="background:linear-gradient(180deg,#e53e3e 0%,#c53030 100%);color:#fff;border-color:#c53030;" onclick="executeDelete()">Borrar</button>
      </div>
    </div>
  </div>

  <script>
    var _deleteUrl = '';
    function confirmDelete(url) {
      _deleteUrl = url;
      var m = document.getElementById('deleteModal');
      m.style.display = 'flex';
    }
    function closeDeleteModal() {
      document.getElementById('deleteModal').style.display = 'none';
      _deleteUrl = '';
    }
    function executeDelete() {
      if (_deleteUrl) window.location.href = _deleteUrl;
    }
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeDeleteModal();
    });
  </script>
</body>
</html>
