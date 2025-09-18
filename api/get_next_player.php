<?php
require_once '../config/database.php';
require_once '../config/functions.php';

header('Content-Type: application/json');


$auction = getAuction();

// Get filter parameters from GET
$statusParam = isset($_GET['status']) ? $_GET['status'] : '';
$excludeId = isset($_GET['exclude_id']) ? $_GET['exclude_id'] : '';

// Parse status filter
$statusList = [];
if ($statusParam) {
    $statusList = array_map('trim', explode(',', $statusParam));
}

if (!$auction || $auction['status'] !== 'Live') {
    echo json_encode(['success' => false, 'message' => 'Auction is not live']);
    exit;
}

// Get current player
$currentPlayer = getCurrentPlayer();

if ($currentPlayer) {
    // Mark current player as unsold if no bids
    $highestBid = getHighestBid($currentPlayer['id']);
    if (!$highestBid) {
        $stmt = $pdo->prepare("UPDATE players SET status = 'Unsold' WHERE id = ?");
        $stmt->execute([$currentPlayer['id']]);
        // Delete from player_schedule
        $stmt = $pdo->prepare("DELETE FROM player_schedule WHERE player_id = ? AND auction_id = ?");
        $stmt->execute([$currentPlayer['id'], $auction['id']]);
    } else {
        // Sell player to highest bidder
        sellPlayer($currentPlayer['id'], $highestBid['team_id'], $highestBid['amount']);
    }
}

// Get next player in schedule

// Get next player whose status is not 'Sold'

// Get next player whose status is not 'Sold'

// Get next player whose status is not 'Sold'

// Get all unsold players in schedule

// Get current player ID
$currentPlayerId = $auction['current_player_id'] ?? null;


// Build query for next player
$query = "SELECT id FROM players WHERE 1=1";
$params = [];
if (!empty($statusList)) {
    $query .= " AND status IN (" . implode(',', array_fill(0, count($statusList), '?')) . ")";
    $params = array_merge($params, $statusList);
}
if ($currentPlayerId) {
    $query .= " AND id != ?";
    $params[] = $currentPlayerId;
}
if ($excludeId) {
    $query .= " AND id != ?";
    $params[] = $excludeId;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$nextPlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($nextPlayers && count($nextPlayers) > 0) {
    // Pick a random eligible player
    $randomIndex = array_rand($nextPlayers);
    $randomPlayerId = $nextPlayers[$randomIndex]['id'];
    // Update auction with next player
    $stmt = $pdo->prepare("UPDATE auction SET current_player_id = ? WHERE id = ?");
    $stmt->execute([$randomPlayerId, $auction['id']]);
    // Get player details
    $stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
    $stmt->execute([$randomPlayerId]);
    $playerDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode([
        'success' => true,
        'player' => $playerDetails,
        'end_time' => $auction['current_player_end_time']
    ]);
    exit;
}

// If no next player, check if all players are sold before completing auction
$stmt = $pdo->query("SELECT COUNT(*) as not_sold_count FROM players WHERE status != 'Sold'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result['not_sold_count'] == 0) {
    // All players are Sold, end auction
    $stmt = $pdo->prepare("UPDATE auction SET status = 'Completed' WHERE id = ?");
    $stmt->execute([$auction['id']]);
    echo json_encode(['success' => false, 'message' => 'Auction completed']);
    exit;
} else {
    // There are unsold players, so keep auction live
    echo json_encode(['success' => false, 'message' => 'Unsold players remain']);
    exit;
}

if ($nextPlayer) {
    // Update auction with next player
    $stmt = $pdo->prepare("UPDATE auction SET current_player_id = ? WHERE id = ?");
    $stmt->execute([$nextPlayer['player_id'], $auction['id']]);
    
    // Set current player end time
    $stmt = $pdo->prepare("UPDATE auction SET current_player_end_time = 
                          (SELECT end_time FROM player_schedule 
                           WHERE auction_id = ? AND player_id = ?) 
                          WHERE id = ?");
    $stmt->execute([$auction['id'], $nextPlayer['player_id'], $auction['id']]);
    
    // Get player details
    $stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
    $stmt->execute([$nextPlayer['player_id']]);
    $playerDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'player' => $playerDetails,
        'end_time' => $auction['current_player_end_time']
    ]);
} else {
    // No more players, end auction
    $stmt = $pdo->prepare("UPDATE auction SET status = 'Completed' WHERE id = ?");
    $stmt->execute([$auction['id']]);
    
    echo json_encode(['success' => false, 'message' => 'Auction completed']);
}
?>