<?php

$sname= "localhost";
$unmae= "root";
$password = "";

$db_name = "social_app";
try{
    $conn = mysqli_connect($sname, $unmae, $password, $db_name);
} catch (exception $e) {
    echo "couldn't connect to database" ;
    echo $e->getMessage();
} 
?>




