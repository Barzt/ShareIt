'use strict';

let profile = {};
let pendingPhoto = null;
let mySharedItems = [];

/* ===== TOAST ===== */
function showToast(msg, isErr) {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.className = 'toast show' + (isErr ? ' error' : '');
    clearTimeout(el._t);
    el._t = setTimeout(() => el.className = 'toast', 3000);
}

/* ===== AVATAR ===== */
function setAvatar(src, firstName) {
    const img = document.getElementById('avatar-img');
    const ltr = document.getElementById('avatar-letter');
    if (src) {
        img.src = '../' + src;
        img.style.display = 'block';
        ltr.style.display = 'none';
    } else {
        img.style.display = 'none';
        ltr.style.display = '';
        if (firstName) ltr.textContent = firstName.charAt(0).toUpperCase();
    }
}

/* ===== LOAD PROFILE (לצורך שדות העריכה ונתונים נוספים) ===== */
async function loadProfile() {
    try {
        const data = await fetch('../api/get_profile.php').then(r => r.json());
        if (!data || data.error) return;
        profile = data;

        /* עדכון view עם נתונים מה-DB */
        if (data.last_name) {
            document.getElementById('v-lastname').textContent = data.last_name;
        }
        if (data.email) {
            document.getElementById('v-email').textContent = data.email;
        }
        if (data.formatted_address) {
            document.getElementById('v-address').textContent = data.formatted_address;
        }
        if (data.phone) {
            document.getElementById('v-phone').textContent = data.phone;
            document.getElementById('v-phone-row').style.display = 'flex';
        }
        if (data.profile_picture) setAvatar(data.profile_picture, data.first_name);

    } catch (e) { /* נתוני ה-session כבר מוצגים */ }
}

/* ===== TOGGLE ITEMS SECTIONS ===== */
function showItemsSection(show) {
    ['items-section', 'consumed-section'].forEach(id => {
        const sec = document.getElementById(id);
        if (sec) sec.style.display = show ? 'block' : 'none';
    });
}

/* ===== OPEN EDIT ===== */
function openEdit() {
    const p = profile;
    document.getElementById('e-fn').value   = p.first_name   || document.getElementById('v-firstname').textContent.trim();
    document.getElementById('e-ln').value   = p.last_name    || '';
    document.getElementById('e-em').value   = p.email        || document.getElementById('v-email').textContent.trim();
    document.getElementById('e-ph').value   = p.phone        || '';
    document.getElementById('e-city').value = p.city         || '';
    document.getElementById('e-str').value  = p.street       || '';
    document.getElementById('e-hn').value   = p.house_number || '';
    document.getElementById('e-apt').value  = p.apartment    || '';

    pendingPhoto = null;
    clearErrors();
    document.getElementById('view-mode').style.display = 'none';
    document.getElementById('edit-mode').style.display = 'block';
    document.getElementById('profile-card').classList.add('editing');
    showItemsSection(false);
}

/* ===== CLOSE EDIT ===== */
function closeEdit() {
    document.getElementById('edit-mode').style.display = 'none';
    document.getElementById('view-mode').style.display = 'block';
    document.getElementById('profile-card').classList.remove('editing');
    showItemsSection(true);
    clearErrors();
    setAvatar(profile.profile_picture || '', profile.first_name || document.getElementById('v-firstname').textContent);
    pendingPhoto = null;
}

/* ===== VALIDATION ===== */
function clearErrors() {
    ['err-fn','err-ln','err-em','err-ph'].forEach(id => {
        document.getElementById(id).textContent = '';
    });
    document.querySelectorAll('#edit-mode input').forEach(el => el.classList.remove('has-error'));
}

function fieldErr(inputId, errId, msg) {
    document.getElementById(inputId).classList.add('has-error');
    document.getElementById(errId).textContent = msg;
}

function validate() {
    clearErrors();
    let ok = true;
    const fn    = document.getElementById('e-fn').value.trim();
    const ln    = document.getElementById('e-ln').value.trim();
    const email = document.getElementById('e-em').value.trim();
    const phone = document.getElementById('e-ph').value.trim();

    if (!fn)   { fieldErr('e-fn','err-fn','שדה חובה'); ok = false; }
    if (!ln)   { fieldErr('e-ln','err-ln','שדה חובה'); ok = false; }
    if (!email){ fieldErr('e-em','err-em','שדה חובה'); ok = false; }
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){ fieldErr('e-em','err-em','אימייל לא תקין'); ok = false; }
    if (phone && !/^[\d\-+\s()]{7,15}$/.test(phone)){ fieldErr('e-ph','err-ph','טלפון לא תקין'); ok = false; }
    return ok;
}

