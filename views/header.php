<?php
// הפעלת ה-Session במידה ועוד לא הופעל בדף הנוכחי
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header>
    <div class="header-right">
        <a href="index.php" class="logo-link">
            <img src="../assets/img/logo.jpeg" alt="ShareIt Logo" class="header-logo">
        </a>
    </div>
    
    <nav id="user-info">
        <style>
            /* עיצוב נקי לקישורים: שחור, בלי קו, ירוק בהיר בריחוף */
            .user-logged-in .nav-link {
                color: #333 !important; /* צבע שחור/כהה */
                text-decoration: none !important; /* ביטול הקו התחתון */
                font-weight: 600;
                transition: color 0.3s ease;
                margin-left: 0;
            }
            
            .user-logged-in .nav-link:hover {
                color: #1ebd7f !important; /* ירוק בהיר בריחוף */
            }

            /* עיצוב כפתורים מיוחדים שדורשים רקע (כמו התנתק או שתף אוכל) נשארים נקיים מקו תחתון */
            .user-logged-in .btn-logout,
            .user-logged-in .btn-share-food {
                text-decoration: none !important;
            }
        </style>

        <?php if (isset($_SESSION['first_name'])): ?>
            <div class="user-logged-in">
                <div class="user-profile-header">
                    <a href="profile.php" class="profile-link">
                        <img src="<?php echo !empty($_SESSION['profile_picture']) ? '../' . htmlspecialchars($_SESSION['profile_picture']) : '../assets/img/default-avatar.png'; ?>" class="header-profile-img" alt="פרופיל">
                    </a>
                    <span class="welcome-text">שלום, <strong><?php echo htmlspecialchars($_SESSION['first_name']); ?></strong></span>
                </div>
                
                <div class="header-nav-menu">
                    <a href="posts.php" class="nav-link">טעים בשכונה 😋</a>
                    <a href="my_chats.php" class="nav-link">הצ'אטים שלי 💬</a>
                    <a href="upload.php" class="nav-link btn-share-food">שתף אוכל +</a>
                    <a href="../api/auth_logout.php" class="nav-link btn-logout">התנתק</a>
                </div>
            </div>
        <?php else: ?>
            <div class="user-guest">
                <a href="login.php" class="nav-link btn-login" style="text-decoration: none;">התחברות</a>
                <a href="register.php" class="nav-link btn-register" style="text-decoration: none;">הרשמה</a>
            </div>
        <?php endif; ?>
    </nav>
</header> 