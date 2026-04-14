-- ActiveRentals
-- All currently unreturned rentals with customer and movie info
-- Used by the admin dashboard
DROP VIEW IF EXISTS ActiveRentals;
CREATE VIEW ActiveRentals AS
    SELECT R.rentalid,
           R.rentaldate,
           R.duedate,
           U.userid,
           CONCAT(U.fname, ' ', U.lname) AS customer,
           U.email,
           M.movieid,
           M.title,
           M.rating,
           CASE
               WHEN R.duedate < CURDATE() THEN 'Overdue' COLLATE utf8mb4_unicode_ci
               ELSE 'Active' COLLATE utf8mb4_unicode_ci
           END AS status,
           DATEDIFF(CURDATE(), R.duedate) AS days_late,
           CalculateCharge(R.rentalid)    AS charge_owing
    FROM RENTAL R
    JOIN USER U     ON R.userid   = U.userid
    JOIN CONTAINS C ON R.rentalid = C.rentalid
    JOIN MOVIE M    ON C.movieid  = M.movieid
    WHERE R.returndate IS NULL;


-- MovieAvailability
-- Each movie with total and available copy counts
-- Used by rent.php and copies.php
DROP VIEW IF EXISTS MovieAvailability;
CREATE VIEW MovieAvailability AS
    SELECT M.movieid,
           M.title,
           M.director,
           M.releaseyear,
           M.duration,
           M.rating,
           COUNT(C.copynumber)  AS total_copies,
           SUM(C.isavailable)   AS available_copies
    FROM MOVIE M
    LEFT JOIN COPY C ON M.movieid = C.movieid
    GROUP BY M.movieid, M.title, M.director, M.releaseyear, M.duration, M.rating;


-- CustomerSummary
-- Per-customer rental and payment totals
-- Used by the admin dashboard and user profile
DROP VIEW IF EXISTS CustomerSummary;
CREATE VIEW CustomerSummary AS
    SELECT U.userid,
           CONCAT(U.fname, ' ', U.lname) AS full_name,
           U.email,
           U.phone,
           U.usertype,
           COUNT(DISTINCT R.rentalid)                                                        AS total_rentals,
           SUM(CASE WHEN R.returndate IS NULL AND R.duedate >= CURDATE() THEN 1 ELSE 0 END) AS active_rentals,
           SUM(CASE WHEN R.returndate IS NULL AND R.duedate <  CURDATE() THEN 1 ELSE 0 END) AS overdue_rentals,
           COALESCE(SUM(P.amount), 0)                                                        AS total_paid,
           MAX(R.rentaldate)                                                                  AS last_rental
    FROM USER U
    LEFT JOIN RENTAL R  ON U.userid  = R.userid
    LEFT JOIN PAYMENT P ON U.userid  = P.userid
    GROUP BY U.userid, U.fname, U.lname, U.email, U.phone, U.usertype;

DELIMITER $$

-- trg_auto_return_copy
-- When a rental's returndate is set, automatically flip the copy back
-- to available. This enforces the rule at the DB level regardless of
-- how the return is triggered.
DROP TRIGGER IF EXISTS trg_auto_return_copy$$
CREATE TRIGGER trg_auto_return_copy
AFTER UPDATE ON RENTAL
FOR EACH ROW
BEGIN
    IF OLD.returndate IS NULL AND NEW.returndate IS NOT NULL THEN
        UPDATE COPY
        SET isavailable = 1
        WHERE (copynumber, movieid) IN (
            SELECT copynumber, movieid
            FROM CONTAINS
            WHERE rentalid = NEW.rentalid
        );
    END IF;
END$$


-- trg_prevent_double_rental
-- Prevents inserting a rental for a copy that is already marked unavailable,
-- catching any attempt to bypass the application logic directly
DROP TRIGGER IF EXISTS trg_prevent_double_rental$$
CREATE TRIGGER trg_prevent_double_rental
BEFORE INSERT ON CONTAINS
FOR EACH ROW
BEGIN
    DECLARE v_available TINYINT;

    SELECT isavailable INTO v_available
    FROM COPY
    WHERE copynumber = NEW.copynumber AND movieid = NEW.movieid;

    IF v_available = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'This copy is not available for rental.';
    END IF;
END$$


-- trg_log_overdue_on_return
-- When a rental is returned late, updates the payment amount to include
-- the overdue fee in case it was inserted before the return date was known
DROP TRIGGER IF EXISTS trg_log_overdue_on_return$$
CREATE TRIGGER trg_log_overdue_on_return
AFTER UPDATE ON RENTAL
FOR EACH ROW
BEGIN
    IF OLD.returndate IS NULL AND NEW.returndate IS NOT NULL THEN
        UPDATE PAYMENT
        SET amount = CalculateCharge(NEW.rentalid)
        WHERE rentalid = NEW.rentalid;
    END IF;
END$$

DELIMITER ;
