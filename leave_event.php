<?php
include "db.php";
session_start();

$data = json_decode(file_get_contents("php://input"), true);
$eventId = $data['event_id'];
$currentRjcode = $_SESSION['user_rjcode'];

try {
    $stmt = $pdo->prepare("DELETE FROM event_invitations WHERE event_id = ? AND invitee_rjcode = ?");
    $stmt->execute([$eventId, $currentRjcode]);

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
