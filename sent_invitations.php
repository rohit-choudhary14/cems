<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_rjcode'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$userId = $_SESSION['user_rjcode'];

$stmt = $pdo->prepare("
    SELECT ei.id, e.title AS event_title, u.rj_code AS invitee_rjcode, ei.status, ei.created_at
    FROM event_invitations ei
    JOIN events e ON ei.event_id = e.id
    JOIN users u ON ei.invitee_id = u.id
    WHERE ei.inviter_id = :userId
    ORDER BY ei.created_at DESC
");
$stmt->execute(["userId" => $userId]);
$invites = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["success" => true, "data" => $invites]);
