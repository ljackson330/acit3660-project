<?php
require_once 'db_connect.php';
require_once 'auth_check.php';

$pageTitle = "My Profile | Movie Rentals";

$userid = $_SESSION['user_id'];
$update_message = '';
$update_error   = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname    = trim($_POST['fname']);
    $lname    = trim($_POST['lname']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $new_pass = $_POST['new_password'];
    $confirm  = $_POST['confirm_password'];

    if (empty($fname) || empty($lname) || empty($email)) {
        $update_error = "Name and email are required.";
    } elseif (!empty($new_pass) && $new_pass !== $confirm) {
        $update_error = "Passwords do not match.";
    } else {
        if (!empty($new_pass)) {
            $stmt = $conn->prepare("UPDATE USER SET fname=?, lname=?, email=?, phone=?, password=? WHERE userid=?");
            $stmt->bind_param("sssssi", $fname, $lname, $email, $phone, $new_pass, $userid);
        } else {
            $stmt = $conn->prepare("UPDATE USER SET fname=?, lname=?, email=?, phone=? WHERE userid=?");
            $stmt->bind_param("ssssi", $fname, $lname, $email, $phone, $userid);
        }

        if ($stmt->execute()) {
            $_SESSION['fname'] = $fname;
            $_SESSION['lname'] = $lname;
            $_SESSION['email'] = $email;
            $update_message = "Profile updated successfully.";
        } else {
            $update_error = "Update failed: " . $conn->error;
        }
    }
}

