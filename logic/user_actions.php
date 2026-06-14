<?php
require_once 'db_config.php';

/**
 * פונקציה לרישום משתמש חדש במערכת ShareIt
 */
function registerUser($fname, $lname, $email, $pass, $phone, $city, $street, $h_num, $apt, $f_address, $lat, $lng) {
    global $conn;
    
    // 1. הגנה: הצפנת סיסמה לפני שמירה
    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
    
    // 2. הכנת השאילתה (Prepared Statement) למניעת פריצות SQL Injection
    $sql = "INSERT INTO users (first_name, last_name, email, password, phone, city, street, house_number, apartment, formatted_address, lat, lng) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    
    // 3. קישור המשתנים (s = string, d = double/decimal)
    // יש לנו 10 מחרוזות ו-2 מספרים עשרוניים (lat, lng)
    $stmt->bind_param("ssssssssssdd", $fname, $lname, $email, $hashed_password, $phone, $city, $street, $h_num, $apt, $f_address, $lat, $lng);
    
    // 4. ביצוע השאילתה
    if ($stmt->execute()) {
        return true; // הצליח
    } else {
        error_log("Error in registration: " . $stmt->error);
        return false; // נכשל
    }
}
?>