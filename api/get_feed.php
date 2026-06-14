<?php
// api/get_feed.php
session_start();
require_once '../logic/db_config.php';
require_once '../logic/request_actions.php';

header('Content-Type: application/json');

// וידוי קידוד לעברית מול ה-DB
$conn->set_charset("utf8mb4");

// שחרור פוסטים שפג תוקפם (פג תוקף של 60 דקות)
releaseExpiredRequests($conn, 60);

/**
 * משימה 5: הגנה על דף הבית
 * אם המשתמש לא מחובר, אנחנו חוסמים את הגישה לנתונים ושולחים מערך ריק.
 */
$logged_in_user_id = $_SESSION['user_id'] ?? null;

if (!$logged_in_user_id) {
    echo json_encode([]); 
    exit;
}

$u_lat = $_SESSION['user_lat'] ?? null;
$u_lng = $_SESSION['user_lng'] ?? null;

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earthRadius * $c;
}

try {
    /**
     * משימה 4: סינון פריטים אישיים
     * התנאי: items.user_id != ? 
     * מבטיח שהמשתמש לא יראה את האוכל שהוא עצמו העלה בפיד הכללי.
     */
    $sql = "SELECT items.*, items.item_id AS id, users.first_name 
            FROM items 
            LEFT JOIN users ON CAST(items.user_id AS UNSIGNED) = CAST(users.user_id AS UNSIGNED)
            WHERE items.status = 'available' 
            AND items.user_id != ? 
            ORDER BY items.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $logged_in_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];

    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            
            if (empty($row['first_name'])) {
                $row['first_name'] = "משתמש";
            }

            $dist = null;
            if (!empty($u_lat) && !empty($u_lng) && !empty($row['item_lat']) && !empty($row['item_lng'])) {
                $dist = calculateDistance($u_lat, $u_lng, $row['item_lat'], $row['item_lng']);
            }

            // סינון רדיוס של 10 ק"מ
            if ($dist === null || $dist <= 10) {
                if (empty($row['image_url'])) {
                    $row['image_url'] = '../assets/img/default-food.png';
                }
                $row['distance'] = $dist;
                $items[] = $row;
            }
        }
    }
    echo json_encode($items);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}