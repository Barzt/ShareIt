<?php
// מניעת הדפסת אזהרות מלוכלכות לתוך ה-JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once '../logic/db_config.php';
require_once '../logic/request_actions.php'; // מוודא שהפונקציה approveRequest זמינה

header('Content-Type: application/json');

// 1. אבטחה: וידוא שהמשתמש מחובר
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'יש להתחבר למערכת.']);
    exit();
}

// 2. קריאת נתוני ה-JSON שהגיעו מה-JavaScript
$inputData = json_decode(file_get_contents('php://input'), true);
$requestId = isset($inputData['request_id']) ? (int)$inputData['request_id'] : 0;

if ($requestId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'מזהה בקשה לא תקין.']);
    exit();
}

try {
    // 3. הרצת פונקציית העדכון מתוך request_actions.php
    $success = approveRequest($requestId);

    if ($success) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'עדכון הסטטוס נכשל במסד הנתונים.']);
    }
    exit();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'שגיאת שרת פנימית: ' . $e->getMessage()]);
    exit();
}
?>