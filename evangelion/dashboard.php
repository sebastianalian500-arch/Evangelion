<?php
require_once 'config.php';
requireLogin();

$user = getUser($pdo, $_SESSION['user_id']);
$expNecesario = $user['nivel'] * 100;
$expPercent = min(100, round(($user['exp'] / $expNecesario) * 100));

// Check missions
$stmt = $pdo->prepare("
    SELECT m.*, mu.completada 
    FROM misiones m
    LEFT JOIN misiones_usuario mu ON m.id = mu.mision_id AND mu.usuario_id = ?
    WHERE mu.id IS NULL OR mu.completada = FALSE
    ORDER BY m.id
");
$stmt->execute([$user['id']]);
$misionesPendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total active missions
$totalMisiones = $pdo->query("SELECT COUNT(*) FROM misiones")->fetchColumn();
$completadas = $pdo->prepare("SELECT COUNT(*) FROM misiones_usuario WHERE usuario_id = ? AND completada = TRUE");
$completadas->execute([$user['id']]);
$totalCompletadas = $completadas->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — NERV System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="nerv-header">
        <div class="nerv-logo">NERV<span>EVANGELION SYSTEM</span></div>
        <nav class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="batalla.php">Batalla</a>
            <a href="ranking.php">Ranking</a>
            <a href="misiones.php">Misiones</a>
            <a href="chat.php">Chat</a>
            <a href="perfil.php">Perfil</a>
            <a href="logout.php" class="logout">Salir</a>
        </nav>
    </header>

    <div class="container">
        <!-- Welcome -->
        <div class="nerv-panel">
            <div class="profile-header">
                <img src="<?php echo $user['personaje_imagen']; ?>" alt="<?php echo $user['personaje_nombre']; ?>" class="profile-avatar" onerror="this.style.background='#1a1a3a'">
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user['nombre_usuario']); ?></h2>
                    <span class="character-badge">🎭 <?php echo $user['personaje_nombre']; ?></span>
                    <span class="character-badge">🤖 <?php echo $user['eva_nombre']; ?></span>
                    <span class="character-badge">👹 <?php echo $user['angel_nombre']; ?></span>
                    <br><br>
                    <div style="color:var(--nerv-cyan);font-size:0.9em;">
                        NIVEL <?php echo $user['nivel']; ?> — <?php echo $user['exp']; ?>/<?php echo $expNecesario; ?> EXP
                    </div>
                    <div class="stat-bar" style="width:300px;height:12px;">
                        <div class="fill velocidad" style="width:<?php echo $expPercent; ?>%;background:var(--nerv-orange);"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-value"><?php echo $user['puntos']; ?></div>
                <div class="stat-label">Puntos</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="color:var(--nerv-green);"><?php echo $user['victorias']; ?></div>
                <div class="stat-label">Victorias</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="color:var(--nerv-red);"><?php echo $user['derrotas']; ?></div>
                <div class="stat-label">Derrotas</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="color:var(--nerv-cyan);">
                    <?php echo $user['victorias'] + $user['derrotas'] > 0 ? round(($user['victorias'] / ($user['victorias'] + $user['derrotas'])) * 100) : 0; ?>%
                </div>
                <div class="stat-label">Win Rate</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="color:var(--nerv-yellow);"><?php echo $totalCompletadas; ?>/<?php echo $totalMisiones; ?></div>
                <div class="stat-label">Misiones</div>
            </div>
        </div>

        <div class="grid-2">
            <!-- EVA Stats -->
            <div class="nerv-panel">
                <div class="panel-title">// UNIDAD <?php echo strtoupper($user['eva_nombre']); ?></div>
                <img src="<?php echo $user['eva_imagen']; ?>" alt="<?php echo $user['eva_nombre']; ?>" 
                     style="width:100%;max-height:200px;object-fit:contain;border:1px solid var(--nerv-border);" onerror="this.style.background='#1a1a3a'">
                <div style="margin-top:15px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
                        <span>ATTAQUE</span><span style="color:var(--nerv-red);"><?php echo $user['eva_ataque']; ?></span>
                    </div>
                    <div class="stat-bar"><div class="fill ataque" style="width:<?php echo $user['eva_ataque']; ?>%"></div></div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:5px;margin-top:10px;">
                        <span>DEFENSA</span><span style="color:var(--nerv-cyan);"><?php echo $user['eva_defensa']; ?></span>
                    </div>
                    <div class="stat-bar"><div class="fill defensa" style="width:<?php echo $user['eva_defensa']; ?>%"></div></div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:5px;margin-top:10px;">
                        <span>VELOCIDAD</span><span style="color:var(--nerv-green);"><?php echo $user['eva_velocidad']; ?></span>
                    </div>
                    <div class="stat-bar"><div class="fill velocidad" style="width:<?php echo $user['eva_velocidad']; ?>%"></div></div>
                </div>
            </div>

            <!-- Angel Stats -->
            <div class="nerv-panel">
                <div class="panel-title">// ÁNGEL <?php echo strtoupper($user['angel_nombre']); ?></div>
                <img src="<?php echo $user['angel_imagen']; ?>" alt="<?php echo $user['angel_nombre']; ?>" 
                     style="width:100%;max-height:200px;object-fit:contain;border:1px solid var(--nerv-border);" onerror="this.style.background='#1a1a3a'">
                <div style="margin-top:15px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
                        <span>ATTAQUE</span><span style="color:var(--nerv-red);"><?php echo $user['angel_ataque']; ?></span>
                    </div>
                    <div class="stat-bar"><div class="fill ataque" style="width:<?php echo $user['angel_ataque']; ?>%"></div></div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:5px;margin-top:10px;">
                        <span>DEFENSA</span><span style="color:var(--nerv-cyan);"><?php echo $user['angel_defensa']; ?></span>
                    </div>
                    <div class="stat-bar"><div class="fill defensa" style="width:<?php echo $user['angel_defensa']; ?>%"></div></div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:5px;margin-top:10px;">
                        <span>VELOCIDAD</span><span style="color:var(--nerv-green);"><?php echo $user['angel_velocidad']; ?></span>
                    </div>
                    <div class="stat-bar"><div class="fill velocidad" style="width:<?php echo $user['angel_velocidad']; ?>%"></div></div>
                </div>
            </div>
        </div>

        <!-- Pending Missions -->
        <div class="nerv-panel">
            <div class="panel-title">// MISIONES PENDIENTES</div>
            <?php if (empty($misionesPendientes)): ?>
                <p style="color:var(--nerv-green);">✓ ¡Todas las misiones completadas! Eres un piloto de élite.</p>
            <?php else: ?>
                <?php foreach (array_slice($misionesPendientes, 0, 3) as $m): ?>
                <div class="mission-card">
                    <div class="mission-info">
                        <h3><?php echo $m['nombre']; ?></h3>
                        <p><?php echo $m['descripcion']; ?></p>
                        <div class="mission-reward">+<?php echo $m['exp_reward']; ?> EXP | +<?php echo $m['puntos_reward']; ?> PTS</div>
                    </div>
                    <span class="mission-status pending">PENDIENTE</span>
                </div>
                <?php endforeach; ?>
                <div style="text-align:center;margin-top:15px;">
                    <a href="misiones.php" class="btn btn-sm">Ver todas las misiones</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick actions -->
        <div style="text-align:center;margin:30px 0;">
            <a href="batalla.php" class="btn btn-primary" style="margin:0 10px;padding:15px 40px;">⚔ INICIAR BATALLA</a>
            <a href="chat.php" class="btn" style="margin:0 10px;padding:15px 40px;">💬 CHAT NERV</a>
        </div>
    </div>
</body>
</html>
