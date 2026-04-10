<?php
// hostname wil change depending on environment
// if developing on localhost w/ xampp (apache/mysql) use 'vcandle.cs.uleth.ca'
$host = 'vcandle.cs.uleth.ca';
$dbname = 'fivg3669';
$username = 'fivg3669';
$password = 'ahW9paich4';

$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>