<?php
require_once 'config.php';
require_once 'functions/helpers.php';  // ADD THIS LINE

// Clear session
session_unset();
session_destroy();

// Redirect to home page
redirect('index.php');
?>
