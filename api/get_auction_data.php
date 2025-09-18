<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$auction = getAuction();
$response = [];

if (!$auction || $auction['status'] !== 'Live') {
    echo json_encode(['auction_status' => $auction ? $auction['status'] : 'Not Created']);
    exit;
}

// Get current player
$currentPlayer = getCurrentPlayer();
if ($currentPlayer) {
    $response['current_player'] = $currentPlayer;

    // Get highest bid
    $highestBid = getHighestBid($currentPlayer['id']);
    if ($highestBid) {
        $response['highest_bid'] = $highestBid;
        $response['highest_bid']['team_id'] = $highestBid['team_id']; // Ensure team_id is present
    }

    // Get all bids
    $allBids = getPlayerBids($currentPlayer['id']);
    $bids = [];
    foreach ($allBids as $bid) {
        $bids[] = [
            'team_name' => $bid['team_name'],
            'amount' => $bid['amount'],
            'time' => date('g:i A', strtotime($bid['bid_time']))
        ];
    }
    $response['bids'] = $bids;
}

// Get recent activity
$stmt = $pdo->query("SELECT b.*, p.name AS player_name, t.name AS team_name 
                    FROM bids b 
                    JOIN players p ON b.player_id = p.id 
                    JOIN teams t ON b.team_id = t.id 
                    ORDER BY b.bid_time DESC LIMIT 10");
$recentBids = $stmt->fetchAll(PDO::FETCH_ASSOC);

$recentActivity = [];
foreach ($recentBids as $bid) {
    $recentActivity[] = [
        'team_name' => $bid['team_name'],
        'player_name' => $bid['player_name'],
        'amount' => $bid['amount'],
        'time' => date('g:i A', strtotime($bid['bid_time']))
    ];
}
$response['recent_activity'] = $recentActivity;

// Get all teams
$allTeams = [];
$teams = getTeams();
foreach ($teams as $team) {
    $teamPlayers = getTeamPlayers($team['id']);
    $allTeams[] = [
        'id' => $team['id'],
        'name' => $team['name'],
        'budget' => $team['budget'],
        'remaining_budget' => $team['remaining_budget'],
        'players_count' => count($teamPlayers)
    ];
}
$response['all_teams'] = $allTeams;

// Get team data if logged in as team
if (isLoggedIn() && isTeam()) {
    $teamId = $_SESSION['team_id'];
    $team = $pdo->query("SELECT * FROM teams WHERE id = $teamId")->fetch(PDO::FETCH_ASSOC);

    if ($team) {
        $teamData = [
            'id' => $team['id'],
            'name' => $team['name'],
            'budget' => $team['budget'],
            'remaining_budget' => $team['remaining_budget']
        ];

        // Get team players
        $teamPlayers = getTeamPlayers($teamId);
        $players = [];
        foreach ($teamPlayers as $player) {
            $players[] = [
                'id' => $player['id'],
                'name' => $player['name'],
                'role' => $player['role'],
                'sold_price' => $player['sold_price']
            ];
        }
        $teamData['players'] = $players;

        $response['team'] = $teamData;
        $response['current_team_id'] = $team['id']; // Add current team id for frontend logic
    }
}

$response['min_bid_increment'] = $auction['min_bid_increment'];
$response['auction_status'] = $auction['status'];

echo json_encode($response);
?>