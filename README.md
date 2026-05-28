# Vybe – Premium Real-Time WhatsApp-Style Messenger ⚡

Vybe is a modern, premium, dark-mode real-time chat application inspired by the clean glassmorphic aesthetics of modern web design. Built using a robust PHP backend, a MySQL database, and smooth AJAX-driven frontend interactions, Vybe supports direct 1-on-1 private conversations and interactive group rooms with real-time polling updates.

---

## ✨ Features

- **Direct Chats**: Engage in secure, private 1-on-1 conversations with registered contacts.
- **Group Rooms**: Participate in shared group rooms (e.g. Study Lounge, Gaming Hub) with distinct, color-coded sender identities.
- **Real-Time Polling**: Messages update seamlessly every 3 seconds without needing to reload the page (via jQuery AJAX).
- **Soft Deletion**: WhatsApp-style message deletion. Deleted messages are securely masked in the database and marked as `This message was deleted` inside chat bubbles.
- **Pulsing Unread Badges**: Real-time notifications that pulse and glow in the sidebar when new messages arrive in other conversations.
- **High-End Glassmorphism UI**: Beautifully styled using deep slate palettes, vibrant HSL gradients, frosted glass borders, and smooth entrance micro-animations.
- **Safe & Modular Design**: Implements centralized PDO database configuration and full input sanitization against XSS and SQL injection.

---

## 🛠️ Tech Stack

- **Frontend**: HTML5, Vanilla CSS3 (Glassmorphism), JavaScript (ES6+), jQuery, Boxicons
- **Backend**: PHP (Modular Object/PDO Architecture)
- **Database**: MySQL (relational InnoDB schema)

---

## 🚀 Local Installation (XAMPP)

Follow these simple steps to run Vybe on your local machine:

### 1. Project Placement
Clone or extract this repository into your local XAMPP web root folder:
`C:\xampp\htdocs\chatapp\`

### 2. Start Services
Open your **XAMPP Control Panel** and click **Start** next to **Apache** and **MySQL**.
*(Note: If port 3306 is already in use by a global MySQL server, make sure to stop that service so XAMPP MySQL can start on port 3306).*

### 3. Import Database
1. Open your browser and go to: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
2. Click on the **Databases** tab, type **`chat_app`** in the "Create database" field, and click **Create**.
3. Select **`chat_app`** in the left sidebar, click the **Import** tab at the top, select the **`database.sql`** file from your project directory, and click **Import** (or **Go**).
4. All tables and pre-loaded conversations will be initialized automatically!

### 4. Open the App
Go to your browser and open:
👉 **[http://localhost/chatapp/index.php](http://localhost/chatapp/index.php)**

---

## 🔑 Demo Credentials

Test accounts are preloaded with the password **`password123`**:

- **alex**
- **emma**
- **liam**
- **sophia**

---

## 📂 Repository Structure

- `db.php` - Centralized, secure PDO database connection manager.
- `index.php` - Main chat application workspace interface.
- `login.php` & `register.php` - Secure user login and registration with password hashing.
- `logout.php` - Terminate session and clear cookies.
- `api.php` - Room-aware JSON API handling endpoints for message sending, fetching, and deletion.
- `chat.js` - Client-side engine managing polling, tabs, modals, and badges.
- `style.css` - Custom styling theme containing core design tokens.
- `database.sql` - Complete database schema dump and seeder records.
- `.gitignore` - Blocks temporary server files and logs from being pushed.
