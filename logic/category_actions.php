<?php
require_once 'db_config.php';

/**
 * פונקציה שמושכת את כל הקטגוריות מהמסד נתונים
 */
function getAllCategories() {
    global $conn;
    $categories = [];

    $sql = "SELECT category_id, category_name FROM categories ORDER BY category_name ASC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }

    return $categories;
}

/**
 * פונקציה שמחזירה את אפשרויות הכשרות המותרות במערכת
 * הערכים מותאמים בדיוק ל-ENUM בטבלת ה-items
 */
function getKosherOptions() {
    return [
        'בשרי',
        'חלבי',
        'פרווה',
        'לא כשר/ללא תעודה'
    ];
}
?>