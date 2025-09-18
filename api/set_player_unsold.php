<?php
require_once '../config/database.php';
require_once '../config/functions.php';
header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Get player_id from JSON body
$input = json_decode(file_get_contents('php://input'), true);
$playerId = isset($input['player_id']) ? intval($input['player_id']) : 0;

if (!$playerId) {
    echo json_encode(['success' => false, 'message' => 'Player ID missing.']);
    exit;
}

// Update player status to 'Unsold' (do NOT delete from player_schedule)
$stmt = $pdo->prepare('UPDATE players SET status = ? WHERE id = ?');
if ($stmt->execute(['Unsold', $playerId])) {
    // Also remove from player_schedule for current auction
    $auctionStmt = $pdo->query('SELECT id FROM auction WHERE status = "Live" LIMIT 1');
    $auction = $auctionStmt->fetch(PDO::FETCH_ASSOC);
    if ($auction) {
        $delStmt = $pdo->prepare('DELETE FROM player_schedule WHERE player_id = ? AND auction_id = ?');
        $delStmt->execute([$playerId, $auction['id']]);
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update player status.']);
}
