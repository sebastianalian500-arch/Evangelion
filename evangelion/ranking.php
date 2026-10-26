<?php
require_once 'config.php';
requireLogin();

$currentUser = getUser($pdo, $_SESSION['user_id']);

// Get top players by points
$stmt = $pdo->query("
    SELECT u.id, u.nombre_usuario, u.nivel, u.puntos, u.victorias, u.derrotas,
           p.nombre as personaje_nombre, p.imagen as personaje_imagen,
           e.nombre as eva_nombre
    FROM usuarios u
    JOIN personajes p ON u.personaje_id = p.id
    JOIN evas e ON u.eva_id = e.id
    ORDER BY u.puntos DESC, u.victorias DESC
    LIMIT 50
");
$ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current user rank
$userRank = 0;
foreach ($ranking as $i => $r) {
    if ($r['id'] == $currentUser['id']) {
        $userRank = $i + 1;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking — NERV System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="nerv-header">
        <div class="nerv-logo">NERV<span>EVANGELION SYSTEM</span></div>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="batalla.php">Batalla</a>
            <a href="ranking.php" class="active">Ranking</a>
            <a href="misiones.php">Misiones</a>
            <a href="chat.php">Chat</a>
            <a href="perfil.php">Perfil</a>
            <a href="logout.php" class="logout">Salir</a>
        </nav>
    </header>

    <div class="container">
        <?php if ($userRank > 0): ?>
        <div class="nerv-panel pulse">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <div style="color:var(--nerv-cyan);font-size:0.85em;">TU POSICIÓN ACTUAL</div>
                    <div style="font-family:'Orbitron',sans-serif;font-size:2em;color:var(--nerv-orange);">
                        #<?php echo $userRank; ?>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="color:var(--nerv-cyan);font-size:0.85em;">PUNTOS</div>
                    <div style="font-family:'Orbitron',sans-serif;font-size:2em;color:var(--nerv-yellow);">
                        <?php echo $currentUser['puntos']; ?>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="color:var(--nerv-cyan);font-size:0.85em;">VICTORIAS</div>
                    <div style="font-family:'Orbitron',sans-serif;font-size:2em;color:var(--nerv-green);">
                        <?php echo $currentUser['victorias']; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="nerv-panel">
            <div class="panel-title">// TABLA DE CLASIFICACIÓN — PILOTOS NERV</div>

            <?php if (empty($ranking)): ?>
                <p style="text-align:center;padding:40px;color:var(--nerv-yellow);">
                    ⚠ No hay pilotos registrados aún. Sé el primero en unirte.
                </p>
            <?php else: ?>
                <table class="ranking-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Piloto</th>
                            <th>Personaje</th>
                            <th>EVA</th>
                            <th>Nivel</th>
                            <th>Puntos</th>
                            <th>V/D</th>
                            <th>Win%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ranking as $i => $r): 
                            $total = $r['victorias'] + $r['derrotas'];
                            $winRate = $total > 0 ? round(($r['victorias'] / $total) * 100) : 0;
                            $rankClass = $i < 3 ? 'rank-' . ($i + 1) : '';
                            $isMe = $r['id'] == $currentUser['id'];
                        ?>
                        <tr style="<?php echo $isMe ? 'background:rgba(255,102,0,0.1);' : ''; ?>">
                            <td class="<?php echo $rankClass; ?>">
                                <?php 
                                if ($i === 0) echo '👑 1';
                                elseif ($i === 1) echo '🥈 2';
                                elseif ($i === 2) echo '🥉 3';
                                else echo ($i + 1);
                                ?>
                            </td>
                            <td style="<?php echo $isMe ? 'color:var(--nerv-orange);font-weight:bold;' : ''; ?>">
                                <?php echo htmlspecialchars($r['nombre_usuario']); ?>
                                <?php if ($isMe): ?> <span style="color:var(--nerv-cyan);font-size:0.75em;">(TÚ)</span><?php endif; ?>
                            </td>
                            <td><?php echo $r['personaje_nombre']; ?></td>
                            <td><?php echo $r['eva_nombre']; ?></td>
                            <td><?php echo $r['nivel']; ?></td>
                            <td style="color:var(--nerv-yellow);"><?php echo $r['puntos']; ?></td>
                            <td>
                                <span style="color:var(--nerv-green);"><?php echo $r['victorias']; ?>V</span>
                                <span style="color:var(--nerv-red);"> / <?php echo $r['derrotas']; ?>D</span>
                            </td>
                            <td><?php echo $winRate; ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
