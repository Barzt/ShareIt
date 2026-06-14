<?php session_start(); ?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShareIt - אודותינו</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/about.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        @media (max-width: 768px) {
            /* ביטול ה-Grid וכפיית מבנה של טור אחיד (אחת מתחת לשנייה) */
            .vision-grid {
                display: flex !important;
                flex-direction: column !important;
                gap: 25px !important;
                padding: 0 10px !important;
                width: 100% !important;
            }
            
            /* עידון התיבות והתאמת המרווחים באפליקציה */
            .vision-card {
                width: 100% !important;
                padding: 25px 20px !important;
                border-radius: 16px !important;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05) !important;
                box-sizing: border-box !important;
            }
            
            .card-icon {
                font-size: 2.2rem !important;
                margin-bottom: 12px !important;
            }
            
            /* שדרוג: מירכוז כותרות המטרה והחזון בתוך הכרטיסיות בנייד */
            .vision-card h2 {
                font-size: 1.3rem !important;
                margin-bottom: 10px !important;
                text-align: center !important; /* ממרכז קשיח את הכותרת */
            }
            
            /* שמירה על יישור גוף הטקסט ימינה בצורה ישרה ונקייה */
            .vision-card p {
                font-size: 0.95rem !important;
                line-height: 1.55 !important;
                color: #444 !important;
                text-align: right !important; /* מיישר את גוף הכתב קשיח לימין */
                direction: rtl !important;    /* מבטיח זרימת קריאה נכונה */
                margin: 0 !important;
            }
            
            /* התאמת אזור ה-Hero של הדף למסך הטלפון */
            .about-hero {
                padding: 50px 15px !important;
            }
            .hero-brand-name {
                font-size: 3.2rem !important;
            }
            .about-hero h1 {
                font-size: 1.5rem !important;
                white-space: normal !important;
                line-height: 1.35 !important;
            }
            .hero-subtitle {
                font-size: 0.95rem !important;
            }
            
            /* סידור צוות המפתחים בטור אנכי נקי וממורכז */
            .team-grid {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important; 
                gap: 15px !important;
                padding: 0 10px !important;
            }
            .team-section {
                padding: 50px 15px !important;
            }
            .section-title {
                font-size: 2rem !important;
            }
            .team-intro {
                font-size: 0.95rem !important;
                margin-bottom: 30px !important;
            }
            .team-card {
                padding: 20px 10px !important;
                border-radius: 15px !important;
            }
            
            /* התאמת קוביות הקרדיט בתחתית */
            .academic-credit {
                padding: 60px 0 !important;
            }
            .credit-badge {
                max-width: 92% !important;
                padding: 35px 20px !important;
                border-radius: 20px !important;
            }
            .credit-badge p {
                font-size: 1rem !important;
            }
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <main class="about-page">
        <section class="about-hero">
            <div class="content-container">
                <div class="hero-brand-name">ShareIt</div>
                <h1>קהילה משתפת, חוסכת ויוצרת קיימות חברתית 🌍♻️</h1>
                <p class="hero-subtitle">הפלטפורמה הקהילתית החכמה לשיתוף מזון</p>
            </div>
        </section>

        <section class="vision-section">
            <div class="content-container">
                <div class="vision-grid">
                    <div class="vision-card purpose-card">
                        <i class="fas fa-bullseye card-icon"></i>
                        <h2>המטרה שלנו</h2>
                        <p>ShareIt היא פלטפורמה קהילתית חכמה שנועדה לצמצם בזבוז מזון, לעודד קיימות חברתית ולחזק את הקהילתיות המקומית. המערכת מאפשרת לשכנים באותו אזור גיאוגרפי לחלוק חומרי גלם טריים ושאריות מזון ביתי בצורה בטוחה ופשוטה.</p>
                    </div>
                    <div class="vision-card">
                        <i class="fas fa-eye card-icon"></i>
                        <h2>החזון שלנו</h2>
                        <p>אנחנו שואפים ליצור עולם שבו מזון לא נזרק אלא עובר לידיים שזקוקות לו. החזון שלנו הוא להפוך את שיתוף המשאבים הקהילתי לנורמה חברתית חדשה, תוך שימוש בטכנולוגיית AI מתקדמת כדי להבטיח את איכות ובטיחות המזון המועבר.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="team-section">
            <div class="content-container"> 
                <h2 class="section-title">הנבחרת שלנו</h2>
                <p class="team-intro">אנחנו ארבעה סטודנטים לתואר ראשון במערכות מידע עם התמחות בחדשנות דיגיטלית</p>
                 
                <div class="team-grid">
                    <div class="team-card">
                        <div class="member-icon"><i class="fas fa-user-graduate"></i></div>
                        <h3>שירה דבח</h3>
                        <p>מערכות מידע | שנה ג'</p>
                    </div>
                    <div class="team-card">
                        <div class="member-icon"><i class="fas fa-user-graduate"></i></div>
                        <h3>עומר ציון</h3>
                        <p>מערכות מידע | שנה ג'</p>
                    </div>
                    <div class="team-card">
                        <div class="member-icon"><i class="fas fa-user-graduate"></i></div>
                        <h3>בר ציטר</h3>
                        <p>מערכות מידע | שנה ג'</p>
                    </div>
                    <div class="team-card">
                        <div class="member-icon"><i class="fas fa-user-graduate"></i></div>
                        <h3>שירה שיתיאת</h3>
                        <p>מערכות מידע | שנה ג'</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="academic-credit">
            <div class="content-container">
                <div class="credit-badge">
                    <p>פיתוח המיזם בוצע במסגרת אקסלרטור ב'</p>
                    <small>המכללה האקדמית תל אביב-יפו</small>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-links">
            <a href="about.php">אודות</a> | <a href="contact.php">צרו קשר</a> | <a href="faq.php">שאלות תשובות</a>
        </div>
        <p>© 2026 ShareIt - פלטפורמה קהילתית לשיתוף מזון</p>
    </footer>

</body>
</html> 