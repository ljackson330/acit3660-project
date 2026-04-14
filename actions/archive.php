<?php
require_once '../db_connect.php';
require_once '../auth_check.php';

if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

$pageTitle = "Rental Archive | Movie Rentals";
$message = '';
$error   = '';

// Check if archive table exists
$table_exists = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS n
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'RENTAL_ARCHIVE'
"))['n'] > 0;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($_POST['action'] === 'create_table') {
        $sql = "CREATE TABLE IF NOT EXISTS RENTAL_ARCHIVE (
            archiveid   INT            NOT NULL AUTO_INCREMENT,
            rentalid    INT            NOT NULL,
            userid      INT            NOT NULL,
            movieid     INT            NOT NULL,
            title       VARCHAR(100)   NOT NULL,
            customer    VARCHAR(101)   NOT NULL,
            rentaldate  DATE           NOT NULL,
            duedate     DATE           NOT NULL,
            returndate  DATE           NOT NULL,
            amount_paid DECIMAL(10,2)  DEFAULT NULL,
            archived_at TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (archiveid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (mysqli_query($conn, $sql)) {
            $table_exists = true;
            $message = "RENTAL_ARCHIVE table created successfully.";
        } else {
            $error = "Failed to create table: " . mysqli_error($conn);
        }

    } elseif ($_POST['action'] === 'archive' && $table_exists) {
        mysqli_begin_transaction($conn);
        try {
            // Insert returned rentals into archive (avoid duplicates)
            $archived = mysqli_query($conn, "
                INSERT INTO RENTAL_ARCHIVE (rentalid, userid, movieid, title, customer, rentaldate, duedate, returndate, amount_paid)
                SELECT R.rentalid,
                       R.userid,
                       M.movieid,
                       M.title,
                       CONCAT(U.fname, ' ', U.lname),
                       R.rentaldate,
                       R.duedate,
                       R.returndate,
                       P.amount
                FROM RENTAL R
                JOIN CONTAINS C  ON R.rentalid = C.rentalid
                JOIN MOVIE M     ON C.movieid  = M.movieid
                JOIN USER U      ON R.userid   = U.userid
                LEFT JOIN PAYMENT P ON R.rentalid = P.rentalid
                WHERE R.returndate IS NOT NULL
                AND R.rentalid NOT IN (SELECT rentalid FROM RENTAL_ARCHIVE)
            ");

            $count = mysqli_affected_rows($conn);
            mysqli_commit($conn);
            $message = "{$count} returned rental(s) archived successfully.";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Archive failed: " . $e->getMessage();
        }

    } elseif ($_POST['action'] === 'drop_table') {
        if (mysqli_query($conn, "DROP TABLE IF EXISTS RENTAL_ARCHIVE")) {
            $table_exists = false;
            $message = "RENTAL_ARCHIVE table dropped.";
        } else {
            $error = "Failed to drop table: " . mysqli_error($conn);
        }
    }
}

// Fetch archive records if table exists
$archive_rows  = [];
$archive_count = 0;
if ($table_exists) {
    $res = mysqli_query($conn, "SELECT * FROM RENTAL_ARCHIVE ORDER BY archived_at DESC");
    while ($row = mysqli_fetch_assoc($res)) {
        $archive_rows[] = $row;
    }
    $archive_count = count($archive_rows);
}

// Count archivable rentals
$archivable = 0;
if ($table_exists) {
    $archivable = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) AS n FROM RENTAL
        WHERE returndate IS NOT NULL
        AND rentalid NOT IN (SELECT rentalid FROM RENTAL_ARCHIVE)
    "))['n'];
}

include '../includes/header.php';
?>

<main>
<div class="container">

    <h2 class="section-title">Rental Archive</h2>
    <p style="color:var(--text-muted); margin-bottom:2rem;">
        The archive table is a separate permanent record of all completed rentals,
        created via <code>CREATE TABLE</code> and populated independently of the live rental data.
    </p>

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

    <!-- Table management -->
    <div class="movie-card" style="padding:1.5rem; margin-bottom:2rem;">
        <h3 style="font-size:1rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px; margin-bottom:1.25rem;">Table Management</h3>

        <div style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">

            <?php if (!$table_exists): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="create_table">
                    <button type="submit" class="btn btn-primary">Create RENTAL_ARCHIVE Table</button>
                </form>
                <span style="color:var(--text-muted); font-size:0.9rem;">Table does not exist yet.</span>

            <?php else: ?>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <span style="color:#28a745; font-weight:700;">&#10003;</span>
                    <span style="font-size:0.95rem;">RENTAL_ARCHIVE exists &mdash; <strong><?= $archive_count ?></strong> record(s)</span>
                </div>

                <?php if ($archivable > 0): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="archive">
                    <button type="submit" class="btn btn-primary">
                        Archive <?= $archivable ?> Returned Rental(s)
                    </button>
                </form>
                <?php else: ?>
                    <span style="color:var(--text-muted); font-size:0.9rem;">No new rentals to archive.</span>
                <?php endif; ?>

                <form method="POST" style="margin-left:auto;">
                    <input type="hidden" name="action" value="drop_table">
                    <button type="submit"
                        style="background:none; border:1px solid var(--primary-accent); color:var(--primary-accent); padding:0.6rem 1rem; border-radius:4px; cursor:pointer; font-size:0.85rem;"
                        onclick="return confirm('Drop the RENTAL_ARCHIVE table and all its data?')">
                        Drop Table
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Archive records -->
    <?php if ($table_exists): ?>
    <h3 class="section-title" style="font-size:1.2rem; margin-bottom:1rem;">Archived Records</h3>

    <?php if (!empty($archive_rows)): ?>
    <div class="movie-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:0.875rem;">
            <thead>
                <tr style="border-bottom:1px solid #333;">
                    <?php foreach (['#','Rental ID','Customer','Movie','Rented','Returned','Paid','Archived At'] as $col): ?>
                    <th style="text-align:left; padding:12px 16px; color:var(--text-muted); font-weight:600; white-space:nowrap;"><?= $col ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($archive_rows as $row): ?>
                <tr style="border-bottom:1px solid #1e1e26;">
                    <td style="padding:10px 16px; color:var(--text-muted);"><?= $row['archiveid'] ?></td>
                    <td style="padding:10px 16px; color:var(--text-muted);">#<?= $row['rentalid'] ?></td>
                    <td style="padding:10px 16px;"><?= htmlspecialchars($row['customer']) ?></td>
                    <td style="padding:10px 16px;"><?= htmlspecialchars($row['title']) ?></td>
                    <td style="padding:10px 16px; color:var(--text-muted);"><?= $row['rentaldate'] ?></td>
                    <td style="padding:10px 16px; color:var(--text-muted);"><?= $row['returndate'] ?></td>
                    <td style="padding:10px 16px;">$<?= number_format($row['amount_paid'], 2) ?></td>
                    <td style="padding:10px 16px; color:var(--text-muted);"><?= $row['archived_at'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php else: ?>
        <p style="color:var(--text-muted);">No records archived yet. Click "Archive Returned Rentals" above to populate.</p>
    <?php endif; ?>
    <?php endif; ?>

</div>
</main>

<?php include '../includes/footer.php'; ?>
