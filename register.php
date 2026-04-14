<?php
session_start();
require_once 'db_connect.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    
    // default to regular customer
    $usertype = 'customer';

    // Note: Ensure your table name is 'USER' or 'USERS' to match your database schema
    $sql = "INSERT INTO USER (fname, lname, email, phone, password, usertype) 
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Movie Rentals</title>
    <link rel="stylesheet" href="/~fivg3669/acit3660-project/css/styles.css"> 
</head>
<body>

<main style="display: flex; align-items: center; justify-content: center; min-height: 90vh; padding: 2rem 0;">
    <div class="container">
        <div class="movie-card" style="width: 100%; max-width: 500px; margin: 0 auto; padding: 2.5rem;">
            
            <div style="text-align: center; margin-bottom: 2rem;">
                <h1 class="logo" style="margin-bottom: 0.5rem;">Movie Rentals</h1>
                <h2 style="font-size: 1.2rem; color: var(--text-light);">Create New Account</h2>
            </div>
            
            <?php if (isset($error)): ?>
                <div style="background: rgba(229, 9, 20, 0.1); color: var(--primary-accent); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.9rem; text-align: center; border: 1px solid var(--primary-accent);">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="fname" class="form-control" placeholder="John" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="lname" class="form-control" placeholder="Doe" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="(555) 000-0000">
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Create Account</button>
            </form>
            
            <p style="margin-top: 2rem; text-align: center; font-size: 0.9rem; color: var(--text-muted);">
                Already have an account? <a href="login.php" style="color: var(--primary-accent); font-weight: bold;">Login here</a>
            </p>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>