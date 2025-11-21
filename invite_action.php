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

if ($action === 'accept') {
    $stmt = $pdo->prepare("UPDATE event_invitations 
                           SET status = 'accepted' 
                           WHERE id = :id AND invitee_rjcode = :userId");
    $stmt->execute([
        "id" => $inviteId,
        "userId" => $userId
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Invitation accepted"]);
    } else {
        echo json_encode(["success" => false, "message" => "Not authorized or already handled"]);
    }

} elseif ($action === 'reject') {
    $stmt = $pdo->prepare("DELETE FROM event_invitations 
                           WHERE id = :id AND invitee_rjcode = :userId");
    $stmt->execute([
        "id" => $inviteId,
        "userId" => $userId
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Invitation rejected and removed"]);
    } else {
        echo json_encode(["success" => false, "message" => "Not authorized or already handled"]);
    }
}
?>
