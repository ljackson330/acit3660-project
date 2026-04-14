<?php
require '../db_connect.php';
require_once '../auth_check.php';

if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

$pageTitle = "Update Movie | Movie Rentals";
$message = '';
$error   = '';
$movie   = null;

if (isset($_GET['movieid']) && $_GET['movieid'] !== '') {
    $id   = (int) $_GET['movieid'];
    $stmt = $conn->prepare("SELECT * FROM MOVIE WHERE movieid = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $movie = $stmt->get_result()->fetch_assoc();
    if (!$movie) $error = "No movie found with that ID.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id        = (int) $_POST['movieid'];
    $title     = trim($_POST['title']);
    $director  = trim($_POST['director']);
    $year      = (int) $_POST['releaseyear'];
    $duration  = (int) $_POST['duration'];
    $rating    = $_POST['rating'];

    if (empty($title) || empty($director)) {
        $error = "Title and director are required.";
    } else {
        $stmt = $conn->prepare("UPDATE MOVIE SET title=?, director=?, releaseyear=?, duration=?, rating=? WHERE movieid=?");
        $stmt->bind_param("ssiisi", $title, $director, $year, $duration, $rating, $id);
        if ($stmt->execute()) {
            $message = "Movie updated successfully.";
            // Refresh movie data
            $stmt2 = $conn->prepare("SELECT * FROM MOVIE WHERE movieid = ?");
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            $movie = $stmt2->get_result()->fetch_assoc();
        } else {
            $error = "Update failed: " . $conn->error;
        }
    }
}

include '../includes/header.php';
?>

<main>
<div class="container" style="max-width:600px;">

    <h2 class="section-title">Update Movie</h2>

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

    <div class="movie-card" style="padding:1.5rem; margin-bottom:1.5rem;">
        <form method="GET">
            <div class="form-group">
                <label>Movie ID</label>
                <input type="number" name="movieid" class="form-control" value="<?= isset($_GET['movieid']) ? (int)$_GET['movieid'] : '' ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Look Up</button>
        </form>
    </div>

    <?php if ($movie): ?>
    <div class="movie-card" style="padding:1.5rem;">
        <form method="POST">
            <input type="hidden" name="movieid" value="<?= $movie['movieid'] ?>">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($movie['title']) ?>" required>
            </div>
            <div class="form-group">
                <label>Director</label>
                <input type="text" name="director" class="form-control" value="<?= htmlspecialchars($movie['director']) ?>" required>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label>Release Year</label>
                    <input type="number" name="releaseyear" class="form-control" value="<?= $movie['releaseyear'] ?>" required>
                </div>
                <div class="form-group">
                    <label>Duration (mins)</label>
                    <input type="number" name="duration" class="form-control" value="<?= $movie['duration'] ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>Rating</label>
                <select name="rating" class="form-control">
                    <?php foreach (['G','PG','PG-13','R','NR'] as $r): ?>
                    <option value="<?= $r ?>" <?= $movie['rating'] === $r ? 'selected' : '' ?>><?= $r ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; gap:1rem; margin-top:0.5rem;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="update.php" style="padding:0.75rem 1.5rem; color:var(--text-muted);">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

</div>
</main>

<?php include '../includes/footer.php'; ?>
