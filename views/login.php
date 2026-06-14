<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShareIt - התחברות</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/login.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <img src="../assets/img/logo.jpeg" alt="ShareIt Logo" class="auth-logo">
        <h1 style="font-family: 'Segoe UI', system-ui, -apple-system, sans-serif !important; font-weight: 700 !important;">ברוכים השבים ל-ShareIt</h1>

        <form action="../api/auth_login.php" method="POST" id="loginForm" novalidate>

            <div class="form-group">
                <label for="email">אימייל:</label>
                <input type="email" name="email" id="email" placeholder="הזינו כתובת אימייל">
                <span class="error-msg" id="email-error"></span>
            </div>

            <div class="form-group">
                <label for="password">סיסמה:</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" placeholder="הזינו סיסמה">
                    <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                </div>
                <span class="error-msg" id="password-error"></span>
            </div>

            <button type="submit">כניסה</button>
        </form>

        <p style="margin-top: 25px; font-size: 0.95rem; color: #555;">
            עדיין אין לכם משתמש? <a href="register.php" style="color: #0d4d44; font-weight: bold; text-decoration: none;">הירשמו עכשיו</a>
        </p>
    </div>

    <script src="../assets/js/login.js?v=<?php echo time(); ?>"></script>
</body>
</html> 