<?php include 'config.php'; ?>
<?php include 'header.php'; ?>

<div class="container mt-4">

<h2 class="text-center mb-4">🍽️ Our Menu</h2>

<div class="row">

<?php
$result = $conn->query("SELECT * FROM products");

while($row = $result->fetch_assoc()):
?>

<div class="col-md-4 mb-4">
  <div class="card shadow-sm h-100">

    <img src="<?= $row['image'] ?>" class="card-img-top" style="height:200px; object-fit:cover;">

    <div class="card-body text-center">
      <h5><?= $row['name'] ?></h5>
      <p class="text-muted">₱<?= $row['price'] ?></p>

      <button class="btn btn-success" onclick="addToCart(<?= $row['id'] ?>)">
        Add to Cart
      </button>
    </div>

  </div>
</div>

<?php endwhile; ?>

</div>

<a href="cart.php" class="btn btn-dark w-100 mt-3">🛒 View Cart</a>

</div>

<?php include 'footer.php'; ?>
