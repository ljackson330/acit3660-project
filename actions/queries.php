<?php
require_once '../db_connect.php';
require_once '../auth_check.php';

$pageTitle = "Advanced Queries";

// Queries
// Each entry: [title, description, sql, columns]
$queries = [

    // DISTINCT + ORDER BY
    [
        'title'       => 'All distinct directors (A–Z)',
        'description' => 'DISTINCT, ORDER BY',
        'sql'         => "SELECT DISTINCT director FROM MOVIE ORDER BY director ASC",
        'columns'     => ['Director'],
    ],

    // Aggregate + GROUP BY + ORDER BY
    [
        'title'       => 'Movies per director',
        'description' => 'COUNT, GROUP BY, ORDER BY',
        'sql'         => "SELECT director,
                                 COUNT(*) AS total_movies,
                                 AVG(duration) AS avg_duration_mins
                          FROM MOVIE
                          GROUP BY director
                          ORDER BY total_movies DESC",
        'columns'     => ['Director', 'Total Movies', 'Avg Duration (mins)'],
    ],

    // HAVING
    [
        'title'       => 'Directors with more than one movie',
        'description' => 'GROUP BY, HAVING',
        'sql'         => "SELECT director, COUNT(*) AS movie_count
                          FROM MOVIE
                          GROUP BY director
                          HAVING movie_count > 1",
        'columns'     => ['Director', 'Movie Count'],
    ],

    // BETWEEN
    [
        'title'       => 'Movies released between 1994 and 2000',
        'description' => 'BETWEEN',
        'sql'         => "SELECT title, director, releaseyear, rating
                          FROM MOVIE
                          WHERE releaseyear BETWEEN 1994 AND 2000
                          ORDER BY releaseyear",
        'columns'     => ['Title', 'Director', 'Year', 'Rating'],
    ],

    // LIKE
    [
        'title'       => 'Movies whose title contains "the"',
        'description' => 'LIKE',
        'sql'         => "SELECT title, director, releaseyear
                          FROM MOVIE
                          WHERE title LIKE '%the%'
                          ORDER BY title",
        'columns'     => ['Title', 'Director', 'Year'],
    ],

    // IN
    [
        'title'       => 'Movies rated G or PG',
        'description' => 'IN',
        'sql'         => "SELECT title, rating, releaseyear, duration
                          FROM MOVIE
                          WHERE rating IN ('G', 'PG')
                          ORDER BY rating, title",
        'columns'     => ['Title', 'Rating', 'Year', 'Duration (mins)'],
    ],

    // IS NULL / IS NOT NULL
    [
        'title'       => 'Rentals not yet returned',
        'description' => 'IS NULL',
        'sql'         => "SELECT R.rentalid,
                                 U.fname, U.lname,
                                 M.title,
                                 R.rentaldate, R.duedate
                          FROM RENTAL R
                          JOIN USER U ON R.userid = U.userid
                          JOIN CONTAINS C ON R.rentalid = C.rentalid
                          JOIN MOVIE M ON C.movieid = M.movieid
                          WHERE R.returndate IS NULL
                          ORDER BY R.duedate",
        'columns'     => ['Rental ID', 'First Name', 'Last Name', 'Movie', 'Rental Date', 'Due Date'],
    ],

    // NOT + IS NULL (returned rentals)
    [
        'title'       => 'Rentals that have been returned',
        'description' => 'NOT, IS NULL',
        'sql'         => "SELECT R.rentalid,
                                 U.fname, U.lname,
                                 M.title,
                                 R.returndate
                          FROM RENTAL R
                          JOIN USER U ON R.userid = U.userid
                          JOIN CONTAINS C ON R.rentalid = C.rentalid
                          JOIN MOVIE M ON C.movieid = M.movieid
                          WHERE NOT R.returndate IS NULL
                          ORDER BY R.returndate DESC",
        'columns'     => ['Rental ID', 'First Name', 'Last Name', 'Movie', 'Return Date'],
    ],

    // CASE expression
    [
        'title'       => 'Rental status per rental',
        'description' => 'CASE expression',
        'sql'         => "SELECT R.rentalid,
                                 M.title,
                                 R.duedate,
                                 CASE
                                     WHEN R.returndate IS NOT NULL          THEN 'Returned'
                                     WHEN R.duedate < CURDATE()             THEN 'Overdue'
                                     ELSE 'Active'
                                 END AS status
                          FROM RENTAL R
                          JOIN CONTAINS C ON R.rentalid = C.rentalid
                          JOIN MOVIE M ON C.movieid = M.movieid
                          ORDER BY R.rentalid",
        'columns'     => ['Rental ID', 'Movie', 'Due Date', 'Status'],
    ],

    // Nested / subquery
    [
        'title'       => 'Movies longer than the average duration',
        'description' => 'Nested subquery',
        'sql'         => "SELECT title, director, duration
                          FROM MOVIE
                          WHERE duration > (SELECT AVG(duration) FROM MOVIE)
                          ORDER BY duration DESC",
        'columns'     => ['Title', 'Director', 'Duration (mins)'],
    ],

    // ALL comparison
    [
        'title'       => 'Longest movie (duration >= ALL others)',
        'description' => 'ALL comparison',
        'sql'         => "SELECT title, director, duration
                          FROM MOVIE
                          WHERE duration >= ALL (SELECT duration FROM MOVIE)",
        'columns'     => ['Title', 'Director', 'Duration (mins)'],
    ],

    // ANY comparison
    [
        'title'       => 'Movies shorter than any R-rated film',
        'description' => 'ANY comparison',
        'sql'         => "SELECT title, rating, duration
                          FROM MOVIE
                          WHERE duration < ANY (
                              SELECT duration FROM MOVIE WHERE rating = 'R'
                          )
                          ORDER BY duration",
        'columns'     => ['Title', 'Rating', 'Duration (mins)'],
    ],

    // UNION
    [
        'title'       => 'All people in the system (users + rental contacts)',
        'description' => 'UNION',
        'sql'         => "SELECT fname, lname, email, 'Account Holder' AS source
                          FROM USER
                          UNION
                          SELECT fname, lname, email, 'Has Rental History' AS source
                          FROM USER
                          WHERE userid IN (SELECT DISTINCT userid FROM RENTAL)
                          ORDER BY source, lname",
        'columns'     => ['First Name', 'Last Name', 'Email', 'Source'],
    ],

    // Genre breakdown per movie (GROUP BY + aggregate + JOIN)
    [
        'title'       => 'Genre count per movie',
        'description' => 'JOIN, GROUP BY, COUNT',
        'sql'         => "SELECT M.title,
                                 COUNT(B.genreid) AS genre_count,
                                 GROUP_CONCAT(G.genrename ORDER BY G.genrename SEPARATOR ', ') AS genres
                          FROM MOVIE M
                          LEFT JOIN BELONGS_TO B ON M.movieid = B.movieid
                          LEFT JOIN GENRE G ON B.genreid = G.genreid
                          GROUP BY M.movieid, M.title
                          ORDER BY genre_count DESC, M.title",
        'columns'     => ['Title', 'Genre Count', 'Genres'],
    ],

    // User profile view — rental history with payment info
    [
        'title'       => 'User profile — rental & payment history',
        'description' => 'Multi-join, aggregate, GROUP BY, ORDER BY',
        'sql'         => "SELECT U.userid,
                                 CONCAT(U.fname, ' ', U.lname) AS full_name,
                                 U.email,
                                 COUNT(DISTINCT R.rentalid)   AS total_rentals,
                                 COALESCE(SUM(P.amount), 0)   AS total_paid,
                                 MAX(R.rentaldate)            AS last_rental
                          FROM USER U
                          LEFT JOIN RENTAL R  ON U.userid  = R.userid
                          LEFT JOIN PAYMENT P ON U.userid  = P.userid
                          GROUP BY U.userid, U.fname, U.lname, U.email
                          ORDER BY total_rentals DESC",
        'columns'     => ['User ID', 'Full Name', 'Email', 'Total Rentals', 'Total Paid ($)', 'Last Rental'],
    ],
];

