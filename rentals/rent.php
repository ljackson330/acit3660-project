<?php
require_once '../db_connect.php';
require_once '../auth_check.php';

$pageTitle = "Rent a Movie | Movie Rentals";

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['movieid'])) {
    $movieid = (int) $_POST['movieid'];
    $userid  = (int) $_SESSION['user_id'];
    $today   = date('Y-m-d');
    $duedate = date('Y-m-d', strtotime('+7 days'));

    $copyResult = mysqli_query($conn, "SELECT copynumber FROM COPY WHERE movieid = $movieid AND isavailable = 1 LIMIT 1");
    $copy = mysqli_fetch_assoc($copyResult);

    if (!$copy) {
        $error = "No copies available.";
    } else {
        $copynumber = $copy['copynumber'];
        mysqli_begin_transaction($conn);
        try {
            $stmt = $conn->prepare("INSERT INTO RENTAL (rentaldate, duedate, userid) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $today, $duedate, $userid);
            $stmt->execute();
            $rentalid = mysqli_insert_id($conn);

            $stmt = $conn->prepare("INSERT INTO CONTAINS (rentalid, copynumber, movieid, quantity) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("iii", $rentalid, $copynumber, $movieid);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE COPY SET isavailable = 0 WHERE copynumber = ? AND movieid = ?");
            $stmt->bind_param("ii", $copynumber, $movieid);
            $stmt->execute();

            mysqli_commit($conn);
            $message = "Rented successfully! Due back by $duedate.";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Something went wrong. Please try again.";
        }
    }
}

// Available movies
$moviesResult = mysqli_query($conn, "
    SELECT M.movieid, M.title, M.director, M.releaseyear, M.rating,
           COUNT(C.copynumber) AS available_copies
    FROM MOVIE M
    JOIN COPY C ON M.movieid = C.movieid
    WHERE C.isavailable = 1
    GROUP BY M.movieid, M.title, M.director, M.releaseyear, M.rating
    HAVING available_copies > 0
    ORDER BY M.title
");

include '../includes/header.php';
?>

<main>
<div class="container">

    <h2 class="section-title">Available Movies</h2>

    <?php if ($message): ?>
        <div style="background-color:var(--bg-card); padding:1rem; border-left:4px solid #28a745; margin-bottom:2rem; border-radius:var(--radius-sm);">
            <p><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background:rgba(229,9,20,0.1); color:var(--primary-accent); border:1px solid var(--primary-accent); padding:12px 16px; border-radius:4px; margin-bottom:1.5rem;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($moviesResult) > 0): ?>
        <form method="POST" action="rent.php">
            <div class="movie-grid">
                <?php while ($movie = mysqli_fetch_assoc($moviesResult)): ?>
                    <div class="movie-card">
                        <div>
                            <div class="movie-details">
                                <span class="badge"><?= htmlspecialchars($movie['rating']) ?></span>
                                <span class="badge"><?= htmlspecialchars($movie['releaseyear']) ?></span>
                            </div>
                            <h3 class="movie-title"><?= htmlspecialchars($movie['title']) ?></h3>
                            <span class="movie-meta-item">Director: <strong><?= htmlspecialchars($movie['director']) ?></strong></span>
                        </div>
                        <div class="availability-status">
                            <p>Stock: <span style="color:#4caf50; font-weight:bold;"><?= $movie['available_copies'] ?> Available</span></p>
                        </div>
                        <button type="submit" name="movieid" value="<?= $movie['movieid'] ?>" class="btn btn-primary">
                            Rent This Movie
                        </button>
                    </div>
                <?php endwhile; ?>
            </div>
        </form>
    <?php else: ?>
        <p style="text-align:center; color:var(--text-muted); padding:3rem 0;">No movies currently available for rent.</p>
    <?php endif; ?>

</div>
</main>

<?php include '../includes/footer.php'; ?>
