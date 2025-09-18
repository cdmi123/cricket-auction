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
$teamId = $_SESSION['team_id'] ?? 0;
$amount = $_POST['bid_amount'] ?? 0;

// echo $playerId.'<br>'.$teamId.'<br>'.$amount; die();

if (!$playerId || !$teamId || !$amount) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters'.$playerId.'-'.$teamId.'-'.$amount]);
    exit;
}

// Verify team ID matches session
if ($teamId != $_SESSION['team_id']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get auction details
$auction = getAuction();
if (!$auction || $auction['status'] !== 'Live') {
    echo json_encode(['success' => false, 'message' => 'Auction is not live']);
    exit;
}

// Check if team has enough budget
$stmt = $pdo->prepare("SELECT remaining_budget FROM teams WHERE id = ?");
$stmt->execute([$teamId]);
$team = $stmt->fetch(PDO::FETCH_ASSOC);

if ($team['remaining_budget'] < $amount) {
    echo json_encode(['success' => false, 'message' => 'Insufficient budget']);
    exit;
}

// Check if bid is higher than current highest
$highestBid = getHighestBid($playerId);
if ($highestBid && $amount <= $highestBid['amount']) {
    echo json_encode(['success' => false, 'message' => 'Bid must be higher than current highest bid']);
    exit;
}

// Check team composition
$composition = validateTeamComposition($teamId);
if (!$composition['valid']) {
    echo json_encode(['success' => false, 'message' => $composition['message']]);
    exit;
}

// Place the bid
$stmt = $pdo->prepare("INSERT INTO bids (player_id, team_id, amount) VALUES (?, ?, ?)");
$stmt->execute([$playerId, $teamId, $amount]);

// Process auto bids
$stmt = $pdo->prepare("SELECT ab.*, t.name AS team_name 
                      FROM auto_bids ab 
                      JOIN teams t ON ab.team_id = t.id 
                      WHERE ab.player_id = ? AND ab.max_bid > ? 
                      ORDER BY ab.max_bid ASC");
$stmt->execute([$playerId, $amount]);
$autoBids = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($autoBids as $autoBid) {
    // Calculate the bid amount (current highest + min increment)
    $bidAmount = $amount + $auction['min_bid_increment'];
    
    // Check if it's within the auto bid limit
    if ($bidAmount <= $autoBid['max_bid']) {
        // Check if team has enough budget
        $stmt = $pdo->prepare("SELECT remaining_budget FROM teams WHERE id = ?");
        $stmt->execute([$autoBid['team_id']]);
        $autoTeam = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($autoTeam['remaining_budget'] >= $bidAmount) {
            // Place the auto bid
            $stmt = $pdo->prepare("INSERT INTO bids (player_id, team_id, amount) VALUES (?, ?, ?)");
            $stmt->execute([$playerId, $autoBid['team_id'], $bidAmount]);
            
            // Update highest bid
            $amount = $bidAmount;
        }
    }
}

echo json_encode(['success' => true, 'message' => 'Bid placed successfully']);
?>