<?php session_start(); ?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShareIt - דף הבית</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/feed.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="toast-success">
            <span>✅</span>
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <?php include 'header.php'; ?>

    <main>
        <section class="hero-section">
            <div class="hero-content">
                <div class="hero-brand-name">ShareIt</div>
                <div class="hero-slogan">Eat. Share. Care.</div>
                <div class="hero-badge">קהילה משתפת, חוסכת ויוצרת קיימות חברתית 🌍♻️</div>
                <h1>
                    <span class="white-text">מה מתחשק לנו היום</span><br>
                    <span id="changing-text">קובה סלק של שישי?</span>
                </h1>
            </div>
        </section>

        <section id="location-bar">
            <p>📍 מציג אוכל ברדיוס של 10 ק"מ מ: 
                <strong><?php echo $_SESSION['user_address'] ?? 'מיקום לא ידוע'; ?></strong>
            </p>
        </section>

        <section id="feed-container">
            <div id="status-area">
                <div id="loading-msg">מחפשים אוכל טעים בסביבה...</div>
            </div>
            
            <div class="carousel-wrapper" id="carousel-wrap" style="display: none;">
                <button class="slider-btn btn-prev" onclick="scrollSlider('prev')">
                    <i class="fas fa-chevron-right"></i>
                </button>
                
                <div id="items-grid" class="grid"></div>
                
                <button class="slider-btn btn-next" onclick="scrollSlider('next')">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-links">
            <a href="about.php">אודות</a> | <a href="contact.php">צרו קשר</a> | <a href="faq.php">שאלות תשובות</a>
        </div>
        <p>© 2026 ShareIt - פלטפורמה קהילתית לשיתוף מזון</p>
    </footer>

    <script src="../assets/js/main.js"></script>
</body> 
</html> 