// Run queries
$results = [];
foreach ($queries as $i => $q) {
    $res = mysqli_query($conn, $q['sql']);
    $rows = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = $row;
        }
    }
    $results[$i] = [
        'rows'  => $rows,
        'error' => $res ? null : mysqli_error($conn),
    ];
}

include '../includes/header.php';
?>

<main>
<div class="container">
    <h2 class="section-title">Advanced Queries</h2>
    
    <?php foreach ($queries as $i => $q): ?>
    <div class="movie-card" style="margin-bottom: 2rem; padding: 1.5rem;">

        <div style="margin-bottom: 1rem;">
            <h3 class="movie-title" style="font-size: 1.1rem;"><?= htmlspecialchars($q['title']) ?></h3>
            <span class="badge" style="background: var(--primary-accent); color: #fff; margin-top: 0.4rem; display: inline-block;">
                <?= htmlspecialchars($q['description']) ?>
            </span>
        </div>

        <!-- SQL block -->
        <pre style="background:#111; color:#ccc; padding:1rem; border-radius:6px; overflow-x:auto; font-size:0.8rem; margin-bottom:1rem; line-height:1.5;"><?= htmlspecialchars(trim(preg_replace('/\s+/', ' ', $q['sql']))) ?></pre>

        <?php if ($results[$i]['error']): ?>
            <p style="color: var(--primary-accent);">Error: <?= htmlspecialchars($results[$i]['error']) ?></p>

        <?php elseif (empty($results[$i]['rows'])): ?>
            <p style="color: var(--text-muted); font-style: italic;">No results returned.</p>

        <?php else: ?>
            <div style="overflow-x: auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.875rem;">
                <thead>
                    <tr>
                        <?php foreach ($q['columns'] as $col): ?>
                        <th style="text-align:left; padding:8px 12px; border-bottom:1px solid #333; color:var(--text-muted); font-weight:600; white-space:nowrap;">
                            <?= htmlspecialchars($col) ?>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results[$i]['rows'] as $row): ?>
                    <tr style="border-bottom:1px solid #1e1e26;">
                        <?php foreach (array_values($row) as $cell): ?>
                        <td style="padding:8px 12px; color:var(--text-light);">
                            <?= htmlspecialchars((string)$cell) ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <p style="color:var(--text-muted); font-size:0.8rem; margin-top:0.5rem;">
                <?= count($results[$i]['rows']) ?> row(s) returned
            </p>
        <?php endif; ?>

    </div>
    <?php endforeach; ?>

</div>
</main>

<?php include '../includes/footer.php'; ?>
