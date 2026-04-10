<?php
require 'db_connect.php';
require_once 'auth_check.php';

$sql = "SELECT * FROM MOVIE";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Query Movies</title>
</head>
<body>

<h2>All Movies</h2>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Director</th>
        <th>Release Year</th>
        <th>Duration (minutes)</th>
        <th>Rating</th>
    </tr>

    <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?= $row['movieid'] ?></td>
            <td><?= $row['title'] ?></td>
            <td><?= $row['director'] ?></td>
            <td><?= $row['releaseyear'] ?></td>
            <td><?= $row['duration'] ?></td>
            <td><?= $row['rating'] ?></td>
        </tr>
    <?php endwhile; ?>
</table>

</body>
</html>