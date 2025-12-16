

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
$sql = "
    SELECT username AS rjcode, display_name
    FROM intra_users
    WHERE
        (
            username ILIKE :search
            OR display_name ILIKE :search
        )
        AND (
            username ILIKE 'RJ%'
            OR username ILIKE 'CRT%'
        )
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':search' => "%{$q}%"
]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
?>
