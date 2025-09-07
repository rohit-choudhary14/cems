<?php
include "db.php";
session_start();

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request', 'type'=>'error'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rjcode = $_POST['rjcode'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($rjcode) || empty($password)) {
        $response['message'] = 'rjcode and password are required';
        echo json_encode($response);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE rjcode = :rjcode");
        $stmt->execute(['rjcode' => $rjcode]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // ✅ Set session
            $_SESSION['user_rjcode'] = $user['rjcode'];

            $response['success'] = true;
            $response['type'] = 'success';
            $response['message'] = 'Login successful';
        } else {
            $response['message'] = 'Invalid credentials';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

echo json_encode($response);
