<?php session_start(); ?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShareIt - שאלות ותשובות</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/faq.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include 'header.php'; ?> 

    <main class="faq-page">
        <section class="faq-hero">
            <div class="content-container">
                <div class="hero-brand-name">ShareIt</div>
                <h1>שאלות ותשובות</h1>
                <p>כל מה שצריך לדעת על שיתוף חכם של מזון בקהילה</p>
            </div>
        </section>

        <section class="faq-section">
            <div class="content-container">
                
                <div class="faq-container">
                    <div class="faq-item">
                        <button class="faq-question">מה זה ShareIt? <i class="fas fa-chevron-down"></i></button>
                        <div class="faq-answer">
                            <p>ShareIt היא פלטפורמה קהילתית חכמה לשיתוף מזון בין תושבים באותו אזור גיאוגרפי, המאפשרת למסור חומרי גלם או שאריות מזון ביתי כדי לצמצם בזבוז ולחזק את הקהילה.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">איך ה- AI עוזר במערכת? <i class="fas fa-chevron-down"></i></button>
                        <div class="faq-answer">
                            <p>הבינה המלאכותית שלנו מבצעת אימות אוטומטי של פוסטים, מזהה אם תמונת המזון ראויה למאכל, ומסננת תוכן לא רלוונטי או פוגעני.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">איך אני יודע שהאוכל בטוח למאכל? <i class="fas fa-chevron-down"></i></button>
                        <div class="faq-answer">
                            <p>המערכת משתמשת ב-AI לזיהוי ראשוני של ראויות למאכל, אך אנו ממליצים תמיד לבדוק את הפריט בעת האיסוף.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">אילו סוגי מזון ניתן לשתף במערכת? <i class="fas fa-chevron-down"></i></button>
                        <div class="faq-answer">
                            <p>ניתן לשתף חומרי גלם טריים כגון פירות, ירקות ולחם, וכן שאריות מזון מבושל כגון תבשילים ביתיים או קוסקוס של שישי. המערכת מסווגת את הפריטים לפי קטגוריה, כמות ותוקף.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">איך מתבצע תיאום האיסוף בין השכנים? <i class="fas fa-chevron-down"></i></button>
                        <div class="faq-answer">
                            <p>לאחר שליחת בקשת איסוף ואישורה, ניתן לתאם את המסירה דרך מודול התקשורת הכולל צ'אט אישי. המערכת מסייעת בתיאום נקודת המפגש, חישוב מרחק וזמני הגעה באמצעות ממשק מפות אינטראקטיבי.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">האם יש תשלום על השימוש בשירות? <i class="fas fa-chevron-down"></i></button>
                        <div class="faq-answer">
                            <p>המערכת מחוברת לספק סליקה חיצוני. כל מסירה כרוכה ב"דמי רצינות" של 5 ש"ח, הכוונה לעמלת שירות סמלית לתפעול הפלטפורמה ושמירה על קהילת ShareIt. </p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">איך מעדכנים את המיקום לחישוב הרדיוס?<i class="fas fa-chevron-down"></i></button>
                        <div class="faq-answer"> 
                            <p>
                                ניתן לעדכן את המיקום ב-2 דרכים:<br><br>
                                
                                1. <strong>שינוי כתובת קבועה:</strong> היכנסו לפרופיל האישי, לחצו על "עריכת פרטים" ועדכנו את הכתובת.<br>
                                <strong class="note-line">*הערה: פוסטים יוצגו מעתה לפי הכתובת החדשה. פוסטים שאינם בטווח הרדיוס החדש יוסרו מהתצוגה!</strong>
                                
                                2. <strong>שינוי כתובת זמני:</strong> בעמוד "שתף פוסט", תחת "איפה האוכל נמצא?", סמנו את התיבה "אני במיקום אחר כרגע". תוכלו להקליד את הכתובת החדשה או לגרור את הסמן במפה.<br>
                                <strong class="note-line">*הערה: אם התחרטתם ותרצו למסור מהכתובת הקבועה, פשוט מחקו את הכתובת שהזנתם והסירו את הסימון מהתיבה!</strong>
                            </p>
                        </div>
                    </div> 

                    <div class="faq-item">
                        <button class="faq-question">כיצד מחושב המרחק בין המשתמשים במערכת? <i class="fas fa-chevron-down"></i></button>
                        <div class="faq-answer">
                            <p>חישוב המרחק מבוצע על בסיס מרחק אווירי בין הכתובת המעודכנת בפרופיל שלכם לבין מיקום הפוסט. כך יוצגו עבורכם הפוסטים הרלוונטיים ביותר ברדיוס סביבתכם!</p>
                        </div>
                    </div>


                    <div class="faq-item">
                        <button class="faq-question">למה המערכת לא זיהתה את התמונה שהעליתי? <i class="fas fa-chevron-down"></i></button>
                        <div class="faq-answer">
                            <p>
                                מערכת ה-AI שלנו חכמה מאוד, אבל לעיתים מתקשה לזהות אוכל אם התמונה צולמה בתנאי תאורה בעייתיים, כגון: צללים חזקים, סנוור או חושך. מאחר והמערכת שלנו מתוכננת לשמור על רף אבטחה גבוה, היא לא תאשר תמונה אם אין לה ודאות מוחלטת שמדובר באוכל בטוח.<br><br>
                                
                               <strong> כדי שהזיהוי יעבוד חלק בפעם הראשונה, אנו ממליצים:</strong><br>
                                • לצלם את המנה באור יום טבעי או תחת תאורה חזקה וברורה<br>
                                • לשמור על פוקוס ולוודא שהתמונה אינה מטושטשת<br>
                                • למרכז את המנה בתוך הפריים<br>
                                <strong class="note-line">*הערה: אם התמונה לא זוהתה, פשוט נסו לצלם מזווית אחרת או באור טוב יותר ולהעלות שוב!</strong>
                            </p>
                        </div>
                    </div>

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

    <script>
        // לוגיקה לפתיחה וסגירה של שאלות
        document.querySelectorAll('.faq-question').forEach(button => {
            button.addEventListener('click', () => {
                const faqItem = button.parentElement;
                faqItem.classList.toggle('active');
            });
        });
    </script>
</body>
</html>  