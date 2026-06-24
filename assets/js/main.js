'use strict';

const foodTypes = [
    "קובה סלק של שישי?", "אבטיח טרי?", "סיר קציצות של אמא?",
    "ירקות כמו חדשים?", "קוסקוס ומרק?", "פסטה טריה?"
];

let currentIndex = 0;
const textElement = document.getElementById('changing-text');

function rotateText() {
    if (!textElement) return;
    textElement.style.opacity = 0;
    setTimeout(() => {
        currentIndex = (currentIndex + 1) % foodTypes.length;
        textElement.innerText = foodTypes[currentIndex];
        textElement.style.opacity = 1;
    }, 500);
}
if (textElement) setInterval(rotateText, 3000);

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


// --- מנגנון סרט נע פיזי, רציף ואינסופי חסין אש ---
let animationFrameId = null;
let isScrolling = false;

function startLiveMovieTicker() {
    const grid = document.getElementById('items-grid');
    const wrapper = document.getElementById('carousel-wrap');
    if (!grid || !wrapper) return;

    // בדיקה אם הפוסטים נכנסים כולם במסך (אין גלילה)
    // אם הכל נכנס במסך, אין צורך בגלגלת ובחצים
    if (grid.scrollWidth <= grid.clientWidth + 5) {
        const prevBtn = wrapper.querySelector('.slider-btn.btn-prev');
        const nextBtn = wrapper.querySelector('.slider-btn.btn-next');
        if (prevBtn) prevBtn.style.display = 'none';
        if (nextBtn) nextBtn.style.display = 'none';
        
        // עצירת האנימציה אם הייתה פעילה
        if (animationFrameId) {
            cancelAnimationFrame(animationFrameId);
            animationFrameId = null;
        }
        return;
    } else {
        // הצגת החצים במידה ויש גלילה
        const prevBtn = wrapper.querySelector('.slider-btn.btn-prev');
        const nextBtn = wrapper.querySelector('.slider-btn.btn-next');
        if (prevBtn) prevBtn.style.display = 'flex';
        if (nextBtn) nextBtn.style.display = 'flex';
    }

    function tickerStep() {
        if (!isScrolling && grid) {
            // מסיע שמאלה בצורה רציפה וחלקה בקצב קריאה נינוח ואידיאלי
            grid.scrollLeft -= 0.6; 

            // ברגע שהגלגלת עברה את 4 הפוסטים הראשונים (חצי מרוחב הגלילה הכולל), היא מאפסת בחזרה ל-0 בלי שישימו לב
            const halfScrollWidth = grid.scrollWidth / 2;
            if (Math.abs(grid.scrollLeft) >= halfScrollWidth) {
                grid.scrollLeft = 0;
            }
        }
        animationFrameId = requestAnimationFrame(tickerStep);
    }
    
    // איפוס אנימציות קודמות למניעת כפילויות מהירות
    if (animationFrameId) cancelAnimationFrame(animationFrameId);
    animationFrameId = requestAnimationFrame(tickerStep);
}

// מימוש פונקציית החצים בצדדים
function scrollSlider(direction) {
    const grid = document.getElementById('items-grid');
    if (!grid || isScrolling) return;

    isScrolling = true;
    
    // הפעלת smooth זמנית רק לצורך לחיצת החץ
    grid.style.scrollBehavior = 'smooth';
    
    // חישוב רוחב של כרטיסייה אחת + המרווח (gap)
    const card = grid.querySelector('.item-card');
    const scrollAmount = card ? card.offsetWidth + 25 : 340; 
    
    if (direction === 'prev') {
        grid.scrollLeft += scrollAmount;
    } else {
        grid.scrollLeft -= scrollAmount;
    }
    
    // המתנה לסיום האנימציה החלקה של הדפדפן ואז החזרת המצב ל-auto
    setTimeout(() => {
        grid.style.scrollBehavior = 'auto';
        
        const halfScrollWidth = grid.scrollWidth / 2;
        if (Math.abs(grid.scrollLeft) >= halfScrollWidth) {
            grid.scrollLeft = 0;
        } else if (grid.scrollLeft > 0) {
            // התאמה ל-RTL למקרה שהמשתמש גלל ימינה מדי
            grid.scrollLeft = -halfScrollWidth;
        }
        
        isScrolling = false;
    }, 500);
}

