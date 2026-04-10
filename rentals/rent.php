<?php
require_once '../db_connect.php';
require_once '../auth_check.php';

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
?>
<!DOCTYPE html>
<html>
<head><title>Rent a Movie</title></head>
<body>

<p><a href="../index.php">Home</a> | <a href="history.php">My Rentals</a> | <a href="../logout.php">Logout</a></p>

<h2>Rent a Movie</h2>

<?php if ($message): ?>
    <p><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<?php if (mysqli_num_rows($moviesResult) > 0): ?>
<form method="POST" action="rent.php">
    <table border="1">
        <tr>
            <th>Title</th><th>Director</th><th>Year</th><th>Rating</th><th>Available Copies</th><th>Action</th>
        </tr>
        <?php while ($movie = mysqli_fetch_assoc($moviesResult)): ?>
        <tr>
            <td><?= htmlspecialchars($movie['title']) ?></td>
            <td><?= htmlspecialchars($movie['director']) ?></td>
            <td><?= htmlspecialchars($movie['releaseyear']) ?></td>
            <td><?= htmlspecialchars($movie['rating']) ?></td>
            <td><?= $movie['available_copies'] ?></td>
            <td><button type="submit" name="movieid" value="<?= $movie['movieid'] ?>">Rent</button></td>
        </tr>
        <?php endwhile; ?>
    </table>
</form>
<?php else: ?>
    <p>No movies available for rent.</p>
<?php endif; ?>

</body>
</html>