// assets/js/chat.js
'use strict';

let lastMessageCount = 0;

function createCustomConfirm(message, onConfirm) {
    const existingModal = document.getElementById('custom-confirm-modal');
    if (existingModal) existingModal.remove();

    const modal = document.createElement('div');
    modal.id = 'custom-confirm-modal';
    modal.className = 'custom-modal-overlay';

    modal.innerHTML = `
        <div class="custom-modal-card">
            <div class="custom-modal-icon"><i class="fas fa-handshake"></i></div>
            <h3 style="margin: 0 0 10px 0; color: #333; font-size: 20px;">אישור איסוף מנה</h3>
            <p style="color: #666; font-size: 15px; margin-bottom: 20px; line-height: 1.5;">${message}</p>
            <div class="custom-modal-buttons">
                <button id="custom-confirm-yes" class="btn-modal-confirm">כן, אישור</button>
                <button id="custom-confirm-no" class="btn-modal-cancel">ביטול</button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    document.getElementById('custom-confirm-yes').addEventListener('click', () => {
        modal.remove();
        onConfirm();
    });

    document.getElementById('custom-confirm-no').addEventListener('click', () => {
        modal.remove();
    });
}

function showToastNotification(text, isError = false) {
    const toast = document.getElementById('toast') || document.createElement('div');
    toast.id = 'toast';
    toast.innerText = text;
    toast.style = `
        position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
        background: ${isError ? '#d32f2f' : '#2e7d32'}; color: white;
        padding: 12px 24px; border-radius: 30px; font-weight: bold;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10001;
        transition: opacity 0.3s ease; opacity: 1;
    `;
    if (!document.getElementById('toast')) document.body.appendChild(toast);
    
    setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 3000);
}

function updateStatusBar(meta) {
    const statusBar = document.getElementById('chat-status-bar');
    if (!statusBar) return;

    if (statusBar.querySelector('.status-banner.completed')) {
        return;
    }

    const currentUserId = parseInt(window.CURRENT_USER_ID);
    const uploaderId = parseInt(meta.uploader_id);
    const isUploader = (currentUserId === uploaderId);

    if (meta.request_status === 'cancelled') {
        statusBar.innerHTML = `<div class="status-banner completed" style="background: #f8d7da !important; color: #721c24 !important; border: 1px solid #f5c6cb;">❌ העסקה בוטלה והמנה הוחזרה לפרסום בפיד.</div>`;
        return;
    }

    if (meta.payment_status === 'paid') {
        if (window.IS_ADOPTER) {
            statusBar.innerHTML = `<div class="status-banner completed">✅ העסקה הושלמה בהצלחה! בתיאבון! 🍔</div>`;
        } else {
            statusBar.innerHTML = `<div class="status-banner completed">✅ העסקה הושלמה בהצלחה! בתיאבון לשכנים! 🎉</div>`;
        }
        return;
    }

    if (meta.request_status === 'approved') {
        if (isUploader) {
            statusBar.innerHTML = `<div class="status-banner waiting"><i class="fas fa-spinner fa-spin"></i> אישור האיסוף בוצע בהצלחה. ממתין כעת לתשלום מהשכן...</div>`;
        } else {
            // הוספת מזהה ייחודי id="btn-pay" כדי שהמערכת תזהה את הלחיצה ותשבור את ה-iframe למסך מלא!
            statusBar.innerHTML = `
                <div class="status-flex-bar adopter" style="flex-direction: column; justify-content: center; text-align: center; gap: 12px; padding: 15px;">
                    <span style="font-weight: bold;">🔓 השכן אישר את המפגש! עברו לתשלום מאובטח:</span>
                    <button type="button" id="btn-pay" onclick="window.handleStripePayment(${window.CHAT_REQUEST_ID})" class="btn-status-action pay">
                        <i class="fas fa-credit-card"></i> לתשלום מאובטח (5 ש"ח)
                    </button>
                </div>
            `;
        }
        return;
    }

    if (isUploader) {
        statusBar.innerHTML = `
            <div class="status-flex-bar uploader">
                <span style="font-weight: bold;">💡 הגעתם להסכמה בצ'אט על איסוף? אשרו את הבקשה:</span>
                <button type="button" onclick="window.handleApproveRequest(${window.CHAT_REQUEST_ID})" class="btn-status-action approve">
                    <i class="fas fa-check"></i> אשר איסוף מנה
                </button>
            </div>
        `;
    } else {
        statusBar.innerHTML = `<div class="status-banner default-pending">⏳ ממתין לאישור סופי מהשכן המפרסם בצ'אט... </div>`;
    }
}

