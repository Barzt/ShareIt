# ShareIt 🍎

**ShareIt** is a modern, community-driven web application designed to reduce food waste and support local communities by allowing users to share surplus food.

Developed as a **Final Capstone Project** within the **Accelerator B Program** at **The Academic College of Tel Aviv-Yaffo**

The application is built on a custom **PHP** backend, a **MySQL** database, and interactive **Vanilla CSS & JavaScript** frontend, integrated with state-of-the-art services like **Google Gemini AI** and **Stripe Payment Gateway**.

---

## 🚀 Key Features

* **User Authentication & Registration:** Secure user sign-up and login, automatically registering new users as Stripe customers in the Stripe Sandbox environment.
* **Smart Upload & AI Verification:** Users can publish items with titles, descriptions, categories, kosher status, allergens, and expiration dates. Images are analyzed in real-time using **Google Gemini AI** to auto-generate tags and verify content safety.
* **OpenStreetMap Integration:** Precise location selection for item pickups with address auto-completion (via Nominatim) and coordinate tracking.
* **Stripe Checkout Integration:** Collection requests charge a small commitment/service fee (e.g., 5 ILS) processed securely via Stripe.
* **Private Chat Coordination:** Approved requests unlock a private messaging channel between the publisher and the collector to coordinate pickup details.
* **City-Based Search API:** A secure API endpoint (`api/city_data.php`) protected by a header API key (`X-API-KEY`) to query available food items filtered by city.

---

## 🛠️ Tech Stack

* **Frontend:** HTML5, CSS3, JavaScript
* **Backend:** PHP (Monolithic Architecture with AJAX & REST API)
* **Database:** MySQL
* **AI:** Gemini API
* **Location:** OpenStreetMap / Nominatim API
* **Payments:** Stripe API
* **Server:** cPanel

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
