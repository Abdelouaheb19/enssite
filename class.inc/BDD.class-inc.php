
<?php

$hostname = "localhost";  
$username= "root";       
$password= "";           
$database = "tuto";    
    
$conn = mysqli_connect($hostname, $username, $password,$database) or trigger_error(mysqli_err(),E_USER_ERROR); 

?>
