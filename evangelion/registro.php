<?php
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $personaje_id = intval($_POST['personaje_id'] ?? 0);
    $eva_id = intval($_POST['eva_id'] ?? 0);
    $angel_id = intval($_POST['angel_id'] ?? 0);

    if (empty($nombre_usuario) || empty($password) || empty($password_confirm) || !$personaje_id || !$eva_id || !$angel_id) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (strlen($nombre_usuario) < 3 || strlen($nombre_usuario) > 50) {
        $error = 'El nombre de usuario debe tener entre 3 y 50 caracteres.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $nombre_usuario)) {
        $error = 'El nombre solo puede contener letras, números y guiones bajos.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE nombre_usuario = ?");
        $stmt->execute([$nombre_usuario]);
        if ($stmt->fetch()) {
            $error = 'Ese nombre de usuario ya está en uso.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_usuario, password_hash, personaje_id, eva_id, angel_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre_usuario, $hash, $personaje_id, $eva_id, $angel_id]);
            $userId = $pdo->lastInsertId();

            // Complete registration mission
            $stmt = $pdo->prepare("SELECT id, exp_reward, puntos_reward FROM misiones WHERE tipo = 'registro'");
            $stmt->execute();
            while ($mision = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $check = $pdo->prepare("SELECT id FROM misiones_usuario WHERE usuario_id = ? AND mision_id = ?");
                $check->execute([$userId, $mision['id']]);
                if (!$check->fetch()) {
                    $ins = $pdo->prepare("INSERT INTO misiones_usuario (usuario_id, mision_id, completada) VALUES (?, ?, TRUE)");
                    $ins->execute([$userId, $mision['id']]);
                    addExp($pdo, $userId, $mision['exp_reward'], $mision['puntos_reward']);
                }
            }

            $_SESSION['user_id'] = $userId;
            $_SESSION['nombre_usuario'] = $nombre_usuario;
            header('Location: dashboard.php');
            exit;
        }
    }
}

$personajes = $pdo->query("SELECT * FROM personajes")->fetchAll(PDO::FETCH_ASSOC);
$evas = $pdo->query("SELECT * FROM evas")->fetchAll(PDO::FETCH_ASSOC);
$angeles = $pdo->query("SELECT * FROM angeles")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro — NERV System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="nerv-header">
        <div class="nerv-logo">NERV<span>EVANGELION SYSTEM</span></div>
        <nav class="nav-links">
            <a href="index.php">Inicio</a>
            <a href="login.php">Iniciar Sesión</a>
        </nav>
    </header>

    <div class="container" style="max-width:900px;">
        <div class="nerv-panel">
            <div class="panel-title">// SISTEMA DE RECLUTAMIENTO NERV</div>

            <?php if ($error): ?>
                <div class="alert alert-danger">⚠ <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="registro.php">
                <div class="grid-2">
                    <div class="form-group">
                        <label for="nombre_usuario">Nombre de Piloto</label>
                        <input type="text" id="nombre_usuario" name="nombre_usuario" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['nombre_usuario'] ?? ''); ?>" 
                               placeholder="Ej: shinji_piloto" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Mínimo 6 caracteres" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirmar Contraseña</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" 
                           placeholder="Repite tu contraseña" required>
                </div>

                <!-- Personaje Selection -->
                <div class="form-group">
                    <label>► Selecciona tu Personaje del Anime</label>
                    <div class="selection-grid">
                        <?php foreach ($personajes as $p): ?>
                        <label class="selection-card" onclick="selectCard(this, 'personaje')">
                            <input type="radio" name="personaje_id" value="<?php echo $p['id']; ?>" required style="display:none;">
                            <img src="<?php echo $p['imagen']; ?>" alt="<?php echo $p['nombre']; ?>" onerror="this.style.background='#1a1a3a';this.alt='<?= addslashes($p['nombre']) ?>';">
                            <div class="card-name"><?php echo $p['nombre']; ?></div>
                            <div class="card-desc"><?php echo htmlspecialchars(substr($p['descripcion'], 0, 60)); ?>...</div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- EVA Selection -->
                <div class="form-group">
                    <label>► Selecciona tu Unidad Evangelion</label>
                    <div class="selection-grid">
                        <?php foreach ($evas as $e): ?>
                        <label class="selection-card" onclick="selectCard(this, 'eva')">
                            <input type="radio" name="eva_id" value="<?php echo $e['id']; ?>" required style="display:none;">
                            <img src="<?php echo $e['imagen']; ?>" alt="<?php echo $e['nombre']; ?>" onerror="this.style.background='#1a1a3a';">
                            <div class="card-name"><?php echo $e['nombre']; ?></div>
                            <div class="card-stats">
                                <span>ATQ: <?php echo $e['ataque']; ?></span>
                                <span>DEF: <?php echo $e['defensa']; ?></span>
                                <span>VEL: <?php echo $e['velocidad']; ?></span>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Angel Selection -->
                <div class="form-group">
                    <label>► Selecciona tu Ángel</label>
                    <div class="selection-grid">
                        <?php foreach ($angeles as $a): ?>
                        <label class="selection-card" onclick="selectCard(this, 'angel')">
                            <input type="radio" name="angel_id" value="<?php echo $a['id']; ?>" required style="display:none;">
                            <img src="<?php echo $a['imagen']; ?>" alt="<?php echo $a['nombre']; ?>" onerror="this.style.background='#1a1a3a';">
                            <div class="card-name"><?php echo $a['nombre']; ?></div>
                            <div class="card-stats">
                                <span>ATQ: <?php echo $a['ataque']; ?></span>
                                <span>DEF: <?php echo $a['defensa']; ?></span>
                                <span>VEL: <?php echo $a['velocidad']; ?></span>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="text-align:center;margin-top:30px;">
                    <button type="submit" class="btn btn-primary" style="font-size:1em;padding:15px 50px;">
                        ► COMPLETAR RECLUTAMIENTO
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function selectCard(el, type) {
        const cards = el.parentElement.querySelectorAll('.selection-card');
        cards.forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
        el.querySelector('input[type="radio"]').checked = true;
    }
    </script>
</body>
</html>
