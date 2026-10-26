<?php
require_once 'config.php';
requireLogin();

$user = getUser($pdo, $_SESSION['user_id']);
$expNecesario = $user['nivel'] * 100;
$expPercent = min(100, round(($user['exp'] / $expNecesario) * 100));

// Get battle history
$stmt = $pdo->prepare("
    SELECT b.*, 
           u1.nombre_usuario as user1_name, u2.nombre_usuario as user2_name,
           p1.nombre as user1_personaje, p2.nombre as user2_personaje
    FROM batallas b
    JOIN usuarios u1 ON b.usuario_1_id = u1.id
    JOIN usuarios u2 ON b.usuario_2_id = u2.id
    JOIN personajes p1 ON u1.personaje_id = p1.id
    JOIN personajes p2 ON u2.personaje_id = p2.id
    WHERE b.usuario_1_id = ? OR b.usuario_2_id = ?
    ORDER BY b.creado_en DESC
    LIMIT 20
");
$stmt->execute([$user['id'], $user['id']]);
$batallas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil — NERV System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="nerv-header">
        <div class="nerv-logo">NERV<span>EVANGELION SYSTEM</span></div>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="batalla.php">Batalla</a>
            <a href="ranking.php">Ranking</a>
            <a href="misiones.php">Misiones</a>
            <a href="chat.php">Chat</a>
            <a href="perfil.php" class="active">Perfil</a>
            <a href="logout.php" class="logout">Salir</a>
        </nav>
    </header>

    <div class="container">
        <!-- Profile Card -->
        <div class="nerv-panel">
            <div class="panel-title">// FICHA DE PILOTO</div>
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
                    <p style="color:var(--nerv-text);font-size:0.8em;margin-top:10px;">
                        Miembro desde: <?php echo date('d/m/Y', strtotime($user['creado_en'])); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-value" style="color:var(--nerv-yellow);"><?php echo $user['puntos']; ?></div>
                <div class="stat-label">Puntos Totales</div>
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
                <div class="stat-label">Tasa de Victoria</div>
            </div>
        </div>

        <!-- Character & Units -->
        <div class="grid-2">
            <div class="nerv-panel">
                <div class="panel-title">// PERSONAJE: <?php echo strtoupper($user['personaje_nombre']); ?></div>
                <img src="<?php echo $user['personaje_imagen']; ?>" alt="<?php echo $user['personaje_nombre']; ?>" 
                     style="width:100%;max-height:300px;object-fit:contain;border:1px solid var(--nerv-border);" onerror="this.style.background='#1a1a3a'">
            </div>

            <div class="nerv-panel">
                <div class="panel-title">// UNIDADES ASIGNADAS</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div style="text-align:center;">
                        <img src="<?php echo $user['eva_imagen']; ?>" alt="<?php echo $user['eva_nombre']; ?>" 
                             style="width:100%;height:150px;object-fit:contain;border:1px solid var(--nerv-border);" onerror="this.style.background='#1a1a3a'">
                        <div style="color:var(--nerv-orange);font-family:'Orbitron',sans-serif;margin-top:8px;">
                            <?php echo $user['eva_nombre']; ?>
                        </div>
                        <div style="font-size:0.8em;margin-top:5px;">
                            <span style="color:var(--nerv-red);">ATQ:<?php echo $user['eva_ataque']; ?></span>
                            <span style="color:var(--nerv-cyan);"> DEF:<?php echo $user['eva_defensa']; ?></span>
                            <span style="color:var(--nerv-green);"> VEL:<?php echo $user['eva_velocidad']; ?></span>
                        </div>
                    </div>
                    <div style="text-align:center;">
                        <img src="<?php echo $user['angel_imagen']; ?>" alt="<?php echo $user['angel_nombre']; ?>" 
                             style="width:100%;height:150px;object-fit:contain;border:1px solid var(--nerv-border);" onerror="this.style.background='#1a1a3a'">
                        <div style="color:var(--nerv-red);font-family:'Orbitron',sans-serif;margin-top:8px;">
                            <?php echo $user['angel_nombre']; ?>
                        </div>
                        <div style="font-size:0.8em;margin-top:5px;">
                            <span style="color:var(--nerv-red);">ATQ:<?php echo $user['angel_ataque']; ?></span>
                            <span style="color:var(--nerv-cyan);"> DEF:<?php echo $user['angel_defensa']; ?></span>
                            <span style="color:var(--nerv-green);"> VEL:<?php echo $user['angel_velocidad']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Battle History -->
        <div class="nerv-panel">
            <div class="panel-title">// HISTORIAL DE BATALLAS</div>
            <?php if (empty($batallas)): ?>
                <p style="text-align:center;padding:30px;color:var(--nerv-text);">
                    Aún no has participado en batallas. ¡Ve a combatir! ⚔
                </p>
            <?php else: ?>
                <?php foreach ($batallas as $b): 
                    $isUser1 = ($b['usuario_1_id'] == $user['id']);
                    $oppName = $isUser1 ? $b['user2_name'] : $b['user1_name'];
                    $oppPersonaje = $isUser1 ? $b['user2_personaje'] : $b['user1_personaje'];
                    $won = ($b['ganador_id'] == $user['id']);
                ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;border-bottom:1px solid var(--nerv-border);">
                    <div>
                        <span style="color:var(--nerv-white);">vs <?php echo htmlspecialchars($oppName); ?></span>
                        <span style="color:var(--nerv-text);font-size:0.8em;">(<?php echo $oppPersonaje; ?>)</span>
                    </div>
                    <div>
                        <span style="color:<?php echo $won ? 'var(--nerv-green)' : 'var(--nerv-red)'; ?>;font-weight:bold;">
                            <?php echo $won ? '✓ VICTORIA' : '✗ DERROTA'; ?>
                        </span>
                        <span style="color:var(--nerv-text);font-size:0.8em;margin-left:15px;">
                            <?php echo date('d/m H:i', strtotime($b['creado_en'])); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
