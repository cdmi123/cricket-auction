<?php
require_once '../config/database.php';
require_once '../config/functions.php';

header('Content-Type: application/json');

$auction = getAuction();

if (!$auction) {
    echo json_encode(['time_remaining' => 0, 'auction_status' => 'Not Created']);
    exit;
}

if ($auction['status'] !== 'Live') {
    echo json_encode(['time_remaining' => 0, 'auction_status' => $auction['status']]);
    exit;
}

$currentTime = new DateTime();
$endTime = new DateTime($auction['current_player_end_time']);
$interval = $currentTime->diff($endTime);
$timeRemaining = $interval->s + $interval->i * 60;

echo json_encode([
    'time_remaining' => $timeRemaining > 0 ? $timeRemaining : 0,
    'auction_status' => $auction['status']
]);
?>