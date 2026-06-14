<?php 
session_start(); 
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShareIt - צרו קשר</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/contact.css?v=1.2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include 'header.php'; ?>

    <main class="contact-main">
        <div class="contact-wrapper">
            
            <section class="info-sidebar">
                <div class="contact-header-card">
                    <h2>נשמח לשמוע מכם! 💬</h2>
                    <p>יש לכם שאלה, הצעה לשיתוף פעולה או שנתקלתם בבעיה טכנית במערכת? הצוות שלנו כאן בשבילכם.</p>
                </div>
                
                <div class="info-links-list">
                    <div class="info-item">
                        <span class="info-icon">✉️</span>
                        <div class="info-text">
                            <span class="info-label">כתובת אימייל לתמיכה</span>
                            <span class="info-value">support@shareit.mtacloud.co.il</span>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-icon">🕒</span>
                        <div class="info-text">
                            <span class="info-label">שעות פעילות מענה</span>
                            <span class="info-value">ימים א'-ה': 18:00 - 09:00</span>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-icon">🌍</span>
                        <div class="info-text">
                            <span class="info-label">קהילת ShareIt</span>
                            <span class="info-value">משתפים אוכל, מחברים בין שכנים ושומרים על הסביבה 💚</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="contact-card">
                <div class="card-top-bar"></div>
                
                <div class="card-body">
                    <h1>שלחו לנו הודעה</h1>
                    <p class="contact-subtitle">מלאו את הפרטים ונחזור אליכם בהקדם האפשרי</p>
                    
                    <form id="contact-form" action="../api/send_contact.php" method="POST">
                        <div class="form-row-2">
                            <div class="fg">
                                <label for="c-name">שם מלא</label>
                                <input type="text" id="c-name" name="name" placeholder="הזינו את שמכם" required>
                            </div>
                            <div class="fg">
                                <label for="c-email">כתובת אימייל</label>
                                <input type="email" id="c-email" name="email" placeholder="name@example.com" required>
                            </div>
                        </div>

                        <div class="fg">
                            <label for="c-subject">נושא הפנייה</label>
                            <input type="text" id="c-subject" name="subject" placeholder="באיזה עניין הפנייה?" required>
                        </div>

                        <div class="fg">
                            <label for="c-message">תוכן ההודעה</label>
                            <textarea id="c-message" name="message" rows="5" placeholder="כתבו לנו כאן את הודעתכם..." required></textarea>
                        </div>

                        <button type="submit" class="btn-submit-contact">שליחת הודעה</button>
                    </form>
                </div>
            </section>

        </div>
    </main>

    <footer>
        <div class="footer-links">
            <a href="about.php">אודות</a> | <a href="contact.php">צרו קשר</a> | <a href="faq.php">שאלות תשובות</a>
        </div>
        <p>© 2026 ShareIt - פלטפורמה קהילתית לשיתוף מזון</p>
    </footer>

</body>
</html> 