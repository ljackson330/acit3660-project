<?php
require_once '../db_connect.php';
require_once '../auth_check.php';

if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

$pageTitle = "Admin Dashboard | Movie Rentals";

$user_message = '';
$user_error   = '';

// Handle create user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'create') {
        $fname    = trim($_POST['fname']);
        $lname    = trim($_POST['lname']);
        $email    = trim($_POST['email']);
        $phone    = trim($_POST['phone']);
        $password = $_POST['password'];
        $usertype = in_array($_POST['usertype'], ['admin','customer']) ? $_POST['usertype'] : 'customer';

        if (empty($fname) || empty($lname) || empty($email) || empty($password)) {
            $user_error = "Name, email, and password are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO USER (fname, lname, email, phone, password, usertype) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $fname, $lname, $email, $phone, $password, $usertype);
            if ($stmt->execute()) {
                $user_message = "User {$fname} {$lname} created successfully.";
            } else {
                $user_error = "Failed to create user. Email may already be taken.";
            }
        }

    } elseif ($_POST['action'] === 'delete') {
        $del_id = (int) $_POST['userid'];
        if ($del_id === (int) $_SESSION['user_id']) {
            $user_error = "You cannot delete your own account.";
        } else {
            mysqli_begin_transaction($conn);
            try {
                // Delete in FK dependency order
                $stmt = $conn->prepare("DELETE FROM PAYMENT WHERE userid = ?");
                $stmt->bind_param("i", $del_id);
                $stmt->execute();

                // Get rental IDs to clean up CONTAINS
                $rental_ids = [];
                $res = $conn->prepare("SELECT rentalid FROM RENTAL WHERE userid = ?");
                $res->bind_param("i", $del_id);
                $res->execute();
                $res->bind_result($rid);
                while ($res->fetch()) { $rental_ids[] = $rid; }
                $res->close();

                foreach ($rental_ids as $rid) {
                    $stmt2 = $conn->prepare("DELETE FROM CONTAINS WHERE rentalid = ?");
                    $stmt2->bind_param("i", $rid);
                    $stmt2->execute();
                }

                $stmt = $conn->prepare("DELETE FROM RENTAL WHERE userid = ?");
                $stmt->bind_param("i", $del_id);
                $stmt->execute();

                $stmt = $conn->prepare("DELETE FROM USER WHERE userid = ?");
                $stmt->bind_param("i", $del_id);
                $stmt->execute();

                mysqli_commit($conn);
                $user_message = "User deleted successfully.";
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $user_error = "Failed to delete user: " . $e->getMessage();
            }
        }
    }
}

// Stats
$stat_movies   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM MOVIE"))['n'];
$stat_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM USER WHERE usertype != 'admin'"))['n'];
$stat_active   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM RENTAL WHERE returndate IS NULL AND duedate >= CURDATE()"))['n'];
$stat_overdue  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM RENTAL WHERE returndate IS NULL AND duedate < CURDATE()"))['n'];
$stat_copies   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM COPY WHERE isavailable = 1"))['n'];
$stat_revenue  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) AS n FROM PAYMENT"))['n'];

