<?php
session_start();
$_SESSION = [];
session_destroy();
header("Location: http://10.130.8.68/intrahc/logout.php");
exit;
?>
