<?php
require_once __DIR__ . '/db.php';

function e($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function isLogged() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLogged()) {
        header('Location: login.php');
        exit;
    }
}

function loginByCredentials($username, $password) {
    global $conn;
    $stmt = mysqli_prepare($conn, 'SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$user) {
        return false;
    }

    $legacyHash = hash('sha256', $password);
    if (password_verify($password, $user['password']) || hash_equals($legacyHash, $user['password'])) {
        return $user;
    }

    return false;
}

function slugify($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : 'entrada';
}

function getPostImages($postId) {
    global $conn;
    $stmt = mysqli_prepare($conn, 'SELECT id, filename FROM post_images WHERE post_id = ? ORDER BY id ASC');
    if (!$stmt) return [];
    mysqli_stmt_bind_param($stmt, 'i', $postId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $images = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $images[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $images;
}

function savePostImages($postId, $files) {
    global $conn;
    $uploadDir = __DIR__ . '/../uploads/posts/';
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $count = is_array($files['name']) ? count($files['name']) : 0;
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        if (!in_array($files['type'][$i], $allowed, true)) continue;
        $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        $filename = uniqid('img_', true) . '.' . $ext;
        if (!move_uploaded_file($files['tmp_name'][$i], $uploadDir . $filename)) continue;
        $stmt = mysqli_prepare($conn, 'INSERT INTO post_images (post_id, filename) VALUES (?, ?)');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'is', $postId, $filename);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

function deletePostImage($imageId, $postId) {
    global $conn;
    $stmt = mysqli_prepare($conn, 'SELECT filename FROM post_images WHERE id = ? AND post_id = ? LIMIT 1');
    if (!$stmt) return;
    mysqli_stmt_bind_param($stmt, 'ii', $imageId, $postId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if (!$row) return;
    $file = __DIR__ . '/../uploads/posts/' . basename($row['filename']);
    if (file_exists($file)) unlink($file);
    $stmt = mysqli_prepare($conn, 'DELETE FROM post_images WHERE id = ? AND post_id = ?');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ii', $imageId, $postId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function getPostById($id) {
    global $conn;
    $stmt = mysqli_prepare($conn, 'SELECT id, title, subtitle, slug, content, author_id FROM posts WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $post = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $post ?: null;
}
