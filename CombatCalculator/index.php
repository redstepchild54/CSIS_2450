<!DOCTYPE html>
<?php
session_start();
require_once 'php/loginredirect.php';
?>
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        echo "<p>You have successfully logged in. Welcome " . $_SESSION['username'] . "!";
        ?>
    </body>
</html>
