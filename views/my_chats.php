<?php
session_start();
require_once '../logic/db_config.php';

// אבטחה: וידוא חיבור משתמש
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$myUserId = (int)$_SESSION['user_id'];

// שליפת כל השיחות/בקשות הפעילות של המשתמש (כבשלן או כמאמץ)
$sql = "SELECT r.request_id, r.request_status, r.payment_status, r.created_at,
               i.title AS item_title, i.image_url AS item_image,
               u1.first_name AS uploader_first, u1.last_name AS uploader_last,
               u2.first_name AS adopter_first, u2.last_name AS adopter_last,
               i.user_id AS uploader_id
        FROM requests r
        JOIN items i ON r.item_id = i.item_id
        JOIN users u1 ON i.user_id = u1.user_id
        JOIN users u2 ON r.adopter_user_id = u2.user_id
        WHERE r.adopter_user_id = ? OR i.user_id = ?
        ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $myUserId, $myUserId);
$stmt->execute();
$result = $stmt->get_result();

$chats = [];
while ($row = $result->fetch_assoc()) {
    $chats[] = $row;
}

// קביעת ה-ID של הצ'אט שייפתח כברירת מחדל (הראשון ברשימה או מה שקיבלנו ב-URL)
$isChatSelected = isset($_GET['request_id']);
$activeRequestId = $isChatSelected ? (int)$_GET['request_id'] : (!empty($chats) ? $chats[0]['request_id'] : 0);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShareIt - הצ'אטים שלי</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/my_chats.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="whatsapp-body-bg">

    <?php include 'header.php'; ?>

    <div class="whatsapp-layout <?php echo $isChatSelected ? 'show-chat-view' : ''; ?>">
        
        <div class="chats-sidebar">
            <div class="sidebar-header">
                <i class="fab fa-whatsapp"></i>
                <span>צ'אטים פתוחים בשכונה</span>
            </div>
            
            <div class="chats-list">
                <?php if (empty($chats)): ?>
                    <div class="sidebar-empty-state">
                        <p>אין שיחות פעילות כרגע.</p>
                        <a href="items.php">לחצו כאן לחיפוש מנות בפיד</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($chats as $chat): 
                        // קביעת השם המוצג לפי התפקיד של המשתמש הנוכחי
                        if ($myUserId === (int)$chat['uploader_id']) {
                            $partnerName = $chat['adopter_first'] . ' ' . $chat['adopter_last'];
                        } else {
                            $partnerName = $chat['uploader_first'] . ' ' . $chat['uploader_last'];
                        }

                        // עיבוד תמונת המנה
                        $img = !empty($chat['item_image']) ? $chat['item_image'] : '../assets/images/default.png';
                        if (strpos($img, '../') === false) { $img = '../' . $img; }

                        // חישוב תגית הסטטוס המעוצבת
                        $badgeClass = 'bg-wa-pending';
                        $statusText = 'ממתין לאישור';
                        if ($chat['payment_status'] === 'paid') {
                            $badgeClass = 'bg-wa-completed';
                            $statusText = 'שולם 🍔';
                        } elseif ($chat['request_status'] === 'approved') {
                            $badgeClass = 'bg-wa-approved';
                            $statusText = 'אושר! לתשלום';
                        } elseif ($chat['request_status'] === 'cancelled') {
                            $badgeClass = 'bg-wa-cancelled';
                            $statusText = 'בוטלה ❌';
                        }
                        
                        $isActive = ((int)$chat['request_id'] === $activeRequestId) ? 'active' : '';
                    ?>
                        <a href="my_chats.php?request_id=<?php echo $chat['request_id']; ?>" class="chat-tab-link <?php echo $isActive; ?>">
                            <img src="<?php echo $img; ?>" class="tab-avatar">
                            <div class="tab-meta">
                                <div class="tab-title-row">
                                    <span class="tab-name"><?php echo htmlspecialchars($partnerName); ?></span>
                                    <span class="badge-wa <?php echo $badgeClass; ?>"><?php echo $statusText; ?></span>
                                </div>
                                <span class="tab-status">לגבי מנת: <?php echo htmlspecialchars($chat['item_title']); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="chat-main-window">
            <?php if ($activeRequestId > 0): ?>
                <div class="mobile-back-bar">
                    <a href="my_chats.php" class="btn-back-chats">
                        <i class="fas fa-arrow-right"></i> חזרה לכל השיחות
                    </a>
                </div>
                <iframe src="chat.php?request_id=<?php echo $activeRequestId; ?>&v=<?php echo time(); ?>" class="chat-iframe"></iframe>
            <?php else: ?>
                <div class="empty-chats-state">
                    <i class="far fa-comments"></i>
                    <h3>ברוכים הבאים ל-ShareIt Chats</h3>
                    <p>בחרו שיחה מצד ימין כדי להתחיל לתאם את איסוף ותשלום המנה.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>