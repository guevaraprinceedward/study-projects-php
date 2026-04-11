<?php include 'config.php'; ?>
<?php include 'header.php'; ?>

<div class="container mt-4">

<h3>Your Cart</h3>

<?php
$total = 0;

if(!empty($_SESSION['cart'])):

foreach($_SESSION['cart'] as $id => $qty):

$res = $conn->query("SELECT * FROM products WHERE id=$id");
$row = $res->fetch_assoc();

$subtotal = $row['price'] * $qty;
$total += $subtotal;
?>

<div class="card mb-2 p-3">
  <b><?= $row['name'] ?></b><br>
  Qty: <?= $qty ?> <br>
  Total: ₱<?= $subtotal ?>

  <a href="cart.php?remove=<?= $id ?>" class="btn btn-danger btn-sm mt-2">Remove</a>
</div>

<?php endforeach; ?>

<h4>Total: ₱<?= $total ?></h4>

<a href="checkout.php" class="btn btn-success w-100">Checkout</a>

<?php else: ?>
<p>No items in cart</p>
<?php endif; ?>

</div>

<?php include 'footer.php'; ?>
