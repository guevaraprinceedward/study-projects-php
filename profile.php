<?php include 'config.php'; ?>
<?php include 'header.php'; ?>

<?php
if (!isset($_SESSION['user'])) {
    header("Location: log-in.php");
    exit();
}
?>

<h1>Welcome, <?php echo $_SESSION['user']; ?> 👤</h1>

<p>This is your profile page.</p>

<?php include 'footer.php'; ?>
