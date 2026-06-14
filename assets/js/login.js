'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');

    // הצגת/הסתרת סיסמה
    if (togglePassword) {
        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    // פונקציות עזר להצגת שגיאות על המסך
    function showError(input, msgEl, message) {
        input.classList.add('input-error');
        msgEl.textContent = message;
    }

    function clearError(input, msgEl) {
        input.classList.remove('input-error');
        msgEl.textContent = '';
    }

    function validateEmail() {
        const msgEl = document.getElementById('email-error');
        const val = emailInput.value.trim();
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!val) {
            showError(emailInput, msgEl, 'יש להזין כתובת אימייל');
            return false;
        }
        if (!emailPattern.test(val)) {
            showError(emailInput, msgEl, 'כתובת האימייל אינה תקינה');
            return false;
        }
        clearError(emailInput, msgEl);
        return true;
    }

    function validatePassword() {
        const msgEl = document.getElementById('password-error');
        const val = passwordInput.value;
        if (!val) {
            showError(passwordInput, msgEl, 'יש להזין סיסמה');
            return false;
        }
        clearError(passwordInput, msgEl);
        return true;
    }

    emailInput.addEventListener('blur', validateEmail);
    passwordInput.addEventListener('blur', validatePassword);

    // שדרוג: מעבר לבקשת AJAX שמונעת את "הדף הלבן" ומציגה שגיאות על המסך
    form.addEventListener('submit', async function (e) {
        e.preventDefault(); // עצירת המעבר לדף הלבן אוטומטית
        
        const emailOk = validateEmail();
        const passOk = validatePassword();
        
        if (!emailOk || !passOk) {
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'מתחבר...';

        try {
            const formData = new FormData(form);
            const response = await fetch('../api/auth_login.php', {
                method: 'POST',
                body: formData
            });

            // אם השרת ביצע הפניה (Redirect) לדף הבית - ההתחברות הצליחה!
            if (response.redirected) {
                window.location.href = response.url;
                return;
            }

            // אם נשארנו בדף ההתחברות, נבדוק איזו שגיאה השרת הדפיס
            const text = await response.text();
            
            // זיהוי שגיאת סיסמה
            if (text.includes('סיסמ') || text.includes('שגוי')) {
                showError(passwordInput, document.getElementById('password-error'), 'סיסמה שגויה, אנא נסה שוב');
            } 
            // זיהוי שגיאת אימייל
            else if (text.includes('אימייל') || text.includes('מייל') || text.includes('נמצא') || text.includes('קיים') || text.includes('משתמש')) {
                showError(emailInput, document.getElementById('email-error'), 'בבקשה הזן אימייל תקין');
            } 
            // שגיאה אחרת לא מוכרת
            else if (text.trim() !== '') {
                showError(emailInput, document.getElementById('email-error'), 'שגיאה בהתחברות, אנא נסו שוב.');
            } 
            // במקרה שהכל תקין אך לא בוצעה הפניה מפורשת
            else {
                window.location.href = '../views/index.php';
            }
            
        } catch (err) {
            console.error("Login Error:", err);
            showError(emailInput, document.getElementById('email-error'), 'שגיאת תקשורת. אנא נסה שוב מאוחר יותר.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalBtnText;
        }
    });
}); 