<?php
require_once 'auth_check.php';

$pageTitle = "Dashboard | Movie Rentals";

include 'includes/header.php'; 
?>

<div class="container">
    <div style="margin-bottom: 2rem;">
        <h2 class="section-title">Welcome, <?php echo htmlspecialchars($_SESSION['fname'] . " " . $_SESSION['lname']); ?>!</h2>
        <p style="color: var(--text-muted);">
            Account Type: <span class="badge" style="background: var(--primary-accent); color: white;"><?php echo htmlspecialchars($_SESSION['usertype']); ?></span>
        </p>
    </div>

    <div class="movie-grid">
        <a href="rentals/rent.php" class="movie-card" style="text-align: center; justify-content: center; min-height: 200px;">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">🎬</div>
            <h3 class="movie-title">Browse Movies</h3>
            <p class="movie-meta-item">Explore our catalog and rent new movies.</p>
        </a>

        <a href="rentals/history.php" class="movie-card" style="text-align: center; justify-content: center; min-height: 200px;">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">📜</div>
            <h3 class="movie-title">Rental History</h3>
            <p class="movie-meta-item">View your active rentals and past returns.</p>
        </a>

        <a href="/profile.php" class="movie-card" style="text-align: center; justify-content: center; min-height: 200px;">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">👤</div>
            <h3 class="movie-title">My Profile</h3>
            <p class="movie-meta-item">Manage your account settings</p>
        </a>

        <?php if (isAdmin()): ?>
            <a href="admin/dashboard.php" class="movie-card" style="text-align: center; justify-content: center; min-height: 200px; border-style: dashed;">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🛠️</div>
                <h3 class="movie-title">Admin Dashboard</h3>
                <p class="movie-meta-item">Manage inventory, users, and reports.</p>
            </a>
        <?php endif; ?>

        <a href="actions/queries.php" class="movie-card" style="text-align: center; justify-content: center; min-height: 200px;">
        <div style="font-size: 2.5rem; margin-bottom: 1rem;">📊</div>
            <h3 class="movie-title">Advanced Queries</h3>
            <p class="movie-meta-item">Browse advanced SQL queries.</p>
        </a>

    </div>

</div>

<?php include 'includes/footer.php'; ?>
