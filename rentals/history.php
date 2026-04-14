<?php
require_once '../db_connect.php';
require_once '../auth_check.php';

$pageTitle = "My Rental History | Movie Rentals";

$userid  = $_SESSION['user_id'];
$message = '';
$error   = '';

// Handle return
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rentalid'])) {
    $rentalid = (int) $_POST['rentalid'];

    $rental = mysqli_fetch_assoc($conn->query("
        SELECT R.returndate, R.duedate, C.copynumber, C.movieid
        FROM RENTAL R
        JOIN CONTAINS C ON R.rentalid = C.rentalid
        WHERE R.rentalid = $rentalid AND R.userid = $userid
        LIMIT 1
    "));

    if (!$rental) {
        $error = "Rental not found.";
    } elseif ($rental['returndate'] !== null) {
        $error = "This rental has already been returned.";
    } else {
        $today      = date('Y-m-d');
        $days_late  = max(0, (int) ((strtotime($today) - strtotime($rental['duedate'])) / 86400));
        $amount     = round(3.99 + ($days_late * 1.00), 2);
        $copynumber = $rental['copynumber'];
        $movieid    = $rental['movieid'];

        mysqli_begin_transaction($conn);
        try {
            mysqli_query($conn, "UPDATE RENTAL SET returndate = '$today' WHERE rentalid = $rentalid");
            mysqli_query($conn, "UPDATE COPY SET isavailable = 1 WHERE copynumber = $copynumber AND movieid = $movieid");
            $stmt = $conn->prepare("INSERT INTO PAYMENT (amount, paydate, userid, rentalid) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("dsii", $amount, $today, $userid, $rentalid);
            $stmt->execute();
            mysqli_commit($conn);
            $message = "Returned successfully. $" . number_format($amount, 2) . " charged.";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Something went wrong. Please try again.";
        }
    }
}

// Fetch rental history
$stmt = $conn->prepare("
    SELECT R.rentalid, M.title, M.director, M.rating,
           R.rentaldate, R.duedate, R.returndate,
           ROUND(3.99 + (GREATEST(0, DATEDIFF(COALESCE(R.returndate, CURDATE()), R.duedate)) * 1.00), 2) AS charge,
           CASE
               WHEN R.returndate IS NOT NULL                       THEN 'Returned'
               WHEN R.duedate < CURDATE() AND R.returndate IS NULL THEN 'Overdue'
               ELSE 'Active'
           END AS status,
           DATEDIFF(CURDATE(), R.duedate) AS days_late
    FROM RENTAL R
    JOIN CONTAINS C ON R.rentalid = C.rentalid
    JOIN MOVIE M    ON C.movieid  = M.movieid
    WHERE R.userid = ?
    ORDER BY R.rentaldate DESC
");
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();

include '../includes/header.php';
?>

<main>
<div class="container">

    <h2 class="section-title">My Rentals</h2>

    <?php if ($message): ?>
        <div style="background:rgba(40,167,69,0.1); color:#28a745; border:1px solid #28a745; padding:12px 16px; border-radius:4px; margin-bottom:1.5rem;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background:rgba(229,9,20,0.1); color:var(--primary-accent); border:1px solid var(--primary-accent); padding:12px 16px; border-radius:4px; margin-bottom:1.5rem;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="movie-grid">
        <?php while ($row = mysqli_fetch_assoc($result)):
            $statusClass   = 'status-' . strtolower($row['status']);
            $is_returnable = $row['status'] === 'Active' || $row['status'] === 'Overdue';
            $days_late     = max(0, (int) $row['days_late']);
            $charge        = number_format($row['charge'], 2);
        ?>
            <div class="movie-card">
                <div>
                    <div class="movie-details" style="justify-content:space-between; align-items:center;">
                        <span style="color:var(--text-muted); font-size:0.8rem;">ID: #<?= $row['rentalid'] ?></span>
                        <span class="status-badge <?= $statusClass ?>"><?= $row['status'] ?></span>
                    </div>

                    <h3 class="movie-title" style="margin-top:10px;"><?= htmlspecialchars($row['title']) ?></h3>
                    <span class="movie-meta-item">Director: <strong><?= htmlspecialchars($row['director']) ?></strong></span>
                    <span class="movie-meta-item">Rating: <strong><?= htmlspecialchars($row['rating']) ?></strong></span>
                </div>

                <div class="availability-status" style="border-top-style:dashed;">
                    <div class="movie-meta-item">Rented: <strong><?= $row['rentaldate'] ?></strong></div>
                    <div class="movie-meta-item">
                        Due: <strong style="<?= $row['status'] === 'Overdue' ? 'color:var(--primary-accent)' : '' ?>">
                            <?= $row['duedate'] ?>
                        </strong>
                    </div>

                    <?php if ($row['returndate']): ?>
                        <div class="movie-meta-item">Returned: <strong><?= $row['returndate'] ?></strong></div>
                    <?php endif; ?>

                    <?php if ($row['status'] === 'Overdue'): ?>
                        <div class="movie-meta-item" style="margin-top:0.5rem; color:var(--primary-accent); font-size:0.85rem;">
                            <?= $days_late ?> day(s) overdue &mdash; charge: <strong>$<?= $charge ?></strong>
                        </div>
                    <?php elseif ($row['status'] === 'Active'): ?>
                        <div class="movie-meta-item" style="margin-top:0.5rem; font-size:0.85rem; color:var(--text-muted);">
                            Charge on return: <strong>$<?= $charge ?></strong>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($is_returnable): ?>
                <form method="POST" style="margin-top:1rem;">
                    <input type="hidden" name="rentalid" value="<?= $row['rentalid'] ?>">
                    <button type="submit" class="btn btn-primary" style="width:100%;"
                        onclick="return confirm('Return \'<?= htmlspecialchars(addslashes($row['title'])) ?>\'? You will be charged $<?= $charge ?>.')">
                        Return &amp; Pay $<?= $charge ?>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
        </div>

    <?php else: ?>
        <div style="text-align:center; padding:4rem 0;">
            <p style="color:var(--text-muted); font-size:1.2rem;">You haven't rented any movies yet.</p>
            <a href="rent.php" class="btn btn-primary" style="margin-top:1rem;">Browse Movies</a>
        </div>
    <?php endif; ?>

</div>
</main>

<?php include '../includes/footer.php'; ?>
