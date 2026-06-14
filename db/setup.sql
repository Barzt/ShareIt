CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    city VARCHAR(50),
    street VARCHAR(100),
    house_number VARCHAR(10),
    apartment VARCHAR(10),
    formatted_address VARCHAR(255),
    lat DECIMAL(10, 8),
    lng DECIMAL(11, 8),
    payment_customer_id VARCHAR(100), -- מזהה משתמש במערכת הסליקה
    is_blocked_debts BOOLEAN DEFAULT FALSE, -- חסימה במקרה של אי-תשלום
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 1. יצירת הטבלה
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. הכנסת הקטגוריות שבחרנו
INSERT INTO categories (category_name) VALUES 
('מזווה וחומרי גלם יבשים'),
('פירות וירקות טריים'),
('מוצרי מקרר וקירור'),
('אוכל מבושל / ארוחות מוכנות'),
('מאפים ולחמים'),
('אחר');


CREATE TABLE items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,           -- המזהה של המשתמש שפרסם
    category_id INT NOT NULL,       -- המזהה של הקטגוריה שנבחרה
    title VARCHAR(100) NOT NULL,    -- שם הפריט
    description TEXT,               -- תיאור חופשי
    
    -- מאפייני מזון
    kosher_status ENUM('בשרי', 'חלבי', 'פרווה', 'לא כשר/ללא תעודה') DEFAULT 'פרווה',
    allergens VARCHAR(255),         -- למשל: "גלוטן, בוטנים"
    
    -- תאריכים
    expiry_date DATE NULL,          -- תוקף (למצרכים)
    cooked_at DATETIME NULL,        -- מתי בושלה המנה (לאוכל מבושל)
    
    -- שדות AI (אימות ותוכן)
    image_url VARCHAR(255),         -- נתיב לתמונה בשרת
    ai_labels TEXT,                 -- תגיות שה-AI זיהה (למשל: "ירקות", "טרי")
    ai_is_safe BOOLEAN DEFAULT TRUE, -- האם ה-AI אישר את תקינות הפוסט
    
    -- שדות Google Maps (מיקום האיסוף הספציפי)
    item_lat DECIMAL(10, 8),
    item_lng DECIMAL(11, 8),
    item_address VARCHAR(255),      -- כתובת האיסוף לפוסט זה
    
    status ENUM('available', 'pending', 'taken') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- יצירת הקשרים (מפתחות זרים)
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 1. טבלת בקשות איסוף ותשלום (עם עמלה קבועה של 5 ש"ח)
CREATE TABLE requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,           -- איזה פריט מבקשים
    adopter_user_id INT NOT NULL,   -- מי המשתמש שרוצה לאסוף
    
    -- סטטוס הבקשה והתשלום
    request_status ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'paid', 'refunded') DEFAULT 'unpaid',
    
    payment_transaction_id VARCHAR(100), -- קוד אישור מהסליקה
    service_fee DECIMAL(5, 2) DEFAULT 5.00, -- עמלה קבועה של 5 ש"ח לכל מוצר
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- קישורים לטבלאות המקור
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    FOREIGN KEY (adopter_user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 2. טבלת הודעות לצ'אט הפרטי (לתיאום האיסוף ושמירה על פרטיות)
CREATE TABLE messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,        -- משוייך לבקשת איסוף ספציפית
    sender_id INT NOT NULL,         -- מי שלח את ההודעה
    message_text TEXT NOT NULL,     -- תוכן ההודעה
    is_read BOOLEAN DEFAULT FALSE,  -- האם הנמען קרא
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (request_id) REFERENCES requests(request_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


