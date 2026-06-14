<?php
session_start();
// שימוש ב-__DIR__ כדי לוודא שהקובץ תמיד יימצא
require_once __DIR__ . '/../logic/db_config.php';

// הגדרת סוג התוכן כדי שהדפדפן יבין שזה JSON
header('Content-Type: application/json');

// בדיקה אם המשתמש מחובר - אם לא, מחזירים מערך ריק
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

try {
    // שליפת פריטים שהמשתמש העלה כולל פרטי עריכה וכל הסטטוסים
    $sql = "SELECT item_id, category_id, title, description, kosher_status, allergens, expiry_date, cooked_at, image_url, status, created_at
            FROM items
            WHERE user_id = ? 
              AND status IN ('available', 'pending', 'taken')
            ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        // טיפול בתמונת ברירת מחדל אם השדה ריק
        if (empty($row['image_url'])) {
            $row['image_url'] = 'assets/img/default-food.png';
        }
        
        $items[] = $row;
    }

    //  שליחת הנתונים חזרה ל-JS
    echo json_encode($items);

} catch (Exception $e) {
    // במקרה של שגיאה, מחזירים הודעה מסודרת במקום שגיאת 500 גנרית
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}