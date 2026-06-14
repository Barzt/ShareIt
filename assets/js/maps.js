'use strict';

// משתנה גלובלי לשמירת המיקום המקורי מה-Session (למקרה של ביטול Checkbox)
let sessionBackup = {
    lat: null,
    lng: null,
    address: ""
};

/**
 * פונקציית עזר לניקוי והרכבת כתובת - מסננת מחוזות ונפות
 */
function formatAddress(addr, backupCity, backupStreet, backupNum) {
    // לוקחים רק את העיר/יישוב (מתעלמים מ-county/נפת)
    const cityName = addr.city || addr.town || addr.village || addr.city_district || backupCity || "";
    const streetName = addr.road || addr.pedestrian || backupStreet || "";
    const hNum = addr.house_number || backupNum || "";
    
    if (cityName && streetName) {
        return `${cityName}, ${streetName} ${hNum}`.trim();
    }
    
    // אם אין רחוב, לפחות נציג עיר
    if (cityName) return cityName;

    return "מיקום לא ידוע";
}

// 1. איתור כתובת לפי הקלדה (מעדכן את המפה כשהמשתמש מקליד)
async function fetchCoordinates() {
    const city = document.getElementById('city')?.value;
    const street = document.getElementById('street')?.value;
    const num = document.getElementById('h_num')?.value;

    if (city && street) {
        const query = `${street} ${num || ""}, ${city}, Israel`;
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&addressdetails=1&accept-language=he`);
            const data = await response.json();
            
            if (data && data.length > 0) {
                const result = data[0];
                const cleanAddress = formatAddress(result.address, city, street, num);

                // עדכון שדות הנסתרים
                updateHiddenFields(result.lat, result.lon, result.display_name);
                
                // עדכון המפה (Marker ו-View) - בהנחה שמשתנה המפה והמרקר גלובליים
                if (window.marker && window.map) {
                    const latlng = [result.lat, result.lon];
                    window.marker.setLatLng(latlng);
                    window.map.setView(latlng, 17);
                }

                updateDisplayBox(cleanAddress);
            }
        } catch (error) {
            console.error("שגיאה באיתור כתובת", error);
        }
    }
}

// 2. עדכון כתובת לפי גרירת המרקר במפה
async function updateAddressByCoords(lat, lng) {
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&addressdetails=1&accept-language=he`);
        const data = await response.json();
        
        if (data && data.address) {
            // ה"מסננת" - לוקחים רק את השדות הרלוונטיים
            const addr = data.address;
            const city = addr.city || addr.town || addr.village || addr.city_district || "";
            const street = addr.road || addr.pedestrian || "";
            const houseNum = addr.house_number || "";

            // בניית הכתובת הקצרה והנקייה
            let shortAddress = "";
            if (city) shortAddress += city;
            if (street) shortAddress += (shortAddress ? ", " : "") + street;
            if (houseNum) shortAddress += " " + houseNum;

            // עדכון שדות הטקסט (התיבות) כדי שהמשתמש יראה מה נבחר
            if(document.getElementById('city')) document.getElementById('city').value = city;
            if(document.getElementById('street')) document.getElementById('street').value = street;
            if(document.getElementById('h_num')) document.getElementById('h_num').value = houseNum;

            // עדכון ה-Hidden Input שנשלח ל-DB 
            const addrEl = document.getElementById('item_address');
            if(addrEl) {
                addrEl.value = shortAddress;
                // הפעלת אירוע כדי שהתצוגה למעלה (בירוק) תתעדכן
                addrEl.dispatchEvent(new Event('input'));
            }
            
            // עדכון התצוגה הירוקה למעלה
            const displayBox = document.getElementById('current-display-address');
            if (displayBox) {
                displayBox.innerHTML = `מיקום נוכחי: <strong>${shortAddress}</strong>`;
            }
        }
    } catch (error) {
        console.error("שגיאה בפענוח מיקום", error);
    }
}

// פונקציות עזר לעדכון ה-DOM
function updateHiddenFields(lat, lng, fullAddr) {
    const latEl = document.getElementById('item_lat');
    const lngEl = document.getElementById('item_lng');
    const addrEl = document.getElementById('item_address');

    if(latEl) latEl.value = lat;
    if(lngEl) lngEl.value = lng;
    if(addrEl) addrEl.value = fullAddr;
}

function updateDisplayBox(text) {
    const displayBox = document.getElementById('current-display-address');
    if (displayBox) {
        displayBox.innerHTML = `📍 מיקום נבחר: <strong>${text}</strong>`;
    }
}

/**
 * מנגנון החזרת מיקום מקורי (לשימוש ב-upload.js)
 */
function resetToOriginalLocation() {
    updateHiddenFields(sessionBackup.lat, sessionBackup.lng, sessionBackup.address);
    updateDisplayBox("מיקום נוכחי (מהפרופיל)");
    
    if (window.map && window.marker) {
        const coords = [sessionBackup.lat, sessionBackup.lng];
        window.marker.setLatLng(coords);
        window.map.setView(coords, 16);
    }
}

// אתחול המשתנים המקוריים מה-Hidden Fields בטעינה
window.addEventListener('DOMContentLoaded', () => {
    sessionBackup.lat = document.getElementById('item_lat')?.value;
    sessionBackup.lng = document.getElementById('item_lng')?.value;
    sessionBackup.address = document.getElementById('item_address')?.value;

    // הוספת מאזינים לתיבות הטקסט (עדכון המפה כשעוזבים את השדה)
    const inputs = ['city', 'street', 'h_num'];
    inputs.forEach(id => {
        document.getElementById(id)?.addEventListener('blur', fetchCoordinates);
    });
});