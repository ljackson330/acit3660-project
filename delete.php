<?php
require 'db_connect.php';

$message ='';
$movie = null;

if (isset($_GET['movieid']) && $_GET['movieid'] != '') {
    $id = $_GET['movieid'];
    $result = mysqli_query($conn, "SELECT * FROM MOVIE WHERE movieid = '$id'");
    $movie = mysqli_fetch_assoc($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['movieid'];
    mysqli_query($conn, "DELETE FROM BELONGS_TO WHERE movieid = '$id'");
    if (mysqli_query($conn, "DELETE FROM MOVIE WHERE movieid = '$id'")) {
        $message = "Movie deleted successfully!";
        $movie = null;
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete Movie</title>
</head>
<body>

<h2>Delete Movie</h2>

<?php if ($message): ?>
    <p><?= $message ?></p>
<?php endif; ?>

<form method="GET" action="delete.php">
    <p>
        <label>Enter Movie ID</label><br>
        <input type="number" name="movieid">
        <button type="submit">Look Up</button>
    </p>
</form>

<?php if ($movie): ?>
<p>Are you sure you want to delete this movie?</p>
<p><b>ID:</b> <?= $movie['movieid'] ?></p>
<p><b>Title:</b> <?= $movie['title'] ?></p>
<p><b>Director:</b> <?= $movie['director'] ?></p>
<p><b>Year:</b> <?= $movie['releaseyear'] ?></p>

<form method="POST" action="delete.php">
    <input type="hidden" name="movieid" value="<?= $movie['movieid'] ?>">
    <button type="submit">Delete</button>
    <a href="delete.php">Cancel</a>
</form>
<?php endif; ?>

</body>
</html>