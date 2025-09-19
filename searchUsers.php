<?php
session_start();
include 'db2.php';
if (!isset($_SESSION['user_rjcode'])) {
  echo json_encode([]);
  exit;
}
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT username AS rjcode FROM intra_users WHERE username LIKE ? LIMIT 10");

$searchTerm = "%$q%";
$stmt->execute([$searchTerm]);

$results = $stmt->fetchAll();

echo json_encode($results);

?>