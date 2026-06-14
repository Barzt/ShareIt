<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../logic/db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
    $senderId  = (int)$_SESSION['user_id'];
    $text      = isset($_POST['message_text']) ? trim($_POST['message_text']) : '';

    if ($requestId <= 0 || $text === '') {
        http_response_code(400);
        die(json_encode(['status' => 'error', 'message' => 'Missing data']));
    }

    // התאמה לבסיס הנתונים: שימוש ב-i.item_id במקום i.id
    $checkSql = "SELECT r.adopter_user_id, i.user_id AS uploader_id 
                 FROM requests r
                 JOIN items i ON r.item_id = i.item_id
                 WHERE r.request_id = ?";
                 
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $requestId);
    $checkStmt->execute();
    $res = $checkStmt->get_result()->fetch_assoc();

    if (!$res) {
        http_response_code(404);
        die(json_encode(['status' => 'error', 'message' => 'Request not found']));
    }

    if ($senderId !== (int)$res['adopter_user_id'] && $senderId !== (int)$res['uploader_id']) {
        http_response_code(403);
        die(json_encode(['status' => 'error', 'message' => 'Access denied']));
    }

    $sql = "INSERT INTO messages (request_id, sender_id, message_text) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $requestId, $senderId, $text);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }
    exit();
}
?>