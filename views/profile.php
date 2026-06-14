<?php 
session_start(); 

// מניעת שמירה במטמון (Cache) כדי שבלחיצה על 'חזור' הדפדפן ייטען מחדש מהשרת
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// אבטחה: אם המשתמש לא מחובר, הפניה לדף ההתחברות
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../logic/category_actions.php';
$categories = getAllCategories();
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShareIt - הפרופיל שלי</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include 'header.php'; ?> 

    <main class="profile-main">

        <section class="profile-card" id="profile-card">

            <div class="card-top-bar"></div>

            <div class="card-body">

                <div class="avatar-wrap">
                    <img id="avatar-img" src="" alt="">
                    <span id="avatar-letter">
                        <?php echo mb_strtoupper(mb_substr($_SESSION['first_name'] ?? 'U', 0, 1, 'UTF-8'), 'UTF-8'); ?>
                    </span>
                    <label class="avatar-edit-overlay" for="pic-input" id="avatar-overlay">📷</label>
                    <input type="file" id="pic-input" accept="image/*">
                </div>

                <div id="view-mode">

                    <h1 class="profile-name">
                        <span id="v-firstname"><?php echo htmlspecialchars($_SESSION['first_name'] ?? ''); ?></span>
                        <span id="v-lastname"></span>
                    </h1>

                    <div class="details-list">

                        <div class="detail-item">
                            <span class="d-icon">✉️</span>
                            <div class="d-content">
                                <span class="d-label">אימייל</span>
                                <span class="d-value" id="v-email">
                                    <?php echo htmlspecialchars($_SESSION['user_email'] ?? '—'); ?>
                                </span>
                            </div>
                        </div>

                        <div class="detail-item" id="v-phone-row" style="display:none">
                            <span class="d-icon">📞</span>
                            <div class="d-content">
                                <span class="d-label">טלפון</span>
                                <span class="d-value" id="v-phone"></span>
                            </div>
                        </div>

                        <div class="detail-item">
                            <span class="d-icon">📍</span>
                            <div class="d-content">
                                <span class="d-label">כתובת מגורים</span>
                                <span class="d-value" id="v-address">
                                    <?php echo htmlspecialchars($_SESSION['user_address'] ?? '—'); ?>
                                </span>
                            </div>
                        </div>

                    </div>

                    <button class="btn-green" id="btn-edit">עריכת פרטים</button>

                </div>

                <form id="edit-mode" novalidate>

                    <div class="form-row-2">
                        <div class="fg">
                            <label for="e-fn">שם פרטי</label>
                            <input type="text" id="e-fn" name="first_name">
                            <span class="ferr" id="err-fn"></span>
                        </div>
                        <div class="fg">
                            <label for="e-ln">שם משפחה</label>
                            <input type="text" id="e-ln" name="last_name">
                            <span class="ferr" id="err-ln"></span>
                        </div>
                    </div>

                    <div class="fg">
                        <label for="e-em">אימייל</label>
                        <input type="email" id="e-em" name="email">
                        <span class="ferr" id="err-em"></span>
                    </div>

                    <div class="fg">
                        <label for="e-ph">טלפון</label>
                        <input type="tel" id="e-ph" name="phone" placeholder="050-0000000">
                        <span class="ferr" id="err-ph"></span>
                    </div>

                    <p class="edit-section-label">כתובת מגורים</p>

                    <div class="form-row-2">
                        <div class="fg">
                            <label for="e-city">עיר</label>
                            <input type="text" id="e-city" name="city">
                        </div>
                        <div class="fg">
                            <label for="e-str">רחוב</label>
                            <input type="text" id="e-str" name="street">
                        </div>
                        <div class="fg">
                            <label for="e-hn">מספר בית</label>
                            <input type="text" id="e-hn" name="house_number">
                        </div>
                        <div class="fg">
                            <label for="e-apt">דירה</label>
                            <input type="text" id="e-apt" name="apartment">
                        </div>
                    </div>

                    <div class="edit-btns">
                        <button type="submit" class="btn-green" id="btn-save">שמור שינויים</button>
                        <button type="button" class="btn-outline" id="btn-cancel">ביטול</button>
                    </div>

                </form>

            </div></section>

        <section class="items-section" id="items-section">
            <h2 class="section-title">הפריטים ששיתפתי</h2>
            <div id="items-grid" class="items-grid"></div>
        </section>

        <section class="items-section" id="consumed-section">
            <h2 class="section-title">הפריטים שצרכתי</h2>
            <div id="consumed-grid" class="items-grid"></div>
        </section>

    </main>

    <div id="toast"></div>

    <!-- Custom Confirm Modal -->
    <div id="confirm-modal" class="custom-modal" style="display:none; z-index: 11000;">
        <div class="modal-content" style="max-width: 400px; text-align: center; overflow: hidden;">
            <h3 style="color: #0d4d44; margin-bottom: 15px; font-weight: 700; font-size: 1.25rem;">אישור פעולה</h3>
            <p id="confirm-message" style="margin-bottom: 25px; color: #555; line-height: 1.5; font-size: 0.95rem;"></p>
            <div class="edit-btns" style="justify-content: center; gap: 12px; display: flex; width: 100%;">
                <button type="button" class="btn-green" id="confirm-yes-btn" style="flex: 1; padding: 10px 20px;">אישור</button>
                <button type="button" class="btn-outline" id="confirm-no-btn" style="flex: 1; padding: 10px 20px; border: 2px solid #ddd; border-radius: 10px;">ביטול</button>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div id="edit-item-modal" class="custom-modal" style="display:none;">
        <div class="modal-content">
            <span class="close-modal-btn" onclick="closeEditItemModal()">&times;</span>
            <h2 style="color: #0d4d44; font-size: 1.5rem; font-weight: 700; margin-bottom: 20px;">עריכת פריט 📝</h2>
            <form id="edit-item-form" novalidate>
                <input type="hidden" id="edit-item-id" name="item_id">
                
                <div class="fg">
                    <label for="edit-title">כותרת המוצר:</label>
                    <input type="text" id="edit-title" name="title" required>
                    <span class="ferr" id="err-edit-title"></span>
                </div>

                <div class="fg">
                    <label for="edit-category">קטגוריה:</label>
                    <select id="edit-category" name="category_id" required>
                        <option value="" disabled selected>בחרו קטגוריה</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>">
                                <?php echo $cat['category_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="fg">
                    <label for="edit-kosher">כשרות:</label>
                    <select id="edit-kosher" name="kosher_status">
                        <option value="בשרי">בשרי</option>
                        <option value="חלבי">חלבי</option>
                        <option value="פרווה">פרווה</option>
                        <option value="לא כשר/ללא תעודה">לא כשר/ללא תעודה</option>
                    </select>
                </div>

                <div class="fg" id="edit-expiry-group" style="display: none;">
                    <label for="edit-expiry">תוקף:</label>
                    <input type="date" id="edit-expiry" name="expiry_date">
                </div>

                <div class="fg" id="edit-cooked-group" style="display: none;">
                    <label for="edit-cooked">מתי המנה בושלה?</label>
                    <input type="datetime-local" id="edit-cooked" name="cooked_at">
                </div>

                <div class="fg">
                    <label for="edit-allergens">אלרגנים (אופציונלי):</label>
                    <input type="text" id="edit-allergens" name="allergens">
                </div>

                <div class="fg">
                    <label for="edit-description">תיאור המזון:</label>
                    <textarea id="edit-description" name="description" rows="3" required></textarea>
                    <span class="ferr" id="err-edit-description"></span>
                </div>

                <div class="edit-btns">
                    <button type="submit" class="btn-green" id="btn-save-item">שמור שינויים</button>
                    <button type="button" class="btn-outline" onclick="closeEditItemModal()">ביטול</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/profile.js"></script>

    <footer>
        <div class="footer-links">
            <a href="about.php">אודות</a> | <a href="contact.php">צרו קשר</a> | <a href="faq.php">שאלות תשובות</a>
        </div>
        <p>© 2026 ShareIt - פלטפורמה קהילתית לשיתוף מזון</p>
    </footer>
</body>
</html> 