// Recent active rentals
$recent = mysqli_query($conn, "
    SELECT R.rentalid,
           CONCAT(U.fname, ' ', U.lname) AS customer,
           M.title,
           R.rentaldate, R.duedate,
           CASE
               WHEN R.duedate < CURDATE() THEN 'Overdue'
               ELSE 'Active'
           END AS status,
           ROUND(3.99 + (GREATEST(0, DATEDIFF(CURDATE(), R.duedate)) * 1.00), 2) AS charge_owing
    FROM RENTAL R
    JOIN USER U     ON R.userid   = U.userid
    JOIN CONTAINS C ON R.rentalid = C.rentalid
    JOIN MOVIE M    ON C.movieid  = M.movieid
    WHERE R.returndate IS NULL
    ORDER BY R.duedate ASC
    LIMIT 10
");

include '../includes/header.php';
?>

<main>
<div class="container">

    <h2 class="section-title">Admin Dashboard</h2>

    <!-- Stat cards -->
    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:1rem; margin-bottom:2.5rem;">

        <?php
        $stats = [
            ['🎬', 'Movies',           $stat_movies,  null],
            ['👥', 'Customers',        $stat_users,   null],
            ['📼', 'Copies Available', $stat_copies,  null],
            ['✅', 'Active Rentals',   $stat_active,  null],
            ['⚠️', 'Overdue',          $stat_overdue, 'color:var(--primary-accent)'],
            ['💰', 'Revenue',          '$'.number_format($stat_revenue, 2), null],
        ];
        foreach ($stats as [$icon, $label, $value, $style]): ?>
        <div class="movie-card" style="text-align:center; padding:1.25rem; min-height:0;">
            <div style="font-size:1.6rem; margin-bottom:0.4rem;"><?= $icon ?></div>
            <div style="font-size:1.5rem; font-weight:700; <?= $style ?? '' ?>"><?= htmlspecialchars((string)$value) ?></div>
            <div style="color:var(--text-muted); font-size:0.8rem; margin-top:0.2rem;"><?= $label ?></div>
        </div>
        <?php endforeach; ?>

    </div>

    <!-- Quick actions -->
    <h3 class="section-title" style="font-size:1.2rem; margin-bottom:1rem;">Manage</h3>
    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:1rem; margin-bottom:2.5rem;">

        <?php
        $actions = [
            ['➕', 'Add Movie',       '../actions/insert.php'],
            ['✏️', 'Update Movie',    '../actions/update.php'],
            ['🗑️', 'Delete Movie',    '../actions/delete.php'],
            ['📼', 'Manage Copies',   '../actions/copies.php'],
            ['📦', 'Rental Archive',  '../actions/archive.php'],
            ['📊', 'Advanced Queries','../actions/queries.php'],
        ];
        foreach ($actions as [$icon, $label, $href]): ?>
        <a href="<?= $href ?>" class="movie-card" style="text-align:center; justify-content:center; padding:1.25rem; min-height:0; display:flex; flex-direction:column; align-items:center; gap:0.5rem;">
            <div style="font-size:1.8rem;"><?= $icon ?></div>
            <div style="font-weight:600; font-size:0.95rem;"><?= $label ?></div>
        </a>
        <?php endforeach; ?>

    </div>

    <!-- Recent rentals table -->
    <h3 class="section-title" style="font-size:1.2rem; margin-bottom:1rem;">Recent Rentals</h3>
    <div class="movie-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:0.875rem;">
            <thead>
                <tr style="border-bottom:1px solid #333;">
                    <?php foreach (['ID','Customer','Movie','Rented','Due','Status','Owing'] as $col): ?>
                    <th style="text-align:left; padding:12px 16px; color:var(--text-muted); font-weight:600; white-space:nowrap;">
                        <?= $col ?>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($recent)): 
                $cls = $row['status'] === 'Overdue' ? 'status-overdue' : 'status-active';
            ?>
                <tr style="border-bottom:1px solid #1e1e26;">
                    <td style="padding:10px 16px; color:var(--text-muted);">#<?= $row['rentalid'] ?></td>
                    <td style="padding:10px 16px;"><?= htmlspecialchars($row['customer']) ?></td>
                    <td style="padding:10px 16px;"><?= htmlspecialchars($row['title']) ?></td>
                    <td style="padding:10px 16px; color:var(--text-muted);"><?= $row['rentaldate'] ?></td>
                    <td style="padding:10px 16px; color:var(--text-muted);"><?= $row['duedate'] ?></td>
                    <td style="padding:10px 16px;">
                        <span class="status-badge <?= $cls ?>"><?= $row['status'] ?></span>
                    </td>
                    <td style="padding:10px 16px; color:var(--text-muted);">$<?= number_format($row['charge_owing'], 2) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- User management -->
    <h3 class="section-title" style="font-size:1.2rem; margin-bottom:1rem; margin-top:2.5rem;">User Management</h3>

    <?php if ($user_message): ?>
        <div style="background:rgba(40,167,69,0.1); color:#28a745; border:1px solid #28a745; padding:12px 16px; border-radius:4px; margin-bottom:1.5rem;">
            <?= htmlspecialchars($user_message) ?>
        </div>
    <?php endif; ?>
    <?php if ($user_error): ?>
        <div style="background:rgba(229,9,20,0.1); color:var(--primary-accent); border:1px solid var(--primary-accent); padding:12px 16px; border-radius:4px; margin-bottom:1.5rem;">
            <?= htmlspecialchars($user_error) ?>
        </div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:2fr 1fr; gap:1.5rem; align-items:start;">

        <!-- User list -->
        <div class="movie-card" style="padding:0; overflow:hidden;">
            <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.875rem;">
                <thead>
                    <tr style="border-bottom:1px solid #333;">
                        <?php foreach (['ID','Name','Email','Phone','Type','Action'] as $col): ?>
                        <th style="text-align:left; padding:12px 16px; color:var(--text-muted); font-weight:600; white-space:nowrap;"><?= $col ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                $users = mysqli_query($conn, "SELECT userid, fname, lname, email, phone, usertype FROM USER ORDER BY usertype, lname");
                while ($u = mysqli_fetch_assoc($users)):
                    $is_self = $u['userid'] == $_SESSION['user_id'];
                ?>
                    <tr style="border-bottom:1px solid #1e1e26;">
                        <td style="padding:10px 16px; color:var(--text-muted);"><?= $u['userid'] ?></td>
                        <td style="padding:10px 16px;"><?= htmlspecialchars($u['fname'] . ' ' . $u['lname']) ?></td>
                        <td style="padding:10px 16px; color:var(--text-muted);"><?= htmlspecialchars($u['email']) ?></td>
                        <td style="padding:10px 16px; color:var(--text-muted);"><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                        <td style="padding:10px 16px;">
                            <span class="badge" style="<?= $u['usertype'] === 'admin' ? 'background:var(--primary-accent);color:#fff;' : '' ?>">
                                <?= htmlspecialchars($u['usertype']) ?>
                            </span>
                        </td>
                        <td style="padding:10px 16px;">
                            <?php if (!$is_self): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="userid" value="<?= $u['userid'] ?>">
                                <button type="submit" style="background:none; border:1px solid var(--primary-accent); color:var(--primary-accent); padding:4px 10px; border-radius:4px; cursor:pointer; font-size:0.8rem;"
                                    onclick="return confirm('Delete <?= htmlspecialchars(addslashes($u['fname'] . ' ' . $u['lname'])) ?>?')">
                                    Delete
                                </button>
                            </form>
                            <?php else: ?>
                                <span style="color:var(--text-muted); font-size:0.8rem;">You</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
        </div>

        <!-- Create user form -->
        <div class="movie-card" style="padding:1.5rem;">
            <h4 style="margin-bottom:1.25rem; font-size:0.9rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px;">Create User</h4>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                    <div class="form-group" style="margin-bottom:0.75rem;">
                        <label style="font-size:0.8rem;">First Name</label>
                        <input type="text" name="fname" class="form-control" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0.75rem;">
                        <label style="font-size:0.8rem;">Last Name</label>
                        <input type="text" name="lname" class="form-control" required>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:0.75rem;">
                    <label style="font-size:0.8rem;">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom:0.75rem;">
                    <label style="font-size:0.8rem;">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="form-group" style="margin-bottom:0.75rem;">
                    <label style="font-size:0.8rem;">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label style="font-size:0.8rem;">Account Type</label>
                    <select name="usertype" class="form-control">
                        <option value="customer">Customer</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Create User</button>
            </form>
        </div>

    </div>

</div>
</main>

<?php include '../includes/footer.php'; ?>
