<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_rjcode'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}
$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['invite_id'], $data['action'])) {
    echo json_encode(["success" => false, "message" => "Missing parameters"]);
    exit;
}
$inviteId = $data['invite_id'];
$action   = $data['action'];
$userId   = $_SESSION['user_rjcode'];

if (!in_array($action, ['accept', 'reject'])) {
    echo json_encode(["success" => false, "message" => "Invalid action"]);
    exit;
}

$status = $action === 'accept' ? 'accepted' : 'rejected';
$stmt = $pdo->prepare("UPDATE event_invitations 
                       SET status = :status 
                       WHERE id = :id AND invitee_rjcode = :userId");
$stmt->execute([
    "status" => $status,
    "id" => $inviteId,
    "userId" => $userId
]);
if ($stmt->rowCount() > 0) {
    echo json_encode(["success" => true, "message" => "Invitation $status"]);
} else {
    echo json_encode(["success" => false, "message" => "Not authorized or already handled"]);
}
?>
