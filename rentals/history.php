<?php
require_once '../db_connect.php';
require_once '../auth_check.php';

$userid = $_SESSION['user_id'];

$result = mysqli_query($conn, "
    SELECT R.rentalid, M.title, M.director, M.rating,
           R.rentaldate, R.duedate, R.returndate,
           CASE
               WHEN R.returndate IS NOT NULL THEN 'Returned'
               WHEN R.duedate < CURDATE() THEN 'Overdue'
               ELSE 'Active'
           END AS status
    FROM RENTAL R
    JOIN CONTAINS C ON R.rentalid = C.rentalid
    JOIN MOVIE M ON C.movieid = M.movieid
    WHERE R.userid = '$userid'
    ORDER BY R.rentaldate DESC
");
?>
<!DOCTYPE html>
<html>
<head><title>My Rental History</title></head>
<body>

<p><a href="../index.php">Home</a> | <a href="../logout.php">Logout</a></p>

<h2>My Rental History</h2>

<?php if (mysqli_num_rows($result) > 0): ?>
<table border="1">
    <tr>
        <th>Rental ID</th>
        <th>Title</th>
        <th>Director</th>
        <th>Rating</th>
        <th>Rental Date</th>
        <th>Due Date</th>
        <th>Return Date</th>
        <th>Status</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
    <tr>
        <td><?= $row['rentalid'] ?></td>
        <td><?= htmlspecialchars($row['title']) ?></td>
        <td><?= htmlspecialchars($row['director']) ?></td>
        <td><?= htmlspecialchars($row['rating']) ?></td>
        <td><?= $row['rentaldate'] ?></td>
        <td><?= $row['duedate'] ?></td>
        <td><?= $row['returndate'] ?? '-' ?></td>
        <td><?= $row['status'] ?></td>
    </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
    <p>You have no rental history.</p>
<?php endif; ?>

</body>
</html>