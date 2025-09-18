<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$playerId = isset($_GET['player_id']) ? intval($_GET['player_id']) : 0;
if (!$playerId) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT b.amount, DATE_FORMAT(b.bid_time, '%H:%i') AS bid_time, t.name AS team_name FROM bids b JOIN teams t ON b.team_id = t.id WHERE b.player_id = ? ORDER BY b.bid_time ASC");
    $stmt->execute([$playerId]);
    $bids = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($bids);
} catch (Exception $e) {
    echo json_encode([]);
}
