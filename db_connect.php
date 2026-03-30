<?php
$host = 'localhost';
$dbname = 'fivg3669';
$username = 'fivg3669';
$password = 'ahW9paich4';

$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>