<?php
session_start();
require_once 'databaseconnection.php';
$myusername = $_POST['uname'];
$mypassword = $_POST['psw'];
            $sql = "SELECT 
                        id_users,
                        first_name,
                        last_name
                    FROM
                        combatcalculator
                    WHERE
                        username = '" . mysql_fix_string($con,$_POST['uname']) . "'
                    AND
                        password = '" . hash("ripemd128",$_POST['psw']) . "'";
             //echo $sql;            
            $result = $con->query($sql);
            
               if(!$result)
            {
                //something went wrong, display the error
                echo 'Something went wrong while signing in. Please try again later.';
                //die($conn->error); //debugging purposes, uncomment when needed
            }
            else
            {
                //the query was successfully executed, there are 2 possibilities
                //1. the query returned data, the user can be signed in
                //2. the query returned an empty result set, the credentials were wrong
                if(mysqli_num_rows($result) == 0)
                {
                    echo 'You have supplied a wrong user/password combination. Please try again.';
                }
                else
                {
                    //set the $_SESSION['signed_in'] variable to TRUE
                    $_SESSION['signed_in'] = true;
                     
                    //we also put the user_id and user_name values in the $_SESSION, so we can use it at various pages
                    while($row = $result->fetch_assoc())
                    {
                        $_SESSION['user_id']    = $row['id_users'];
                        $_SESSION['user_name']  = $row['first_name']." ".$row['last_name'];
                    }
                     
                    echo 'Welcome, ' . $_SESSION['uname'] . '';
                }
            }
        
