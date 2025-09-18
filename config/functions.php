
<?php
require_once 'database.php';

// Check if all players are sold and complete auction if true
function checkAndCompleteAuction() {
    global $pdo;
    // Count players not marked as Sold
    $stmt = $pdo->query("SELECT COUNT(*) as not_sold_count FROM players WHERE status != 'Sold'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    // Debug: print count
    error_log('Players not sold count: ' . $result['not_sold_count']);
    if ($result['not_sold_count'] == 0) {
        // All players are Sold, mark auction as completed
        $stmt = $pdo->prepare("UPDATE auction SET status = 'Completed' WHERE status != 'Completed'");
        $stmt->execute();
        return true;
    }
    return false;
}

// Get all teams
function getTeams() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM teams ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all players
function getPlayers($status = 'All') {
    global $pdo;
    $sql = "SELECT p.*, t.name AS team_name FROM players p LEFT JOIN teams t ON p.sold_to = t.id";
    
    if ($status != 'All') {
        $sql .= " WHERE p.status = '$status'";
    }
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get auction details
function getAuction() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM auction ORDER BY id DESC LIMIT 1");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get current player in auction
function getCurrentPlayer() {
    global $pdo;
    // Get current auction
    $auction = getAuction();
    if (!$auction || !$auction['current_player_id']) {
        return null;
    }
    // Get player by auction.current_player_id
    $stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
    $stmt->execute([$auction['current_player_id']]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    return $player ?: null;
}

// Get highest bid for a player
function getHighestBid($playerId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT b.*, t.name AS team_name FROM bids b 
                          JOIN teams t ON b.team_id = t.id 
                          WHERE b.player_id = ? 
                          ORDER BY b.amount DESC LIMIT 1");
    $stmt->execute([$playerId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all bids for a player
function getPlayerBids($playerId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT b.amount, DATE_FORMAT(b.bid_time, '%H:%i') AS bid_time, t.name AS team_name FROM bids b JOIN teams t ON b.team_id = t.id WHERE b.player_id = ? ORDER BY b.bid_time ASC");
    $stmt->execute([$playerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Place a bid
function placeBid($playerId, $teamId, $amount) {
    global $pdo;
    
    // Check if team has enough budget
    $stmt = $pdo->prepare("SELECT remaining_budget FROM teams WHERE id = ?");
    $stmt->execute([$teamId]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($team['remaining_budget'] < $amount) {
        return ['success' => false, 'message' => 'Insufficient budget'];
    }
    
    // Check if bid is higher than current highest
    $highestBid = getHighestBid($playerId);
    if ($highestBid && $amount <= $highestBid['amount']) {
        return ['success' => false, 'message' => 'Bid must be higher than current highest bid'];
    }
    
    // Check team composition
    $composition = validateTeamComposition($teamId);
    if (!$composition['valid']) {
        return ['success' => false, 'message' => $composition['message']];
    }
    
    // Place the bid
    $stmt = $pdo->prepare("INSERT INTO bids (player_id, team_id, amount) VALUES (?, ?, ?)");
    $stmt->execute([$playerId, $teamId, $amount]);
    
    return ['success' => true, 'message' => 'Bid placed successfully'];
}

// Sell player to team
function sellPlayer($playerId, $teamId, $amount) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Update player status
        $stmt = $pdo->prepare("UPDATE players SET status = 'Sold', sold_to = ?, sold_price = ? WHERE id = ?");
        $stmt->execute([$teamId, $amount, $playerId]);
        
        // Update team budget
        $stmt = $pdo->prepare("UPDATE teams SET remaining_budget = remaining_budget - ? WHERE id = ?");
        $stmt->execute([$amount, $teamId]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

// Get team players
function getTeamPlayers($teamId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM players WHERE sold_to = ?");
    $stmt->execute([$teamId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get team statistics
function getTeamStats($teamId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT 
                          COUNT(*) AS total_players,
                          SUM(sold_price) AS total_spent,
                          AVG(sold_price) AS avg_price
                          FROM players WHERE sold_to = ?");
    $stmt->execute([$teamId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Validate team composition
function validateTeamComposition($teamId) {
    global $pdo;
    
    // Get team details
    $stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
    $stmt->execute([$teamId]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get team players
    $teamPlayers = getTeamPlayers($teamId);
    
    // Check max players
    if (count($teamPlayers) >= $team['max_players']) {
        return ['valid' => false, 'message' => 'Team has reached maximum player limit'];
    }
    
    // Check role composition (example: min 4 batsmen, 4 bowlers, 1 wicket-keeper, 2 all-rounders)
    $roleCounts = [
        'Batsman' => 0,
        'Bowler' => 0,
        'All-rounder' => 0,
        'Wicket-keeper' => 0
    ];
    
    foreach ($teamPlayers as $player) {
        $roleCounts[$player['role']]++;
    }
    
    // You can customize these rules as needed
    $roleRules = [
        'Batsman' => ['min' => 4, 'max' => 8],
        'Bowler' => ['min' => 4, 'max' => 8],
        'All-rounder' => ['min' => 2, 'max' => 4],
        'Wicket-keeper' => ['min' => 1, 'max' => 2]
    ];
    
    foreach ($roleRules as $role => $rules) {
        if ($roleCounts[$role] >= $rules['max']) {
            return ['valid' => false, 'message' => "Team has reached maximum limit for $role players"];
        }
    }
    
    // Check overseas players (example: max 4 overseas players)
    $overseasCount = 0;
    $homeCountry = 'India'; // Set home country
    
    foreach ($teamPlayers as $player) {
        if ($player['country'] !== $homeCountry) {
            $overseasCount++;
        }
    }
    
    if ($overseasCount >= 4) {
        return ['valid' => false, 'message' => 'Team has reached maximum limit for overseas players'];
    }
    
    return ['valid' => true, 'message' => 'Team composition is valid'];
}
?>