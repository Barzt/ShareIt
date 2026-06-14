<?php
// api/auth_register.php
session_start();
require_once '../logic/db_config.php';
// חיבור לספריית ה-vendor
require_once '../vendor/stripe-php/init.php';

// המפתח הסודי מה-Sandbox
\Stripe\Stripe::setApiKey('YOUR_STRIPE_KEY_HERE');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. קליטת נתונים מהטופס
    $first_name = $_POST['first_name'];
    $last_name  = $_POST['last_name'];
    $email      = $_POST['email'];
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone      = $_POST['phone'] ?? null;
    $city       = $_POST['city'];
    $street     = $_POST['street'];
    $h_number   = $_POST['house_number'];
    $apartment  = $_POST['apartment'] ?? null;
    
    // בניית כתובת נקייה מהשדות שהמשתמש הקליד ידנית
    $formatted_addr = trim("$city, $street $h_number");
    
    // אם השדות האלו ריקים, רק אז ניקח את הכתובת מהמפה כגיבוי
    if (empty($city) || empty($street)) {
        $formatted_addr = $_POST['formatted_address'] ?? "מיקום לא ידוע";
    }
    
    $lat = !empty($_POST['lat']) ? (float)$_POST['lat'] : null;
    $lng = !empty($_POST['lng']) ? (float)$_POST['lng'] : null;

    // 2. יצירת לקוח ב-Stripe Sandbox
    $customer_id = null;
    try {
        $customer = \Stripe\Customer::create([
            'email' => $email,
            'name' => $first_name . ' ' . $last_name,
            'description' => 'User registered from ShareIt Website',
        ]);
        $customer_id = $customer->id; // זה ה-cus_test_... שייכנס ל-DB
    } catch (Exception $e) {
        // אם Stripe נכשל, נמשיך ברישום אבל ה-ID יהיה null (כדי לא לתקוע את האתר)
        error_log("Stripe Customer Error: " . $e->getMessage());
    }

    // 3. שאילתת ההכנסה לטבלה - מעודכנת ל-13 עמודות (כולל payment_customer_id)
    $sql = "INSERT INTO users (
                first_name, last_name, email, password, phone, 
                city, street, house_number, apartment, 
                formatted_address, lat, lng, payment_customer_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
 
    $stmt->bind_param("ssssssssssdds", 
        $first_name, $last_name, $email, $password, $phone, 
        $city, $street, $h_number, $apartment, 
        $formatted_addr, $lat, $lng, $customer_id
    );

    if ($stmt->execute()) {
        $new_user_id = $stmt->insert_id;
        
        $_SESSION['user_id']    = $new_user_id;
        $_SESSION['first_name'] = $first_name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name']  = $first_name;
        $_SESSION['user_lat']   = $lat;
        $_SESSION['user_lng']   = $lng;
        $_SESSION['user_address'] = $formatted_addr;

        $_SESSION['success_message'] = "נרשמת בהצלחה! ברוך הבא לקהילת ShareIt 🌍";


        // מעבר לדף הבית אחרי הצלחה
        header("Location: ../views/index.php");
        exit();
    } else {
        // אם זה נכשל, נראה בדיוק למה (למשל אם חסרה עמודה ב-DB)
        die("שגיאה ברישום ל-DB: " . $stmt->error);
    }
}