<?php
header('Content-Type: application/json');
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_rjcode'])) {
    echo json_encode(["status" => "error", "message" => "Not authenticated"]);
    exit;
}

$stmt = $pdo->prepare("SELECT rjcode FROM users WHERE rjcode = ?");
$stmt->execute([$_SESSION['user_rjcode']]);
$creator = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$creator) {
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit;
}

try {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input) {
        $input = $_POST;
    }

    if (!empty($input['invite_rjcode'])) {
        if (is_array($input['invite_rjcode'])) {
            // Multiple invites → store as comma-separated string
            $invite_rjcode = implode(",", array_map('trim', $input['invite_rjcode']));
        } else {
            // Single invite
            $invite_rjcode = trim($input['invite_rjcode']);
        }
    } else {
        $invite_rjcode = null;
    }
    if (empty($input['title']) || empty($input['start']) || empty($input['end'])) {
        throw new Exception("Missing required fields: title, start, or end date.");
    }
    $reminder_before = isset($input['reminder']) ? (int) $input['reminder'] : 10;
    $repeat_frequency = isset($input['repeat_frequency']) && $input['repeat_frequency'] !== 'none'
        ? $input['repeat_frequency']
        : null;
    $is_repeating = (!empty($input['is_repeating']) && $repeat_frequency !== null) ? 1 : 0;
    if (!$is_repeating) {
        $repeat_frequency = null;
    }

    if (empty($input['id'])) {
        $sql = "INSERT INTO events 
                (title, start_date, end_date, description, priority, user_id, created_by,
                 reminder_before, is_repeating, repeat_frequency) 
                VALUES (:title, :start_date, :end_date, :description, :priority, :user_id, :created_by,
                        :reminder_before, :is_repeating, :repeat_frequency)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title' => $input['title'],
            ':start_date' => $input['start'],
            ':end_date' => $input['end'],
            ':description' => $input['description'] ?? null,
            ':priority' => $input['priority'] ?? 'low',
            ':user_id' => $creator['rjcode'],
            ':created_by' => $creator['rjcode'],
            ':reminder_before' => $reminder_before,
            ':is_repeating' => $is_repeating,
            ':repeat_frequency' => $repeat_frequency
        ]);

        $eventId = $pdo->lastInsertId();
        if (!empty($invite_rjcode)) {
            $invitees = is_array($invite_rjcode) ? $invite_rjcode : explode(',', $invite_rjcode);

            foreach ($invitees as $code) {
                $code = trim($code);
                if (empty($code) || $code === $creator['rjcode']) {
                    continue;
                }
                $stmt = $pdo->prepare("SELECT rjcode FROM users WHERE rjcode = ?");
                $stmt->execute([$code]);
                $invitee = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($invitee) {
                    $stmt = $pdo->prepare("
                INSERT INTO event_invitations (event_id, inviter_rjcode, invitee_rjcode, status) 
                VALUES (?, ?, ?, 'pending')
            ");
                    $stmt->execute([$eventId, $creator['rjcode'], $invitee['rjcode']]);
                }
            }
        }
        echo json_encode([
            "status" => "success",
            "message" => "Event created successfully",
            "eventId" => $eventId
        ]);
        exit;

    } else {
        $eventId = $input['id'];
        $stmt = $pdo->prepare("SELECT user_id FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event || $event['user_id'] != $creator['rjcode']) {
            throw new Exception("You don't have permission to update this event.");
        }

        $sql = "UPDATE events 
                SET title = :title, start_date = :start_date, end_date = :end_date, 
                    description = :description, priority = :priority,
                    reminder_before = :reminder_before,
                    is_repeating = :is_repeating,
                    repeat_frequency = :repeat_frequency
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $eventId,
            ':title' => $input['title'],
            ':start_date' => $input['start'],
            ':end_date' => $input['end'],
            ':description' => $input['description'] ?? null,
            ':priority' => $input['priority'] ?? 'low',
            ':reminder_before' => $reminder_before,
            ':is_repeating' => $is_repeating,
            ':repeat_frequency' => $repeat_frequency
        ]);
        if (!empty($invite_rjcode)) {
            $invitees = is_array($invite_rjcode) ? $invite_rjcode : explode(',', $invite_rjcode);
            foreach ($invitees as $code) {
                $code = trim($code);
                if (empty($code) || $code === $creator['rjcode']) {
                    continue;
                }
                $stmt = $pdo->prepare("SELECT rjcode FROM users WHERE rjcode = ?");
                $stmt->execute([$code]);
                $invitee = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($invitee) {
                    $stmt = $pdo->prepare("
                INSERT INTO event_invitations (event_id, inviter_rjcode, invitee_rjcode, status) 
                VALUES (?, ?, ?, 'pending')
            ");
                    $stmt->execute([$eventId, $creator['rjcode'], $invitee['rjcode']]);
                }
            }
        }


        echo json_encode([
            "status" => "success",
            "message" => "Event updated successfully",
            "eventId" => $eventId
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
