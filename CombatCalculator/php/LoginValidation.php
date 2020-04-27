<?php

session_start();
unset($_SESSION['bad_login']);
unset($_SESSION['locked_out']);

// Connect to the server and select database
require_once 'databaseconnection.php';

// Username an password sent from the form, sanitized to protect fro MySQL injection
$myUsername = mysql_fix_string($mySqlConnection, $_POST['uname']);
$myPassword = mysql_fix_string($mySqlConnection, $_POST['psw']);

$maxLoginAttempts = 5; // Number of login attempts before a user is locked out
$ipAddressLockoutInterval = 24; // Set in Hours
$myIpAddress = getUserIpAddress(); // Get the users IP Address
//
// Return the number of times this IP address has failed to login and how long ago that attempt was.
// If the IP Address has not been used before, add it to the loginattempts table.
$sqlStatement = "SELECT last_login_attempt, failed_login_attempts FROM combatcalculator.loginattempts WHERE ip_address =inet6_aton('$myIpAddress')";

$sqlQueryReturn = $mySqlConnection->query($sqlStatement);

if (!$sqlQueryReturn) {
    $message = "Whole query " . $sqlStatement;
    echo $message;
    die('Invalid query: ' . mysqli_error($mySqlConnection));
} else if (mysqli_num_rows($sqlQueryReturn) == 0) {
    console_log("There are no login attempts from this IP address.");
    $sqlStatement = "INSERT INTO `combatcalculator`.`loginattempts` (`ip_address`, `last_login_attempt`) VALUES (INET6_ATON('$myIpAddress'), UTC_TIMESTAMP())";

    $sqlQueryReturn = $mySqlConnection->query($sqlStatement);

    if (!$sqlQueryReturn) {
        $message = "Whole query " . $sqlStatement;
        echo $message;
        die('Invalid query: ' . mysqli_error($mySqlConnection));
    } else {
        attemptLogin($myUsername, $myPassword, $myIpAddress, 0, $mySqlConnection);
    }
} else {
    while ($row = $sqlQueryReturn->fetch_assoc()) {
        $lastLoginAttempt = $row['last_login_attempt'];
        $failedLoginAttempts = $row['failed_login_attempts'];
    }
    console_log("Last Login Attempt: $lastLoginAttempt");
    console_log(" Number of Failed Login Attempts: $failedLoginAttempts");

    if ($failedLoginAttempts >= $maxLoginAttempts) {
        console_error("The max number of login attempts has been reached.");

        // Check to see if the difference between the last login attempt and now was
        // longer than the lockout interval.
        $dateDifference = date_diff(date_create(gmdate("Y-m-d H:i:s")), date_create($lastLoginAttempt));
        if ($dateDifference->h + $dateDifference->days * 24 >= $ipAddressLockoutInterval) {
            updateFailedLoginAttempts($myIpAddress, 0, $mySqlConnection);
            attemptLogin($myUsername, $myPassword, $myIpAddress, 0, $mySqlConnection);
        } else {
            header("Location:../login.php");
            $_SESSION['locked_out'] = true;
            console_error("User has exceeded the number of login attempts. Try again after at least 24 hours.");
        }
    } else {
        attemptLogin($myUsername, $myPassword, $myIpAddress, $failedLoginAttempts, $mySqlConnection);
    }
}
/*
  $result = $mySqlConnection->query($sql);

  if (!$result) {
  //something went wrong, display the error
  echo 'Something went wrong while signing in. Please try again later.';
  //die($conn->error); //debugging purposes, uncomment when needed
  } else {
  //the query was successfully executed, there are 2 possibilities
  //1. the query returned data, the user can be signed in
  //2. the query returned an empty result set, the credentials were wrong
  if (mysqli_num_rows($result) == 0) {
  echo 'You have supplied a wrong user/password combination. Please try again.';
  } else {
  //set the $_SESSION['signed_in'] variable to TRUE
  $_SESSION['signed_in'] = true;

  //we also put the user_id and user_name values in the $_SESSION, so we can use it at various pages
  while ($row = $result->fetch_assoc()) {
  $_SESSION['user_id'] = $row['id_users'];
  $_SESSION['user_name'] = $row['username'];
  $_SESSION['first_name'] = $row['first_name'];
  $_SESSION['last_name'] = $row['last_name'];
  $_SESSION['email'] = $row['email'];
  }

  echo 'Welcome, ' . $_SESSION['uname'] . '';
  }
  } */

