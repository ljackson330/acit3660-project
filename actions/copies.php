<?php
require_once '../db_connect.php';
require_once '../auth_check.php';

if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

$pageTitle = "Manage Copies | Movie Rentals";
$message = '';
$error   = '';

// Handle add / delete copy
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($_POST['action'] === 'add') {
        $movieid   = (int) $_POST['movieid'];
        $condition = in_array($_POST['condition'], ['Good','Fair','Poor']) ? $_POST['condition'] : 'Good';

        // Get next copynumber for this movie
        $res = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT COALESCE(MAX(copynumber), 0) + 1 AS next_copy
            FROM COPY WHERE movieid = '$movieid'
        "));
        $next = (int) $res['next_copy'];

        $stmt = $conn->prepare("INSERT INTO COPY (copynumber, movieid, `condition`, isavailable) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("iis", $next, $movieid, $condition);
        if ($stmt->execute()) {
            $message = "Copy #{$next} added successfully.";
        } else {
            $error = "Failed to add copy.";
        }

    } elseif ($_POST['action'] === 'delete') {
        $copynumber = (int) $_POST['copynumber'];
        $movieid    = (int) $_POST['movieid'];

        // Check not currently rented out
        $check = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT isavailable FROM COPY
            WHERE copynumber = '$copynumber' AND movieid = '$movieid'
        "));

        if (!$check) {
            $error = "Copy not found.";
        } elseif (!$check['isavailable']) {
            $error = "Cannot delete a copy that is currently rented out.";
        } else {
            // Remove from CONTAINS history first if any returned rentals reference it
            mysqli_query($conn, "
                DELETE FROM CONTAINS
                WHERE copynumber = '$copynumber' AND movieid = '$movieid'
                AND rentalid IN (SELECT rentalid FROM RENTAL WHERE returndate IS NOT NULL)
            ");
            $stmt = $conn->prepare("DELETE FROM COPY WHERE copynumber = ? AND movieid = ?");
            $stmt->bind_param("ii", $copynumber, $movieid);
            if ($stmt->execute()) {
                $message = "Copy deleted successfully.";
            } else {
                $error = "Failed to delete copy: " . $conn->error;
            }
        }
    }
}

// Fetch all movies with copy info
$movies = mysqli_query($conn, "
    SELECT M.movieid, M.title, M.director, M.releaseyear,
           COUNT(C.copynumber)  AS total_copies,
           SUM(C.isavailable)   AS available_copies
    FROM MOVIE M
    LEFT JOIN COPY C ON M.movieid = C.movieid
    GROUP BY M.movieid, M.title, M.director, M.releaseyear
    ORDER BY M.title
");

include '../includes/header.php';
?>

<main>
<div class="container">

    <h2 class="section-title">Manage Copies</h2>

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

    <?php while ($movie = mysqli_fetch_assoc($movies)): ?>
    <div class="movie-card" style="margin-bottom:1.5rem; padding:1.5rem;">

        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem;">
            <div>
                <h3 class="movie-title" style="font-size:1.1rem;"><?= htmlspecialchars($movie['title']) ?></h3>
                <span class="movie-meta-item"><?= htmlspecialchars($movie['director']) ?> &middot; <?= $movie['releaseyear'] ?></span>
            </div>
            <div style="text-align:right;">
                <div style="font-size:1.4rem; font-weight:700;"><?= $movie['total_copies'] ?></div>
                <div style="font-size:0.75rem; color:var(--text-muted);">total copies</div>
                <div style="font-size:0.85rem; color:#28a745;"><?= $movie['available_copies'] ?> available</div>
            </div>
        </div>

        <!-- Existing copies -->
        <?php
        $copies = mysqli_query($conn, "
            SELECT C.copynumber, C.`condition`, C.isavailable
            FROM COPY C
            WHERE C.movieid = '{$movie['movieid']}'
            ORDER BY C.copynumber
        ");
        if (mysqli_num_rows($copies) > 0): ?>
        <table style="width:100%; border-collapse:collapse; font-size:0.85rem; margin-bottom:1rem;">
            <thead>
                <tr style="border-bottom:1px solid #333;">
                    <th style="text-align:left; padding:6px 12px; color:var(--text-muted);">Copy #</th>
                    <th style="text-align:left; padding:6px 12px; color:var(--text-muted);">Condition</th>
                    <th style="text-align:left; padding:6px 12px; color:var(--text-muted);">Status</th>
                    <th style="padding:6px 12px;"></th>
                </tr>
            </thead>
            <tbody>
            <?php while ($copy = mysqli_fetch_assoc($copies)): ?>
                <tr style="border-bottom:1px solid #1e1e26;">
                    <td style="padding:6px 12px;">#<?= $copy['copynumber'] ?></td>
                    <td style="padding:6px 12px; color:var(--text-muted);"><?= htmlspecialchars($copy['condition']) ?></td>
                    <td style="padding:6px 12px;">
                        <?php if ($copy['isavailable']): ?>
                            <span class="status-badge status-returned">Available</span>
                        <?php else: ?>
                            <span class="status-badge status-overdue">Rented Out</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:6px 12px; text-align:right;">
                        <?php if ($copy['isavailable']): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="copynumber" value="<?= $copy['copynumber'] ?>">
                            <input type="hidden" name="movieid" value="<?= $movie['movieid'] ?>">
                            <button type="submit"
                                style="background:none; border:1px solid var(--primary-accent); color:var(--primary-accent); padding:3px 10px; border-radius:4px; cursor:pointer; font-size:0.8rem;"
                                onclick="return confirm('Delete copy #<?= $copy['copynumber'] ?>?')">
                                Delete
                            </button>
                        </form>
                        <?php else: ?>
                            <span style="color:var(--text-muted); font-size:0.8rem;">In use</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- Add copy form -->
        <form method="POST" style="display:flex; gap:0.75rem; align-items:flex-end;">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="movieid" value="<?= $movie['movieid'] ?>">
            <div>
                <label style="font-size:0.8rem; color:var(--text-muted); display:block; margin-bottom:4px;">Condition</label>
                <select name="condition" class="form-control" style="width:auto; padding:0.5rem;">
                    <option value="Good">Good</option>
                    <option value="Fair">Fair</option>
                    <option value="Poor">Poor</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="padding:0.5rem 1rem; font-size:0.85rem;">+ Add Copy</button>
        </form>

    </div>
    <?php endwhile; ?>

</div>
</main>

<?php include '../includes/footer.php'; ?>
