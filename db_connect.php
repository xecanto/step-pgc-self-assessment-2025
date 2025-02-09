<?php
$servername = "localhost";
$username = "root";
$password = "12340";
$dbname = "step_pgc";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>