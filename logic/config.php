<?php
// logic/config.php

// 1. הגדרות בסיס הנתונים
define('DB_HOST', 'localhost');
define('DB_USER', 'YOUR_DB_USER_HERE'); 
define('DB_PASS', 'YOUR_DB_PASS_HERE');    
define('DB_NAME', 'YOUR_DB_NAME_HERE'); 

// 2. המפתח של Gemini AI (מעודכן למודל Gemini 3 Flash)
define('GEMINI_API_KEY', 'YOUR_GEMINI_KEY_HERE');

// 3. הגדרות נתיבים
define('UPLOAD_DIR', '../uploads/');

// 4. מפתח API אקדמי לגישה לנתוני ערים
define('CITY_DATA_API_KEY', 'YOUR_CITY_DATA_API_KEY_HERE');

/**
 * הגדרת פרוטוקול מאובטח כקבוע גלובלי
 * עוזר במקרה שצריך לבנות כתובות URL מלאות בעתיד
 */
define('SITE_PROTOCOL', 'https://');
?>