<?php
session_start();
include 'db.php';

// Make sure user is logged in
if (!isset($_SESSION['user_rjcode'])) {
  echo json_encode([]);
  exit;
}

$user_rjcode = $_SESSION['user_rjcode'];

// Get current user id from rjcode
$stmt = $pdo->prepare("SELECT rjcode FROM users WHERE rjcode = ?");
$stmt->execute([$user_rjcode]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
  echo json_encode([]);
  exit;
}

$user_id = $currentUser['rjcode'];
$query ="
  SELECT 
    e.id, 
    e.title, 
    e.start_date, 
    e.end_date, 
    e.description, 
    e.priority, 
    e.user_id,
    e.reminder_before,
    e.repeat_frequency,
    e.is_repeating,
    COALESCE(string_agg(ei.invitee_rjcode, ','), '') AS invitees
  FROM events e
  LEFT JOIN event_invitations ei 
         ON e.id = ei.event_id 
        AND ei.status IN ('accepted', 'pending')  
  WHERE e.user_id = :uid
  GROUP BY e.id, e.title, e.start_date, e.end_date, e.description, 
           e.priority, e.user_id, e.reminder_before, e.repeat_frequency, e.is_repeating

  UNION

  SELECT 
    e.id, 
    e.title, 
    e.start_date, 
    e.end_date, 
    e.description, 
    e.priority, 
    e.user_id,
    e.reminder_before,
    e.repeat_frequency,
    e.is_repeating,
    COALESCE(string_agg(ei2.invitee_rjcode, ','), '') AS invitees
  FROM events e
  JOIN event_invitations ei 
       ON e.id = ei.event_id 
      AND ei.status IN ('accepted', 'pending') 
  LEFT JOIN event_invitations ei2 
       ON e.id = ei2.event_id 
      AND ei2.status IN ('accepted', 'pending') 
  WHERE ei.invitee_rjcode = :uid
  GROUP BY e.id, e.title, e.start_date, e.end_date, e.description, 
           e.priority, e.user_id, e.reminder_before, e.repeat_frequency, e.is_repeating
";



$stmt = $pdo->prepare($query);
$stmt->execute(['uid' => $user_id]);

$events = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $events[] = [
    'id' => $row['id'],
    'title' => $row['title'],
    'start' => $row['start_date'],
    'end' => $row['end_date'],
    'description' => $row['description'],
    'priority' => $row['priority'],
    'user_id' => $row['user_id'],
    'reminder_before' => $row['reminder_before'],
    'repeat_frequency' => $row['repeat_frequency'],
    'is_repeating' => $row['is_repeating'],
    'invitees' => $row['invitees'] ? explode(",", $row['invitees']) : [] // array for frontend

  ];
}

echo json_encode($events);
?>