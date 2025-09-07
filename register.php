<?php
include "db.php";
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
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE rjcode = :rjcode");
        $checkStmt->execute(['rjcode' => $rjcode]);
        $exists = $checkStmt->fetchColumn();

        if ($exists) {
            $response['type'] = 'info';
            $response['message'] = 'User already exists';
            echo json_encode($response);
            exit;
        }
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $insertStmt = $pdo->prepare("INSERT INTO users (rjcode, password) VALUES (:rjcode, :password)");
        $insertStmt->execute([
            'rjcode' => $rjcode,
            'password' => $passwordHash
        ]);
          $response['type'] = 'success';
        $response['success'] = true;
        $response['message'] = 'Registration successful';

    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>
