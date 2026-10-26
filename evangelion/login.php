<?php
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($nombre_usuario) || empty($password)) {
        $error = 'Todos los campos son obligatorios.';
    } else {
        $stmt = $pdo->prepare("SELECT id, password_hash FROM usuarios WHERE nombre_usuario = ?");
        $stmt->execute([$nombre_usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nombre_usuario'] = $nombre_usuario;
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Nombre de usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — NERV System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="nerv-header">
        <div class="nerv-logo">NERV<span>EVANGELION SYSTEM</span></div>
        <nav class="nav-links">
            <a href="index.php">Inicio</a>
            <a href="registro.php" class="btn-primary">Registrarse</a>
        </nav>
    </header>

    <div class="container" style="max-width:500px;padding-top:80px;">
        <div class="nerv-panel pulse">
            <div class="panel-title">// ACCESO AL SISTEMA NERV</div>

            <?php if ($error): ?>
                <div class="alert alert-danger">⚠ <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="nombre_usuario">Nombre de Piloto</label>
                    <input type="text" id="nombre_usuario" name="nombre_usuario" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['nombre_usuario'] ?? ''); ?>" 
                           placeholder="Tu nombre de usuario" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Tu contraseña" required>
                </div>
                <div style="text-align:center;margin-top:30px;">
                    <button type="submit" class="btn btn-primary" style="width:100%;padding:15px;">
                        ► AUTENTICAR
                    </button>
                </div>
            </form>

            <p style="text-align:center;margin-top:20px;color:var(--nerv-text);font-size:0.85em;">
                ¿No tienes cuenta? <a href="registro.php" style="color:var(--nerv-orange);">Regístrate aquí</a>
            </p>
        </div>
    </div>
</body>
</html>
