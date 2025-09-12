<?php
const METHOD = 'AES-128-CBC';
const KEY = 'O2qr8hZ+6H7t5cFk';
const TOKEN = "08649D03EB3FE5E253F33A159D2653195FED73B4AD7C7EA2B0";

function encrypt_common_fun($data) {
    $iv = KEY;
    $encrypt = openssl_encrypt($data, METHOD, KEY, OPENSSL_RAW_DATA, $iv);
    return base64_encode($encrypt);
}

function decrypt_common_fun($data) {
    $iv = KEY;
    $data = base64_decode($data);
    return openssl_decrypt($data, METHOD, KEY, OPENSSL_RAW_DATA, $iv);
}

// Get JSON input from frontend
$input = json_decode(file_get_contents('php://input'), true);

// Check input
if (!isset($input['month']) || !isset($input['year'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing month or year']);
    exit;
}

// Prepare data for encryption
$requestPayload = [
    'token' => TOKEN,
    'request_type' => '1',
    'leave_year' => (int)$input['year'],
    'leave_month' => (int)$input['month']
];

$encryptedData = encrypt_common_fun(json_encode($requestPayload));

// Send encrypted request to actual API
$ch = curl_init('http://10.130.8.95/joassessment_api/calendar_api.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['data' => $encryptedData]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['error' => 'API request failed']);
    exit;
}

curl_close($ch);

// Decrypt the response
$decrypted = decrypt_common_fun($response);

// Send decrypted JSON back to frontend
header('Content-Type: application/json');
echo $decrypted;
