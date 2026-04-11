<?php include 'config.php'; ?>
<?php include 'header.php'; ?>

<div class="container mt-4">

<h3>Order History</h3>

<?php
$result = $conn->query("SELECT * FROM orders WHERE user_id={$_SESSION['user_id']}");

while($order = $result->fetch_assoc()):
?>

<div class="card mb-3 p-3">
  <b>Order #<?= $order['id'] ?></b><br>
  Total: ₱<?= $order['total'] ?><br>
  Date: <?= $order['created_at'] ?>
</div>

<?php endwhile; ?>

</div>

<?php include 'footer.php'; ?>