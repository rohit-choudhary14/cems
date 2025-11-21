<?php
$host = 'localhost';
$port = '5432';           
$dbname = 'cems';  
$user = 'postgres'; 
$password = '1234'; 
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
