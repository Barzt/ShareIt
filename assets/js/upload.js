'use strict';

// --- 1. ניהול שדות דינמיים לפי קטגוריה ---
function toggleFields() {
    const categorySelect = document.getElementById('category_id');
    const expiryGroup = document.getElementById('expiry_group');
    const cookedGroup = document.getElementById('cooked_group');

    if (!categorySelect || !expiryGroup || !cookedGroup) return;

    const categoryId = categorySelect.value;

    expiryGroup.style.display = 'none';
    cookedGroup.style.display = 'none';

    if (categoryId === "4") {
        cookedGroup.style.display = 'block';
    } 
    else if (["1", "3", "5"].includes(categoryId)) {
        expiryGroup.style.display = 'block';
    }
}

// --- 2. תצוגה מקדימה וניתוח תמונה אוטומטי בעזרת Gemini AI ---
if (document.getElementById('imageInput')) {
    document.getElementById('imageInput').onchange = async evt => {
        const [file] = evt.target.files;
        if (file) {
            const preview = document.getElementById('imagePreview');
            preview.src = URL.createObjectURL(file);
            preview.style.display = 'block';
            
            const labelSpan = document.querySelector('.custom-file-upload span');
            if (labelSpan) labelSpan.innerText = "📸 מנתח את התמונה ברקע...";

            // בניית FormData לשליחה מהירה לשרת
            const formData = new FormData();
            formData.append('item_image', file);
            formData.append('ajax_analyze', true); // דגל שמסמן ל-API לבצע רק ניתוח בלי שמירה

            try {
                // קריאה זמנית ברקע ל-upload_item שמפעיל את ה-AI_Processor
                const response = await fetch('../api/upload_item.php', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    const data = await response.json();
                    
                    // הזרקת הנתונים שה-AI חילץ ישירות לטופס
                    if (data && data.is_food) {
                        if (document.getElementById('title')) {
                            document.getElementById('title').value = data.label || "";
                        }
                        if (document.getElementById('description')) {
                            document.getElementById('description').value = data.description || "";
                        }
                        if (labelSpan) labelSpan.innerText = "✨ ה-AI זיהה את המאכל בהצלחה!";
                    } else if (data && !data.is_food) {
                        if (labelSpan) labelSpan.innerText = "❌ שגיאה: התמונה אינה מכילה מאכל תקין!";
                        alert("שומר הסף של ShareIt זיהה שהתמונה שהעלית אינה אוכל. אנא העלה תמונת מאכל תקינה.");
                        evt.target.value = ""; // מאפס את בחירת הקובץ
                        preview.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error("Gemini Scan Error:", error);
                if (labelSpan) labelSpan.innerText = "📸 תמונה נבחרה!";
            }
        }
    };
}

// --- 3. ניהול המפה (Leaflet) ---
const latInput = document.getElementById('item_lat');
const lngInput = document.getElementById('item_lng');
const initLat = (latInput && latInput.value) ? latInput.value : 32.0853;
const initLng = (lngInput && lngInput.value) ? lngInput.value : 34.7818;

if (document.getElementById('map')) {
    window.map = L.map('map').setView([initLat, initLng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(window.map);

    window.marker = L.marker([initLat, initLng], {draggable: true}).addTo(window.map);

    window.marker.on('dragend', function() {
        const position = window.marker.getLatLng();
        document.getElementById('item_lat').value = position.lat;
        document.getElementById('item_lng').value = position.lng;

        if (typeof updateAddressByCoords === "function") {
            updateAddressByCoords(position.lat, position.lng);
        }
    });
}

// --- 4. ניהול ה-Checkbox (מיקום אחר) ---
const toggleLocation = document.getElementById('toggleLocation');
if (toggleLocation) {
    toggleLocation.onchange = function() {
        const manualSection = document.getElementById('manual-location');
        if (manualSection) {
            manualSection.style.display = this.checked ? 'block' : 'none';
            
            if(this.checked) {
                setTimeout(() => { window.map.invalidateSize(); }, 200); 
            } else {
                if (typeof resetToOriginalLocation === "function") {
                    resetToOriginalLocation();
                }
            }
        }
    };
}

// --- 5. אתחול והגדרות בטעינה ---
document.addEventListener('DOMContentLoaded', function() {
    toggleFields(); 
    
    const catSelect = document.getElementById('category_id');
    if (catSelect) catSelect.addEventListener('change', toggleFields);

    document.querySelectorAll('#city, #street, #h_num').forEach(input => {
        input.addEventListener('blur', () => {
            if (typeof fetchCoordinates === "function") fetchCoordinates();
        });
    });

    const addrInput = document.getElementById('item_address');
    const displayBox = document.getElementById('current-display-address');

    if (addrInput && displayBox) {
        const refreshDisplay = () => {
            if (typeof normalizeJSAddress === "function") {
                displayBox.innerText = normalizeJSAddress(addrInput.value);
            } else {
                displayBox.innerText = addrInput.value;
            }
        };

        const observer = new MutationObserver(refreshDisplay);
        observer.observe(addrInput, { attributes: true, attributeFilter: ['value'] });
        
        refreshDisplay();
    }
});