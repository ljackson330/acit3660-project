# CPSC 3660 - Movie Rental System

## Group Members
Liam Jackson
Niko Strazinger
Jean Pascua
Nick Tan

## Setup

Database credentials are in `db_connect.php`

## Test Accounts

Email / Password / Role
niko@niko.com password admin
liam@rental.com password123 admin
jean@rental.com password123 customer
nick@rental.com password123 customer

## How to Test

**As a customer:**
- Register a new account or log in with one of the customer accounts above
- Browse and rent movies from the main page
- View rental history and return movies (charges $3.99 base + $1.00/day if overdue)
- Edit account info from the profile page

**As an admin:**
- Log in with an admin account
- Dashboard shows live stats and all active/overdue rentals
- From the dashboard you can: add/update/delete movies, manage copies, create/delete users, view advanced queries, and access the rental archive
- Adding a movie lets you set the initial number of copies at the same time
- The rental archive page demonstrates DDL — click "Create RENTAL_ARCHIVE Table" then "Archive Returned Rentals"

## Advanced Queries

`actions/queries.php` — 15 queries demonstrating DISTINCT, COUNT/AVG, GROUP BY, HAVING, BETWEEN, LIKE, IN, IS NULL, NOT, CASE, subqueries, ALL, ANY, and UNION.

## Stored Procedures / Views / Triggers

We implemented stored procedures (`CreateRental`, `ProcessReturn`), a function (`CalculateCharge`), three views (`ActiveRentals`, `MovieAvailability`, `CustomerSummary`), and three triggers - see `sql/procedures.sql` and `sql/views_triggers.sql`

These all work correctly on a local MariaDB instance but could not be deployed to vcandle due to apparent permissions issues, the equivalent functionality is implemented in the relevant php files now
