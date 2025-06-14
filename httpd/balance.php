<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "hameed", "hameed123", "bank");
if ($conn->connect_error) die("Connection failed");

$stmt = $conn->prepare("SELECT username, balance FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($username, $balance);
$stmt->fetch();

echo "<h1>Welcome, $username</h1>";
echo "<p>Your balance is: ₹$balance</p>";
?>

<form action="logout.php" method="post">
    <button type="submit">Logout</button>
</form>
