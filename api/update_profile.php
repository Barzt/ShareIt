<?php
session_start();
require_once '../logic/db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'לא מחובר']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'שיטה לא חוקית']);
    exit();
}

$userId    = $_SESSION['user_id'];
$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name']  ?? '');
$email     = trim($_POST['email']      ?? '');
$phone     = trim($_POST['phone']      ?? '');
$city      = trim($_POST['city']       ?? '');
$street    = trim($_POST['street']     ?? '');
$houseNum  = trim($_POST['house_number'] ?? '');
$apartment = trim($_POST['apartment']  ?? '');

// ולידציה בסיסית
if (!$firstName || !$lastName || !$email) {
    echo json_encode(['success' => false, 'message' => 'שם ואימייל הם שדות חובה']);
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'כתובת אימייל לא תקינה']);
    exit();
}

// טיפול בתמונת פרופיל
$pictureUpdate = '';
$pictureValue  = null;

if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $file     = $_FILES['profile_picture'];
    $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    
    // בדיקה בטוחה אם ספרית fileinfo זמינה בשרת
    if (function_exists('finfo_open')) {
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    } else {
        $mimeType = $file['type'];
    }

    if (!in_array($mimeType, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'סוג קובץ לא נתמך (JPEG/PNG/WEBP בלבד)']);
        exit();
    }
    if ($file['size'] > 3 * 1024 * 1024) { // מקסימום 3MB
        echo json_encode(['success' => false, 'message' => 'הקובץ גדול מדי (מקסימום 3MB)']);
        exit();
    }

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'profile_' . $userId . '_' . time() . '.' . $ext;
    $destPath = '../uploads/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        echo json_encode(['success' => false, 'message' => 'שגיאה בשמירת התמונה']);
        exit();
    }

    $pictureValue  = 'uploads/' . $fileName;
    $pictureUpdate = ', profile_picture = ?';
}

// בניית כתובת מפורמטת
$formattedAddress = trim("$street $houseNum, $city");
if ($apartment) $formattedAddress .= " דירה $apartment";

// בניית השאילתה - שימי לב ש-$pictureUpdate נכנס כחלק מהמחרוזת
$sql = "UPDATE users SET 
        first_name=?, last_name=?, email=?, phone=?, 
        city=?, street=?, house_number=?, apartment=?, 
        formatted_address=? $pictureUpdate 
        WHERE user_id=?";

$stmt = $conn->prepare($sql);

if ($pictureValue) {
    // 11 פרמטרים: 10 מחרוזות (s) ומזהה משתמש אחד (i)
    $stmt->bind_param("ssssssssssi",
        $firstName, $lastName, $email, $phone,
        $city, $street, $houseNum, $apartment, $formattedAddress,
        $pictureValue, $userId
    );
} else {
    // 10 פרמטרים: 9 מחרוזות (s) ומזהה משתמש אחד (i)
    $stmt->bind_param("sssssssssi",
        $firstName, $lastName, $email, $phone,
        $city, $street, $houseNum, $apartment, $formattedAddress,
        $userId
    );
}

if ($stmt->execute()) {
    // 🌍 שדרוג הערת המנחה: שליפת קואורדינטות חדשות מ-OpenStreetMap (Nominatim) בזמן אמת!
    $newLat = null;
    $newLng = null;

    $encodedAddress = urlencode($formattedAddress);
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$encodedAddress}&limit=1";

    // OSM מחייבים לשלוח User-Agent תקין בקריאות מהשרת כדי למנוע חסימות
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: ShareItApp-ProjectGamer\r\n",
            "timeout" => 5
        ]
    ];
    $context = stream_context_create($opts);
    $osmResponse = false;

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ShareItApp-ProjectGamer');
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $osmResponse = curl_exec($ch);
        curl_close($ch);
    }

    if ($osmResponse === false) {
        $osmResponse = @file_get_contents($url, false, $context);
    }

    if ($osmResponse) {
        $osmData = json_decode($osmResponse, true);
        if (!empty($osmData) && isset($osmData[0]['lat']) && isset($osmData[0]['lon'])) {
            $newLat = (float)$osmData[0]['lat'];
            $newLng = (float)$osmData[0]['lon'];
        }
    }

    // Fallback: If address geocoding failed, try geocoding only the city name
    if (($newLat === null || $newLng === null) && !empty($city)) {
        $encodedCity = urlencode($city);
        $urlFallback = "https://nominatim.openstreetmap.org/search?format=json&q={$encodedCity}&limit=1";
        
        $osmResponseFallback = false;
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $urlFallback);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'ShareItApp-ProjectGamer');
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $osmResponseFallback = curl_exec($ch);
            curl_close($ch);
        }
        if ($osmResponseFallback === false) {
            $osmResponseFallback = @file_get_contents($urlFallback, false, $context);
        }

        if ($osmResponseFallback) {
            $osmDataFallback = json_decode($osmResponseFallback, true);
            if (!empty($osmDataFallback) && isset($osmDataFallback[0]['lat']) && isset($osmDataFallback[0]['lon'])) {
                $newLat = (float)$osmDataFallback[0]['lat'];
                $newLng = (float)$osmDataFallback[0]['lon'];
            }
        }
    }

    if ($newLat !== null && $newLng !== null) {
        // 1. עדכון ה-lat וה-lng החדשים בדאטהבייס עבור המשתמש
        $updateGeoSql = "UPDATE users SET lat = ?, lng = ? WHERE user_id = ?";
        $updateGeoStmt = $conn->prepare($updateGeoSql);
        if ($updateGeoStmt) {
            $updateGeoStmt->bind_param("ddi", $newLat, $newLng, $userId);
            $updateGeoStmt->execute();
        }

        // 2. עדכון ה-Session הפעיל כדי שחישוב הרדיוס בפיד ישתנה מיידית!
        $_SESSION['user_lat'] = $newLat;
        $_SESSION['user_lng'] = $newLng;
    }

    // עדכון שאר נתוני ה-Session הקיימים שלכן
    $_SESSION['first_name']   = $firstName;
    $_SESSION['user_address'] = $formattedAddress;
    if ($pictureValue) {
        $_SESSION['profile_picture'] = $pictureValue;
    }

    $response = ['success' => true, 'message' => 'הפרופיל עודכן בהצלחה'];
    if ($pictureValue) $response['profile_picture'] = $pictureValue;
    echo json_encode($response);
} else {
    error_log("update_profile error: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'שגיאה בשמירת הנתונים']);
}