<?php
// logic/cleanup_requests.php

// במקום לפתוח חיבור חדש עם סיסמה שאנחנו לא יודעים,
// אנחנו משתמשים ב-$conn שכבר נוצר ב-db_config.php
require_once __DIR__ . '/db_config.php';

if (isset($conn) && $conn instanceof mysqli) {
    // השאילתה לשחרור פריטים
    $conn->query("UPDATE items SET status = 'available' 
                  WHERE status = 'pending' 
                  AND item_id IN (SELECT item_id FROM requests WHERE request_status = 'pending' AND update_at < NOW() - INTERVAL 1 HOUR)");

    // ביטול הבקשות
    $conn->query("UPDATE requests SET request_status = 'cancelled' 
                  WHERE request_status = 'pending' 
                  AND update_at < NOW() - INTERVAL 1 HOUR");
}
?>