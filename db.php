<?php
    $conn = new mysqli("localhost", "root", "", "BigBrother", "3308"); //remove 3308 for your setup, mine personally starts at port 3308
    if ($conn->connect_error){
        die("Connection failed: " . $conn->connect_error);
    }
?>