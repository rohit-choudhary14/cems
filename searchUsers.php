

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

// Case-insensitive search by username or display_name
$stmt = $pdo->prepare("
    SELECT username AS rjcode, display_name
    FROM intra_users
    WHERE LOWER(username) LIKE LOWER(:search)
       OR LOWER(display_name) LIKE LOWER(:search)

");

$searchTerm = "%$q%";
$stmt->execute(['search' => $searchTerm]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
?>
