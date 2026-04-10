<?php
require_once 'auth_check.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Movie Rental - Dashboard</title>
</head>
<body>
    <h2>Welcome, <?php echo $_SESSION['fname'] . " " . $_SESSION['lname']; ?>!</h2>
    <p>You are logged in as: <strong><?php echo $_SESSION['usertype']; ?></strong></p>
    
    <hr>
    
    <h3>Main Menu</h3>
    <ul>
        <li><a href="rentals/rent.php">Browse Movies</a></li>
        // TODO: user profile page
        <li><a href="">My Profile & Rental History</a></li>
        
        <?php if (isAdmin()) { ?>
            // TODO: admin dashboard
            <li><a href="">Admin Dashboard</a></li>
        <?php } ?>
        
        <li><a href="actions/query.php">View All Movies (Test)</a></li>
    </ul>
    
    <hr>
    <a href="logout.php">Logout</a>
</body>
</html>