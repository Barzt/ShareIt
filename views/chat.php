<?php 
session_start(); 
require_once '../logic/db_config.php';

// 1. אבטחה: וידוא שהמשתמש מחובר
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$myUserId = (int)$_SESSION['user_id'];

// קליטת הפרמטרים
$requestId = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;
$itemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if ($requestId <= 0 && $itemId > 0) {
    $findReqSql = "SELECT request_id FROM requests WHERE item_id = ? ORDER BY request_id DESC LIMIT 1";
    $findReqStmt = $conn->prepare($findReqSql);
    $findReqStmt->bind_param("i", $itemId);
    $findReqStmt->execute();
    $findRes = $findReqStmt->get_result()->fetch_assoc();
    
    if ($findRes) {
        $requestId = (int)$findRes['request_id'];
    }
}

if ($requestId <= 0) {
    header("Location: index.php"); 
    exit();
}

// 2. שליפת פרטים
$partnerName = "שכן מהקהילה";
$partnerPic = null;
$itemTitle = "פריט משותף";
$itemImage = "../assets/images/default.png";
$isAdopter = false;
$dbPaymentStatus = "unpaid";

$infoSql = "SELECT i.title, i.image_url, i.user_id AS uploader_id, r.adopter_user_id, r.payment_status,
                   u1.first_name AS uploader_first, u1.last_name AS uploader_last, u1.profile_picture AS uploader_pic,
                   u2.first_name AS adopter_first, u2.last_name AS adopter_last, u2.profile_picture AS adopter_pic
            FROM requests r
            JOIN items i ON r.item_id = i.item_id
            JOIN users u1 ON i.user_id = u1.user_id
            JOIN users u2 ON r.adopter_user_id = u2.user_id
            WHERE r.request_id = ?";

$infoStmt = $conn->prepare($infoSql);
$infoStmt->bind_param("i", $requestId);
$infoStmt->execute();
$info = $infoStmt->get_result()->fetch_assoc();

if ($info) {
    $itemTitle = $info['title'];
    $dbPaymentStatus = $info['payment_status'];
    if ($info['image_url']) {
        if (strpos($info['image_url'], '../') !== false) {
            $itemImage = $info['image_url'];
        } else {
            $itemImage = '../' . $info['image_url'];
        }
    }
    
    if ($myUserId === (int)$info['adopter_user_id']) {
        $isAdopter = true;
        $partnerName = $info['uploader_first'] . ' ' . $info['uploader_last'];
        $partnerPic = $info['uploader_pic'];
    } else {
        $partnerName = $info['adopter_first'] . ' ' . $info['adopter_last'];
        $partnerPic = $info['adopter_pic'];
    }
}

// עדכון סטטוסים בשרת
if (isset($_GET['payment']) && $_GET['payment'] === 'success') {
    $updateSql = "UPDATE requests SET payment_status = 'paid', request_status = 'completed' WHERE request_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    if ($updateStmt) {
        $updateStmt->bind_param("i", $requestId);
        $updateStmt->execute();
        
        $itemUpdateSql = "UPDATE items i JOIN requests r ON i.item_id = r.item_id SET i.status = 'taken' WHERE r.request_id = ?";
        $itemUpdateStmt = $conn->prepare($itemUpdateSql);
        if ($itemUpdateStmt) {
            $itemUpdateStmt->bind_param("i", $requestId);
            $itemUpdateStmt->execute();
        }
        $dbPaymentStatus = "paid";
    }
}

$finalStatusHtml = "";
if ($dbPaymentStatus === 'paid') {
    $finalText = $isAdopter ? "העסקה הושלמה בהצלחה! בתיאבון!" : "העסקה הושלמה בהצלחה! בתיאבון לשכנים!";
    $finalStatusHtml = '<div class="status-banner completed">✅ ' . $finalText . '</div>';
}

$paymentCancelAlert = "";
if (isset($_GET['payment']) && $_GET['payment'] === 'cancel' && $isAdopter) {
    // עדכון הסטטוסים במסד הנתונים: ביטול הבקשה ושחרור הפריט חזרה לפיד (בטרנזקציה אטומית)
    $conn->begin_transaction();
    try {
        // 1. עדכון סטטוס הבקשה ל-cancelled
        $updateReqSql = "UPDATE requests SET request_status = 'cancelled' WHERE request_id = ?";
        $updateReqStmt = $conn->prepare($updateReqSql);
        if ($updateReqStmt) {
            $updateReqStmt->bind_param("i", $requestId);
            $updateReqStmt->execute();
            $updateReqStmt->close();
        }
        
        // 2. שחרור הפריט חזרה ל-available (שיופיע שוב בפוסטים של האתר)
        $updateItemSql = "UPDATE items i 
                          JOIN requests r ON i.item_id = r.item_id 
                          SET i.status = 'available' 
                          WHERE r.request_id = ?";
        $updateItemStmt = $conn->prepare($updateItemSql);
        if ($updateItemStmt) {
            $updateItemStmt->bind_param("i", $requestId);
            $updateItemStmt->execute();
            $updateItemStmt->close();
        }
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Stripe Cancel Transaction failed: " . $e->getMessage());
    }
    
    // שמירת ההתראה בסשן ומעבר לכתובת נקייה כדי למנוע כפילויות בריענון
    $_SESSION['payment_cancel_alert'] = $requestId;
    header("Location: chat.php?request_id=" . $requestId);
    exit();
}