function attemptLogin($username, $password, $ipAddress, $failedLoginAttempts, $sqlConnection) {
    //See if there are any users in the database with the username.
    $sqlStatement = "SELECT password "
            . "FROM `combatcalculator`.`users` "
            . "WHERE username = '$username'";

    $sqlQueryReturn = $sqlConnection->query($sqlStatement);

    if (!$sqlQueryReturn) {
        $message = "Attempted SQL Query: " . $sqlStatement;
        console_error($message);
        die('Invalid query: ' . mysqli_error($sqlConnection));
    } else if (mysqli_num_rows($sqlQueryReturn) == 0) {
        // If there are no users that match the specified username, increase the number of failed login attempts.
        updateFailedLoginAttempts($ipAddress, $failedLoginAttempts + 1, $sqlConnection);
        header("Location:../login.php");
        $_SESSION['bad_login']++;
    } else {
        // If there are users that match the specified username, see if the saved hashed password matches the input password.
        while ($row = $sqlQueryReturn->fetch_assoc()) {
            $sqlPassword = $row['password'];
        }
        if (password_verify($password, $sqlPassword)) {
            // If the passwords match, log the user in.
            updateFailedLoginAttempts($ipAddress, 0, $sqlConnection);
            $sqlStatement = "SELECT id_users, first_name, last_name, email, username"
                    . "FROM `combatcalculator`.`users` "
                    . "WHERE username = '$username'";

            $sqlQueryReturn = $sqlConnection->query($sqlStatement);

            if (!$sqlQueryReturn) {
                $message = "Attempted SQL Query: " . $sqlStatement;
                console_error($message);
                die('Invalid query: ' . mysqli_error($sqlConnection));
            } else {
                //set the $_SESSION['signed_in'] variable to TRUE
                $_SESSION['signed_in'] = true;

                //we also put the user_id and user_name values in the $_SESSION, so we can use it at various pages
                while ($row = $sqlQueryReturn->fetch_assoc()) {
                    $_SESSION['user_id'] = $row['id_users'];
                    $_SESSION['user_name'] = $row['username'];
                    $_SESSION['first_name'] = $row['first_name'];
                    $_SESSION['last_name'] = $row['last_name'];
                    $_SESSION['email'] = $row['email'];
                }
            }
        } else {
            // If the passwords do not match, update the failed login attempts.
            updateFailedLoginAttempts($ipAddress, $failedLoginAttempts + 1, $sqlConnection);
            header("Location:../login.php");
            $_SESSION['bad_login']++;
        }
    }
}

function updateFailedLoginAttempts($ipAddress, $newFailedAttemptsValue, $sqlConnection) {
    $sqlStatement = "UPDATE `combatcalculator`.`loginattempts`"
            . "SET last_login_attempt = UTC_TIMESTAMP(), failed_login_attempts = $newFailedAttemptsValue "
            . "WHERE ip_address = INET6_ATON('$ipAddress')";

    $sqlQueryReturn = $sqlConnection->query($sqlStatement);

    if (!$sqlQueryReturn) {
        $message = "Attempted SQL Query: " . $sqlStatement;
        console_error($message);
        die('Invalid query: ' . mysqli_error($sqlConnection));
    }
}

function console_log($message) {
    echo "<script>console.log('$message');</script>";
}

function console_error($message) {
    echo "<script>console.error('$message');</script>";
}