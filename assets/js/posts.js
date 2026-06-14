'use strict';

let allItems = [];
let currentCategory = 'all';
let searchQuery = '';

// פונקציית עזר לניקוי כתובת
function getCleanAddressJS(rawAddress) {
    if (!rawAddress) return "";
    let cleanStr = rawAddress.replace('📍 מציג אוכל ברדיוס של 10 ק"מ מ:', '').replace('📍', '').replace('מיקום נוכחי:', '').trim();
    const parts = cleanStr.split(',').map(p => p.trim());
    const cleanParts = parts.filter(p => {
        const forbidden = ['ישראל', 'Israel', 'מחוז המרכז', 'Center District', 'Rehovot Subdistrict', 'נפת רחובות', 'ישוב'];
        return p.length < 30 && !forbidden.includes(p);
    });
    return cleanParts.slice(0, 3).join(', ');
}

// פונקציית הרחבה וצמצום דינמית של תיאורים (תומכת בשני הבלוקים בנפרד)
window.toggleDescription = function(btn, descId) {
    const descEl = document.getElementById(descId);
    if (!descEl) return;
    
    if (descEl.classList.contains('expanded')) {
        descEl.classList.remove('expanded');
        btn.innerText = 'לחצו להרחבה... 🔽';
    } else {
        descEl.classList.add('expanded');
        btn.innerText = 'הציגו פחות 🔼';
    }
};

// --- שדרוג: פונקציות הניהול עבור חלונית הפופ-אפ של התמונה ---
window.openImageModal = function(imgSrc) {
    const modal = document.getElementById('image-popup-modal');
    const modalImg = document.getElementById('popup-target-img');
    if (!modal || !modalImg) return;
    
    modalImg.src = imgSrc;
    modal.style.display = 'flex';
};

window.closeImageModal = function() {
    const modal = document.getElementById('image-popup-modal');
    if (modal) modal.style.display = 'none';
};

// --- שדרוג אבטחתי: פונקציה לטיפול בלחיצה על "מתאים לי בול!" ומעבר ישיר לצ'אט לתיאום ---
window.handleAdoptClick = async function(btn, itemId) {
    if (btn.disabled) return;
    
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = "פותח שיחת תיאום... 💬";

    try {
        // קריאה לשרת כדי ליצור בקשה ולנעול את הפוסט ל-pending
        const response = await fetch('../api/create_checkout.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ item_id: itemId })
        });

        const data = await response.json();

        if (response.ok && data.request_id) {
            // מעבר ישיר לעמוד הצ'אט עם ה-request_id החדש שנפתח!
            window.location.href = `chat.php?request_id=${data.request_id}`;
        } else {
            alert(data.message || "אופס! נראה שהפריט הזה כבר לא זמין כרגע.");
            fetchAllPosts();
        }

    } catch (error) {
        console.error("Error:", error);
        alert("חלה שגיאה בתקשורת מול השרת.");
        btn.disabled = false;
        btn.innerText = originalText;
    }
}; // <-- הסוגר התיקוני שהיה חסר וגרם לקריסה!