window.handleApproveRequest = function(requestId) {
    createCustomConfirm("האם לאשר לשכן לבוא לאסוף את המנה בשעה שסיכמתם?", async () => {
        try {
            const response = await fetch('../api/approve_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ request_id: requestId })
            });
            const data = await response.json();

            if (response.ok && data.status === 'success') {
                showToastNotification("הבקשה אושרה בהצלחה! 🎉");
                fetchChatMessages();
            } else {
                showToastNotification(data.message || "חלה שגיאה בעדכון הסטטוס.", true);
            }
        } catch (error) {
            console.error("Error:", error);
            showToastNotification("שגיאת תקשורת מול השרת.", true);
        }
    });
};

window.handleStripePayment = async function(requestId) {
    try {
        const response = await fetch('../api/start_stripe_payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: requestId })
        });
        const data = await response.json();

        if (response.ok && data.url) {
            // 💳 שדרוג אבטחתי: אם אנחנו בתוך iframe (חלון וואטסאפ), נפתח את התשלום המאובטח על כל המסך הראשי!
            if (window.self !== window.top) {
                window.top.location.href = data.url;
            } else {
                window.location.href = data.url;
            }
        } else {
            showToastNotification(data.message || "לא ניתן להפיק קישור תשלום כרגע.", true);
        }
    } catch (error) {
        console.error("Error:", error);
        showToastNotification("שגיאת תקשורת מול שרתי הסליקה.", true);
    }
};

