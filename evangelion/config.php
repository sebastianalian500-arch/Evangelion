<?php
session_start();
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'evangelion_db';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function getUser($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT u.*, p.nombre as personaje_nombre, p.imagen as personaje_imagen,
               e.nombre as eva_nombre, e.imagen as eva_imagen, e.ataque as eva_ataque, e.defensa as eva_defensa, e.velocidad as eva_velocidad,
               a.nombre as angel_nombre, a.imagen as angel_imagen, a.ataque as angel_ataque, a.defensa as angel_defensa, a.velocidad as angel_velocidad
        FROM usuarios u
        JOIN personajes p ON u.personaje_id = p.id
        JOIN evas e ON u.eva_id = e.id
        JOIN angeles a ON u.angel_id = a.id
        WHERE u.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function addExp($pdo, $userId, $exp, $puntos) {
    $user = getUser($pdo, $userId);
    $newExp = $user['exp'] + $exp;
    $newPuntos = $user['puntos'] + $puntos;
    $newNivel = $user['nivel'];
    $expNecesario = $user['nivel'] * 100;

    while ($newExp >= $expNecesario) {
        $newExp -= $expNecesario;
        $newNivel++;
        $expNecesario = $newNivel * 100;
    }

    $stmt = $pdo->prepare("UPDATE usuarios SET exp=?, puntos=?, nivel=? WHERE id=?");
    $stmt->execute([$newExp, $newPuntos, $newNivel, $userId]);
}
?>
