<?php
require_once '../config/database.php';
require_once '../config/functions.php';
header('Content-Type: application/json');

$player = getCurrentPlayer();
if ($player) {
    echo json_encode(['success' => true, 'player' => $player]);
} else {
    echo json_encode(['success' => false, 'message' => 'No current player found']);
}
