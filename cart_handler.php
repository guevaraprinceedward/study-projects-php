<?php
session_start();

if(isset($_GET['add'])){
    $id = $_GET['add'];
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
}

if(isset($_GET['remove'])){
    unset($_SESSION['cart'][$_GET['remove']]);
}

header("Location: cart.php");
