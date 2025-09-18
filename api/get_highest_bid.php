<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$playerId = isset($_GET['player_id']) ? intval($_GET['player_id']) : 0;
if (!$playerId) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT b.amount, t.name AS team_name FROM bids b JOIN teams t ON b.team_id = t.id WHERE b.player_id = ? ORDER BY b.amount DESC LIMIT 1");
    $stmt->execute([$playerId]);
    $bid = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($bid ? $bid : []);
} catch (Exception $e) {
    echo json_encode([]);
}
