<?php
require_once 'config.php';
requireLogin();

$user = getUser($pdo, $_SESSION['user_id']);

// Get all missions with user completion status
$stmt = $pdo->prepare("
    SELECT m.*, mu.completada, mu.fecha as fecha_completada
    FROM misiones m
    LEFT JOIN misiones_usuario mu ON m.id = mu.mision_id AND mu.usuario_id = ?
    ORDER BY m.tipo, m.id
");
$stmt->execute([$user['id']]);
$misiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$completadas = 0;
$total = count($misiones);
foreach ($misiones as $m) {
    if ($m['completada']) $completadas++;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Misiones — NERV System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="nerv-header">
        <div class="nerv-logo">NERV<span>EVANGELION SYSTEM</span></div>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="batalla.php">Batalla</a>
            <a href="ranking.php">Ranking</a>
            <a href="misiones.php" class="active">Misiones</a>
            <a href="chat.php">Chat</a>
            <a href="perfil.php">Perfil</a>
            <a href="logout.php" class="logout">Salir</a>
        </nav>
    </header>

    <div class="container">
        <div class="nerv-panel">
            <div class="panel-title">// CENTRO DE MISIONES NERV</div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <span style="color:var(--nerv-cyan);">Progreso total:</span>
                    <strong style="color:var(--nerv-orange);"><?php echo $completadas; ?>/<?php echo $total; ?></strong>
                </div>
                <div class="stat-bar" style="width:300px;height:12px;">
                    <div class="fill velocidad" style="width:<?php echo $total > 0 ? round(($completadas/$total)*100) : 0; ?>%;background:var(--nerv-green);"></div>
                </div>
            </div>
        </div>

        <?php
        $tipoLabels = [
            'batalla' => '⚔ MISIONES DE BATALLA',
            'chat' => '💬 MISIONES DE COMUNICACIÓN',
            'registro' => '📋 MISIONES DE RECLUTAMIENTO'
        ];
        $currentTipo = '';
        foreach ($misiones as $m):
            if ($m['tipo'] !== $currentTipo) {
                $currentTipo = $m['tipo'];
                echo '<div class="nerv-panel"><div class="panel-title">' . ($tipoLabels[$currentTipo] ?? $currentTipo) . '</div>';
                echo '<div style="display:flex;flex-direction:column;gap:0;">';
            }
        ?>
            <div class="mission-card <?php echo $m['completada'] ? 'completed' : ''; ?>" style="margin-bottom:10px;">
                <div class="mission-info">
                    <h3><?php echo $m['nombre']; ?></h3>
                    <p><?php echo $m['descripcion']; ?></p>
                    <div class="mission-reward">
                        🎁 Recompensa: +<?php echo $m['exp_reward']; ?> EXP | +<?php echo $m['puntos_reward']; ?> PTS
                    </div>
                </div>
                <span class="mission-status <?php echo $m['completada'] ? 'completed' : 'pending'; ?>">
                    <?php echo $m['completada'] ? '✓ COMPLETADA' : '○ PENDIENTE'; ?>
                </span>
            </div>
        <?php
            $nextTipo = next($misiones) ? $misiones[key($misiones)]['tipo'] : null;
            if ($nextTipo !== $currentTipo) {
                echo '</div></div>';
            }
        endforeach;
        ?>
    </div>
</body>
</html>
