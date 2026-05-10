# Edu-Planning — AI-Powered Academic Platform

> Intelligent study planning for students of Université Cadi Ayyad, Marrakech.

Edu-Planning is a full-stack SaaS web application that combines a PHP/MySQL backend with a Node.js Gemini AI microservice to generate personalized, 7-day revision schedules based on a student's module data, exam dates, difficulty, and current progress.

---

## Features

- 🎓 **Module Tracking** — Add, edit, and monitor all your academic modules
- 🤖 **AI Study Plans** — Generate personalized 7-day schedules powered by Google Gemini
- 📅 **Revision Calendar** — Visualize your study schedule by month
- 🔐 **Secure Auth** — CSRF protection, session fixation prevention, brute-force rate limiting
- 📊 **Dashboard Analytics** — Real-time progress charts and module mastery overview
- 👤 **User Profiles** — Update name, email, and password

---

## Tech Stack

| Layer      | Technology                         |
|------------|------------------------------------|
| Frontend   | HTML, CSS (Vanilla), Bootstrap 5   |
| Backend    | PHP 8.1+, PDO                      |
| Database   | MySQL 8+ (MariaDB compatible)      |
| AI Service | Node.js 20+, Express 5, Google Gemini API |
| Auth       | PHP Sessions, CSRF tokens          |

---

## Prerequisites

- **PHP** 8.1 or higher (with `pdo_mysql`, `curl` extensions)
- **MySQL** 8.0+ or **MariaDB** 10.6+
- **Node.js** 20+ and npm
- **XAMPP** (or Apache + MySQL for local dev)
- A **Google Gemini API Key** — [Get one here](https://aistudio.google.com/)

---

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/your-username/Edu-Planning-v1.git
cd Edu-Planning-v1
```

### 2. Configure environment variables

```bash
cp .env.example .env
```

Open `.env` and fill in your values:

```env
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASS=your_db_password
DB_NAME=edu_planning

GEMINI_API_KEY=your_gemini_api_key_here
INTERNAL_API_SECRET=your_strong_random_secret_here

PORT=3001
```

> ⚠️ **Never commit `.env` to version control.** It is already in `.gitignore`.

### 3. Set up the database

Import the schema using XAMPP phpMyAdmin or the command line:

```bash
mysql -u root -p < db/schema.sql
```

> **Note:** The schema includes demo seed data for development. Remove the `INSERT INTO` statements at the bottom of `schema.sql` before deploying to production.

### 4. Install Node.js dependencies

```bash
npm install
```

### 5. Start the AI backend server

```bash
npm start
# or for development with auto-reload:
npm run dev
```

The Node.js server will run at `http://127.0.0.1:3001` (internal only).

### 6. Configure PHP web server

Place the project folder inside your XAMPP `htdocs` directory (or your Apache web root).

Access the app at: `http://localhost/Edu-Planning-v1/`

---

## Project Structure

```
Edu-Planning-v1/
├── assets/
│   └── images/           # Static images & 3D assets
├── css/                  # Per-page stylesheets
├── db/
│   └── schema.sql        # Database schema with indexes
├── include/
│   ├── auth.php          # Session, CSRF, brute-force protection
│   ├── ai_api.php        # Gemini API client (PHP → Gemini REST)
│   ├── config.php        # DB connection, env loading, security headers
│   └── connectiondb.php  # Thin wrapper (delegates to config.php)
├── js/                   # Per-page JavaScript modules
├── modules/              # Module CRUD sub-pages
│   ├── _bootstrap.php    # Shared auth + helpers for module pages
│   ├── add.php
│   ├── edit.php
│   ├── view.php
│   └── delete.php
├── dashboard.php
├── generate_plan.php
├── index.php             # Public landing page
├── login.php
├── logout.php
├── module.php
├── planning.php
├── profile.php
├── register.php
├── server.js             # Node.js Express + Gemini AI microservice
├── Welcome.php           # Animated entry screen
├── package.json
├── .env.example          # Environment variable template
└── .gitignore
```

---

## Security

- All POST forms are protected by CSRF tokens
- Login is rate-limited (5 attempts per IP per 15 minutes)
- Sessions are regenerated on login (prevents session fixation)
- The Node.js AI API is only accessible via `127.0.0.1` with an internal Bearer token
- Security headers are sent on every PHP response (X-Frame-Options, X-Content-Type-Options, etc.)
- Passwords are hashed with bcrypt
- All user input is sanitized with `htmlspecialchars()` before rendering

---

## Development Notes

- The **PHP backend** calls Gemini directly via the REST API (no Node.js required for basic functionality).
- The **Node.js server** (`server.js`) is an optional microservice for future use cases.
- `css/style.css` is a legacy file — verify before removing.
- `css/animations.css` is referenced in comments but not linked in any page.

---

## License

MIT © 2026 Université Cadi Ayyad — Edu-Planning Project
