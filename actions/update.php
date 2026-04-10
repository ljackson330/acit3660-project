<?php
require '../db_connect.php';
require_once '../auth_check.php';

if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

$message ='';
$movie = null;

if (isset($_GET['movieid']) && $_GET['movieid'] != '') {
    $id = $_GET['movieid'];
    $result = mysqli_query($conn, "SELECT * FROM MOVIE WHERE movieid = '$id'");
    $movie = mysqli_fetch_assoc($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['movieid'];
    $title = $_POST['title'];
    $director = $_POST['director'];
    $releaseyear = $_POST['releaseyear'];
    $duration = $_POST['duration'];
    $rating = $_POST['rating'];

    $sql = "UPDATE MOVIE SET title='$title', director='$director', releaseyear='$releaseyear', duration='$duration', rating='$rating' WHERE movieid=$id";

    if (mysqli_query($conn, $sql)) {
        $message = "Movie updated successfully!";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Movie</title>
</head>
<body>

<h2>Update Movie</h2>

<?php if ($message): ?>
    <p><?= $message ?></p>
<?php endif; ?>

<form method="GET" action="update.php">
    <p>
        <label>Enter Movie ID</label><br>
        <input type="number" name="movieid">
        <button type="submit">Look Up</button>
    </p>
</form>

<?php if ($movie): ?>
<form method="POST" action="update.php">
    <input type="hidden" name="movieid" value="<?= $movie['movieid'] ?>">
    <p>
        <label>Title</label><br>
        <input type="text" name="title" value="<?= $movie['title'] ?>">
    </p>
    <p>
        <label>Director</label><br>
        <input type="text" name="director" value="<?= $movie['director'] ?>">
    </p>
    <p>
        <label>Release Year</label><br>
        <input type="number" name="releaseyear" value="<?= $movie['releaseyear'] ?>">
    </p>
    <p>
        <label>Duration (minutes)</label><br>
        <input type="number" name="duration" value="<?= $movie['duration'] ?>">
    </p>
    <p>
        <label>Rating</label><br>
        <select name="rating">
            <option value="G"    <?= $movie['rating'] === 'G'    ? 'selected' : '' ?>>G</option>
            <option value="PG"   <?= $movie['rating'] === 'PG'   ? 'selected' : '' ?>>PG</option>
            <option value="PG-13"<?= $movie['rating'] === 'PG-13'? 'selected' : '' ?>>PG-13</option>
            <option value="R"    <?= $movie['rating'] === 'R'    ? 'selected' : '' ?>>R</option>
            <option value="NR"   <?= $movie['rating'] === 'NR'   ? 'selected' : '' ?>>NR</option>
        </select>
    </p>

    <button type="submit">Update Movie</button>
</form>
<?php endif; ?>

</body>
</html>