// חשיפת הפונקציה ל-window כדי שה-HTML inline onclick יוכל להכיר אותה
window.scrollSlider = scrollSlider;

// שדרוג אבטחתי: פונקציה לטיפול בלחיצה על "מתאים לי בול!" ומעבר ישיר לצ'אט לתיאום
async function handleAdoptClick(btn, itemId) {
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
            if (typeof fetchAllPosts === 'function') {
                fetchAllPosts();
            } else if (typeof fetchFeed === 'function') {
                fetchFeed();
            }
        }

    } catch (error) {
        console.error("Error:", error);
        alert("חלה שגיאה בתקשורת מול השרת.");
        btn.disabled = false;
        btn.innerText = originalText;
    }
}

// חשיפת הפונקציה ל-window כדי ששני הדפים יוכלו להשתמש בה
window.handleAdoptClick = handleAdoptClick;

async function fetchFeed() {
    const grid = document.getElementById('items-grid');
    const statusArea = document.getElementById('status-area');
    const feedContainer = document.getElementById('feed-container');
    const carouselWrap = document.getElementById('carousel-wrap');

    if (!grid || !feedContainer || !statusArea) return; 

    try {
        const response = await fetch('../api/get_feed.php');
        const items = await response.json();
        
        // מקרה שאין פוסטים - הצגת מסך ריק
        if (!items || items.length === 0) {
            feedContainer.classList.remove('has-posts');
            if (carouselWrap) carouselWrap.style.display = 'none';
            statusArea.style.display = 'flex';
            statusArea.innerHTML = `
                <div class="no-results">
                    <h3>אין כרגע אוכל זמין בסביבה.</h3>
                    <p>🥗 שתפו משהו ראשונים!</p>
                    <a href="upload.php" style="color: #0d4d44; font-weight: bold; text-decoration: none;">לחצו כאן לשיתוף</a>
                </div>
            `;
            grid.innerHTML = '';
            return;
        }

        // מקרה שיש פוסטים - הפעלת מצב רקע אדום וסרט נע
        feedContainer.classList.add('has-posts');
        statusArea.style.display = 'none';
        if (carouselWrap) carouselWrap.style.display = 'flex';
        grid.innerHTML = '';

        // שדרוג 1: הגבלה קשיחה ל-4 הפוסטים הכי עדכניים בלבד!
        const recentItems = items.slice(0, 4);

        // שדרוג 2: שכפול ה-4 פעם אחת ליצירת רצף תנועה מעגלי ומושלם ללא קפיצות קצה
        const displayItems = [...recentItems, ...recentItems];

        displayItems.forEach(item => {
            const card = document.createElement('div');
            card.className = 'item-card';
            const fullImageUrl = item.image_url.startsWith('..') ? item.image_url : `../${item.image_url}`;
            const displayAddress = getCleanAddressJS(item.item_address || item.address || item.formatted_address || item.location_name);

            card.innerHTML = `
                <div class="item-img-container">
                    <img src="${fullImageUrl}" alt="${item.title}">
                </div>
                <div class="item-card-content">
                    <h3>${item.title}</h3>
                    <p style="color:#888; font-size:0.85rem; margin:0;">🏠 ${displayAddress}</p>
                    <p class="item-desc">${item.description || 'אין תיאור זמין עבור מוצר זה.'}</p>
                    <p style="color:#d63031; font-weight:bold; font-size:0.95rem; margin:0; margin-top:auto;">
                        📍 במרחק ${parseFloat(item.distance || 0).toFixed(1)} ק"מ
                    </p>
                </div>
                <button class="btn-primary-feed" onclick="window.handleAdoptClick(this, ${item.item_id || item.id})">
                    מתאים לי בול! 😋
                </button>
            `;
            grid.appendChild(card);
        });

        // הפעלת מנוע הסרט הנע הרציף
        startLiveMovieTicker();

    } catch (e) {
        console.error("טעינת הפיד נכשלה:", e);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    fetchFeed();

    const locationBar = document.querySelector('#location-bar strong');
    if (locationBar) {
        locationBar.innerText = getCleanAddressJS(locationBar.innerText);
    }
}); 