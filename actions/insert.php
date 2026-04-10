<?php
require '../db_connect.php';
require_once '../auth_check.php';

if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

$message ='';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $director = $_POST['director'];
    $releaseyear = $_POST['releaseyear'];
    $duration = $_POST['duration'];
    $rating = $_POST['rating'];

    $sql = "INSERT INTO MOVIE (title, director, releaseyear, duration, rating)
    VALUES ('$title', '$director', '$releaseyear', '$duration', '$rating')";

    if (mysqli_query($conn, $sql)) {
        $message = "Movie added successfully!";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Insert Movie</title>
</head>
<body>

<?php if ($message): ?>
    <p><?= $message ?></p>
<?php endif; ?>

<form method="POST" action="insert.php">
    <p>
        <label>Title</label><br>
        <input type="text" name="title">
    </p>
    <p>
        <label>Director</label><br>
        <input type="text" name="director">
    </p>
    <p>
        <label>Release Year</label><br>
        <input type="number" name="releaseyear">
    </p>
    <p>
        <label>Duration (minutes)</label><br>
        <input type="number" name="duration">
    </p>
    <p>
        <label>Rating</label><br>
        <select name="rating">
            <option value="G">G</option>
            <option value="PG">PG</option>
            <option value="PG-13">PG-13</option>
            <option value="R">R</option>
            <option value="NR">NR</option>
        </select>
    </p>
    <p>
        <button type="submit">Add Movie</button>
    </p>
</form>

</body>
</html>