/* ===== SAVE ===== */
async function saveProfile(e) {
    e.preventDefault();
    if (!validate()) return;

    const btn = document.getElementById('btn-save');
    btn.disabled = true;
    btn.textContent = 'שומר...';

    const fd = new FormData();
    fd.append('first_name',   document.getElementById('e-fn').value.trim());
    fd.append('last_name',    document.getElementById('e-ln').value.trim());
    fd.append('email',        document.getElementById('e-em').value.trim());
    fd.append('phone',        document.getElementById('e-ph').value.trim());
    fd.append('city',         document.getElementById('e-city').value.trim());
    fd.append('street',       document.getElementById('e-str').value.trim());
    fd.append('house_number', document.getElementById('e-hn').value.trim());
    fd.append('apartment',    document.getElementById('e-apt').value.trim());
    if (pendingPhoto) fd.append('profile_picture', pendingPhoto);

    try {
        const data = await fetch('../api/update_profile.php', { method:'POST', body:fd }).then(r => r.json());

        if (data.success) {
            profile.first_name   = fd.get('first_name');
            profile.last_name    = fd.get('last_name');
            profile.email        = fd.get('email');
            profile.phone        = fd.get('phone');
            profile.city         = fd.get('city');
            profile.street       = fd.get('street');
            profile.house_number = fd.get('house_number');
            profile.apartment    = fd.get('apartment');
            profile.formatted_address = [fd.get('street'), fd.get('house_number'), fd.get('city')].filter(Boolean).join(' ');
            if (data.profile_picture) profile.profile_picture = data.profile_picture;

            document.getElementById('v-firstname').textContent = profile.first_name;
            document.getElementById('v-lastname').textContent  = profile.last_name;
            document.getElementById('v-email').textContent     = profile.email;
            document.getElementById('v-address').textContent   = profile.formatted_address;
            if (profile.phone) {
                document.getElementById('v-phone').textContent    = profile.phone;
                document.getElementById('v-phone-row').style.display = 'flex';
            }
            closeEdit();
            showToast('הפרופיל עודכן בהצלחה ✓');
        } else {
            showToast(data.message || 'שגיאה בשמירה', true);
        }
    } catch { showToast('שגיאת תקשורת', true); }
    finally  { btn.disabled = false; btn.textContent = 'שמור שינויים'; }
}

/* ===== PHOTO PREVIEW ===== */
document.getElementById('pic-input').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) { showToast('יש לבחור קובץ תמונה', true); return; }
    if (file.size > 3 * 1024 * 1024)    { showToast('קובץ גדול מדי (מקס 3MB)', true); return; }
    pendingPhoto = file;
    const reader = new FileReader();
    reader.onload = ev => {
        const img = document.getElementById('avatar-img');
        img.src = ev.target.result;
        img.style.display = 'block';
        document.getElementById('avatar-letter').style.display = 'none';
    };
    reader.readAsDataURL(file);
});

/* ===== ITEMS ===== */
async function fetchItems() {
    const grid = document.getElementById('items-grid');
    try {
        const items = await fetch('../api/get_user_shared_items.php').then(r => r.json());
        if (!Array.isArray(items) || !items.length) {
            mySharedItems = [];
            grid.innerHTML = `<div class="empty-state"><div class="empty-icon">🍱</div><p>עדיין לא שיתפת אוכל</p></div>`;
            return;
        }
        mySharedItems = items;
        grid.innerHTML = '';
        items.forEach(item => {
            const avail = item.status === 'available';
            const c = document.createElement('div');
            c.className = 'item-card';
            c.id = 'item-' + item.item_id;
            c.innerHTML = `
                <img src="../${item.image_url}" alt="${item.title}">
                <div class="item-card-body">
                    <h3>${item.title}</h3>
                    <span class="status-badge ${avail ? 'status-available':'status-collected'}">${avail?'זמין':'נמסר'}</span>
                    <div class="item-actions">
                        <button class="btn-collect" onclick="markCollected(${item.item_id},this)" ${!avail?'disabled':''}>סמן כנמסר</button>
                        <button class="btn-edit-post" onclick="openEditItem(${item.item_id})">עריכה</button>
                        <button class="btn-danger"  onclick="removeItem(${item.item_id})">מחיקה</button>
                    </div>
                </div>`;
            grid.appendChild(c);
        });
    } catch {
        grid.innerHTML = `<div class="empty-state"><div class="empty-icon">⚠️</div><p>שגיאה בטעינת הפריטים</p></div>`;
    }
}

