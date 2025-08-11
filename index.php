<?php
require 'config.php';
if(isset($_SESSION['user_id'])){
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Document System</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .container {
            text-align: center;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 90%;
        }
        .logo {
            width: 150px;
            height: 150px;
            margin-bottom: 20px;
        }
        h2 {
            color: #333;
            margin-bottom: 30px;
        }
        .buttons {
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 30px;
            margin: 0 10px;
            text-decoration: none;
            color: white;
            background-color: #007bff;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="images/brgy-logo.png" alt="Barangay Logo" class="logo">
        <h2>Welcome to Barangay Document System</h2>
        <div class="buttons">
            <a href="login.php" class="btn">Login</a>
        </div>
    </div>
</body>
</html>
