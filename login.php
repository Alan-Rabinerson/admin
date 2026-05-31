<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $error = 'Usuario y contraseña son obligatorios.';
    } else {
        $user = loginByCredentials($username, $password);
        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Credenciales inválidas.';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login - Admin</title>
  <link rel="stylesheet" href="styles/inline-extracted.css">
</head>
<body>
  <div class="auth-shell">
    <div class="auth-card">
      <h1>Panel Admin</h1>
      <?php if ($error): ?><div class="text-error"><?php echo e($error); ?></div><?php endif; ?>
      <form method="post" action="">
        <label>Usuario
          <input type="text" name="username" value="<?php echo e($_POST['username'] ?? ''); ?>">
        </label>
        <label>Contraseña
          <input type="password" name="password">
        </label>
        <button type="submit" class="btn btn-primary">Entrar</button>
      </form>
    </div>
  </div>
</body>
</html>
