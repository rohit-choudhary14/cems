<?php
$host = 'localhost';      // e.g. localhost
$port = '5432';           // default PostgreSQL port
$dbname = 'cems';  // your database name
$user = 'postgres';  // your DB username
$password = '1234'; // your DB password

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
