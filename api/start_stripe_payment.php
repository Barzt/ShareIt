<?php
// 1. נפעיל זמנית הצגת שגיאות - אם משהו נכשל, נראה את זה מיד בטקסט ולא במסך לבן!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../logic/db_config.php';

header('Content-Type: application/json');

// 2. אבטחה: וידוא שהמשתמש מחובר
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['status' => 'error', 'message' => 'יש להתחבר למערכת.']));
}

// קבלת מזהה הבקשה מהפרונט-אנד
$inputData = json_decode(file_get_contents('php://input'), true);
$requestId = isset($inputData['request_id']) ? (int)$inputData['request_id'] : (isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0);

if ($requestId <= 0) {
    die(json_encode(['status' => 'error', 'message' => 'מזהה בקשה לא תקין.']));
}

$myUserId = (int)$_SESSION['user_id'];
$stripeCustomerId = '';
$priceInCents = 500; // ברירת מחדל של 500 אגורות (5 ש"ח)

try {
    // =========================================================================
    // 3. שליפת ה-Stripe Customer ID מטבלת users
    // =========================================================================
    $userSql = "SELECT payment_customer_id FROM users WHERE user_id = ?";
    $userStmt = $conn->prepare($userSql);
    if ($userStmt) {
        $userStmt->bind_param("i", $myUserId);
        $userStmt->execute();
        $userData = $userStmt->get_result()->fetch_assoc();
        $stripeCustomerId = $userData ? $userData['payment_customer_id'] : '';
    }

    // =========================================================================
    // 4. שליפת ה-service_fee המדויק ישירות מטבלת requests
    // =========================================================================
    $requestSql = "SELECT service_fee FROM requests WHERE request_id = ?";
    $requestStmt = $conn->prepare($requestSql);
    if ($requestStmt) {
        $requestStmt->bind_param("i", $requestId);
        $requestStmt->execute();
        $requestData = $requestStmt->get_result()->fetch_assoc();
        
        if ($requestData && isset($requestData['service_fee'])) {
            $priceInCents = (int)((float)$requestData['service_fee'] * 100);
        }
    }

    // 🚨 5. הגדרת מפתח ה-API הסודי של Stripe
    $apiKey = 'YOUR_STRIPE_KEY_HERE';

    // בנייה דינמית והרמטית של כתובת האתר כולל תתי-תיקיות אם יש!
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $currentDirUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    
    // החלפת הנתיב מתיקיית ה-api לתיקיית ה-views בצורה אוטומטית וחסינת באגים
    $chatPageUrl = str_replace('api/start_stripe_payment.php', 'views/chat.php', $currentDirUrl);
    
    // הסרת פרמטרים מיותרים מה-URI המקורי אם השתרבבו
    if (($pos = strpos($chatPageUrl, '?')) !== false) {
        $chatPageUrl = substr($chatPageUrl, 0, $pos);
    }

    $success_url = $chatPageUrl . '?request_id=' . $requestId . '&payment=success';
    $cancel_url  = $chatPageUrl . '?request_id=' . $requestId . '&payment=cancel';

    // =========================================================================
    // 6. פניית cURL ישירה ל-Stripe API
    // =========================================================================
    $fields = [
        'payment_method_types[0]' => 'card',
        'mode' => 'payment',
        'success_url' => $success_url,
        'cancel_url' => $cancel_url,
        'line_items[0][price_data][currency]' => 'ils',
        'line_items[0][price_data][unit_amount]' => $priceInCents,
        'line_items[0][price_data][product_data][name]' => 'דמי רצינות עבור בקשה מספר ' . $requestId,
        'line_items[0][quantity]' => 1
    ];

    if (!empty($stripeCustomerId)) {
        $fields['customer'] = $stripeCustomerId;
    }

    $ch = curl_init("https://api.stripe.com/v1/checkout/sessions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $apiKey]);

    $response = curl_exec($ch);
    curl_close($ch);

    $stripeData = json_decode($response, true);

    // 7. החזרת ה-URL לפרונט-אנד
    if (isset($stripeData['url'])) {
        echo json_encode(['status' => 'success', 'url' => $stripeData['url']]);
    } else {
        $msg = isset($stripeData['error']['message']) ? $stripeData['error']['message'] : 'תקלה מול שרתי Stripe';
        echo json_encode(['status' => 'error', 'message' => $msg]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'שגיאה במערכת: ' . $e->getMessage()]);
}
?>