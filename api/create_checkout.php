<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// api/create_checkout.php
session_start();

// טעינת ספריות הלוגיקה והחיבור ל-DB
require_once __DIR__ . '/../logic/db_config.php';
require_once __DIR__ . '/../logic/request_actions.php'; 

// אבטחה: וידוא שהמשתמש מחובר במערכת
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    die(json_encode(['error' => 'session_missing', 'message' => 'יש להתחבר למערכת כדי לבצע פעולה זו.']));
}

header('Content-Type: application/json');

// קליטת ה-item_id שנשלח מה-JavaScript (תומך גם ב-JSON payload וגם ב-POST רגיל)
$inputData = json_decode(file_get_contents('php://input'), true);
$itemId = $inputData['item_id'] ?? ($_POST['item_id'] ?? null);

if (!$itemId) {
    http_response_code(400);
    die(json_encode(['error' => 'missing_item_id', 'message' => 'מזהה פריט חסר.']));
}

$adopterId = $_SESSION['user_id'];

// שחרור פוסטים שפג תוקפם לפני שמנסים ליצור בקשה חדשה
releaseExpiredRequests($conn, 60);

// =========================================================================
// 1. הפעלת הצינור האטומי: בדיקת זמינות, נעילת הפריט ל-pending ויצירת שורת בקשה ב-DB
// =========================================================================
$requestId = createNewRequest($itemId, $adopterId);

if (!$requestId) {
    // הטרנזקציה החזירה false - כלומר המוצר כבר תפוס (pending או taken) ע'י מישהו אחר
    http_response_code(409); // Conflict
    die(json_encode([
        'error' => 'item_already_taken', 
        'message' => 'אופס! מישהו בדיוק הקדים אותך וחטף את המנה הזו מילישנייה לפניך.'
    ]));
}

// =========================================================================
// 2. החזרת מזהה הבקשה ל-Frontend כדי שה-JS יעביר את המשתמש ישירות לצ'אט
// =========================================================================
echo json_encode([
    'status' => 'success',
    'message' => 'הפוסט ננעל בבטחה. מעביר לשיחת תיאום מול המפרסם...',
    'request_id' => $requestId
]);
exit();
?>