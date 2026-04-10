<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    
    // default to regular customer
    $usertype = 'customer';

    $sql = "INSERT INTO USERS (fname, lname, email, phone, password, usertype) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $fname, $lname, $email, $phone, $password, $usertype);

    if ($stmt->execute()) {
        header("Location: login.php?registered=1");
        exit();
    } else {
        $error = "Registration failed. Email may already be taken.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Movie Rental</title>
</head>
<body>
    <h2>Register New Account</h2>
    
    <?php if (isset($error)) echo "<p style='color:red'>$error</p>"; ?>
    <?php if (isset($_GET['registered'])) echo "<p style='color:green'>Registration successful! Please login.</p>"; ?>
    
    <form method="POST">
        <label>First Name:</label><br>
        <input type="text" name="fname" required><br><br>
        
        <label>Last Name:</label><br>
        <input type="text" name="lname" required><br><br>
        
        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>
        
        <label>Phone:</label><br>
        <input type="text" name="phone"><br><br>
        
        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>
        
        <button type="submit">Register</button>
    </form>
    
    <p>Already have an account? <a href="login.php">Login here</a></p>
</body>
</html>