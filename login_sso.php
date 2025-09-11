<?php
session_start();
function encrypt_common_fun($data)
{
    $method = 'AES-128-CBC';
    $key = '084s@yb3z0j2l2#X';

    $iv = $key;
    $encrypt = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);
    $EncryptTxt = base64_encode($encrypt);
    return $EncryptTxt;
}
function decrypt_common_fun($data)
{
    $method = 'AES-128-CBC';
    $key = '084s@yb3z0j2l2#X';
    $iv = $key;
    $data = base64_decode($data);
    $decrypt = openssl_decrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);
    return $decrypt;
}
$token = $_POST['token'];
$data = [
    'TokSe' => $token,
    'type' => '1'
];

$encryptedData = encrypt_common_fun(json_encode($data));
$url = "http://10.130.8.68/intrahc/";
$apiUrl = "http://10.130.8.68/api/ValidateToken.php";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['data' => $encryptedData]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    $error = curl_error($ch);
    curl_close($ch);
    echo json_encode(['success' => false, 'message' => 'cURL Error: ' . $error]);
    exit;
}

curl_close($ch);
$decrypted = decrypt_common_fun($response);

$responseData = json_decode($decrypted, true);
if (!isset($responseData['result']) || !is_array($responseData['result'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid response from API']);
    header("location:$url");
    exit;
} 
$result = $responseData['result'];

$_SESSION['user_rjcode'] = $result['username'];
$_SESSION['user_name'] = $result['display_name'];

$_SESSION['success'] = true;
$_SESSION['type'] = 'success';
$_SESSION['message'] = 'Login successful';

header('location:profile.php');
