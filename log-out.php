<?php
include 'config.php';

session_destroy();
header("Location: log-in.php");
exit();
?>
