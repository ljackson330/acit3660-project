<?php
require_once '../db_connect.php';
require_once '../auth_check.php';

$userid = $_SESSION['user_id'];
$pageTitle = "My Rental History | Movie Rentals";

$result = mysqli_query($conn, "
    SELECT R.rentalid, M.title, M.director, M.rating,
           R.rentaldate, R.duedate, R.returndate,
           CASE
               WHEN R.returndate IS NOT NULL THEN 'Returned'
               WHEN R.duedate < CURDATE() AND R.returndate IS NULL THEN 'Overdue'
               ELSE 'Active'
           END AS status
    FROM RENTAL R
    JOIN CONTAINS C ON R.rentalid = C.rentalid
    JOIN MOVIE M ON C.movieid = M.movieid
    WHERE R.userid = '$userid'
    ORDER BY R.rentaldate DESC
");

include '../includes/header.php'; 
?>

<div class="container">
    <h2 class="section-title">My Rental History</h2>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="movie-grid">
            <?php while ($row = mysqli_fetch_assoc($result)): 
                // Determine the CSS class based on status
                $statusClass = 'status-' . strtolower($row['status']);
            ?>
                <div class="movie-card">
                    <div>
                        <div class="movie-details" style="justify-content: space-between; align-items: center;">
                            <span class="rental-id-tag">ID: #<?= $row['rentalid'] ?></span>
                            <span class="status-badge <?= $statusClass ?>"><?= $row['status'] ?></span>
                        </div>
                        
                        <h3 class="movie-title" style="margin-top: 10px;"><?= htmlspecialchars($row['title']) ?></h3>
                        
                        <span class="movie-meta-item">Director: <strong><?= htmlspecialchars($row['director']) ?></strong></span>
                        <span class="movie-meta-item">Rating: <strong><?= htmlspecialchars($row['rating']) ?></strong></span>
                    </div>

                    <div class="availability-status" style="border-top-style: dashed;">
                        <div class="movie-meta-item">Rented: <strong><?= $row['rentaldate'] ?></strong></div>
                        <div class="movie-meta-item">Due: <strong style="<?= $row['status'] == 'Overdue' ? 'color: var(--primary-accent);' : '' ?>"><?= $row['duedate'] ?></strong></div>
                        
                        <?php if ($row['returndate']): ?>
                            <div class="movie-meta-item">Returned: <strong><?= $row['returndate'] ?></strong></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 4rem 0;">
            <p style="color: var(--text-muted); font-size: 1.2rem;">You haven't rented any movies yet.</p>
            <a href="rent.php" class="btn btn-primary" style="margin-top: 1rem;">Browse Movies</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>