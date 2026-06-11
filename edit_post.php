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
  <link rel="stylesheet" href="styles/app.css">
</head>
<body>
  <div class="max-w-270 mx-auto mt-4.5 mb-6 border border-[#b9d0b6] bg-[#fffffff7] shadow-[0_4px_18px_rgba(25,54,28,0.08)] overflow-hidden max-md:mx-2">
    <header class="admin-header">
      <div class="font-bold tracking-[0.2px]">PIME <span class="font-normal opacity-90">menorca</span></div>
      <div><a href="dashboard.php">Volver al panel</a></div>
    </header>

    <main class="px-4.5 pt-3.5 pb-4.5 bg-white">
      <h1 class="mt-0.5 mb-3.5 text-lg font-bold text-brand">Edición de Noticias</h1>

      <?php if ($error): ?>
        <div class="text-[#bb0000] mb-3"><?php echo e($error); ?></div>
      <?php endif; ?>

      <form method="post" action="" id="newsForm" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">

        <div class="flex border-b-2 border-[#d9e3d6] bg-white">
          <button type="button" class="tab-link active" data-tab="general">General</button>
          <button type="button" class="tab-link" data-tab="images">Imágenes</button>
        </div>

        <section class="tab-content active" id="general">
          <div class="editor-grid">
            <div class="min-w-0">
              <div class="mb-4">
                <label for="title" class="block font-semibold text-forest mb-1.5 mt-0">Título</label>
                <input type="text" id="title" name="title" value="<?php echo e($title); ?>" required class="w-full p-2 border border-[#d0d0d0] rounded-xs bg-white text-[#333] text-sm box-border mt-0">
              </div>

              <div class="mb-4">
                <label for="subtitle" class="block font-semibold text-forest mb-1.5 mt-0">Subtítulo</label>
                <input type="text" id="subtitle" name="subtitle" value="<?php echo e($subtitle); ?>" class="w-full p-2 border border-[#d0d0d0] rounded-xs bg-white text-[#333] text-sm box-border mt-0">
              </div>

              <div class="mb-4">
                <label for="content_editor" class="block font-semibold text-forest mb-1.5 mt-0">Cuerpo</label>
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

              <div class="mb-4">
                <label for="tags" class="block font-semibold text-forest mb-1.5 mt-0">Etiquetas <span class="text-[#888] font-normal text-xs">(Separadas por comas)</span></label>
                <input type="text" id="tags" name="tags" value="<?php echo e($tags); ?>" class="w-full p-2 border border-[#d0d0d0] rounded-xs bg-white text-[#333] text-sm box-border mt-0">
              </div>

              <div class="mb-4">
                <label for="slug" class="block font-semibold text-forest mb-1.5 mt-0">Alias de URL <span class="text-[#888] font-normal text-xs">(Dejar en blanco para generar automáticamente)</span></label>
                <input type="text" id="slug" name="slug" value="<?php echo e($slug); ?>" class="w-full p-2 border border-[#d0d0d0] rounded-xs bg-white text-[#333] text-sm box-border mt-0">
              </div>
            </div>

            <aside class="min-w-0">
              <div class="border border-[#d6d6d6] bg-[#fafafa] p-3.5 rounded-xs">
                <div class="font-bold text-[#555] mb-3">Añadir imágenes</div>
                <button type="button" class="w-full border border-[#cfcfcf] bg-linear-to-b from-white to-[#f2f2f2] px-2.5 py-2 mb-3 cursor-pointer mt-0" id="pickImages">Seleccionar imágenes</button>
                <input type="file" id="imageInput" name="images[]" accept="image/*" multiple hidden>
                <div class="border-2 border-dashed border-[#d5d5d5] rounded-[3px] py-4.5 px-3 text-center text-[#777] text-[13px] bg-white cursor-pointer" id="dropzone">Arrastra aquí las imágenes</div>
                <div class="grid grid-cols-[repeat(auto-fill,minmax(88px,1fr))] gap-2.5 mt-3" id="imageGallery">
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
          <div class="border border-[#d6d6d6] bg-[#fafafa] p-3.5 rounded-xs mt-1">
            <div class="font-bold text-[#555] mb-3">Añadir imágenes</div>
            <button type="button" class="w-full border border-[#cfcfcf] bg-linear-to-b from-white to-[#f2f2f2] px-2.5 py-2 mb-3 cursor-pointer mt-0" id="pickImages2">Seleccionar imágenes</button>
            <input type="file" id="imageInput2" name="images[]" accept="image/*" multiple hidden>
            <div class="border-2 border-dashed border-[#d5d5d5] rounded-[3px] py-4.5 px-3 text-center text-[#777] text-[13px] bg-white cursor-pointer" id="dropzone2">Arrastra aquí las imágenes</div>
            <div class="grid grid-cols-[repeat(auto-fill,minmax(88px,1fr))] gap-2.5 mt-3" id="imageGallery2"></div>
          </div>
        </section>

        <div class="flex gap-2 mt-5">
          <button class="btn btn-primary" type="submit">Actualizar</button>
          <a class="btn" href="dashboard.php">Cancelar</a>
        </div>
      </form>
    </main>
  </div>

  <script src="./js/edit_post.js"></script>
</body>
</html>