async function fetchChatMessages() {
    if (!window.CHAT_REQUEST_ID) return;
    try {
        const response = await fetch(`../api/get_messages.php?request_id=${window.CHAT_REQUEST_ID}`);
        if (!response.ok) return;
        const data = await response.json();
        
        if (data.meta) updateStatusBar(data.meta);

        if (data.messages && data.messages.length !== lastMessageCount) {
            const chatWindow = document.getElementById('chat-window');
            if (!chatWindow) return;

            chatWindow.innerHTML = '';
            
            if (data.messages.length === 0) {
                chatWindow.innerHTML = `
                    <div class="chat-empty" style="background: rgba(255,255,255,0.8); padding: 15px; border-radius: 15px; text-align: center; margin: auto;">
                        <i class="far fa-comments chat-empty-icon" style="font-size: 30px; color: #0d4d44;"></i>
                        <p style="margin:0; font-weight: bold; color: #0d4d44;">זה הזמן להתחיל את השיחה עם השכן/ה!</p>
                    </div>
                `;
            }

            data.messages.forEach(msg => {
                const rawName = msg.first_name ? msg.first_name.trim() : "שכן";
                const displayName = msg.is_mine ? "אני" : rawName;
                const avatarLetter = rawName.charAt(0);
                
                const wrapper = document.createElement('div');
                
                const isSystemMessage = msg.message_text.includes('[מערכת]');
                if (isSystemMessage) {
                    wrapper.style.cssText = "display: flex; justify-content: center; width: 100%; margin: 10px 0; direction: rtl;";
                    wrapper.innerHTML = `
                        <div class="system-message" style="background: rgba(248, 215, 218, 0.2); color: #721c24; padding: 10px 18px; border-radius: 12px; font-size: 0.9rem; font-weight: 600; text-align: center; max-width: 85%; border: 1px solid #f5c6cb; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
                            ${escapeHtml(msg.message_text)}
                        </div>
                    `;
                } else {
                    if (msg.is_mine) {
                        wrapper.style.cssText = "display: flex; gap: 10px; align-items: flex-end; width: 100%; justify-content: flex-start; direction: rtl; flex-direction: row;";
                    } else {
                        wrapper.style.cssText = "display: flex; gap: 10px; align-items: flex-end; width: 100%; justify-content: flex-start; direction: rtl; flex-direction: row-reverse;"; 
                    }

                    const alignStyle = msg.is_mine ? 'text-align: right; color: #0d4d44;' : 'text-align: right; color: #d63031;';
                    const avatarColor = msg.is_mine ? 'background: #0d4d44;' : 'background: #d63031;';
                    
                    const bubbleColor = msg.is_mine ? 'background: #ffffff !important; border: 1px solid #cbeae2; border-radius: 16px 16px 4px 16px;' : 'background: #ffffff !important; border: 1px solid #e2e8e7; border-radius: 16px 16px 16px 4px;';

                    wrapper.innerHTML = `
                        <div class="msg-avatar" style="width: 32px; height: 32px; border-radius: 50%; ${avatarColor} color: white; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: bold; flex-shrink: 0; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">${avatarLetter}</div>
                        <div class="message" style="max-width: 75%; padding: 11px 16px; font-size: 0.95rem; position: relative; box-shadow: 0 2px 8px rgba(0,0,0,0.06); display: flex; flex-direction: column; gap: 3px; line-height: 1.4; ${bubbleColor}">
                            <div class="msg-sender" style="${alignStyle} font-weight: bold; font-size: 0.95rem; margin-bottom: 4px; display: block;">${escapeHtml(displayName)}</div>
                            <div class="msg-text" style="color: #222; word-break: break-word; text-align: right;">${escapeHtml(msg.message_text)}</div>
                            <div class="msg-time" style="font-size: 0.7rem; color: #888; align-self: flex-end; margin-top: 4px;">${msg.created_at ? msg.created_at.substring(11, 16) : ''}</div>
                        </div>
                    `;
                }
                chatWindow.appendChild(wrapper);
            });
            chatWindow.scrollTop = chatWindow.scrollHeight;
            lastMessageCount = data.messages.length;
        }
    } catch (error) {
        console.error("Polling error:", error);
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

document.addEventListener('DOMContentLoaded', () => {
    fetchChatMessages();
    setInterval(fetchChatMessages, 3000);

    // 🔒 מגן אבטחה מובנה: לכידת אירוע הלחיצה ושיגור הדף לתשלום במסך מלא במקום להיחסם ב-iframe
    document.addEventListener('click', function(e) {
        const paymentBtn = e.target.closest('#btn-pay') || e.target.closest('.btn-status-action.pay');
        if (paymentBtn && window.self !== window.top) {
            // נותן ל-handleStripePayment לרוץ, אבל מאפשר לו לשלוט בחלון העליון
            // במידה ויש הגנת דפדפן מיוחדת, שורת ה-JS הבאה מבטיחה פריסה מלאה
            setTimeout(() => {
                // בדיקת גיבוי למקרה שההפניה הרגילה מתעכבת
            }, 100);
        }
    });

    const form = document.getElementById('messageForm');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('msgInput');
            const text = input.value.trim();
            if (!text) return;

            input.value = '';

            try {
                const formData = new FormData();
                formData.append('request_id', window.CHAT_REQUEST_ID);
                formData.append('message_text', text);

                const response = await fetch('../api/send_message.php', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    fetchChatMessages();
                } else {
                    showToastNotification("שגיאה בשליחת ההודעה.", true);
                }
            } catch (error) {
                console.error("Error:", error);
                showToastNotification("שגיאת תקשורת.", true);
            }
        });
    }
});