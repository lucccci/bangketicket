<?php

    $servername = "localhost";

    $username = "u895643266_innovatrix10";

    $password = "InnovatrixACDJKM10!";

    $dbname = "u895643266_bangketicketdb"; 

    $conn = new mysqli($servername, $username, $password, $dbname);

    if($conn->connect_error) {

        die("Connection Failed" . $conn->connect_error);

    }
    // Set the time zone for the MySQL session
    $conn->query("SET time_zone = '+08:00';");
?> 