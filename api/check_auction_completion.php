<?php
require_once '../config/database.php';
header('Content-Type: application/json');

require_once '../config/functions.php';
$auction = getAuction();
if ($auction) {
    // Count players whose status is not 'Sold'
    $stmt = $pdo->prepare("SELECT COUNT(*) as not_sold_count FROM players WHERE status != 'Sold'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['not_sold_count'] == 0) {
        // Mark auction as completed for current auction
        $stmt = $pdo->prepare("UPDATE auction SET status = 'Completed' WHERE id = ?");
        $stmt->execute([$auction['id']]);
        echo json_encode(['completed' => true]);
    } else {
        echo json_encode(['completed' => false]);
    }
} else {
    echo json_encode(['completed' => false, 'message' => 'No auction found.']);
}
