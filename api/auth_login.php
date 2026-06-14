<?php
// api/auth_login.php
session_start();
require_once '../logic/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // שליפת המשתמש לפי אימייל
    $sql = "SELECT user_id, first_name, password, email, lat, lng, formatted_address, profile_picture FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // בדיקה אם הסיסמה המוצפנת ב-DB תואמת למה שהוקלד
        if (password_verify($password, $user['password'])) {
            
            // הצלחה! שומרים את כל הנתונים החשובים ב-Session
            $_SESSION['user_id']      = $user['user_id'];
            $_SESSION['first_name']   = $user['first_name'];
            $_SESSION['user_email']   = $user['email'];
            $_SESSION['user_lat']     = $user['lat'];
            $_SESSION['user_lng']     = $user['lng'];
            $_SESSION['user_address'] = $user['formatted_address'];
            $_SESSION['profile_picture'] = $user['profile_picture'];

            header("Location: ../views/index.php");
            exit();
        } else {
            echo "סיסמה שגויה.";
        }
    } else {
        echo "משתמש לא נמצא.";
    }
}