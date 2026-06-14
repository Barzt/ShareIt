<?php
$servername = "localhost";
$username = "YOUR_DB_USER_HERE"; 
$password = "YOUR_DB_PASS_HERE"; 
$dbname = "YOUR_DB_NAME_HERE";     

// יצירת החיבור
$conn = new mysqli($servername, $username, $password, $dbname);

// בדיקת חיבור
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// הגדרת קידוד לעברית
$conn->set_charset("utf8mb4");
?>