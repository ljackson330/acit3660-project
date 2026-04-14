<?php
require '../db_connect.php';
require_once '../auth_check.php';

if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

$pageTitle = "Add Movie | Movie Rentals";
$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title     = trim($_POST['title']);
    $director  = trim($_POST['director']);
    $year      = (int) $_POST['releaseyear'];
    $duration  = (int) $_POST['duration'];
    $rating    = $_POST['rating'];
    $copies    = max(1, (int) $_POST['copies']);

    if (empty($title) || empty($director)) {
        $error = "Title and director are required.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            $stmt = $conn->prepare("INSERT INTO MOVIE (title, director, releaseyear, duration, rating) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiss", $title, $director, $year, $duration, $rating);
            $stmt->execute();
            $movieid = mysqli_insert_id($conn);

            for ($i = 1; $i <= $copies; $i++) {
                $stmt2 = $conn->prepare("INSERT INTO COPY (copynumber, movieid, `condition`, isavailable) VALUES (?, ?, 'Good', 1)");
                $stmt2->bind_param("ii", $i, $movieid);
                $stmt2->execute();
            }

            mysqli_commit($conn);
            $message = "Movie \"" . htmlspecialchars($title) . "\" added with {$copies} copy/copies.";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Failed to add movie: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<main>
<div class="container" style="max-width:600px;">

    <h2 class="section-title">Add Movie</h2>

    <?php if ($message): ?>
        <div style="background:rgba(40,167,69,0.1); color:#28a745; border:1px solid #28a745; padding:12px 16px; border-radius:4px; margin-bottom:1.5rem;">
            <?= $message ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background:rgba(229,9,20,0.1); color:var(--primary-accent); border:1px solid var(--primary-accent); padding:12px 16px; border-radius:4px; margin-bottom:1.5rem;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="movie-card" style="padding:2rem;">
        <form method="POST">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Director</label>
                <input type="text" name="director" class="form-control" required>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label>Release Year</label>
                    <input type="number" name="releaseyear" class="form-control" min="1888" max="2099" required>
                </div>
                <div class="form-group">
                    <label>Duration (mins)</label>
                    <input type="number" name="duration" class="form-control" min="1" required>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label>Rating</label>
                    <select name="rating" class="form-control">
                        <option value="G">G</option>
                        <option value="PG">PG</option>
                        <option value="PG-13">PG-13</option>
                        <option value="R">R</option>
                        <option value="NR">NR</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Initial Copies</label>
                    <input type="number" name="copies" class="form-control" min="1" max="99" value="1" required>
                </div>
            </div>
            <div style="display:flex; gap:1rem; margin-top:0.5rem;">
                <button type="submit" class="btn btn-primary">Add Movie</button>
                <a href="/admin/dashboard.php" style="padding:0.75rem 1.5rem; color:var(--text-muted);">Cancel</a>
            </div>
        </form>
    </div>

</div>
</main>

<?php include '../includes/footer.php'; ?>
