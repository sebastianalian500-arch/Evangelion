<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NERV System — Evangelion</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php if (isLoggedIn()): ?>
        <header class="nerv-header">
            <div class="nerv-logo">NERV<span>EVANGELION SYSTEM</span></div>
            <nav class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="batalla.php">Batalla</a>
                <a href="ranking.php">Ranking</a>
                <a href="misiones.php">Misiones</a>
                <a href="chat.php">Chat</a>
                <a href="perfil.php">Perfil</a>
                <a href="logout.php" class="logout">Salir</a>
            </nav>
        </header>
    <?php else: ?>
        <header class="nerv-header">
            <div class="nerv-logo">NERV<span>EVANGELION SYSTEM</span></div>
            <nav class="nav-links">
                <a href="login.php">Iniciar Sesión</a>
                <a href="registro.php" class="btn-primary">Registrarse</a>
            </nav>
        </header>
    <?php endif; ?>

    <div class="hero">
        <h1 class="flicker">EVANGELION</h1>
        <p class="subtitle">// SISTEMA DE COMBATE NERV v3.33 //</p>
        <p style="max-width:600px;margin:0 auto 30px;color:var(--nerv-text);line-height:1.8;">
            Únete a la organización NERV. Elige tu personaje del anime, 
            pilota tu EVA y desafía a otros pilotos en batallas épicas. 
            Completa misiones, sube de ranking y demuestra que eres el mejor piloto.
        </p>
        <?php if (!isLoggedIn()): ?>
            <a href="registro.php" class="btn btn-primary" style="font-size:1.1em;padding:15px 50px;">
                ► INICIAR RECLUTAMIENTO
            </a>
            <br><br>
            <a href="login.php" class="btn">Ya tengo cuenta</a>
        <?php else: ?>
            <a href="dashboard.php" class="btn btn-primary" style="font-size:1.1em;padding:15px 50px;">
                ► ENTRAR AL SISTEMA
            </a>
        <?php endif; ?>
    </div>

    <div class="container">
        <div class="nerv-panel">
            <div class="panel-title">// UNIDADES EVANGELION DISPONIBLES</div>
            <div class="selection-grid">
                <?php
                $stmt = $pdo->query("SELECT * FROM evas");
                while ($eva = $stmt->fetch(PDO::FETCH_ASSOC)):
                ?>
                <div class="selection-card">
                    <img src="<?php echo $eva['imagen']; ?>" alt="<?php echo $eva['nombre']; ?>" onerror="this.style.display='none'">
                    <div class="card-name"><?php echo $eva['nombre']; ?></div>
                    <div class="card-stats">
                        <span>ATQ: <?php echo $eva['ataque']; ?></span>
                        <span>DEF: <?php echo $eva['defensa']; ?></span>
                        <span>VEL: <?php echo $eva['velocidad']; ?></span>
                    </div>
                    <div class="stat-bar"><div class="fill ataque" style="width:<?php echo $eva['ataque']; ?>%"></div></div>
                    <div class="stat-bar"><div class="fill defensa" style="width:<?php echo $eva['defensa']; ?>%"></div></div>
                    <div class="stat-bar"><div class="fill velocidad" style="width:<?php echo $eva['velocidad']; ?>%"></div></div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="nerv-panel">
            <div class="panel-title">// ÁNGELES DETECTADOS</div>
            <div class="selection-grid">
                <?php
                $stmt = $pdo->query("SELECT * FROM angeles");
                while ($angel = $stmt->fetch(PDO::FETCH_ASSOC)):
                ?>
                <div class="selection-card">
                    <img src="<?php echo $angel['imagen']; ?>" alt="<?php echo $angel['nombre']; ?>" onerror="this.style.display='none'">
                    <div class="card-name"><?php echo $angel['nombre']; ?></div>
                    <div class="card-stats">
                        <span>ATQ: <?php echo $angel['ataque']; ?></span>
                        <span>DEF: <?php echo $angel['defensa']; ?></span>
                        <span>VEL: <?php echo $angel['velocidad']; ?></span>
                    </div>
                    <div class="stat-bar"><div class="fill ataque" style="width:<?php echo $angel['ataque']; ?>%"></div></div>
                    <div class="stat-bar"><div class="fill defensa" style="width:<?php echo $angel['defensa']; ?>%"></div></div>
                    <div class="stat-bar"><div class="fill velocidad" style="width:<?php echo $angel['velocidad']; ?>%"></div></div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <footer style="text-align:center;padding:30px;color:var(--nerv-text);font-size:0.8em;border-top:1px solid var(--nerv-border);">
        NERV SYSTEM v3.33 // PROYECTO EVANGELION // TODOS LOS DERECHOS RESERVADOS
    </footer>
</body>
</html>
