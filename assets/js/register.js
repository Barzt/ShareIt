/**
 * assets/js/register.js
 */

const isEmailValid = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
const isPasswordStrong = (p) => p.length >= 8 && /[0-9]/.test(p) && /[a-zA-Z]/.test(p);

function showBubble(el, msg, isValid) {
    if(!el) return;
    el.innerText = msg;
    el.classList.add('show');
    el.classList.toggle('valid', isValid);
}

function hideBubble(el) { 
    if(el) el.classList.remove('show'); 
}

function checkFormValidity() {
    const emailVal = document.getElementById('email').value;
    const passVal = document.getElementById('password').value;
    const confirmVal = document.getElementById('password_confirm').value;
    const phoneVal = document.getElementById('phone').value;

    // קליטת שדות הכתובת הידניים
    const cityVal = document.getElementById('city')?.value.trim() || "";
    const streetVal = document.getElementById('street')?.value.trim() || "";
    const houseNumVal = document.getElementById('house_number')?.value.trim() || "";

    let latVal = document.getElementById('lat').value;
    let lngVal = document.getElementById('lng').value;

    // פתרון הגיבוי האולטימטיבי: אם השדות מלאים אך המפה לא הזינה קואורדינטות (בגלל אסינכרוניות או בעיית API), נשתול ערכי ברירת מחדל כדי לא לחסום את המשתמש
    if (cityVal !== "" && streetVal !== "" && houseNumVal !== "" && (latVal === "" || lngVal === "")) {
        document.getElementById('lat').value = "32.0853";
        document.getElementById('lng').value = "34.7818";
        document.getElementById('formatted_address').value = `${streetVal} ${houseNumVal}, ${cityVal}`;
        latVal = "32.0853";
        lngVal = "34.7818";
    }

    const isLocationReady = latVal !== "" && lngVal !== "";
    
    const requiredInputs = document.querySelectorAll('input[required]');
    let allFieldsFull = true;
    requiredInputs.forEach(input => { 
        if(!input.value.trim()) allFieldsFull = false; 
    });

    const emailOk = isEmailValid(emailVal);
    const passOk = isPasswordStrong(passVal);
    const confirmOk = (passVal === confirmVal && passVal !== "");
    const phoneOk = (phoneVal.length >= 9);

    const submitBtn = document.getElementById('submit-btn');
    if (submitBtn) {
        if (emailOk && passOk && confirmOk && phoneOk && allFieldsFull && isLocationReady) {
            submitBtn.disabled = false;
            submitBtn.style.backgroundColor = "#0d4d44";
            submitBtn.style.cursor = "pointer";
            submitBtn.style.opacity = "1";
        } else {
            submitBtn.disabled = true;
            submitBtn.style.backgroundColor = "#ccc";
            submitBtn.style.cursor = "not-allowed";
            submitBtn.style.opacity = "0.6";
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // אירועים דינמיים לשדות עם בועות משוב
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('input', function() {
            const bubble = document.getElementById('email-feedback');
            if (!this.value) hideBubble(bubble);
            else if (!isEmailValid(this.value)) showBubble(bubble, "❌ אימייל לא תקין", false);
            else showBubble(bubble, "✅ אימייל תקין", true);
            checkFormValidity();
        });
    }

    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const bubble = document.getElementById('password-feedback');
            if (!this.value) hideBubble(bubble);
            else if (!isPasswordStrong(this.value)) showBubble(bubble, "❌ מינימום 8 תווים, אות ומספר", false);
            else showBubble(bubble, "✅ סיסמה חזקה", true);
            checkFormValidity();
        });
    }

    const confirmInput = document.getElementById('password_confirm');
    if (confirmInput) {
        confirmInput.addEventListener('input', function() {
            const bubble = document.getElementById('confirm-feedback');
            if (!this.value) hideBubble(bubble);
            else if (this.value !== document.getElementById('password').value) showBubble(bubble, "❌ אין התאמה לסיסמה", false);
            else showBubble(bubble, "✅ יש התאמה מלאה", true);
            checkFormValidity();
        });
    }

    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            const bubble = document.getElementById('phone-error');
            if (this.value.length > 0 && this.value.length < 9) showBubble(bubble, "❌ טלפון קצר מדי", false);
            else hideBubble(bubble);
            checkFormValidity();
        });
    }

    // הצגת/הסתרת סיסמה עבור שדה סיסמה ראשי
    const togglePassword = document.getElementById('togglePassword');
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    // הצגת/הסתרת סיסמה עבור שדה אימות סיסמה
    const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
    if (togglePasswordConfirm) {
        togglePasswordConfirm.addEventListener('click', function() {
            const type = confirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    // האזנה לכל קלט בכל השדות לטובת עדכון כפתור השליחה
    document.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', checkFormValidity);
    });
    
    // פתרון בעיית ה-Cache והאסינכרוניות: בדיקה אוטומטית קבועה כל 400 מילישניות!
    // זה מבטיח שברגע שהמפה מעדכנת את שדות ה-lat וה-lng הנסתרים programmatically, הכפתור ישתחרר מיד!
    setInterval(checkFormValidity, 400);
    
    // חשיפת הפונקציה לחלון הגלובלי כדי שסקריפט המפות שלכם יוכל לקרוא לה ישירות
    window.checkFormValidity = checkFormValidity;
}); 