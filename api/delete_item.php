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
$checkStmt->bind_param("i", $itemId);
$checkStmt->execute();
$checkRes = $checkStmt->get_result()->fetch_assoc();

if (!$checkRes) {
    echo json_encode(['success' => false, 'message' => 'הפריט לא נמצא']);
    exit();
}

if ((int)$checkRes['user_id'] !== (int)$userId) {
    echo json_encode(['success' => false, 'message' => 'אין לך הרשאה למחוק פריט זה']);
    exit();
}

// שליפת נתיב התמונה של הפריט ומחיקתה מהשרת לפני מחיקת השורה מהבסיס נתונים
$imageSql = "SELECT image_url FROM items WHERE item_id = ?";
$imageStmt = $conn->prepare($imageSql);
$imageStmt->bind_param("i", $itemId);
$imageStmt->execute();
$imageResult = $imageStmt->get_result()->fetch_assoc();

if ($imageResult && !empty($imageResult['image_url'])) {
    $imageUrl = $imageResult['image_url'];
    $imagePath = (strpos($imageUrl, '../') === 0) ? $imageUrl : '../' . $imageUrl;
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
}

// מחיקת הפריט (הבסיס נתונים יבצע מחיקה מדורגת לבקשות והודעות בזכות CASCADE)
$deleteSql = "DELETE FROM items WHERE item_id = ?";
$deleteStmt = $conn->prepare($deleteSql);
$deleteStmt->bind_param("i", $itemId);

if ($deleteStmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'הפריט נמחק בהצלחה']);
} else {
    echo json_encode(['success' => false, 'message' => 'שגיאה במחיקת הפריט: ' . $conn->error]);
}
