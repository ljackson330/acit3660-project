<?php
require '../db_connect.php';
require_once '../auth_check.php';

if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

$pageTitle = "Delete Movie | Movie Rentals";
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
    $id = (int) $_POST['movieid'];

    mysqli_begin_transaction($conn);
    try {
        $stmt = $conn->prepare("DELETE FROM CONTAINS WHERE movieid = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM COPY WHERE movieid = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM BELONGS_TO WHERE movieid = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM MOVIE WHERE movieid = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        mysqli_commit($conn);
        $message = "Movie deleted successfully.";
        $movie   = null;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Failed to delete movie: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<main>
<div class="container" style="max-width:600px;">

    <h2 class="section-title">Delete Movie</h2>

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
                <input type="number" name="movieid" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Look Up</button>
        </form>
    </div>

    <?php if ($movie): ?>
    <div class="movie-card" style="padding:1.5rem;">
        <h3 class="movie-title" style="margin-bottom:1rem;">Confirm Deletion</h3>
        <div class="movie-meta-item">ID: <strong><?= $movie['movieid'] ?></strong></div>
        <div class="movie-meta-item">Title: <strong><?= htmlspecialchars($movie['title']) ?></strong></div>
        <div class="movie-meta-item">Director: <strong><?= htmlspecialchars($movie['director']) ?></strong></div>
        <div class="movie-meta-item">Year: <strong><?= $movie['releaseyear'] ?></strong></div>

        <div style="display:flex; gap:1rem; margin-top:1.5rem;">
            <form method="POST">
                <input type="hidden" name="movieid" value="<?= $movie['movieid'] ?>">
                <button type="submit" class="btn btn-primary"
                    onclick="return confirm('Permanently delete \'<?= htmlspecialchars(addslashes($movie['title'])) ?>\'?')"
                    style="background:var(--primary-accent);">
                    Delete
                </button>
            </form>
            <a href="delete.php" style="padding:0.75rem 1.5rem; color:var(--text-muted);">Cancel</a>
        </div>
    </div>
    <?php endif; ?>

</div>
</main>

<?php include '../includes/footer.php'; ?>
