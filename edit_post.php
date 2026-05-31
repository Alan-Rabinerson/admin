<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}

$post = getPostById($id);
if (!$post) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$title = $post['title'];
$subtitle = $post['subtitle'] ?? '';
$content = $post['content'];
$tags = '';
$slug = $post['slug'];
$images = getPostImages($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $slug = trim($_POST['slug'] ?? '');

    if ($title === '') {
        $error = 'El titular es obligatorio.';
    } else {
        $slug = $slug !== '' ? slugify($slug) : slugify($title);

        $stmt = mysqli_prepare($conn, 'UPDATE posts SET title = ?, subtitle = ?, slug = ?, content = ? WHERE id = ?');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ssssi', $title, $subtitle, $slug, $content, $id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                foreach (array_map('intval', $_POST['delete_images'] ?? []) as $imgId) {
                    if ($imgId > 0) deletePostImage($imgId, $id);
                }
                if (!empty($_FILES['images']['name'][0])) {
                    savePostImages($id, $_FILES['images']);
                }
                header('Location: dashboard.php');
                exit;
            }
            mysqli_stmt_close($stmt);
            $error = 'No se pudo actualizar la noticia.';
        } else {
            $error = 'No se pudo preparar la actualización.';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Editar noticia</title>
  <link rel="stylesheet" href="styles/inline-extracted.css">
</head>
<body>
  <div class="admin-wrap editor-shell">
    <header class="admin-header">
      <div class="brand">PIME <span>menorca</span></div>
      <div class="user-info"><a href="dashboard.php">Volver al panel</a></div>
    </header>

    <main class="admin-panel">
      <h1>Edición de Noticias</h1>

      <?php if ($error): ?>
        <div class="text-error"><?php echo e($error); ?></div>
      <?php endif; ?>

      <form method="post" action="" id="newsForm" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">

        <div class="tabs-header">
          <button type="button" class="tab-link active" data-tab="general">General</button>
          <button type="button" class="tab-link" data-tab="images">Imágenes</button>
        </div>

        <section class="tab-content active" id="general">
          <div class="editor-grid">
            <div class="editor-main">
              <div class="form-group">
                <label for="title">Título</label>
                <input type="text" id="title" name="title" value="<?php echo e($title); ?>" required>
              </div>

              <div class="form-group">
                <label for="subtitle">Subtítulo</label>
                <input type="text" id="subtitle" name="subtitle" value="<?php echo e($subtitle); ?>">
              </div>

              <div class="form-group">
                <label for="content_editor">Cuerpo</label>
                <div class="editor-toolbar" aria-label="Herramientas del editor">
                  <button type="button" data-cmd="bold"><strong>B</strong></button>
                  <button type="button" data-cmd="italic"><em>I</em></button>
                  <button type="button" data-cmd="underline"><u>U</u></button>
                  <button type="button" data-cmd="insertUnorderedList">• Lista</button>
                  <button type="button" data-cmd="createLink">Enlace</button>
                </div>
                <div class="editor-body" id="content_editor" contenteditable="true"><?php echo $content; ?></div>
                <input type="hidden" id="content" name="content" value="<?php echo e($content); ?>">
              </div>

              <div class="form-group">
                <label for="tags">Etiquetas <span class="field-note">(Separadas por comas)</span></label>
                <input type="text" id="tags" name="tags" value="<?php echo e($tags); ?>">
              </div>

              <div class="form-group">
                <label for="slug">Alias de URL <span class="field-note">(Dejar en blanco para generar automáticamente)</span></label>
                <input type="text" id="slug" name="slug" value="<?php echo e($slug); ?>">
              </div>
            </div>

            <aside class="editor-aside">
              <div class="image-panel">
                <div class="image-panel-title">Añadir imágenes</div>
                <button type="button" class="image-upload-btn" id="pickImages">Seleccionar imágenes</button>
                <input type="file" id="imageInput" name="images[]" accept="image/*" multiple hidden>
                <div class="image-dropzone" id="dropzone">Arrastra aquí las imágenes</div>
                <div class="image-gallery" id="imageGallery">
                  <?php foreach ($images as $img): ?>
                    <div class="image-item" data-image-id="<?php echo (int)$img['id']; ?>">
                      <img src="uploads/posts/<?php echo e($img['filename']); ?>" alt="Imagen">
                      <button class="image-item-delete" type="button">Borrar</button>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </aside>
          </div>
        </section>

        <section class="tab-content" id="images">
          <div class="image-panel image-panel-large">
            <div class="image-panel-title">Añadir imágenes</div>
            <button type="button" class="image-upload-btn" id="pickImages2">Seleccionar imágenes</button>
            <input type="file" id="imageInput2" name="images[]" accept="image/*" multiple hidden>
            <div class="image-dropzone" id="dropzone2">Arrastra aquí las imágenes</div>
            <div class="image-gallery" id="imageGallery2"></div>
          </div>
        </section>

        <div class="form-actions">
          <button class="btn btn-primary" type="submit">Actualizar</button>
          <a class="btn" href="dashboard.php">Cancelar</a>
        </div>
      </form>
    </main>
  </div>

  <script src="./js/edit_post.js">
  </script>
</body>
</html>