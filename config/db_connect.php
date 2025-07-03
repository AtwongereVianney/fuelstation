<?php

$host = 'localhost';
$user = 'root'; // Change if not using root
$pass = '';    // Change to your MySQL password
$db   = 'uganda_fuel_stations';

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}
?> 