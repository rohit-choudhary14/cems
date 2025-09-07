<?php
include "db.php";

$frontend_origin = "http://127.0.0.1:5500"; // Your frontend origin URL

header("Access-Control-Allow-Origin: $frontend_origin");
header('Content-Type: application/json'); 
header("Access-Control-Allow-Credentials: true");  
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

$response = ['success' => false];

if (isset($_SESSION['user_email'])) {
    $response['success'] = true;
    $response['user'] = ['email' => $_SESSION['user_email']];
} elseif (isset($_COOKIE['user_email'])) {
    try {
        $stmt = $pdo->prepare("SELECT email FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $_COOKIE['user_email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $response['success'] = true;
            $response['user'] = ['email' => $user['email']];
        }
    } catch (PDOException $e) {
        $response['message'] = "Database error: " . $e->getMessage();
    }
}

echo json_encode($response);
?>
