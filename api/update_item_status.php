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

$userId = $_SESSION['user_id'];
$itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;

if ($itemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'מזהה פריט לא תקין']);
    exit();
}

// בדיקה אם הפריט שייך למשתמש המחובר
$checkSql = "SELECT user_id FROM items WHERE item_id = ?";
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
    echo json_encode(['success' => false, 'message' => 'אין לך הרשאה לבצע פעולה זו']);
    exit();
}

// התחלת טרנזקציה לעדכון הפריט והבקשות שלו בצורה אטומית
$conn->begin_transaction();

try {
    // במערכת שלנו הסטטוס המבוקש הוא 'taken' (נמסר)
    $dbStatus = 'taken';
    
    $updateSql = "UPDATE items SET status = ? WHERE item_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    if (!$updateStmt) {
        throw new Exception("שגיאה בהכנת עדכון סטטוס הפריט: " . $conn->error);
    }
    $updateStmt->bind_param("si", $dbStatus, $itemId);
    $updateStmt->execute();

    // עדכון כל בקשות האיסוף המשויכות לפריט זה לסטטוס 'completed' ו'paid'
    $updateReqSql = "UPDATE requests SET request_status = 'completed', payment_status = 'paid' WHERE item_id = ? AND request_status IN ('pending', 'approved')";
    $updateReqStmt = $conn->prepare($updateReqSql);
    if (!$updateReqStmt) {
        throw new Exception("שגיאה בהכנת עדכון בקשות האיסוף: " . $conn->error);
    }
    $updateReqStmt->bind_param("i", $itemId);
    $updateReqStmt->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'הפריט סומן כנמסר בהצלחה']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'שגיאה בעדכון הסטטוס: ' . $e->getMessage()]);
}
