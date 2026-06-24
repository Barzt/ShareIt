<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// api/upload_item.php
session_start();
require_once __DIR__ . '/../logic/db_config.php';
require_once __DIR__ . '/../logic/item_actions.php'; 
require_once __DIR__ . '/../logic/ai_processor.php'; // טעינת פונקציית ה-AI

// אבטחה: וידוא שהמשתמש מחובר
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    die(json_encode(['status' => 'error', 'message' => 'session_missing']));
}

// =========================================================================
//  מנגנון AJAX לניתוח מהיר של תמונה ברקע (לפני שליחת הטופס הסופי)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_analyze']) && isset($_FILES['item_image'])) {
    header('Content-Type: application/json');
    
    $tmpName = $_FILES['item_image']['tmp_name'];
    $originalName = $_FILES['item_image']['name'];
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    
    // יצירת נתיב זמני בתוך תיקיית ה-uploads המקומית של האתר
    // פותר בעיות של הרשאות קריאה מתיקיית /tmp/ של השרת (open_basedir)
    $tempTarget = '../uploads/temp_analyze_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    
    if (move_uploaded_file($tmpName, $tempTarget)) {
        // שליחת התמונה לניתוח ב-Gemini מתוך תיקיית האתר המורשית
        $aiResults = analyzeImageWithGemini($tempTarget);
        
        // מחיקת הקובץ הזמני מיד בסיום הניתוח
        if (file_exists($tempTarget)) {
            unlink($tempTarget);
        }
        
        if ($aiResults) {
            echo json_encode($aiResults);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ai_failed']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'temp_upload_failed']);
    }
    exit(); // עוצר כאן ולא ממשיך לשמירה ב-Database
}

// =========================================================================
//  תהליך שליחת הטופס הסופי ושמירה במסד הנתונים
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['item_image'])) {
    $userId    = $_SESSION['user_id'];
    
    // קליטת נתונים מהטופס (כולל שדה הכותרת החדש שערוך ע'י המשתמש)
    $title     = $_POST['title'] ?? 'מוצר חדש'; 
    $lat       = $_POST['item_lat'] ?? 0;
    $lng       = $_POST['item_lng'] ?? 0;
    $address   = $_POST['item_address'] ?? '';
    $catId     = $_POST['category_id'] ?? 6; 
    $kosher    = $_POST['kosher_status'] ?? 'פרווה';
    $allergens = $_POST['allergens'] ?? '';
    $expiry    = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $cooked    = !empty($_POST['cooked_at']) ? $_POST['cooked_at'] : null;
    
    // קליטת שדה התיאור
    $userDesc  = $_POST['description'] ?? '';

    // העברה לפונקציית העיבוד המרכזית ב-logic/item_actions.php
    $newItemId = handleNewItemUpload(
        $userId, 
        $catId, 
        $kosher, 
        $allergens, 
        $expiry, 
        $cooked, 
        $userDesc,
        $_FILES['item_image'], 
        $lat, 
        $lng, 
        $address,
        $title // העברת הכותרת הסופית שהתקבלה מהטופס
    );

    if ($newItemId) {
        // הצלחה: הפניה חזרה לפיד עם הודעת אישור
        $_SESSION['success_message'] = "הפריט פורסם בהצלחה!";
        header("Location: ../views/index.php?upload=success");
        exit();
    } else {
        // כישלון: הודעה למשתמש
        error_log("Upload failed for user ID: $userId");
        die("שגיאה: השמירה נכשלה. ייתכן שיש בעיה בחיבור ל-Database או ב-AI. בדקי את ה-Log של השרת.");
    }
} else {
    die("בקשה לא תקינה או חסרה תמונה.");
}