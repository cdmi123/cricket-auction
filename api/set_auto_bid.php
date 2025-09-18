<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isTeam()) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$playerId = $_POST['player_id'] ?? 0;
$teamId = $_POST['team_id'] ?? 0;
$maxBid = $_POST['max_bid'] ?? 0;

if (!$playerId || !$teamId || !$maxBid) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Verify team ID matches session
if ($teamId != $_SESSION['team_id']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if team has enough budget
$stmt = $pdo->prepare("SELECT remaining_budget FROM teams WHERE id = ?");
$stmt->execute([$teamId]);
$team = $stmt->fetch(PDO::FETCH_ASSOC);

if ($team['remaining_budget'] < $maxBid) {
    echo json_encode(['success' => false, 'message' => 'Maximum bid exceeds remaining budget']);
    exit;
}

// Check if auto bid already exists
$stmt = $pdo->prepare("SELECT id FROM auto_bids WHERE player_id = ? AND team_id = ?");
$stmt->execute([$playerId, $teamId]);
$existingBid = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existingBid) {
    // Update existing auto bid
    $stmt = $pdo->prepare("UPDATE auto_bids SET max_bid = ? WHERE id = ?");
    $stmt->execute([$maxBid, $existingBid['id']]);
} else {
    // Create new auto bid
    $stmt = $pdo->prepare("INSERT INTO auto_bids (player_id, team_id, max_bid) VALUES (?, ?, ?)");
    $stmt->execute([$playerId, $teamId, $maxBid]);
}

echo json_encode(['success' => true, 'message' => 'Auto bid set successfully']);
?>