// עיבוד וסינון נתונים בזמן אמת והזרקתם לגריד
function filterAndRenderItems() {
    const grid = document.getElementById('posts-grid');
    const statusArea = document.getElementById('posts-status-area');
    if (!grid || !statusArea) return;

    const filtered = allItems.filter(item => {
        const matchCategory = (currentCategory === 'all' || currentCategory.split(',').includes(String(item.category_id)));
        
        const combinedText = `${item.title} ${item.description || ''} ${item.item_address || ''}`.toLowerCase();
        const matchSearch = combinedText.includes(searchQuery.toLowerCase());
        
        return matchCategory && matchSearch;
    });

    if (filtered.length === 0) {
        statusArea.style.display = 'block';
        statusArea.innerHTML = `
            <div class="no-results">
                <h3>לא מצאנו בדיוק את זה... 🔍</h3>
                <p>נסו לשנות את מילת החיפוש או לבחור בקטגוריה אחרת.</p>
            </div>
        `;
        grid.innerHTML = '';
        return;
    }

    statusArea.style.display = 'none';
    grid.innerHTML = '';

    filtered.forEach((item, index) => {
        const card = document.createElement('div');
        card.className = 'item-card';
        const fullImageUrl = item.image_url.startsWith('..') ? item.image_url : `../${item.image_url}`;
        const displayAddress = getCleanAddressJS(item.item_address || item.address || item.formatted_address || item.location_name);

        const firstName = item.first_name || 'שכן';
        const lastName = item.last_name || 'מהקהילה';
        const uploaderFullName = `${firstName} ${lastName}`;

        let specsHTML = '';
        specsHTML += `<div class="post-spec-row"><span>🙋‍♂️ מפרסם/ת:</span> <strong>${uploaderFullName}</strong></div>`;

        if (item.kosher_status) {
            specsHTML += `<div class="post-spec-row"><span>✨ כשרות:</span> <strong>${item.kosher_status}</strong></div>`;
        }
        
        if (item.allergens && item.allergens.trim() && item.allergens.toLowerCase() !== 'אין') {
            specsHTML += `<div class="post-spec-row"><span>⚠️ אלרגנים:</span> <strong>${item.allergens}</strong></div>`;
        } else {
            specsHTML += `<div class="post-spec-row" style="color: #2e7d32;"><span>🥦 אלרגנים:</span> <strong>ללא אלרגנים מוצהרים</strong></div>`;
        }

        if (String(item.category_id) === "4" && item.cooked_at) {
            let cookedTime = item.cooked_at.replace('T', ' ');
            specsHTML += `<div class="post-spec-row"><span>🍲 בושל ב:</span> <strong>${cookedTime}</strong></div>`;
        } else if (["1", "3", "5"].includes(String(item.category_id)) && item.expiry_date) {
            specsHTML += `<div class="post-spec-row"><span>📅 תוקף עד:</span> <strong>${item.expiry_date}</strong></div>`;
        }

        let rawDesc = item.description || '';
        let userDescription = rawDesc;
        let systemFeedback = item.ai_analysis || item.ai_description || item.ai_feedback || '';

        if (rawDesc.includes("ניתוח AI") || rawDesc.includes("פידבק מהמערכת") || rawDesc.includes("פידבק המערכת")) {
            const splitParts = rawDesc.split(/ניתוח AI:?|פידבק מהמערכת:?|פידבק המערכת:?/i);
            userDescription = splitParts[0].trim();
            if (!systemFeedback && splitParts[1]) {
                systemFeedback = splitParts[1].trim();
            }
        }

        userDescription = userDescription.replace(/תיאור המשתמש:?/gi, '')
                                         .replace(/תיאור המפרסם:?/gi, '')
                                         .replace(/ניתוח AI:?/gi, '')
                                         .trim();
                                         
        systemFeedback = systemFeedback.replace(/פידבק המערכת:?/gi, '')
                                       .replace(/ניתוח AI:?/gi, '')
                                       .replace(/תיאור המשתמש:?/gi)
                                       .trim();

        let distanceText = '';
        let dist = parseFloat(item.distance || 0);
        if (dist < 1) {
            let meters = Math.round(dist * 1000);
            distanceText = `📍 במרחק ${meters} מטרים`;
        } else {
            distanceText = `📍 במרחק ${dist.toFixed(1)} ק"מ`;
        }

        const userDescId = `user-desc-${item.item_id || item.id}-${index}`;
        const aiDescId = `ai-desc-${item.item_id || item.id}-${index}`;
        const realItemId = item.item_id || item.id;

        card.innerHTML = `
            <div class="item-img-container" onclick="window.openImageModal('${fullImageUrl}')">
                <img src="${fullImageUrl}" alt="${item.title}">
            </div>
            <div class="item-card-content">
                <h3 class="post-card-title">${item.title}</h3>
                <p class="post-card-address">🏠 ${displayAddress}</p>
                
                <div class="post-specs-container">
                    ${specsHTML}
                </div>
                
                <div class="description-block">
                    <h4 class="small-spec-heading">✍️ תיאור המפרסם:</h4>
                    <p class="item-desc" id="${userDescId}">${userDescription || 'אין תיאור נוסף פנוי.'}</p>
                    ${userDescription && userDescription.length > 75 ? `
                        <button class="read-more-btn" onclick="window.toggleDescription(this, '${userDescId}')">לחצו להרחבה... 🔽</button>
                    ` : ''}
                </div>
                
                ${systemFeedback ? `
                    <div class="ai-feedback-block">
                        <h4 class="small-spec-heading">🤖 פידבק המערכת:</h4>
                        <p class="ai-feedback-text" id="${aiDescId}">${systemFeedback}</p>
                        ${systemFeedback.length > 75 ? `
                            <button class="read-more-btn" onclick="window.toggleDescription(this, '${aiDescId}')">לחצו להרחבה... 🔽</button>
                        ` : ''}
                    </div>
                ` : ''}
                
                <p class="post-card-distance">
                    ${distanceText}
                </p>
            </div>
            <button class="btn-primary-feed" onclick="window.handleAdoptClick(this, ${realItemId})">
                מתאים לי בול! 😋
            </button>
        `;
        grid.appendChild(card);
    });
}

async function fetchAllPosts() {
    const statusArea = document.getElementById('posts-status-area');
    try {
        const response = await fetch('../api/get_feed.php');
        allItems = await response.json();
        
        if (!Array.isArray(allItems)) {
            allItems = [];
        }
        
        filterAndRenderItems();
    } catch (e) {
        console.error("טעינת הלוח נכשלה:", e);
        if (statusArea) {
            statusArea.innerHTML = `<div class="no-results"><h3 style="color:#d63031;">שגיאה בתקשורת עם השרת.</h3></div>`;
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    fetchAllPosts();

    const modal = document.getElementById('image-popup-modal');
    const closeBtn = document.querySelector('.close-modal-btn');
    
    if (closeBtn) {
        closeBtn.addEventListener('click', window.closeImageModal);
    }
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) window.closeImageModal();
        });
    }

    const searchInput = document.getElementById('posts-search');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.trim();
            filterAndRenderItems();
        });
    }

    const pills = document.querySelectorAll('#categories-filter-bar .pill');
    pills.forEach(pill => {
        pill.addEventListener('click', (e) => {
            pills.forEach(p => p.classList.remove('active'));
            e.target.classList.add('active');
            currentCategory = e.target.getAttribute('data-category');
            filterAndRenderItems();
        });
    });
});