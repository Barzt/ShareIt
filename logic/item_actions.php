<?php
// logic/item_actions.php
require_once __DIR__ . '/config.php';       
require_once __DIR__ . '/db_config.php';    
require_once __DIR__ . '/ai_processor.php'; 

/**
 * פונקציה לניהול כל תהליך העלאת הפריט: תמונה -> AI -> בסיס נתונים
 */
function handleNewItemUpload($user_id, $cat_id, $kosher, $allergens, $expiry, $cooked, $user_description, $file, $lat, $lng, $addr, $title = null) {
    
    // 1. הגדרת נתיבים
    $uploadDir = UPLOAD_DIR;
    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
    $targetPath = $uploadDir . $fileName; 
    $dbImgUrl = 'uploads/' . $fileName;

    if (!is_writable($uploadDir)) {
        die("DEBUG ERROR: Folder is not writable.");
    }

    // 2. שמירת הקובץ הפיזי בשרת
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        
        // 3. ניתוח תמונה באמצעות AI - בדיקה אם יש כבר תוצאות שנשלחו מהדפדפן (ממנוע ה-AJAX של הטופס)
        $aiResults = null;
        if (isset($_POST['ai_is_safe']) && $_POST['ai_is_safe'] !== "") {
            // ה-AI כבר זיהה בהצלחה ברקע, נשתמש בנתונים הקיימים כדי למנוע כפל פניות לגוגל וחסימת 429
            $aiResults = [
                'is_food' => true,
                'is_safe' => $_POST['ai_is_safe'] === '1' ? true : false,
                'label' => $_POST['ai_labels'] ?? 'Food',
                'description' => $_POST['ai_description'] ?? '',
                'ai_feedback' => $_POST['ai_feedback'] ?? ''
            ];
        } else {
            // גיבוי: אם הדפדפן לא שלח נתונים, ננסה לנתח כעת
            $aiResults = analyzeImageWithGemini($targetPath);
        }
        
        // --- לוגיקת בטיחות (Safety Gate) ---
        // אם ה-AI זיהה שהאוכל לא בטוח או לא אוכל, אנחנו עוצרים הכל
        if ($aiResults && isset($aiResults['is_safe']) && $aiResults['is_safe'] === false) {
            unlink($targetPath); // מחיקת התמונה כי היא לא תפורסם
            $reason = $aiResults['safety_warning'] ?? "התמונה זוהתה כלא בטוחה או לא מתאימה למאכל אדם.";
            die("סליחה, לא ניתן לפרסם את הפריט: " . $reason);
        }

        // 4. חילוץ נתונים מה-AI עם ערכי ברירת מחדל חכמים
        $aiAutoDesc = $aiResults['description'] ?? "";
        $aiFeedback = $aiResults['ai_feedback'] ?? "";
        $aiSafe     = ($aiResults && isset($aiResults['is_safe']) && $aiResults['is_safe']) ? 1 : 0;
        
        // ניקוי תוויות: אם זה אוכל נרשום את סוג המזון, אחרת Unknown
        $aiLabels   = ($aiResults && isset($aiResults['is_food']) && $aiResults['is_food']) ? ($aiResults['label'] ?? "Food") : "Unknown";

        // קביעת הכותרת הסופית: אם המשתמש שלח כותרת מהטופס - נשתמש בה. אם לא - ניקח את של ה-AI
        $finalTitle = (!empty($title) && $title !== "הכותרת תתמלא אוטומטית ע'י ה-AI או הזן ידנית...") ? $title : ($aiResults['label'] ?? "מוצר חדש");

        // 5. שילוב תיאורים - הזרקת הפידבק לתוך ה-Description כדי לחסוך עמודות ב-SQL
        $fullDescLines = [];
        if (!empty($user_description)) $fullDescLines[] = "תיאור המשתמש: " . $user_description;
        if (!empty($aiAutoDesc))      $fullDescLines[] = "ניתוח AI: " . $aiAutoDesc;
        if (!empty($aiFeedback))      $fullDescLines[] = "💡 פידבק מהמערכת: " . $aiFeedback;
        
        $finalDescription = implode("\n\n", $fullDescLines);

        // 6. שמירה סופית בבסיס הנתונים עם ה-Title המעודכן והנכון!
        $result = addItem($user_id, $cat_id, $finalTitle, $finalDescription, $kosher, $allergens, $expiry, $cooked, $dbImgUrl, $aiLabels, $aiSafe, $lat, $lng, $addr);
        
        return $result;
    }
    
    return false;
}

/**
 * פונקציה לביצוע ה-SQL Insert בפועל
 */
function addItem($user_id, $cat_id, $title, $desc, $kosher, $allergens, $expiry, $cooked, $img_url, $ai_labels, $ai_safe, $lat, $lng, $addr) {
    global $conn;
    if (!$conn) return false;

    $sql = "INSERT INTO items (user_id, category_id, title, description, kosher_status, allergens, expiry_date, cooked_at, image_url, ai_labels, ai_is_safe, item_lat, item_lng, item_address, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    
    // וידוא פורמט הכתובת (קיצור אם ארוך מדי)
    $cleanAddr = mb_substr($addr, 0, 255);
    
    $stmt->bind_param("iissssssssidss", 
        $user_id, $cat_id, $title, $desc, $kosher, $allergens, $expiry, $cooked, $img_url, $ai_labels, $ai_safe, $lat, $lng, $cleanAddr
    );
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    return false;
}