async function markCollected(id, btn) {
    showCustomConfirm('לסמן את הפריט כנמסר?', async () => {
        btn.disabled = true; btn.textContent = '...';
        try {
            const fd = new FormData(); fd.append('item_id', id); fd.append('status', 'collected');
            const data = await fetch('../api/update_item_status.php', {method:'POST',body:fd}).then(r=>r.json());
            if (data.success) {
                const badge = document.getElementById('item-'+id).querySelector('.status-badge');
                badge.className = 'status-badge status-collected';
                badge.textContent = 'נמסר';
                btn.textContent = 'סמן כנמסר';
                showToast('סומן כנמסר');
            } else { btn.disabled=false; btn.textContent='סמן כנמסר'; showToast(data.message||'שגיאה',true); }
        } catch (err) {
            console.error("Mark collected error:", err);
            btn.disabled=false; btn.textContent='סמן כנמסר';
            showToast('שגיאת תקשורת: ' + err.message,true);
        }
    });
}

async function removeItem(id) {
    showCustomConfirm('למחוק את הפריט?', async () => {
        try {
            const fd = new FormData(); fd.append('item_id', id);
            const data = await fetch('../api/delete_item.php', {method:'POST',body:fd}).then(r=>r.json());
            if (data.success) {
                const card = document.getElementById('item-'+id);
                card.style.cssText = 'opacity:0;transform:scale(0.9);transition:0.3s';
                setTimeout(() => {
                    card.remove();
                    if (!document.querySelector('.item-card'))
                        document.getElementById('items-grid').innerHTML = `<div class="empty-state"><div class="empty-icon">🍱</div><p>עדיין לא שיתפת אוכל</p></div>`;
                }, 300);
                showToast('הפריט נמחק');
            } else { showToast(data.message||'שגיאה',true); }
        } catch (err) {
            console.error("Remove item error:", err);
            showToast('שגיאת תקשורת: ' + err.message,true);
        }
    });
}

/* ===== CONSUMED ITEMS ===== */
async function fetchConsumedItems() {
    const grid = document.getElementById('consumed-grid');
    try {
        const items = await fetch('../api/get_consumed_items.php').then(r => r.json());
        if (!Array.isArray(items) || !items.length) {
            grid.innerHTML = `<div class="empty-state"><div class="empty-icon">🛒</div><p>עדיין לא קיבלת אוכל מהקהילה</p></div>`;
            return;
        }
        grid.innerHTML = '';
        items.forEach(item => {
            const done = item.request_status === 'completed';
            const c = document.createElement('div');
            c.className = 'item-card';
            c.innerHTML = `
                <img src="../${item.image_url}" alt="${item.title}">
                <div class="item-card-body">
                    <h3>${item.title}</h3>
                    <span class="status-badge ${done ? 'status-collected' : 'status-available'}">
                        ${done ? 'הושלם' : 'אושר'}
                    </span>
                </div>`;
            grid.appendChild(c);
        });
    } catch {
        grid.innerHTML = `<div class="empty-state"><div class="empty-icon">⚠️</div><p>שגיאה בטעינת הפריטים</p></div>`;
    }
}

/* ===== EDIT ITEM MODAL LOGIC ===== */
function openEditItem(itemId) {
    const item = mySharedItems.find(x => x.item_id === itemId);
    if (!item) return;

    document.getElementById('edit-item-id').value = item.item_id;
    document.getElementById('edit-title').value = item.title;
    document.getElementById('edit-category').value = item.category_id;
    document.getElementById('edit-kosher').value = item.kosher_status || 'פרווה';
    document.getElementById('edit-allergens').value = item.allergens || '';
    
    // Extract user description (without AI commentary)
    let rawDesc = item.description || '';
    let userDescription = rawDesc;
    if (rawDesc.includes("ניתוח AI") || rawDesc.includes("פידבק מהמערכת") || rawDesc.includes("פידבק המערכת")) {
        const splitParts = rawDesc.split(/ניתוח AI:?|פידבק מהמערכת:?|פידבק המערכת:?/i);
        userDescription = splitParts[0].trim();
    }
    userDescription = userDescription.replace(/תיאור המשתמש:?/gi, '')
                                     .replace(/תיאור המפרסם:?/gi, '')
                                     .replace(/ניתוח AI:?/gi, '')
                                     .trim();
    document.getElementById('edit-description').value = userDescription;

    if (item.expiry_date) {
        document.getElementById('edit-expiry').value = item.expiry_date;
    } else {
        document.getElementById('edit-expiry').value = '';
    }
    if (item.cooked_at) {
        document.getElementById('edit-cooked').value = item.cooked_at.replace(' ', 'T');
    } else {
        document.getElementById('edit-cooked').value = '';
    }

    toggleEditFields();
    document.getElementById('edit-item-modal').style.display = 'flex';
}

