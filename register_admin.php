<?php
require 'config.php';
$conn = db_connect();
$err = '';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $address = trim($_POST['address']);

    if (!$name || !$email || !$pass || !$address) {
        $err = 'All fields are required.';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, address, is_verified, role) VALUES (?,?,?,?,1,'admin')");
        $stmt->bind_param('ssss', $name, $email, $hash, $address);
        if ($stmt->execute()) {
            $msg = 'Admin account created! You can now log in.';
        } else {
            $err = 'Error: ' . $conn->error;
        }
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Register Admin</title>
</head>
<body>
<h2>Create Admin Account</h2>
<?php if ($err) echo "<p style='color:red'>$err</p>"; ?>
<?php if ($msg) echo "<p style='color:green'>$msg</p>"; ?>
<form method="post">
    Full Name:<br><input name="full_name"><br>
    Email:<br><input type="email" name="email"><br>
    Password:<br><input type="password" name="password"><br>
    Address:<br><input name="address"><br><br>
    <button type="submit">Register Admin</button>
</form>
<p><a href="login.php">Go to Login</a></p>
</body>
</html>
