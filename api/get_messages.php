<?php
// הגדרות דיבאג זמניות - אם יש שגיאה, שהיא לא תתערבב ב-JSON בצורה מלוכלכת
ini_set('display_errors', 0); 
error_reporting(E_ALL);

session_start();
require_once '../logic/db_config.php';

header('Content-Type: application/json');

// 1. אבטחה: וידוא שהמשתמש מחובר
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized', 'message' => 'יש להתחבר למערכת.']);
    exit();
}

$requestId = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;
$myId = (int)$_SESSION['user_id'];

if ($requestId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_request_id']);
    exit();
}

try {
    // =========================================================================
    // 2. שלב א': שליפת נתוני הבקשה מטבלת requests
    // =========================================================================
    $reqSql = "SELECT item_id, adopter_user_id, request_status, payment_status FROM requests WHERE request_id = ?";
    $reqStmt = $conn->prepare($reqSql);
    $reqStmt->bind_param("i", $requestId);
    $reqStmt->execute();
    $requestData = $reqStmt->get_result()->fetch_assoc();

    if (!$requestData) {
        http_response_code(404);
        echo json_encode(['error' => 'request_not_found', 'message' => 'בקשת האיסוף לא נמצאה במערכת.']);
        exit();
    }

    $itemId    = (int)$requestData['item_id'];
    $adopterId = (int)$requestData['adopter_user_id'];

    // =========================================================================
    // 3. שלב ב': שליפת מזהה המפרסם (user_id כמפתח זר) מטבלת items
    // =========================================================================
    $itemSql = "SELECT user_id FROM items WHERE item_id = ?";
    $itemStmt = $conn->prepare($itemSql);
    $itemStmt->bind_param("i", $itemId);
    $itemStmt->execute();
    $itemData = $itemStmt->get_result()->fetch_assoc();

    if (!$itemData) {
        http_response_code(404);
        echo json_encode(['error' => 'item_not_found', 'message' => 'הפריט המשויך לבקשה זו לא נמצא.']);
        exit();
    }

    $uploaderId = (int)$itemData['user_id'];

    // =========================================================================
    // 4. בדיקת אבטחה: וידוא שרק המשתמשים המורשים (נועה או דנה) צופים בצ'אט
    // =========================================================================
    if ($myId !== $adopterId && $myId !== $uploaderId) {
        http_response_code(403);
        echo json_encode(['error' => 'access_denied', 'message' => 'אין לך הרשאה לצפות בצ\'אט זה.']);
        exit();
    }

    // בניית מערך ה-Meta
    $metaData = [
        'request_status' => $requestData['request_status'],
        'payment_status' => $requestData['payment_status'],
        'uploader_id'    => $uploaderId
    ];

    // =========================================================================
    // 5. שלב ג': שליפת היסטוריית הודעות הצ'אט
    // =========================================================================
    $msgSql = "SELECT m.*, u.first_name 
               FROM messages m 
               JOIN users u ON m.sender_id = u.user_id 
               WHERE m.request_id = ? 
               ORDER BY m.created_at ASC";

    $msgStmt = $conn->prepare($msgSql);
    $msgStmt->bind_param("i", $requestId);
    $msgStmt->execute();
    $result = $msgStmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $row['is_mine'] = ((int)$row['sender_id'] === $myId);
        $messages[] = $row;
    }

    // החזרת הנתונים בצורה נקייה ומובטחת
    echo json_encode([
        'meta' => $metaData,
        'messages' => $messages
    ]);
    exit();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error', 'message' => $e->getMessage()]);
    exit();
}
?>