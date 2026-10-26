<?php
require_once 'config.php';
requireLogin();

$user = getUser($pdo, $_SESSION['user_id']);

// Check daily battle limit
$today = date('Y-m-d');
if ($user['ultima_batalla'] !== $today) {
    $stmt = $pdo->prepare("UPDATE usuarios SET batallas_hoy = 0, ultima_batalla = ? WHERE id = ?");
    $stmt->execute([$today, $user['id']]);
    $user['batallas_hoy'] = 0;
}

$dailyLimit = 20;
$remaining = $dailyLimit - $user['batallas_hoy'];

// Get real opponents (excluding self)
$stmt = $pdo->prepare("
    SELECT u.id, u.nombre_usuario, u.nivel, u.puntos, u.victorias, u.derrotas,
           p.nombre as personaje_nombre, p.imagen as personaje_imagen,
           e.nombre as eva_nombre, e.imagen as eva_imagen,
           e.ataque as eva_ataque, e.defensa as eva_defensa, e.velocidad as eva_velocidad,
           a.nombre as angel_nombre, a.imagen as angel_imagen,
           a.ataque as angel_ataque, a.defensa as angel_defensa, a.velocidad as angel_velocidad
    FROM usuarios u
    JOIN personajes p ON u.personaje_id = p.id
    JOIN evas e ON u.eva_id = e.id
    JOIN angeles a ON u.angel_id = a.id
    WHERE u.id != ?
    ORDER BY RAND()
    LIMIT 5
");
$stmt->execute([$user['id']]);
$opponents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Always-available NPC opponents
$npcs = [
    ['id' => 'npc1', 'nombre_usuario' => 'Ángel Sachiel', 'nivel' => 3, 'puntos' => 50, 'victorias' => 5, 'derrotas' => 2,
     'personaje_nombre' => 'Sachiel', 'personaje_imagen' => 'img/angeles/sachiel.svg',
     'eva_nombre' => 'Unit-00', 'eva_imagen' => 'img/evas/unit00.svg', 'eva_ataque' => 15, 'eva_defensa' => 12, 'eva_velocidad' => 10,
     'angel_nombre' => 'Sachiel', 'angel_imagen' => 'img/angeles/sachiel.svg', 'angel_ataque' => 18, 'angel_defensa' => 14, 'angel_velocidad' => 12],
    ['id' => 'npc2', 'nombre_usuario' => 'Ramiel', 'nivel' => 5, 'puntos' => 120, 'victorias' => 12, 'derrotas' => 4,
     'personaje_nombre' => 'Ramiel', 'personaje_imagen' => 'img/angeles/ramiel.svg',
     'eva_nombre' => 'Unit-01', 'eva_imagen' => 'img/evas/unit01.svg', 'eva_ataque' => 20, 'eva_defensa' => 18, 'eva_velocidad' => 15,
     'angel_nombre' => 'Ramiel', 'angel_imagen' => 'img/angeles/ramiel.svg', 'angel_ataque' => 25, 'angel_defensa' => 20, 'angel_velocidad' => 10],
    ['id' => 'npc3', 'nombre_usuario' => 'Zeruel', 'nivel' => 8, 'puntos' => 300, 'victorias' => 25, 'derrotas' => 8,
     'personaje_nombre' => 'Zeruel', 'personaje_imagen' => 'img/angeles/zeruel.svg',
     'eva_nombre' => 'Unit-02', 'eva_imagen' => 'img/evas/unit02.svg', 'eva_ataque' => 28, 'eva_defensa' => 22, 'eva_velocidad' => 18,
     'angel_nombre' => 'Zeruel', 'angel_imagen' => 'img/angeles/zeruel.svg', 'angel_ataque' => 35, 'angel_defensa' => 25, 'angel_velocidad' => 15],
    ['id' => 'npc4', 'nombre_usuario' => 'Armisael', 'nivel' => 12, 'puntos' => 600, 'victorias' => 40, 'derrotas' => 10,
     'personaje_nombre' => 'Armisael', 'personaje_imagen' => 'img/angeles/armisael.svg',
     'eva_nombre' => 'Unit-01', 'eva_imagen' => 'img/evas/unit01.svg', 'eva_ataque' => 35, 'eva_defensa' => 30, 'eva_velocidad' => 22,
     'angel_nombre' => 'Armisael', 'angel_imagen' => 'img/angeles/armisael.svg', 'angel_ataque' => 45, 'angel_defensa' => 35, 'angel_velocidad' => 20],
    ['id' => 'npc5', 'nombre_usuario' => 'Kaworu (17º Ángel)', 'nivel' => 15, 'puntos' => 999, 'victorias' => 60, 'derrotas' => 5,
     'personaje_nombre' => 'Kaworu Nagisa', 'personaje_imagen' => 'img/personajes/kaworu.svg',
     'eva_nombre' => 'Unit-02', 'eva_imagen' => 'img/evas/unit02.svg', 'eva_ataque' => 40, 'eva_defensa' => 35, 'eva_velocidad' => 25,
     'angel_nombre' => 'Kaworu', 'angel_imagen' => 'img/angeles/kaworu_angel.svg', 'angel_ataque' => 55, 'angel_defensa' => 40, 'angel_velocidad' => 30],
];

// Merge: NPCs always first, then real users
$opponents = array_merge($npcs, $opponents);

$battleResult = null;
$battleLog = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['opponent_id'])) {
    $opponentId = $_POST['opponent_id'];

    if ($remaining <= 0) {
        $battleResult = ['error' => 'Has alcanzado el límite de batallas diarias. Vuelve mañana.'];
    } else {
        // Find opponent: NPC or real user
        $opponent = null;
        $isNPC = false;
        if (strpos($opponentId, 'npc') === 0) {
            foreach ($npcs as $npc) {
                if ($npc['id'] === $opponentId) {
                    $opponent = $npc;
                    $isNPC = true;
                    break;
                }
            }
        } else {
            $opponent = getUser($pdo, intval($opponentId));
        }

        if (!$opponent) {
            $battleResult = ['error' => 'Oponente no encontrado.'];
        } else {
            // Battle logic - EVA vs EVA and Angel vs Angel combined
            $userEvaAtk = $user['eva_ataque'] + $user['nivel'] * 2;
            $userEvaDef = $user['eva_defensa'] + $user['nivel'] * 2;
            $userEvaVel = $user['eva_velocidad'] + $user['nivel'] * 2;
            $userAngelAtk = $user['angel_ataque'] + $user['nivel'] * 2;
            $userAngelDef = $user['angel_defensa'] + $user['nivel'] * 2;
            $userAngelVel = $user['angel_velocidad'] + $user['nivel'] * 2;

            $oppEvaAtk = $opponent['eva_ataque'] + $opponent['nivel'] * 2;
            $oppEvaDef = $opponent['eva_defensa'] + $opponent['nivel'] * 2;
            $oppEvaVel = $opponent['eva_velocidad'] + $opponent['nivel'] * 2;
            $oppAngelAtk = $opponent['angel_ataque'] + $opponent['nivel'] * 2;
            $oppAngelDef = $opponent['angel_defensa'] + $opponent['nivel'] * 2;
            $oppAngelVel = $opponent['angel_velocidad'] + $opponent['nivel'] * 2;

            // Combined stats
            $userAtk = ($userEvaAtk + $userAngelAtk) / 2;
            $userDef = ($userEvaDef + $userAngelDef) / 2;
            $userVel = ($userEvaVel + $userAngelVel) / 2;
            $oppAtk = ($oppEvaAtk + $oppAngelAtk) / 2;
            $oppDef = ($oppEvaDef + $oppAngelDef) / 2;
            $oppVel = ($oppEvaVel + $oppAngelVel) / 2;

            $userHP = 100 + ($user['nivel'] * 5);
            $oppHP = 100 + ($opponent['nivel'] * 5);
            $userMaxHP = $userHP;
            $oppMaxHP = $oppHP;

            $log = [];
            $round = 1;
            $userGoesFirst = ($userVel >= $oppVel) ? true : false;

            $log[] = ['type' => 'info', 'text' => "═══ BATALLA INICIADA ═══"];
            $log[] = ['type' => 'info', 'text' => "{$user['nombre_usuario']} (Nv.$user[nivel]) vs {$opponent['nombre_usuario']} (Nv.$opponent[nivel])"];
            $log[] = ['type' => 'info', 'text' => "═══════════════════════"];
            $log[] = ['type' => 'info', 'text' => $userGoesFirst ? "¡{$user['nombre_usuario']} ataca primero!" : "¡{$opponent['nombre_usuario']} ataca primero!"];

            while ($userHP > 0 && $oppHP > 0 && $round <= 20) {
                $log[] = ['type' => 'info', 'text' => "--- Ronda $round ---"];

                // Determine turn order
                $order = $userGoesFirst ? ['user', 'opp'] : ['opp', 'user'];
                if ($round > 1) {
                    // Shuffle based on speed each round
                    $order = (mt_rand(1, 100) <= 50) ? ['user', 'opp'] : ['opp', 'user'];
                }

                foreach ($order as $attacker) {
                    if ($userHP <= 0 || $oppHP <= 0) break;

                    if ($attacker === 'user') {
                        $isCrit = mt_rand(1, 100) <= 10;
                        $dmg = max(5, round($userAtk * (mt_rand(80, 120) / 100) - $oppDef * 0.3));
                        if ($isCrit) $dmg = round($dmg * 1.5);
                        $oppHP = max(0, $oppHP - $dmg);

                        // Counter chance
                        if ($oppHP > 0 && mt_rand(1, 100) <= 15) {
                            $counterDmg = max(3, round($oppAtk * 0.3 - $userDef * 0.1));
                            $userHP = max(0, $userHP - $counterDmg);
                            $log[] = ['type' => 'damage', 'text' => "{$opponent['nombre_usuario']} contraataca: -{$counterDmg} HP"];
                        }

                        if ($isCrit) {
                            $log[] = ['type' => 'critical', 'text' => "¡{$user['nombre_usuario']} ATACA CRÍTICO!: -{$dmg} HP ({$opponent['nombre_usuario']})"];
                        } else {
                            $log[] = ['type' => 'damage', 'text' => "{$user['nombre_usuario']} ataca: -{$dmg} HP"];
                        }
                    } else {
                        $isCrit = mt_rand(1, 100) <= 10;
                        $dmg = max(5, round($oppAtk * (mt_rand(80, 120) / 100) - $userDef * 0.3));
                        if ($isCrit) $dmg = round($dmg * 1.5);
                        $userHP = max(0, $userHP - $dmg);

                        // Counter chance
                        if ($userHP > 0 && mt_rand(1, 100) <= 15) {
                            $counterDmg = max(3, round($userAtk * 0.3 - $oppDef * 0.1));
                            $oppHP = max(0, $oppHP - $counterDmg);
                            $log[] = ['type' => 'damage', 'text' => "{$user['nombre_usuario']} contraataca: -{$counterDmg} HP"];
                        }

                        if ($isCrit) {
                            $log[] = ['type' => 'critical', 'text' => "¡{$opponent['nombre_usuario']} ATACA CRÍTICO!: -{$dmg} HP ({$user['nombre_usuario']})"];
                        } else {
                            $log[] = ['type' => 'damage', 'text' => "{$opponent['nombre_usuario']} ataca: -{$dmg} HP"];
                        }
                    }
                }
                $round++;
            }

            // Determine winner
            if ($userHP > $oppHP) {
                $winnerId = $user['id'];
                $expReward = 20 + $opponent['nivel'] * 5;
                $puntosReward = 10 + $opponent['nivel'] * 3;
                $log[] = ['type' => 'info', 'text' => "═══════════════════════"];
                $log[] = ['type' => 'heal', 'text' => "¡VICTORIA! ¡{$user['nombre_usuario']} gana la batalla!"];
                $log[] = ['type' => 'heal', 'text' => "+{$expReward} EXP | +{$puntosReward} PTS"];

                if (!$isNPC) {
                    $stmt = $pdo->prepare("UPDATE usuarios SET victorias = victorias + 1 WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    $stmt = $pdo->prepare("UPDATE usuarios SET derrotas = derrotas + 1 WHERE id = ?");
                    $stmt->execute([$opponent['id']]);
                }
                addExp($pdo, $user['id'], $expReward, $puntosReward);

                // Check battle missions
                $stmt = $pdo->prepare("
                    SELECT m.id, m.exp_reward, m.puntos_reward, m.descripcion
                    FROM misiones m
                    LEFT JOIN misiones_usuario mu ON m.id = mu.mision_id AND mu.usuario_id = ?
                    WHERE m.tipo = 'batalla' AND (mu.id IS NULL OR mu.completada = FALSE)
                ");
                $stmt->execute([$user['id']]);
                $pendingMissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $totalVictorias = $user['victorias'] + 1;
                $missionMsgs = [];
                foreach ($pendingMissions as $pm) {
                    $completed = false;
                    if (strpos($pm['descripcion'], 'primera') !== false && $totalVictorias >= 1) $completed = true;
                    if (strpos($pm['descripcion'], '3 batallas') !== false && $totalVictorias >= 3) $completed = true;
                    if (strpos($pm['descripcion'], '10 batallas') !== false && $totalVictorias >= 10) $completed = true;

                    if ($completed) {
                        $check = $pdo->prepare("SELECT id FROM misiones_usuario WHERE usuario_id = ? AND mision_id = ?");
                        $check->execute([$user['id'], $pm['id']]);
                        if (!$check->fetch()) {
                            $ins = $pdo->prepare("INSERT INTO misiones_usuario (usuario_id, mision_id, completada) VALUES (?, ?, TRUE)");
                            $ins->execute([$user['id'], $pm['id']]);
                            addExp($pdo, $user['id'], $pm['exp_reward'], $pm['puntos_reward']);
                            $missionName = $pm['nombre'] ?? $pm['descripcion'];
                            $missionMsgs[] = "¡Misión completada: {$missionName}! +{$pm['exp_reward']} EXP";
                        }
                    }
                }

                $battleResult = ['win' => true, 'exp' => $expReward, 'puntos' => $puntosReward, 'missions' => $missionMsgs];
            } elseif ($oppHP > $userHP) {
                $winnerId = $opponent['id'];
                $expReward = 5;
                $puntosReward = 2;
                $log[] = ['type' => 'info', 'text' => "═══════════════════════"];
                $log[] = ['type' => 'damage', 'text' => "DERROTA... {$opponent['nombre_usuario']} gana la batalla."];
                $log[] = ['type' => 'info', 'text' => "+{$expReward} EXP (consolación)"];

                if (!$isNPC) {
                    $stmt = $pdo->prepare("UPDATE usuarios SET derrotas = derrotas + 1 WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    $stmt = $pdo->prepare("UPDATE usuarios SET victorias = victorias + 1 WHERE id = ?");
                    $stmt->execute([$opponent['id']]);
                }
                addExp($pdo, $user['id'], $expReward, $puntosReward);

                $battleResult = ['win' => false, 'exp' => $expReward, 'puntos' => $puntosReward];
            } else {
                $winnerId = $user['id'];
                $log[] = ['type' => 'info', 'text' => "═══ EMPATE ═══"];
                $battleResult = ['win' => null, 'exp' => 10, 'puntos' => 5];
                addExp($pdo, $user['id'], 10, 5);
            }

            // Save battle (only for real users)
            if (!$isNPC) {
                $logText = json_encode($log, JSON_UNESCAPED_UNICODE);
                $stmt = $pdo->prepare("INSERT INTO batallas (usuario_1_id, usuario_2_id, ganador_id, registro) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user['id'], $opponent['id'], $winnerId, $logText]);
            }

            // Increment daily count
            $stmt = $pdo->prepare("UPDATE usuarios SET batallas_hoy = batallas_hoy + 1 WHERE id = ?");
            $stmt->execute([$user['id']]);

            // Update local data
            $user = getUser($pdo, $user['id']);
            $remaining = $dailyLimit - $user['batallas_hoy'];
            $battleLog = $log;
            $battleOpponent = $opponent;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batalla — NERV System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="nerv-header">
        <div class="nerv-logo">NERV<span>EVANGELION SYSTEM</span></div>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="batalla.php" class="active">Batalla</a>
            <a href="ranking.php">Ranking</a>
            <a href="misiones.php">Misiones</a>
            <a href="chat.php">Chat</a>
            <a href="perfil.php">Perfil</a>
            <a href="logout.php" class="logout">Salir</a>
        </nav>
    </header>

    <div class="container">
        <div class="nerv-panel">
            <div class="panel-title">// SISTEMA DE COMBATE</div>
            <p style="color:var(--nerv-cyan);">
                Batallas restantes hoy: <strong style="color:var(--nerv-orange);"><?php echo max(0, $remaining); ?></strong>/<?php echo $dailyLimit; ?>
            </p>
        </div>

        <?php if ($battleResult && isset($battleResult['error'])): ?>
            <div class="alert alert-danger">⚠ <?php echo $battleResult['error']; ?></div>
        <?php endif; ?>

        <?php if ($battleLog): ?>
            <!-- Battle Results -->
            <div class="nerv-panel">
                <div class="panel-title">// RESULTADO DE BATALLA</div>
                
                <?php if (isset($battleResult['win'])): ?>
                    <?php if ($battleResult['win'] === true): ?>
                        <div class="alert alert-success" style="font-family:'Orbitron',sans-serif;font-size:1.2em;text-align:center;">
                            ⚔ ¡VICTORIA! +<?php echo $battleResult['exp']; ?> EXP | +<?php echo $battleResult['puntos']; ?> PTS
                        </div>
                    <?php elseif ($battleResult['win'] === false): ?>
                        <div class="alert alert-danger" style="font-family:'Orbitron',sans-serif;font-size:1.2em;text-align:center;">
                            💀 DERROTA... +<?php echo $battleResult['exp']; ?> EXP
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" style="font-family:'Orbitron',sans-serif;font-size:1.2em;text-align:center;">
                            🤝 ¡EMPATE! +<?php echo $battleResult['exp']; ?> EXP
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($battleResult['missions'])): ?>
                        <?php foreach ($battleResult['missions'] as $mm): ?>
                            <div class="alert alert-success">🎯 <?php echo $mm; ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="battle-log" id="battleLog">
                    <?php foreach ($battleLog as $entry): ?>
                        <div class="log-entry <?php echo $entry['type']; ?>"><?php echo $entry['text']; ?></div>
                    <?php endforeach; ?>
                </div>

                <div style="text-align:center;margin-top:20px;">
                    <a href="batalla.php" class="btn btn-primary">► NUEVA BATALLA</a>
                </div>
            </div>

        <?php else: ?>
            <!-- Opponent Selection -->
            <div class="nerv-panel">
                <div class="panel-title">// SELECCIONAR OPONENTE</div>

                <?php if (empty($opponents)): ?>
                    <p style="color:var(--nerv-yellow);text-align:center;padding:30px;">
                        ⚠ No hay oponentes disponibles. ¡Sé el primero en registrarte!
                    </p>
                <?php else: ?>
                    <div class="selection-grid">
                        <?php foreach ($opponents as $opp): ?>
                        <div class="fighter-card" style="cursor:default;">
                            <img src="<?php echo $opp['personaje_imagen']; ?>" alt="<?php echo $opp['personaje_nombre']; ?>" onerror="this.style.background='#1a1a3a'">
                            <div class="fighter-name"><?php echo htmlspecialchars($opp['nombre_usuario']); ?></div>
                            <div style="font-size:0.8em;color:var(--nerv-cyan);margin-bottom:5px;">
                                🎭 <?php echo $opp['personaje_nombre']; ?> | Nv.<?php echo $opp['nivel']; ?>
                            </div>
                            <div style="font-size:0.75em;color:var(--nerv-text);">
                                🤖 <?php echo $opp['eva_nombre']; ?> | 👹 <?php echo $opp['angel_nombre']; ?>
                            </div>
                            <div style="font-size:0.75em;margin-top:5px;">
                                <span style="color:var(--nerv-green);">⚔ <?php echo $opp['victorias']; ?>V</span>
                                <span style="color:var(--nerv-red);"> 💀 <?php echo $opp['derrotas']; ?>D</span>
                                <span style="color:var(--nerv-yellow);"> ★ <?php echo $opp['puntos']; ?></span>
                            </div>
                            <form method="POST" action="batalla.php" style="margin-top:15px;">
                                <input type="hidden" name="opponent_id" value="<?php echo $opp['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">⚔ DESAFIAR</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($battleLog): ?>
    <script>
    // Auto-scroll battle log
    const log = document.getElementById('battleLog');
    if (log) log.scrollTop = log.scrollHeight;
    </script>
    <?php endif; ?>
</body>
</html>
