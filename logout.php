<?php
session_start();

// Destroy all session variables
session_unset();
session_destroy();

// Redirect to main page
header('Location: index.html');
exit;
?>