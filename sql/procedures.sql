DELIMITER $$

-- CalculateCharge
-- Returns the amount owed for a rental based on base rate + overdue fees
-- Base rate: $3.99, overdue adds $1.00 per day past due date
DROP FUNCTION IF EXISTS CalculateCharge$$
CREATE FUNCTION CalculateCharge(p_rentalid INT)
RETURNS DECIMAL(10,2)
READS SQL DATA
BEGIN
    DECLARE v_duedate    DATE;
    DECLARE v_returndate DATE;
    DECLARE v_days_late  INT;
    DECLARE v_charge     DECIMAL(10,2);

    SELECT duedate, returndate
    INTO v_duedate, v_returndate
    FROM RENTAL
    WHERE rentalid = p_rentalid;

    -- Use today if not yet returned
    SET v_days_late = GREATEST(0, DATEDIFF(COALESCE(v_returndate, CURDATE()), v_duedate));
    SET v_charge    = 3.99 + (v_days_late * 1.00);

    RETURN ROUND(v_charge, 2);
END$$


-- ProcessReturn
-- Marks a rental as returned, flips the copy back to available,
-- and inserts a payment record. All in one transaction
-- OUT p_charge returns the amount charged
DROP PROCEDURE IF EXISTS ProcessReturn$$
CREATE PROCEDURE ProcessReturn(
    IN  p_rentalid INT,
    IN  p_userid   INT,
    OUT p_charge   DECIMAL(10,2),
    OUT p_error    VARCHAR(255)
)
BEGIN
    DECLARE v_returndate DATE;
    DECLARE v_copynumber INT;
    DECLARE v_movieid    INT;
    DECLARE v_today      DATE DEFAULT CURDATE();

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_error = 'An unexpected database error occurred.';
    END;

    SET p_error = NULL;

    -- Validate rental exists, belongs to user, and is not already returned
    SELECT returndate, copynumber, movieid
    INTO v_returndate, v_copynumber, v_movieid
    FROM RENTAL R
    JOIN CONTAINS C ON R.rentalid = C.rentalid
    WHERE R.rentalid = p_rentalid AND R.userid = p_userid
    LIMIT 1;

    IF v_returndate IS NOT NULL THEN
        SET p_error = 'This rental has already been returned.';
    ELSE
        SET p_charge = CalculateCharge(p_rentalid);

        START TRANSACTION;

        UPDATE RENTAL
        SET returndate = v_today
        WHERE rentalid = p_rentalid;

        UPDATE COPY
        SET isavailable = 1
        WHERE copynumber = v_copynumber AND movieid = v_movieid;

        INSERT INTO PAYMENT (amount, paydate, userid, rentalid)
        VALUES (p_charge, v_today, p_userid, p_rentalid);

        COMMIT;
    END IF;
END$$


-- CreateRental
-- Finds an available copy of the requested movie, creates a rental record,
-- links it via CONTAINS, and marks the copy unavailable
-- OUT p_rentalid returns the new rental ID (or -1 on failure)
DROP PROCEDURE IF EXISTS CreateRental$$
CREATE PROCEDURE CreateRental(
    IN  p_userid   INT,
    IN  p_movieid  INT,
    OUT p_rentalid INT,
    OUT p_duedate  DATE,
    OUT p_error    VARCHAR(255)
)
BEGIN
    DECLARE v_copynumber INT DEFAULT NULL;
    DECLARE v_today      DATE DEFAULT CURDATE();

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_rentalid = -1;
        SET p_error    = 'An unexpected database error occurred.';
    END;

    SET p_error    = NULL;
    SET p_rentalid = -1;
    SET p_duedate  = DATE_ADD(v_today, INTERVAL 7 DAY);

    -- Find an available copy
    SELECT copynumber INTO v_copynumber
    FROM COPY
    WHERE movieid = p_movieid AND isavailable = 1
    LIMIT 1;

    IF v_copynumber IS NULL THEN
        SET p_error = 'No copies available for this movie.';
    ELSE
        START TRANSACTION;

        INSERT INTO RENTAL (rentaldate, duedate, userid)
        VALUES (v_today, p_duedate, p_userid);

        SET p_rentalid = LAST_INSERT_ID();

        INSERT INTO CONTAINS (rentalid, copynumber, movieid, quantity)
        VALUES (p_rentalid, v_copynumber, p_movieid, 1);

        UPDATE COPY
        SET isavailable = 0
        WHERE copynumber = v_copynumber AND movieid = p_movieid;

        COMMIT;
    END IF;
END$$

DELIMITER ;
