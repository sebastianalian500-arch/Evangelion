<?php
require_once 'config.php';
requireLogin();

// AJAX endpoint for getting messages
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if ($_GET['action'] === 'get_messages') {
        $lastId = intval($_GET['last_id'] ?? 0);
        $stmt = $pdo->prepare("
            SELECT m.id, m.contenido, m.creado_en, u.nombre_usuario, p.imagen as personaje_imagen
            FROM mensajes m
            JOIN usuarios u ON m.usuario_id = u.id
            JOIN personajes p ON u.personaje_id = p.id
            WHERE m.id > ?
            ORDER BY m.id ASC
            LIMIT 50
        ");
        $stmt->execute([$lastId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($messages);
        exit;
    }

    if ($_GET['action'] === 'send') {
        $contenido = trim($_POST['contenido'] ?? '');
        if (!empty($contenido) && strlen($contenido) <= 500) {
            $stmt = $pdo->prepare("INSERT INTO mensajes (usuario_id, contenido) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $contenido]);

            // Check chat missions
            $count = $pdo->prepare("SELECT COUNT(*) FROM mensajes WHERE usuario_id = ?");
            $count->execute([$_SESSION['user_id']]);
            $msgCount = $count->fetchColumn();

            $stmt = $pdo->prepare("
                SELECT m.id, m.exp_reward, m.puntos_reward, m.nombre
                FROM misiones m
                LEFT JOIN misiones_usuario mu ON m.id = mu.mision_id AND mu.usuario_id = ?
                WHERE m.tipo = 'chat' AND (mu.id IS NULL OR mu.completada = FALSE)
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $chatMissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $completedMissions = [];
            foreach ($chatMissions as $cm) {
                if ($msgCount >= 5 && strpos($cm['nombre'], 'Activo') !== false) {
                    $check = $pdo->prepare("SELECT id FROM misiones_usuario WHERE usuario_id = ? AND mision_id = ?");
                    $check->execute([$_SESSION['user_id'], $cm['id']]);
                    if (!$check->fetch()) {
                        $ins = $pdo->prepare("INSERT INTO misiones_usuario (usuario_id, mision_id, completada) VALUES (?, ?, TRUE)");
                        $ins->execute([$_SESSION['user_id'], $cm['id']]);
                        addExp($pdo, $_SESSION['user_id'], $cm['exp_reward'], $cm['puntos_reward']);
                        $completedMissions[] = $cm['nombre'];
                    }
                }
                if ($msgCount >= 20 && strpos($cm['nombre'], 'Comunicación') !== false) {
                    $check = $pdo->prepare("SELECT id FROM misiones_usuario WHERE usuario_id = ? AND mision_id = ?");
                    $check->execute([$_SESSION['user_id'], $cm['id']]);
                    if (!$check->fetch()) {
                        $ins = $pdo->prepare("INSERT INTO misiones_usuario (usuario_id, mision_id, completada) VALUES (?, ?, TRUE)");
                        $ins->execute([$_SESSION['user_id'], $cm['id']]);
                        addExp($pdo, $_SESSION['user_id'], $cm['exp_reward'], $cm['puntos_reward']);
                        $completedMissions[] = $cm['nombre'];
                    }
                }
            }

            echo json_encode(['success' => true, 'missions' => $completedMissions]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Mensaje vacío o demasiado largo (máx 500).']);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat — NERV System</title>
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
            <a href="chat.php" class="active">Chat</a>
            <a href="perfil.php">Perfil</a>
            <a href="logout.php" class="logout">Salir</a>
        </nav>
    </header>

    <div class="container">
        <div class="nerv-panel">
            <div class="panel-title">// CANAL DE COMUNICACIÓN NERV — CANAL PRINCIPAL</div>
            
            <div class="chat-container">
                <div class="chat-messages" id="chatMessages">
                    <div style="text-align:center;color:var(--nerv-cyan);padding:20px;">
                        <span class="loading-dots">Conectando al canal NERV</span>
                    </div>
                </div>
                
                <form id="chatForm" class="chat-input-area">
                    <input type="text" id="chatInput" class="form-control" placeholder="Escribe tu mensaje..." maxlength="500" autocomplete="off">
                    <button type="submit" class="btn btn-primary">► ENVIAR</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    const userId = <?php echo $_SESSION['user_id']; ?>;
    let lastId = 0;
    const chatMessages = document.getElementById('chatMessages');
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatTime(dateStr) {
        const d = new Date(dateStr.replace(' ', 'T'));
        return d.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
    }

    function addMessage(msg) {
        const div = document.createElement('div');
        div.className = 'chat-message';
        div.innerHTML = `
            <span class="msg-user">${escapeHtml(msg.nombre_usuario)}</span>
            <span class="msg-time">${formatTime(msg.creado_en)}</span>
            <div class="msg-text">${escapeHtml(msg.contenido)}</div>
        `;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    async function loadMessages() {
        try {
            const resp = await fetch(`chat.php?action=get_messages&last_id=${lastId}`);
            const messages = await resp.json();

            if (lastId === 0 && messages.length > 0) {
                chatMessages.innerHTML = '';
            }

            messages.forEach(msg => {
                addMessage(msg);
                lastId = Math.max(lastId, parseInt(msg.id));
            });
        } catch (e) {
            console.error('Error loading messages:', e);
        }
    }

    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const contenido = chatInput.value.trim();
        if (!contenido) return;

        chatInput.value = '';
        
        try {
            const formData = new FormData();
            formData.append('contenido', contenido);

            const resp = await fetch('chat.php?action=send', {
                method: 'POST',
                body: formData
            });
            const result = await resp.json();

            if (result.success) {
                if (result.missions && result.missions.length > 0) {
                    result.missions.forEach(m => {
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-success';
                        alert.style.cssText = 'position:fixed;top:80px;right:20px;z-index:1000;';
                        alert.textContent = `🎯 ¡Misión completada: ${m}!`;
                        document.body.appendChild(alert);
                        setTimeout(() => alert.remove(), 3000);
                    });
                }
                await loadMessages();
            }
        } catch (e) {
            console.error('Error sending message:', e);
        }
    });

    // Initial load and polling
    loadMessages();
    setInterval(loadMessages, 2000);

    // Scroll to bottom on load
    chatMessages.scrollTop = chatMessages.scrollHeight;
    </script>
</body>
</html>
