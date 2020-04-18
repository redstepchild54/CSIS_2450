<?php

$host = "localhost"; //host location
$user = "root"; 
$password = ""; //This will need to be updated and changed based on your instance of MySQL
$dbname = "combatcalculator";
$con = new mysqli($host, $user, $password, $dbname)
        or die('Could not connect to the database server.  ' . mysqli_connect_error($con));

function mysql_fix_string($conn, $string) {
    if (get_magic_quotes_gpc()) {
        $string = stripslashes($string);
    }
    $string = htmlentities($string);
    return $conn->real_escape_string($string);
}
