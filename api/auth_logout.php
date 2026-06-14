<?php
session_start();

// מחיקת כל המשתנים ב-Session (כולל הכתובת הישנה והארוכה)
$_SESSION = array();

// השמדת ה-Session בשרת
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// חזרה לדף הבית כשהמערכת נקייה
header("Location: ../views/index.php");
exit();
?>