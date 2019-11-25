<?php
    $server = "localhost";
    $username = "root";
    $password = "";
    $database = "";
    
    //establish the connection to the DB
    $connection = mysqli_connect($server,$username,$password,$database);
try {
    $connection = mysqli_connect($server,$username,$password,$database);
     if($connection) {
    }
} catch (Exception $errormsg) {
    echo $errormsg->getMessage();
}
?>