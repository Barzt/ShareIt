<?php
// api/city_data.php
require_once __DIR__ . '/../logic/config.php';

// 1. אבטחה: מפתח ה-API 
$VALID_API_KEY = defined('CITY_DATA_API_KEY') ? CITY_DATA_API_KEY : 'YOUR_CITY_DATA_API_KEY_HERE';

// 2. קבלת ה-Header של ה-API Key (תומך בשרתים שונים וב-CGI/CLI)
$apiKey = null;
if (isset($_SERVER['HTTP_X_API_KEY'])) {
    $apiKey = $_SERVER['HTTP_X_API_KEY'];
} elseif (function_exists('getallheaders')) {
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    if (isset($headers['x-api-key'])) {
        $apiKey = $headers['x-api-key'];
    }
}

// הגדרת פורמט תגובה כ-JSON מקודד כראוי לעברית
header('Content-Type: application/json; charset=utf-8');

// 3. אימות מפתח ה-API
if ($apiKey !== $VALID_API_KEY) {
    http_response_code(403);
    echo json_encode(
        ['error' => 'Forbidden: Invalid or missing API Key (X-API-KEY)'], 
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    );
    exit;
}

// 4. קריאת שם העיר מפרמטר ה-GET (לדוגמה: ?city=תל אביב)
$city = isset($_GET['city']) ? trim($_GET['city']) : '';
if (empty($city)) {
    http_response_code(400);
    echo json_encode(
        ['error' => 'Bad Request: Missing required query parameter "city"'], 
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    );
    exit;
}

// 5. חיבור למסד הנתונים
try {
    require_once '../logic/db_config.php';
    
    // וידוא קידוד לעברית מול ה-DB
    $conn->set_charset("utf8mb4");
    
    // 6. שאילתת SELECT לשליפת פוסטים פעילים (status = 'available') בעיר המבוקשת בלבד.
    // אנחנו מסננים לפי מיקום הפריט הפיזי (item_address). אם הוא ריק, אנחנו משתמשים כגיבוי בעיר המגורים של המשתמש שהעלה את הפריט.
    // השימוש ב-REPLACE עוזר להתגבר על הבדלים ברווחים ומקפים (לדוגמה: "תל אביב" מול "תל-אביב" או "תל־אביב").
    $sql = "SELECT 
                i.item_id, 
                i.user_id, 
                i.category_id, 
                i.title, 
                i.description, 
                i.kosher_status, 
                i.allergens, 
                i.expiry_date, 
                i.cooked_at, 
                i.image_url, 
                i.ai_labels, 
                i.item_lat, 
                i.item_lng, 
                i.item_address, 
                i.status, 
                i.created_at,
                u.first_name AS publisher_name,
                u.city AS publisher_city
            FROM items i
            LEFT JOIN users u ON CAST(i.user_id AS UNSIGNED) = CAST(u.user_id AS UNSIGNED)
            WHERE i.status = 'available' 
              AND (
                (i.item_address IS NOT NULL AND i.item_address != '' AND 
                 REPLACE(REPLACE(REPLACE(i.item_address, ' ', ''), '-', ''), '־', '') LIKE CONCAT('%', REPLACE(REPLACE(REPLACE(?, ' ', ''), '-', ''), '־', ''), '%')
                )
                OR
                ((i.item_address IS NULL OR i.item_address = '') AND 
                 REPLACE(REPLACE(REPLACE(u.city, ' ', ''), '-', ''), '־', '') = REPLACE(REPLACE(REPLACE(?, ' ', ''), '-', ''), '־', '')
                )
              )
            ORDER BY i.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    
    $stmt->bind_param("ss", $city, $city);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        // התאמת נתיב ברירת מחדל לתמונה במידה והוא ריק
        if (empty($row['image_url'])) {
            $row['image_url'] = '../assets/img/default-food.png';
        }
        $items[] = $row;
    }
    
    // החזרת הנתונים כ-JSON
    echo json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(
        ['error' => 'Database error: ' . $e->getMessage()], 
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    );
}
?>