function closeEditItemModal() {
    document.getElementById('edit-item-modal').style.display = 'none';
    document.getElementById('err-edit-title').textContent = '';
    document.getElementById('err-edit-description').textContent = '';
    document.getElementById('edit-title').classList.remove('has-error');
    document.getElementById('edit-description').classList.remove('has-error');
}

function toggleEditFields() {
    const categorySelect = document.getElementById('edit-category');
    const expiryGroup = document.getElementById('edit-expiry-group');
    const cookedGroup = document.getElementById('edit-cooked-group');

    if (!categorySelect || !expiryGroup || !cookedGroup) return;

    const categoryId = categorySelect.value;
    expiryGroup.style.display = 'none';
    cookedGroup.style.display = 'none';

    if (categoryId === "4") {
        cookedGroup.style.display = 'block';
    } else if (["1", "3", "5"].includes(categoryId)) {
        expiryGroup.style.display = 'block';
    }
}

async function saveItem(e) {
    e.preventDefault();
    
    const titleInput = document.getElementById('edit-title');
    const descInput = document.getElementById('edit-description');
    const titleErr = document.getElementById('err-edit-title');
    const descErr = document.getElementById('err-edit-description');

    titleErr.textContent = '';
    descErr.textContent = '';
    titleInput.classList.remove('has-error');
    descInput.classList.remove('has-error');

    let ok = true;
    if (!titleInput.value.trim()) {
        titleInput.classList.add('has-error');
        titleErr.textContent = 'שדה חובה';
        ok = false;
    }
    if (!descInput.value.trim()) {
        descInput.classList.add('has-error');
        descErr.textContent = 'שדה חובה';
        ok = false;
    }
    if (!ok) return;

    const btn = document.getElementById('btn-save-item');
    btn.disabled = true;
    btn.textContent = 'שומר...';

    const fd = new FormData();
    fd.append('item_id', document.getElementById('edit-item-id').value);
    fd.append('title', titleInput.value.trim());
    fd.append('category_id', document.getElementById('edit-category').value);
    fd.append('kosher_status', document.getElementById('edit-kosher').value);
    fd.append('expiry_date', document.getElementById('edit-expiry').value);
    fd.append('cooked_at', document.getElementById('edit-cooked').value);
    fd.append('allergens', document.getElementById('edit-allergens').value);
    fd.append('description', descInput.value.trim());

    try {
        const data = await fetch('../api/update_item.php', { method: 'POST', body: fd }).then(r => r.json());
        if (data.success) {
            closeEditItemModal();
            showToast('הפריט עודכן בהצלחה ✓');
            fetchItems();
        } else {
            showToast(data.message || 'שגיאה בשמירה', true);
        }
    } catch (err) {
        console.error("Save item error:", err);
        showToast('שגיאת תקשורת: ' + err.message, true);
    } finally {
        btn.disabled = false;
        btn.textContent = 'שמור שינויים';
    }
}

/* ===== CUSTOM CONFIRMATION DIALOG ===== */
function showCustomConfirm(message, onConfirm) {
    const modal = document.getElementById('confirm-modal');
    const msgEl = document.getElementById('confirm-message');
    const yesBtn = document.getElementById('confirm-yes-btn');
    const noBtn = document.getElementById('confirm-no-btn');

    msgEl.textContent = message;
    modal.style.display = 'flex';

    const handleYes = () => {
        modal.style.display = 'none';
        cleanup();
        onConfirm();
    };

    const handleNo = () => {
        modal.style.display = 'none';
        cleanup();
    };

    const cleanup = () => {
        yesBtn.removeEventListener('click', handleYes);
        noBtn.removeEventListener('click', handleNo);
    };

    yesBtn.addEventListener('click', handleYes);
    noBtn.addEventListener('click', handleNo);
}

/* ===== INIT ===== */
document.getElementById('btn-edit').addEventListener('click', openEdit);
document.getElementById('btn-cancel').addEventListener('click', closeEdit);
document.getElementById('edit-mode').addEventListener('submit', saveProfile);
document.getElementById('edit-category').addEventListener('change', toggleEditFields);
document.getElementById('edit-item-form').addEventListener('submit', saveItem);

window.openEditItem = openEditItem;
window.closeEditItemModal = closeEditItemModal;

loadProfile();
fetchItems();
fetchConsumedItems();


