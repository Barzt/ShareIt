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
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShareIt - מה טעים בשכונה?</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/posts.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include 'header.php'; ?>

    <main class="posts-page-main">
        
        <section class="posts-hero">
            <h1 style="font-family: 'Segoe UI', system-ui, -apple-system, sans-serif !important; font-weight: 700 !important; font-size: 1.8rem !important; color: #0d4d44 !important;">מה טעים בשכונה? 🍏</h1>
            <p>כאן תוכלו למצוא את כל המנות והמוצרים שהשכנים סביבכם משתפים כרגע</p>
        </section>

        <section class="search-filter-container">
            <div class="search-box-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="posts-search" placeholder="חפשו מאכל, תיאור או כתובת... (למשל: פסטה, קובה, רחובות)">
            </div>
            
            <div class="filter-pills" id="categories-filter-bar">
                <button class="pill active" data-category="all">הכל ✨</button>
                <button class="pill" data-category="4">מבושל וביתי 🍲</button>
                <button class="pill" data-category="2">פירות וירקות 🍇</button>
                <button class="pill" data-category="5">מאפים ומתוקים 🥐</button>
                <button class="pill" data-category="1,3">מוצרי מדף ומקרר 🧀</button>
            </div>
        </section>

        <section class="posts-feed-section">
            <div id="posts-status-area">
                <div class="posts-loader">
                    <i class="fas fa-circle-notch fa-spin"></i> טוענים עבורכם את כל השפע...
                </div>
            </div>
            
            <div id="posts-grid" class="posts-grid"></div>
        </section>

    </main>

    <div id="image-popup-modal" class="image-custom-modal">
        <span class="close-modal-btn">&times;</span>
        <img class="modal-popup-img" id="popup-target-img" alt="תמונה מוגדלת בשלמותה">
    </div>

    <footer>
        <div class="footer-links">
            <a href="about.php">אודות</a> | <a href="contact.php">צרו קשר</a> | <a href="faq.php">שאלות תשובות</a>
        </div>
        <p>© 2026 ShareIt - פלטפורמה קהילתית לשיתוף מזון</p>
    </footer>

    <script src="../assets/js/posts.js?v=<?php echo time(); ?>"></script>
</body>
</html> 