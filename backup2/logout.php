<?php
require_once 'includes/functions.php';

// Destroy session
session_destroy();

// Redirect to login page
header('Location: /lch/login.php');
exit;
?>