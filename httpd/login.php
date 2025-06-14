<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "hameed", "hameed123", "bank");
    if ($conn->connect_error) die("Connection failed");

    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashedPassword);
    if ($stmt->fetch() && $password === $hashedPassword) {
        $_SESSION['user_id'] = $id;
        header("Location: balance.php");
    } else {
        echo "Invalid credentials.";
    }
}
?>

<form method="post">
    Username: <input type="text" name="username"><br>
    Password: <input type="password" name="password"><br>
    <input type="submit" value="Login">
</form>
