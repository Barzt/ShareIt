# ShareIt 🌍

**ShareIt** is a modern, community-driven web application designed to reduce food waste and support local communities by allowing users to share surplus food.

Developed as a **Final Capstone Project** within the **Accelerator B Program** at **The Academic College of Tel Aviv-Yaffo** (המכללה האקדמית תל אביב-יפו).

The application is built on a custom **PHP** backend, a **MySQL** database, and interactive **Vanilla CSS & JavaScript** frontend, integrated with state-of-the-art services like **Google Gemini AI** and **Stripe Payment Gateway**.

---

## 🚀 Key Features

* **User Authentication & Registration:** Secure user sign-up and login, automatically registering new users as Stripe customers in the Stripe Sandbox environment.
* **Smart Upload & AI Verification:** Users can publish items with titles, descriptions, categories, kosher status, allergens, and expiration dates. Images are analyzed in real-time using **Google Gemini AI** to auto-generate tags and verify content safety.
* **Google Maps Integration:** Precise location selection for item pickups with address auto-completion and coordinate tracking.
* **Stripe Checkout Integration:** Collection requests charge a small commitment/service fee (e.g., 5 ILS) processed securely via Stripe.
* **Private Chat Coordination:** Approved requests unlock a private messaging channel between the publisher and the collector to coordinate pickup details.
* **City-Based Search API:** A secure API endpoint (`api/city_data.php`) protected by a header API key (`X-API-KEY`) to query available food items filtered by city.

---

## 🛠️ Tech Stack

* **Backend:** PHP (OOP & Procedural integration)
* **Database:** MySQL
* **Frontend:** HTML5, CSS3, Vanilla JS
* **Third-Party Integrations:**
  * **Google Gemini API** (Content moderation & image labeling)
  * **Stripe API** (Payment processing & customer management)
  * **Google Maps API** (Geocoding & interactive maps)

---

## ⚙️ Project Structure

```text
shareit_project/
│
├── api/                  # Backend API endpoints (Stripe checkout, registration, city search)
├── assets/               # CSS, JS, Images, and custom assets
├── db/                   # Database schemas and scripts (setup.sql)
├── logic/                # Core PHP business logic, AI processor, DB and general configs
├── uploads/              # Directory for user uploaded images (ignored in Git)
└── views/                # Frontend pages (Home, Chat, Profile, Posts)
```

---

## 💻 Setup & Installation

Follow these steps to run the project locally:

### 1. Clone the Repository
```bash
git clone https://github.com/your-username/shareit.git
cd shareit
```

### 2. Database Configuration
1. Create a new MySQL database named `shareit_db` (or choice of name).
2. Import the schema from [db/setup.sql](file:///c:/Users/bar45/Desktop/College/shareit_project/db/setup.sql) into your database.

### 3. Local Configurations
Before running the application, update the configuration files with your environment secrets:

* Update [logic/db_config.php](file:///c:/Users/bar45/Desktop/College/shareit_project/logic/db_config.php):
  ```php
  $username = "YOUR_DB_USER_HERE";
  $password = "YOUR_DB_PASS_HERE";
  $dbname = "YOUR_DB_NAME_HERE";
  ```
* Update [logic/config.php](file:///c:/Users/bar45/Desktop/College/shareit_project/logic/config.php):
  ```php
  define('DB_USER', 'YOUR_DB_USER_HERE'); 
  define('DB_PASS', 'YOUR_DB_PASS_HERE');    
  define('DB_NAME', 'YOUR_DB_NAME_HERE'); 
  define('GEMINI_API_KEY', 'YOUR_GEMINI_KEY_HERE');
  define('CITY_DATA_API_KEY', 'YOUR_CITY_DATA_API_KEY_HERE');
  ```
* Update Stripe Secret Key in:
  * [api/auth_register.php](file:///c:/Users/bar45/Desktop/College/shareit_project/api/auth_register.php)
  * [api/start_stripe_payment.php](file:///c:/Users/bar45/Desktop/College/shareit_project/api/start_stripe_payment.php)

### 4. Install Dependencies
Run Composer in the root folder to download the required PHP packages (including Stripe PHP SDK):
```bash
composer install
```

### 5. Running the Application
Serve the files using a local PHP web server (such as Apache via XAMPP/WampServer, or the built-in PHP development server):
```bash
php -S localhost:8000
```
Then navigate to `http://localhost:8000/views/index.php` in your web browser.
