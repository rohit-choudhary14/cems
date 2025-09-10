<?php
session_start();
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($_SESSION['user_rjcode'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

if (empty($data['id'])) {
    echo json_encode(["success" => false, "message" => "Missing event ID"]);
    exit;
}

// Get current user ID from rj_code
$stmt = $pdo->prepare("SELECT rjcode FROM users WHERE rjcode = ?");
$stmt->execute([$_SESSION['user_rjcode']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}

$user_id = $currentUser['rjcode'];

$stmt = $pdo->prepare("SELECT created_by FROM events WHERE id = ?");
$stmt->execute([$data['id']]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo json_encode(["success" => false, "message" => "Event not found"]);
    exit;
}

if ($event['created_by'] != $user_id) {
    echo json_encode(["success" => false, "message" => "Only the creator can delete this event"]);
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("DELETE FROM event_invitations WHERE event_id = ?");
    $stmt->execute([$data['id']]);
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = :id OR (created_by = :creator AND id = :id)");
    $stmt->execute([
        ':id' => $data['id'],
        ':creator' => $user_id
    ]);

    $pdo->commit();

    echo json_encode(["success" => true, "message" => "Event deleted for all invitees"]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["success" => false, "message" => "Error deleting event: " . $e->getMessage()]);
}
