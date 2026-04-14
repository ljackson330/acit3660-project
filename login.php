<?php
session_start();
require_once 'db_connect.php';

// If already logged in, kick them to the dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT userid, fname, lname, email, usertype 
            FROM USER 
            WHERE email = ? AND password = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        $_SESSION['user_id'] = $user['userid'];
        $_SESSION['fname'] = $user['fname'];
        $_SESSION['lname'] = $user['lname'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['usertype'] = $user['usertype'];

        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid email or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Movie Rentals</title>
    <link rel="stylesheet" href="/acit3660-project/css/styles.css"> 
</head>
<body>

<main style="display: flex; align-items: center; justify-content: center; min-height: 80vh;">
    <div class="container">
        <div class="movie-card" style="width: 100%; max-width: 400px; margin: 0 auto; padding: 2.5rem;">
            
            <div style="text-align: center; margin-bottom: 2rem;">
                <h1 class="logo" style="margin-bottom: 0.5rem;">Movie Rentals</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Please sign in to your account</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div style="background: rgba(229, 9, 20, 0.1); color: var(--primary-accent); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.9rem; text-align: center; border: 1px solid var(--primary-accent);">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="email@example.com" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Sign In</button>
            </form>
            
            <p style="margin-top: 2rem; text-align: center; font-size: 0.9rem; color: var(--text-muted);">
                Don't have an account? <a href="register.php" style="color: var(--primary-accent); font-weight: bold;">Register here</a>
            </p>
        </div>
    </div>
</main>

<?php 
include 'includes/footer.php'; 
?>