<?php
require_once '../config/database.php';
require_once '../config/functions.php';
header('Content-Type: application/json');

$currentPlayer = getCurrentPlayer();
$currentId = $currentPlayer ? $currentPlayer['id'] : 0;

$stmt = $pdo->prepare('SELECT * FROM players WHERE status = "Unsold" AND id != ?');
$stmt->execute([$currentId]);
$unsoldPlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'players' => $unsoldPlayers]);
