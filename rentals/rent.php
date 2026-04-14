<?php
require_once '../db_connect.php';
require_once '../auth_check.php';

$pageTitle = "Rent a Movie | Movie Rentals";

$message = '';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movieid = $_POST['movieid'];
    $userid = $_SESSION['user_id'];
    $rentaldate = date('Y-m-d');
    $duedate = date('Y-m-d', strtotime('+7 days'));

    $copyResult = mysqli_query($conn, "SELECT copynumber FROM COPY WHERE movieid = '$movieid' AND isavailable = 1 LIMIT 1");
    $copy = mysqli_fetch_assoc($copyResult);

    if (!$copy) {
        $message = "Sorry, no copies available.";
    } else {
        $copynumber = $copy['copynumber'];
        mysqli_begin_transaction($conn);
        try {
            mysqli_query($conn, "INSERT INTO RENTAL (rentaldate, duedate, userid) VALUES ('$rentaldate', '$duedate', '$userid')");
            $rentalid = mysqli_insert_id($conn);
            mysqli_query($conn, "INSERT INTO CONTAINS (rentalid, copynumber, movieid, quantity) VALUES ('$rentalid', '$copynumber', '$movieid', 1)");
            mysqli_query($conn, "UPDATE COPY SET isavailable = 0 WHERE copynumber = '$copynumber' AND movieid = '$movieid'");
            mysqli_commit($conn);
            $message = "Movie rented successfully! Due back by $duedate.";
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
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = "Something went wrong. Please try again.";
        }
    }
}

include '../includes/header.php';
?>

<body>
    
    <div class="container">
        <h2 class="section-title">Available Movies</h2>

        <?php if ($message): ?>
            <div
                style="background-color: var(--bg-card); padding: 1rem; border-left: 4px solid var(--primary-accent); margin-bottom: 2rem; border-radius: var(--radius-sm);">
                <p><?= htmlspecialchars($message) ?></p>
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

                                <span class="movie-meta-item">Director:
                                    <strong><?= htmlspecialchars($movie['director']) ?></strong></span>
                            </div>

                            <div class="availability-status">
                                <p>Stock: <span style="color: #4caf50; font-weight: bold;"><?= $movie['available_copies'] ?>
                                        Available</span></p>
                            </div>

                            <button type="submit" name="movieid" value="<?= $movie['movieid'] ?>" class="btn btn-primary">Rent
                                This Movie</button>
                        </div>
                    <?php endwhile; ?>
                </div>
            </form>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-muted); padding: 3rem 0;">No movies currently available for
                rent.</p>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>

</body>

</html>