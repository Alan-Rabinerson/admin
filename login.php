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
  <link rel="stylesheet" href="styles/app.css">
</head>
<body>
  <div class="min-h-screen grid place-items-center p-6">
    <div class="w-full max-w-[420px] bg-white border border-[#d8e4d6] shadow-[0_4px_18px_rgba(25,54,28,0.08)] rounded p-7">
      <h1 class="mt-0 mb-4.5 text-[22px] text-brand">Panel Admin</h1>
      <?php if ($error): ?><div class="text-[#bb0000]"><?php echo e($error); ?></div><?php endif; ?>
      <form method="post" action="">
        <label class="block mt-3">Usuario
          <input type="text" name="username" value="<?php echo e($_POST['username'] ?? ''); ?>" class="w-full p-2 mt-1.5 border border-[#d0d0d0] rounded-xs bg-white text-[#333] text-sm box-border">
        </label>
        <label class="block mt-3">Contraseña
          <input type="password" name="password" class="w-full p-2 mt-1.5 border border-[#d0d0d0] rounded-xs bg-white text-[#333] text-sm box-border">
        </label>
        <button type="submit" class="btn btn-primary mt-3">Entrar</button>
      </form>
    </div>
  </div>
</body>
</html>
