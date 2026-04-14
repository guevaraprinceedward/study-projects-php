<?php include 'config.php'; ?>

<?php
if(!isset($_SESSION['user_id'])){
    header("Location: log-in.php");
    exit;
}

$total = 0;

foreach($_SESSION['cart'] as $id => $qty){
    $res = $conn->query("SELECT * FROM products WHERE id=$id");
    $row = $res->fetch_assoc();
    $total += $row['price'] * $qty;
}

// INSERT ORDER
$conn->query("INSERT INTO orders (user_id,total)
VALUES ({$_SESSION['user_id']}, $total)");

$order_id = $conn->insert_id;

// INSERT ITEMS
foreach($_SESSION['cart'] as $id => $qty){
    $conn->query("INSERT INTO order_items (order_id,product_id,quantity)
    VALUES ($order_id,$id,$qty)");
}

unset($_SESSION['cart']);

echo "<h2>✅ Order placed!</h2>";
echo "<a href='orders.php'>View Orders</a>";