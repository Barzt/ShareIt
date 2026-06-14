<?php
session_start();
require_once '../logic/db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'לא מחובר']);
    exit();
}

$sql = "SELECT first_name, last_name, email, phone, city, street, house_number, apartment, formatted_address, profile_picture
        FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode($user);
