<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_rjcode'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['invite_id'])) {
    echo json_encode(["success" => false, "message" => "Missing invitation ID"]);
    exit;
}
$userId = $_SESSION['user_rjcode'];
$stmt = $pdo->prepare("DELETE FROM event_invitations WHERE id = :id AND inviter_rjcode = :userId");
$stmt->execute([
    "id" => $data['invite_id'],
    "userId" => $userId
]);

if ($stmt->rowCount() > 0) {
    echo json_encode(["success" => true, "message" => "Invitation cancelled"]);
} else {
    echo json_encode(["success" => false, "message" => "Not authorized or invite not found"]);
}
?>
