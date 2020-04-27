<?php

$host = "localhost"; //host location
$user = "root";
$password = ""; //This will need to be updated and changed based on your instance of MySQL
$dbname = "combatcalculator";
$mySqlConnection = new mysqli($host, $user, $password, $dbname)
        or die('Could not connect to the database server.  ' . mysqli_connect_error($mySqlConnection));

function mysql_fix_string($conn, $string) {
    if (get_magic_quotes_gpc()) {
        $string = stripslashes($string);
    }
    $string = htmlentities($string);
    return $conn->real_escape_string($string);
}

function getUserIpAddress() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        //ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        //ip pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}
