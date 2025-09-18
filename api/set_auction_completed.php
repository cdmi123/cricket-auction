<?php
require_once '../config/database.php';
header('Content-Type: application/json');

// Get current auction
require_once '../config/functions.php';
$auction = getAuction();
if ($auction) {
	$stmt = $pdo->prepare("UPDATE auction SET status = 'Completed' WHERE id = ?");
	$stmt->execute([$auction['id']]);
	echo json_encode(['success' => true]);
} else {
	echo json_encode(['success' => false, 'message' => 'No auction found.']);
}
