<?php 
session_start(); 
require_once '../logic/category_actions.php'; // טעינת הפונקציות החדשות

// אבטחה: אם המשתמש לא מחובר
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// משיכת הנתונים מה-DB עבור הטופס
$categories = getAllCategories();
$kosherOptions = getKosherOptions();

/**
 * פונקציית עזר לנירמול הכתובת בתצוגה הראשונית
 */
function formatDisplayAddress($full_address) {
    if (!$full_address || $full_address == 'לא הוגדרה כתובת') return 'לא הוגדרה כתובת';
    $parts = explode(',', $full_address);
    if (count($parts) > 4) {
        return trim($parts[4]) . ", " . trim($parts[2]) . " " . trim($parts[1]);
    } elseif (count($parts) >= 2) {
        return trim($parts[1]) . ", " . trim($parts[0]);
    }
    return $full_address;
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShareIt - שיתוף אוכל</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/upload.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>

    <?php include 'header.php'; ?> 

    <main class="upload-wrapper">
        <div class="upload-card">
            <div class="hero-brand-name" style="color: #d63031; font-size: 3rem; font-weight: 900; text-align: center; margin-bottom: 10px;">ShareIt</div>
            <h1>שתפו אוכל עם השכנים 🍎</h1>
            <p class="subtitle">העלו תמונה ומלאו את פרטי המזון הבאים עבור השכנים!</p>
            
            <form action="../api/upload_item.php" method="POST" enctype="multipart/form-data">
                
                <div class="upload-section">
                    <label class="custom-file-upload">
                        <input type="file" name="item_image" id="imageInput" accept="image/*" required>
                        <span>📸 לחצו לבחירת תמונה או צילום</span>
                    </label>
                    <div id="preview-container">
                        <img id="imagePreview" src="#" alt="תצוגה מקדימה" style="display:none;">
                    </div>
                </div>

                <hr class="divider">

                <div class="details-section">
                    <h3>📝 פרטי המזון</h3>
                    
                    <div class="input-group">
                        <label for="title">כותרת המוצר:</label>
                        <input type="text" name="title" id="title" placeholder="הכותרת תתמלא אוטומטית ע'י ה-AI או הזן ידנית..." required>
                    </div>
                    
                    <div class="input-group">
                        <label for="category_id">קטגוריה:</label>
                        <select name="category_id" id="category_id" required>
                            <option value="" disabled selected>בחרו קטגוריה</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>">
                                    <?php echo $cat['category_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="kosher_status">כשרות:</label>
                        <select name="kosher_status" id="kosher_status">
                            <?php foreach ($kosherOptions as $option): ?>
                                <option value="<?php echo $option; ?>" <?php echo ($option === 'פרווה') ? 'selected' : ''; ?>>
                                    <?php echo $option; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-group" id="expiry_group" style="display: none;">
                        <label for="expiry_date">תוקף:</label>
                        <input type="date" name="expiry_date" id="expiry_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="input-group" id="cooked_group" style="display: none;">
                        <label for="cooked_at">מתי המנה בושלה?</label>
                        <input type="datetime-local" name="cooked_at" id="cooked_at">
                    </div>

                    <div class="input-group">
                        <label for="allergens">אלרגנים (אופציונלי):</label>
                        <input type="text" name="allergens" id="allergens" placeholder="למשל: גלוטן, בוטנים">
                    </div>

                    <div class="input-group">
                        <label for="description">תיאור המזון:</label>
                        <textarea name="description" id="description" rows="3" placeholder="ספרו קצת על האוכל (למשל: 'נשאר מחתונה', 'קניתי בטעות פעמיים')..." required></textarea>
                    </div>
                </div>

                <hr class="divider">

                <div class="location-section">
                    <h3>📍 איפה האוכל נמצא?</h3>
                    <div class="current-address-box">
                        מיקום נוכחי: <strong id="current-display-address"><?php echo formatDisplayAddress($_SESSION['user_address'] ?? 'לא הוגדרה כתובת'); ?></strong>
                    </div>
                    
                    <label class="checkbox-container">
                        <input type="checkbox" id="toggleLocation">
                         אני במיקום אחר כרגע
                    </label>

                    <div id="manual-location" class="hidden-section">
                        <p class="instruction">הזינו כתובת חדשה או גררו את הסמן במפה:</p>
                        <div class="address-grid">
                            <input type="text" id="city" placeholder="עיר">
                            <input type="text" id="street" placeholder="רחוב">
                            <input type="text" id="h_num" placeholder="מספר">
                        </div>
                        <div id="map"></div>
                    </div>

                    <input type="hidden" name="item_lat" id="item_lat" value="<?php echo $_SESSION['user_lat']; ?>">
                    <input type="hidden" name="item_lng" id="item_lng" value="<?php echo $_SESSION['user_lng']; ?>">
                    <input type="hidden" name="item_address" id="item_address" value="<?php echo $_SESSION['user_address']; ?>">
                    <!-- שדות מוסתרים לשימור תוצאות ה-AI ומניעת קריאות כפולות -->
                    <input type="hidden" name="ai_labels" id="ai_labels" value="">
                    <input type="hidden" name="ai_is_safe" id="ai_is_safe" value="">
                    <input type="hidden" name="ai_feedback" id="ai_feedback" value="">
                    <input type="hidden" name="ai_description" id="ai_description" value="">
                </div>

                <button type="submit" class="btn-publish">פרסם פריט ✨</button>
            </form>
        </div>
    </main>

    <footer>
        <div class="footer-links">
            <a href="about.php">אודות</a> | <a href="contact.php">צרו קשר</a> | <a href="faq.php">שאלות תשובות</a>
        </div>
        <p>© 2026 ShareIt - פלטפורמה קהילתית לשיתוף מזון</p>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/maps.js?v=10.0"></script>
    <script src="../assets/js/upload.js"></script>
</body>
</html>