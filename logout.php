<?php
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

header("Location: /cems/");
exit;
?>
