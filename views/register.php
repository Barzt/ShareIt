<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShareIt - הרשמה</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/register.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-container">
            <h1>הצטרפו ל-ShareIt</h1>
            <p class="auth-subtitle">קהילה משתפת, חוסכת ויוצרת קיימות חברתית 🌍♻️</p>
            
            <form action="../api/auth_register.php" method="POST" id="registerForm">
                
                <div class="form-row">
                    <div class="input-wrapper">
                        <input type="text" name="first_name" id="first_name" placeholder="שם פרטי" required>
                    </div>
                    <div class="input-wrapper">
                        <input type="text" name="last_name" id="last_name" placeholder="שם משפחה" required>
                    </div>
                </div>

                <div class="form-group">
                    <input type="email" name="email" id="email" placeholder="אימייל" required>
                    <div id="email-feedback" class="error-bubble"></div>
                </div>

                <div class="form-group">
                    <input type="password" name="password" id="password" placeholder="סיסמה (מינימום 8 תווים, אות ומספר)" required>
                    <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    <div id="password-feedback" class="error-bubble"></div>
                </div>

                <div class="form-group">
                    <input type="password" name="password_confirm" id="password_confirm" placeholder="אימות סיסמה" required>
                    <i class="fas fa-eye toggle-password" id="togglePasswordConfirm"></i>
                    <div id="confirm-feedback" class="error-bubble"></div>
                </div>

                <div class="form-group">
                    <input type="tel" name="phone" id="phone" placeholder="טלפון (למשל 0501234567)" required>
                    <div id="phone-error" class="error-bubble"></div>
                </div>

                <h3 class="form-section-title">כתובת למסירת אוכל</h3>
                <div class="address-grid">
                    <input type="text" name="city" id="city" placeholder="עיר" required>
                    <input type="text" name="street" id="street" placeholder="רחוב" required>
                    <input type="text" name="house_number" id="house_number" placeholder="מספר בית" required>
                    <input type="text" name="apartment" id="apartment" placeholder="דירה">
                </div>

                <input type="hidden" name="formatted_address" id="formatted_address">
                <input type="hidden" name="lat" id="lat">
                <input type="hidden" name="lng" id="lng">

                <button type="submit" id="submit-btn" class="btn-primary" disabled>צרו חשבון</button>
            </form>

            <div class="auth-footer-link">
                כבר רשומים? <a href="login.php">התחברו כאן</a>
            </div>
        </div>
    </div>

    <script src="../assets/js/register.js?v=<?php echo time(); ?>"></script>
</body>
</html> 