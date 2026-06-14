<?php
session_start();
require_once __DIR__ . '/../logic/db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'לא מחובר']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'שיטה לא חוקית']);
    exit();
}

$userId     = $_SESSION['user_id'];
$itemId     = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$title      = trim($_POST['title'] ?? '');
$catId      = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
$kosher     = trim($_POST['kosher_status'] ?? 'פרווה');
$expiry     = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
$cooked     = !empty($_POST['cooked_at']) ? $_POST['cooked_at'] : null;
$allergens  = trim($_POST['allergens'] ?? '');
$userDesc   = trim($_POST['description'] ?? '');

if ($itemId <= 0 || !$title || $catId <= 0 || !$userDesc) {
    echo json_encode(['success' => false, 'message' => 'אנא מלא את כל שדות החובה']);
    exit();
}

// בדיקה אם הפריט שייך למשתמש המחובר
$checkSql = "SELECT user_id, description FROM items WHERE item_id = ?";
$checkStmt = $conn->prepare($checkSql);
if (!$checkStmt) {
    echo json_encode(['success' => false, 'message' => 'שגיאה בהכנת שאילתת הבדיקה: ' . $conn->error]);
    exit();
}
$checkStmt->bind_param("i", $itemId);
$checkStmt->execute();
$checkRes = $checkStmt->get_result()->fetch_assoc();

if (!$checkRes) {
    echo json_encode(['success' => false, 'message' => 'הפריט לא נמצא']);
    exit();
}

if ((int)$checkRes['user_id'] !== (int)$userId) {
    echo json_encode(['success' => false, 'message' => 'אין לך הרשאה לערוך פריט זה']);
    exit();
}

// שימור חלקי ה-AI מהתיאור הקיים באמצעות חיפוש אינדקסים בטוח
$existingDesc = $checkRes['description'] ?? '';
$aiParts = '';
$aiIndex = false;
$markers = ["ניתוח AI:", "💡 פידבק מהמערכת:", "פידבק מהמערכת:", "פידבק המערכת:"];
foreach ($markers as $marker) {
    $pos = mb_strpos($existingDesc, $marker);
    if ($pos !== false) {
        if ($aiIndex === false || $pos < $aiIndex) {
            $aiIndex = $pos;
        }
    }
}
if ($aiIndex !== false) {
    $aiParts = "\n\n" . trim(mb_substr($existingDesc, $aiIndex));
}

// הרכבת התיאור הסופי החדש
$finalDescription = "תיאור המשתמש: " . $userDesc;
if (!empty($aiParts)) {
    $finalDescription .= $aiParts;
}

// התאמת התאריכים לפי קטגוריה
if ($catId === 4) {
    $expiry = null; // אוכל מבושל לא צריך תוקף אלא זמן בישול
} else if (in_array($catId, [1, 3, 5])) {
    $cooked = null; // מוצרים יבשים/קפואים צריכים תאריך תפוגה
} else {
    $expiry = null;
    $cooked = null;
}

if ($cooked) {
    $cooked = str_replace('T', ' ', $cooked);
}

// עדכון בסיס הנתונים
$updateSql = "UPDATE items SET 
              title = ?, 
              category_id = ?, 
              description = ?, 
              kosher_status = ?, 
              expiry_date = ?, 
              cooked_at = ?, 
              allergens = ? 
              WHERE item_id = ?";

$updateStmt = $conn->prepare($updateSql);
if (!$updateStmt) {
    echo json_encode(['success' => false, 'message' => 'שגיאה בהכנת השאילתה: ' . $conn->error]);
    exit();
}

$updateStmt->bind_param("sisssssi", 
    $title, 
    $catId, 
    $finalDescription, 
    $kosher, 
    $expiry, 
    $cooked, 
    $allergens, 
    $itemId
);

if ($updateStmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'הפריט עודכן בהצלחה']);
} else {
    echo json_encode(['success' => false, 'message' => 'שגיאה בעדכון הפריט: ' . $conn->error]);
}
