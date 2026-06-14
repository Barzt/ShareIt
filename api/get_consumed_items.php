<?php
session_start();
require_once '../logic/db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'לא מחובר']);
    exit();
}

// שליפת פריטים שהמשתמש קיבל/אסף - חסין ובודק שהעסקה הושלמה והתשלום בוצע!
$sql = "SELECT i.item_id, i.title, i.image_url, i.status, r.request_status, r.created_at
        FROM requests r
        JOIN items i ON i.item_id = r.item_id
        WHERE r.adopter_user_id = ?
          AND r.request_status = 'completed'
          AND r.payment_status = 'paid'
        ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

header('Content-Type: application/json');
echo json_encode($items);