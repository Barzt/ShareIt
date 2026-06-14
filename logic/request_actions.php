<?php
require_once 'db_config.php';

/**
 * שלב 1: המאמץ לוחץ "מתאים לי בול!" 
 * נועלים את הפריט ל-pending ומייצרים בקשה במנגנון טרנזקציה אטומי
 */
function createNewRequest($item_id, $adopter_id) {
    global $conn;
    if (!$conn) return false;

    // התחלת טרנזקציה מאובטחת למניעת Race Conditions
    $conn->begin_transaction();

    try {
        // 1. בדיקה שהפריט עדיין פנוי וזמין (available) - שימוש ב-item_id וב-status
        $checkSql = "SELECT status FROM items WHERE item_id = ? FOR UPDATE";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $item_id);
        $checkStmt->execute();
        $res = $checkStmt->get_result()->fetch_assoc();

        if (!$res || $res['status'] !== 'available') {
            // מישהו כבר תפס את האוכל בחלקיק השנייה הזה
            $conn->rollback();
            return false;
        }

        // 2. עדכון סטטוס הפריט ל-pending - שימוש ב-item_id
        $updateItemSql = "UPDATE items SET status = 'pending' WHERE item_id = ?";
        $updateItemStmt = $conn->prepare($updateItemSql);
        $updateItemStmt->bind_param("i", $item_id);
        $updateItemStmt->execute();

        // 3. יצירת הבקשה בטבלת requests 
        $sql = "INSERT INTO requests (item_id, adopter_user_id, request_status, payment_status) VALUES (?, ?, 'pending', 'unpaid')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $item_id, $adopter_id);
        $stmt->execute();
        
        $requestId = $stmt->insert_id;

        // הכל עבר בהצלחה - שומרים ומחילים את השינויים ב-DB
        $conn->commit();
        return $requestId;

    } catch (Exception $e) {
        // במקרה של שגיאה - מבטלים את הכל (Rollback) כדי לשמור על שלמות הנתונים
        $conn->rollback();
        error_log("Transaction failed in createNewRequest: " . $e->getMessage());
        return false;
    }
}

/**
 * שלב 2: המפרסם מאשר את הבקשה בצ'אט (משנה את request_status ל-approved)
 */
function approveRequest($request_id) {
    global $conn;
    if (!$conn) return false;
    
    $sql = "UPDATE requests SET request_status = 'approved' WHERE request_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $request_id);
    return $stmt->execute();
}

/**
 * שלב 3: עדכון לאחר תשלום מוצלח ב-Stripe
 * מעבירים את ה-request ל-approved/completed ואת ה-item ל-taken סופית
 */
function markAsPaid($request_id, $transaction_id) {
    global $conn;
    if (!$conn) return false;

    // התחלת טרנזקציה למניעת מצב שבו התשלום מתעדכן אך הפריט לא ננעל
    $conn->begin_transaction();

    try {
        // 1. עדכון טבלת requests לסטטוס שולם והזנת מזהה העסקה הרשמי
        $sql = "UPDATE requests SET payment_status = 'paid', payment_transaction_id = ?, request_status = 'approved' WHERE request_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $transaction_id, $request_id);
        $stmt->execute();

        // 2. משיכת ה-item_id המשויך לבקשה הזו
        $itemSql = "SELECT item_id FROM requests WHERE request_id = ?";
        $itemStmt = $conn->prepare($itemSql);
        $itemStmt->bind_param("i", $request_id);
        $itemStmt->execute();
        $itemRes = $itemStmt->get_result()->fetch_assoc();

        if ($itemRes) {
            $itemId = $itemRes['item_id'];
            // 3. עדכון סטטוס הפריט ל-taken (נאסף ושולם סופית, יורד מהאתר) - שימוש ב-item_id
            $updateItemSql = "UPDATE items SET status = 'taken' WHERE item_id = ?";
            $updateItemStmt = $conn->prepare($updateItemSql);
            $updateItemStmt->bind_param("i", $itemId);
            $updateItemStmt->execute();
        }

        // שמירת כל השינויים בצורה אטומית
        $conn->commit();
        return true;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Transaction failed in markAsPaid: " . $e->getMessage());
        return false;
    }
}

/**
 * מנגנון שחרור פוסטים שפג תוקפם (ללא תשלום/אישור) לאחר 60 דקות
 */
function releaseExpiredRequests($conn, $timeoutMinutes = 60) {
    if (!$conn) {
        error_log("releaseExpiredRequests - No database connection provided.");
        return;
    }

    // 1. עדכון סטטוס הפריטים בחזרה ל-available
    $updateItemsSql = "UPDATE items 
                       JOIN requests ON items.item_id = requests.item_id
                       SET items.status = 'available'
                       WHERE items.status = 'pending'
                         AND requests.request_status IN ('pending', 'approved')
                         AND requests.payment_status = 'unpaid'
                         AND TIMESTAMPDIFF(MINUTE, requests.updated_at, NOW()) > ?";
    
    $stmt1 = $conn->prepare($updateItemsSql);
    if (!$stmt1) {
        error_log("releaseExpiredRequests - stmt1 prepare failed: " . $conn->error);
    } else {
        $stmt1->bind_param("i", $timeoutMinutes);
        $stmt1->execute();
        if ($stmt1->affected_rows > 0) {
            error_log("releaseExpiredRequests - Reverted " . $stmt1->affected_rows . " items back to available.");
        }
        $stmt1->close();
    }

    // 2. ביטול בקשות האימוץ שפג תוקפן
    $updateRequestsSql = "UPDATE requests 
                          SET request_status = 'cancelled'
                          WHERE request_status IN ('pending', 'approved')
                            AND payment_status = 'unpaid'
                            AND TIMESTAMPDIFF(MINUTE, updated_at, NOW()) > ?";
    $stmt2 = $conn->prepare($updateRequestsSql);
    if (!$stmt2) {
        error_log("releaseExpiredRequests - stmt2 prepare failed: " . $conn->error);
    } else {
        $stmt2->bind_param("i", $timeoutMinutes);
        $stmt2->execute();
        if ($stmt2->affected_rows > 0) {
            error_log("releaseExpiredRequests - Cancelled " . $stmt2->affected_rows . " expired requests.");
        }
        $stmt2->close();
    }
}
?>