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
                    console.log("AI Response Data:", data);
                    
                    // הזרקת הנתונים שה-AI חילץ ישירות לטופס
                    if (data && data.is_food) {
                        if (data.is_safe === false) {
                            if (labelSpan) labelSpan.innerText = "❌ שגיאה: המזון פג תוקף או לא בטוח!";
                            alert("שומר הסף של ShareIt זיהה שהמזון אינו בטוח לפרסום:\n\n" + (data.safety_warning || "המוצר פג תוקף או אינו ראוי למאכל."));
                            evt.target.value = ""; // מאפס את בחירת הקובץ
                            preview.style.display = 'none';
                            return;
                        }

                        if (document.getElementById('title')) {
                            document.getElementById('title').value = data.label || "";
                        }
                        if (document.getElementById('description')) {
                            document.getElementById('description').value = data.description || "";
                        }
                        
                        // שמירת תוצאות ה-AI בשדות מוסתרים כדי למנוע קריאה כפולה ומכפילה לשרת
                        if (document.getElementById('ai_labels')) document.getElementById('ai_labels').value = data.label || "Food";
                        if (document.getElementById('ai_is_safe')) document.getElementById('ai_is_safe').value = data.is_safe ? "1" : "0";
                        if (document.getElementById('ai_feedback')) document.getElementById('ai_feedback').value = data.ai_feedback || "";
                        if (document.getElementById('ai_description')) document.getElementById('ai_description').value = data.description || "";
                        
                        // מילוי אוטומטי של שדה התאריך בטופס אם חולץ בהצלחה
                        if (data.expiry_date && document.getElementById('expiry_date')) {
                            let formattedDate = "";
                            if (data.expiry_date.includes('/')) {
                                let parts = data.expiry_date.split('/');
                                if (parts.length === 3) {
                                    let day = parts[0].trim().padStart(2, '0');
                                    let month = parts[1].trim().padStart(2, '0');
                                    let year = parts[2].trim();
                                    if (year.length === 2) {
                                        year = "20" + year;
                                    }
                                    formattedDate = `${year}-${month}-${day}`;
                                }
                            } else if (data.expiry_date.match(/^\d{4}-\d{2}-\d{2}$/)) {
                                formattedDate = data.expiry_date;
                            }
                            if (formattedDate) {
                                document.getElementById('expiry_date').value = formattedDate;
                            }
                        }

                        if (labelSpan) labelSpan.innerText = "✨ ה-AI זיהה את המאכל בהצלחה!";
                    } else if (data && data.is_food === false) {
                        if (labelSpan) labelSpan.innerText = "❌ שגיאה: התמונה אינה מכילה מאכל תקין!";
                        alert("שומר הסף של ShareIt זיהה שהתמונה שהעלית אינה אוכל. אנא העלה תמונת מאכל תקינה.");
                        evt.target.value = ""; // מאפס את בחירת הקובץ
                        preview.style.display = 'none';
                    } else if (data && (data.status === 'error' || data.message === 'ai_failed' || !data.hasOwnProperty('is_food'))) {
                        if (labelSpan) labelSpan.innerText = "⚠️ שגיאת תקשורת עם ה-AI. ניתן להמשיך להעלות ידנית.";
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