// קריאת ההתראה מהסשן
if (isset($_SESSION['payment_cancel_alert']) && $_SESSION['payment_cancel_alert'] === $requestId) {
    $paymentCancelAlert = '<div class="payment-cancel-alert">⚠️ התשלום לא הושלם או בוטל.</div>';
    unset($_SESSION['payment_cancel_alert']);
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShareIt - שיחה עם <?php echo htmlspecialchars($partnerName); ?></title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/chat.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
</head>
<body>

    <div id="chat-header-container">
        <?php include 'header.php'; ?>
    </div>

    <main class="chat-main">
        <div class="chat-card">
            <div class="card-top-bar"></div>

            <div class="chat-partner-profile">
                <div class="partner-avatar-top" style="<?php echo !empty($partnerPic) ? 'background: transparent;' : ''; ?>">
                    <?php if (!empty($partnerPic)): ?>
                        <img src="../<?php echo htmlspecialchars($partnerPic); ?>" alt="<?php echo htmlspecialchars($partnerName); ?>" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <?php echo mb_substr($partnerName, 0, 1, 'UTF-8'); ?>
                    <?php endif; ?>
                </div>
                <div>
                    <h3 style="margin:0; font-size:20px; color:#0d4d44; text-align: center; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif !important; font-weight: 700 !important;">
                        שיחה עם: <strong style="color: #d63031; font-family: inherit !important; font-weight: 700 !important;"><?php echo htmlspecialchars($partnerName); ?></strong>
                    </h3>
                    <div class="chat-item-mini-card">
                        <span>לגבי המנה:</span>
                        <img src="<?php echo $itemImage; ?>" class="mini-item-img" alt="מנה">
                        <strong style="color: #0d4d44;"><?php echo htmlspecialchars($itemTitle); ?></strong>
                    </div>
                </div>
            </div>

            <?php if (!empty($paymentCancelAlert)): ?>
                <?php echo $paymentCancelAlert; ?>
            <?php endif; ?>

            <div id="chat-status-bar" class="chat-status-bar">
                <?php if (!empty($finalStatusHtml)): ?>
                    <?php echo $finalStatusHtml; ?>
                <?php else: ?>
                    <div class="status-loading">
                        <i class="fas fa-spinner fa-spin"></i> טוען סטטוס עסקה...
                    </div>
                <?php endif; ?>
            </div>

            <div id="chat-window">
                <div class="chat-loading">
                    <div class="dot-bounce"></div>
                    <div class="dot-bounce"></div>
                    <div class="dot-bounce"></div>
                </div>
            </div>

            <div class="chat-input-area">
                <form id="messageForm">
                    <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">
                    <input
                        type="text"
                        name="message_text"
                        id="msgInput"
                        placeholder="כתבו הודעה ולחצו Enter או שלח..."
                        autocomplete="off"
                        required
                    >
                    <button type="submit" id="btn-send" class="btn-send-airplane" title="שלח הודעה">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </main>

    <div id="toast"></div>

    <div id="chat-footer-container">
        <footer>
            <div class="footer-links">
                <a href="about.php">אודות</a> | <a href="contact.php">צרו קשר</a> | <a href="faq.php">שאלות תשובות</a>
            </div>
            <p>© 2026 ShareIt - פלטפורמה קהילתית לשיתוף מזון</p>
        </footer>
    </div>

    <script>
        window.CHAT_REQUEST_ID = <?php echo $requestId; ?>;
        window.CURRENT_USER_ID = <?php echo (int)$_SESSION['user_id']; ?>;
        window.IS_ADOPTER = <?php echo $isAdopter ? 'true' : 'false'; ?>;

        // ✂️ מניעת "חלון בתוך חלון": אם הדף נטען בתוך iframe, נעלים את התפריטים ונשפר פרופורציות
        if (window.self !== window.top) {
            document.body.classList.add('inside-iframe');
            
            const headerCont = document.getElementById('chat-header-container');
            const footerCont = document.getElementById('chat-footer-container');
            
            if (headerCont) headerCont.style.display = 'none';
            if (footerCont) footerCont.style.display = 'none';
        }
    </script>
    <script src="../assets/js/chat.js?v=<?php echo time(); ?>"></script>
</body>
</html>