<?php include 'config.php'; ?>

<?php
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $_SESSION['user'] = $username;
        header("Location: profile.php");
    } else {
        echo "Invalid login!";
    }
}
?>

<form method="POST">
    <h2>Login</h2>
    <input type="text" name="username" required><br>
    <input type="password" name="password" required><br>
    <button type="submit" name="login">Login</button>
</form>

<a href="register.php">Register</a>