// Account info
$stmt = $conn->prepare("SELECT fname, lname, email, phone, usertype FROM USER WHERE userid = ?");
$stmt->bind_param("i", $userid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Rental stats (via CustomerSummary view)
$stmt = $conn->prepare("
    SELECT
        COUNT(DISTINCT R.rentalid) AS total_rentals,
        SUM(CASE WHEN R.returndate IS NULL AND R.duedate >= CURDATE() THEN 1 ELSE 0 END) AS active,
        SUM(CASE WHEN R.returndate IS NULL AND R.duedate <  CURDATE() THEN 1 ELSE 0 END) AS overdue,
        SUM(CASE WHEN R.returndate IS NOT NULL                         THEN 1 ELSE 0 END) AS returned,
        COALESCE(SUM(P.amount), 0) AS total_paid
    FROM RENTAL R
    LEFT JOIN PAYMENT P ON R.rentalid = P.rentalid AND P.userid = ?
    WHERE R.userid = ?
");
$stmt->bind_param("ii", $userid, $userid);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Rental history
$stmt = $conn->prepare("
    SELECT R.rentalid,
           M.title, M.director, M.rating,
           R.rentaldate, R.duedate, R.returndate,
           CASE
               WHEN R.returndate IS NOT NULL      THEN 'Returned'
               WHEN R.duedate < CURDATE()         THEN 'Overdue'
               ELSE 'Active'
           END AS status
    FROM RENTAL R
    JOIN CONTAINS C ON R.rentalid = C.rentalid
    JOIN MOVIE M    ON C.movieid  = M.movieid
    WHERE R.userid = ?
    ORDER BY R.rentaldate DESC
");
$stmt->bind_param("i", $userid);
$stmt->execute();
$history = $stmt->get_result();

include 'includes/header.php';
?>

<main>
<div class="container">

    <h2 class="section-title">My Profile</h2>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:2rem;">

        <!-- Edit account info -->
        <div class="movie-card" style="padding:1.5rem;">
            <h3 style="margin-bottom:1.25rem; font-size:1rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px;">Account Info</h3>

            <?php if ($update_message): ?>
                <div style="background:rgba(40,167,69,0.1); color:#28a745; border:1px solid #28a745; padding:10px 14px; border-radius:4px; margin-bottom:1rem; font-size:0.9rem;">
                    <?= htmlspecialchars($update_message) ?>
                </div>
            <?php endif; ?>
            <?php if ($update_error): ?>
                <div style="background:rgba(229,9,20,0.1); color:var(--primary-accent); border:1px solid var(--primary-accent); padding:10px 14px; border-radius:4px; margin-bottom:1rem; font-size:0.9rem;">
                    <?= htmlspecialchars($update_error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="form-group" style="margin-bottom:1rem;">
                        <label style="font-size:0.8rem;">First Name</label>
                        <input type="text" name="fname" class="form-control" value="<?= htmlspecialchars($user['fname']) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom:1rem;">
                        <label style="font-size:0.8rem;">Last Name</label>
                        <input type="text" name="lname" class="form-control" value="<?= htmlspecialchars($user['lname']) ?>" required>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:1rem;">
                    <label style="font-size:0.8rem;">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div class="form-group" style="margin-bottom:1rem;">
                    <label style="font-size:0.8rem;">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin-bottom:1rem;">
                    <label style="font-size:0.8rem;">New Password <span style="color:var(--text-muted)">(leave blank to keep current)</span></label>
                    <input type="password" name="new_password" class="form-control" placeholder="••••••••">
                </div>
                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label style="font-size:0.8rem;">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="••••••••">
                </div>
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <button type="submit" class="btn btn-primary" style="padding:0.6rem 1.25rem;">Save Changes</button>
                    <span class="badge" style="background:var(--primary-accent);color:#fff;"><?= htmlspecialchars($user['usertype']) ?></span>
                </div>
            </form>
        </div>

        <!-- Rental stats -->
        <div class="movie-card" style="padding:1.5rem;">
            <h3 style="margin-bottom:1.25rem; font-size:1rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px;">Rental Summary</h3>

            <?php
            $summary = [
                ['Total Rentals', $stats['total_rentals'], null],
                ['Active',        $stats['active'],        'color:#007bff'],
                ['Overdue',       $stats['overdue'],       'color:var(--primary-accent)'],
                ['Returned',      $stats['returned'],      'color:#28a745'],
                ['Total Paid',    '$'.number_format($stats['total_paid'], 2), null],
            ];
            foreach ($summary as [$label, $value, $style]): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; padding:0.5rem 0; border-bottom:1px solid #1e1e26;">
                <span style="color:var(--text-muted); font-size:0.9rem;"><?= $label ?></span>
                <span style="font-weight:700; <?= $style ?? '' ?>"><?= htmlspecialchars((string)$value) ?></span>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- Rental history -->
    <h3 class="section-title" style="font-size:1.2rem; margin-bottom:1rem;">Rental History</h3>

    <?php if (mysqli_num_rows($history) > 0): ?>
    <div class="movie-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:0.875rem;">
            <thead>
                <tr style="border-bottom:1px solid #333;">
                    <?php foreach (['#','Movie','Director','Rating','Rented','Due','Returned','Status'] as $col): ?>
                    <th style="text-align:left; padding:12px 16px; color:var(--text-muted); font-weight:600; white-space:nowrap;"><?= $col ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($history)):
                $statusColors = [
                    'Active'   => 'status-active',
                    'Overdue'  => 'status-overdue',
                    'Returned' => 'status-returned',
                ];
                $cls = $statusColors[$row['status']] ?? '';
            ?>
                <tr style="border-bottom:1px solid #1e1e26;">
                    <td style="padding:10px 16px; color:var(--text-muted);">#<?= $row['rentalid'] ?></td>
                    <td style="padding:10px 16px;"><?= htmlspecialchars($row['title']) ?></td>
                    <td style="padding:10px 16px; color:var(--text-muted);"><?= htmlspecialchars($row['director']) ?></td>
                    <td style="padding:10px 16px;"><?= htmlspecialchars($row['rating']) ?></td>
                    <td style="padding:10px 16px; color:var(--text-muted);"><?= $row['rentaldate'] ?></td>
                    <td style="padding:10px 16px; color:var(--text-muted);"><?= $row['duedate'] ?></td>
                    <td style="padding:10px 16px; color:var(--text-muted);"><?= $row['returndate'] ?? '—' ?></td>
                    <td style="padding:10px 16px;"><span class="status-badge <?= $cls ?>"><?= $row['status'] ?></span></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>

    <?php else: ?>
    <div style="text-align:center; padding:3rem 0;">
        <p style="color:var(--text-muted); font-size:1.1rem;">You haven't rented any movies yet.</p>
        <a href="rentals/rent.php" class="btn btn-primary" style="margin-top:1rem;">Browse Movies</a>
    </div>
    <?php endif; ?>

</div>
</main>

<?php include 'includes/footer.php'; ?>
