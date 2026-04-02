# 🎓 Edu-Planning — UCA Academic Planner

> **Intelligent Study Planning powered by Google Gemini AI**
>
> Transform your study notes into personalized 7-day revision plans with AI analysis.
>
> *Academic Project — Université Cadi Ayyad (UCA), Marrakech*

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2-777BB4?style=flat-square&logo=php" />
  <img src="https://img.shields.io/badge/Node.js-16%2B-339933?style=flat-square&logo=nodedotjs" />
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql" />
  <img src="https://img.shields.io/badge/Gemini-AI-8E75B2?style=flat-square&logo=google" />
  <img src="https://img.shields.io/badge/Express-5.2-000000?style=flat-square&logo=express" />
  <img src="https://img.shields.io/badge/Status-Production%20Ready-brightgreen?style=flat-square" />
</p>

---

## 📌 What is Edu-Planning?

**Edu-Planning** is a smart academic management platform for UCA students. It allows students to organize their modules and notes, then uses **Google Gemini AI** to generate optimized 7-day revision schedules based on their actual study observations.

### 🎯 Student Journey

```
  1️⃣ Register / Login
         ↓
  2️⃣ Create Modules (Math, Physics, etc.)
         ↓
  3️⃣ Add Study Notes (difficulties, observations)
         ↓
  4️⃣ Generate AI Plan (via Recommendations page)
         ↓
  5️⃣ View 7-Day Schedule (Calendar + Sessions)
         ↓
  6️⃣ Track Progress (Dashboard + Logs)
```

---

## ✨ Features

| Feature | Description | Status |
|---------|-------------|--------|
| 🔐 Secure Authentication | Session-based login with CSRF & XSS protection | ✅ Live |
| 📚 Module Management | Create, edit & delete academic subjects | ✅ Live |
| 📝 Study Notes | Record difficulties & observations per module | ✅ Live |
| 🤖 Gemini AI Planning | Generate personalized 7-day revision plans | ✅ Live |
| 📊 Dashboard | Statistics: modules, notes, sessions, days left | ✅ Live |
| 📆 Calendar View | Visualize sessions by day with colors & priorities | ✅ Live |
| 📋 Recommendation History | Browse and delete past AI-generated plans | ✅ Live |
| 👤 User Profile | Edit profile & change password | ✅ Live |
| 🔍 Activity Logs | Full audit trail of user actions | ✅ Live |
| 🏥 Health Check | System diagnostics for all components | ✅ Live |
| 🐳 Docker Support | Run full stack with `docker-compose up` | ✅ Live |

---

## 🏗️ Technology Stack

### Frontend (Presentation Layer)
```
  PHP 8.2  ·  HTML5  ·  CSS3  ·  JavaScript ES6+
  Vanilla CSS (UCA Design System)  ·  FontAwesome 6
```
- **Server-Side Rendering** via PHP
- **UCA Brand Colors**: Brun Terracotta `#8B4513`, Brun Foncé `#5C2E0E`, Orange Chaud `#C0622A`
- **Smooth Animations** with custom CSS keyframes (`assets/animations.css`)
- **Session-Based Auth** with `PHPSESSID` cookie

### Backend (API Layer)
```
  Node.js 16+ LTS  ·  Express.js 5.2.1
  @google/generative-ai SDK 0.24.1
```
- **REST API** running on port `3001`
- **Gemini 2.5 Flash** model for AI planning
- **ESM Modules** (`"type": "module"` in package.json)

### Data Layer
```
  MySQL 8.0
  Port 3306 (XAMPP) / 3307 (Docker)
  6 relational tables
```

---

## 📁 Project Structure

```
Edu-Planning/
│
├── 📄 index.php              # Landing page (hero + features)
├── 📄 login.php              # Login form
├── 📄 register.php           # Registration form
├── 📄 logout.php             # Session destroy + redirect
├── 📄 dashboard.php          # Main dashboard (stats + quick links)
├── 📄 modules.php            # Module & notes management (CRUD)
├── 📄 planning.php           # Calendar view of generated plans
├── 📄 recommendations.php    # AI planning generator + history
├── 📄 profile.php            # User profile edit
├── 📄 logs.php               # Activity audit trail
├── 📄 health_check.php       # System diagnostics
│
├── 🔐 config.php             # DB connection (PDO) + env loading
├── 🔒 auth.php               # require_login() helper
├── 🔒 auth-helper.php        # Hash, session, access control
│
├── 🟢 server.js              # Node.js Express API (Gemini AI)
├── 📦 package.json           # Node.js dependencies
│
├── 🗄️ schema.sql             # MySQL database schema (6 tables)
├── 🐳 docker-compose.yml     # Docker: PHP + MySQL + Node
│
├── 🌐 assets/
│   ├── style.css             # Main stylesheet (UCA Design System)
│   ├── animations.css        # Keyframes & transitions
│   ├── app.js                # Client-side JS (AJAX, DOM, events)
│   └── images/               # Logos & icons
│
├── 🔑 .env                   # ⚠️ Git-ignored — secrets here
├── 📋 .env.example           # Template for environment setup
├── 🚫 .gitignore             # Excludes .env, node_modules, etc.
│
├── 📚 README.md              # This file
└── 🏗️ ARCHITECTURE.md        # Full architecture guide
```

---

## 🔒 Security

### Security Principles

1. **API Keys & Credentials** — Loaded from `.env` (git-ignored), never hardcoded
2. **SQL Injection** — PDO prepared statements everywhere
3. **Password Security** — `password_hash()` / `password_verify()` (bcrypt)
4. **XSS Protection** — `htmlspecialchars()` on all user-generated output
5. **CSRF Tokens** — Validated on all form submissions
6. **Session Security** — `HttpOnly`, `SameSite: Strict`, 1-hour timeout
7. **Audit Logs** — All user actions logged to `logs` table

### Environment Configuration

Copy `.env.example` to `.env` and fill in your values:

```env
# Database
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASS=your_password_here
DB_NAME=edu_planning

# Google Gemini API (from Google AI Studio)
GEMINI_API_KEY=YOUR_GEMINI_KEY_HERE

# Node.js API Server
API_SERVER=http://localhost:3001
API_TIMEOUT=30

# Session
SESSION_TIMEOUT=3600
SESSION_SECURE=false
```

> ⚠️ **Never commit `.env` to Git.** It is git-ignored by design.

---

## 🚀 Quick Start

### Prerequisites

| Tool | Version | Purpose |
|------|---------|---------|
| PHP | 8.2+ | Server-side rendering |
| Node.js | 16+ LTS | Gemini API gateway |
| MySQL | 8.0 | Database |
| XAMPP / Apache | Any | Local web server |

### Installation (Local — XAMPP)

```bash
# 1. Clone the repository
git clone https://github.com/Mohammed-ES/Edu-Planning.git
cd Edu-Planning

# 2. Install Node.js dependencies
npm install

# 3. Setup environment
cp .env.example .env
# Edit .env with your DB credentials and Gemini API key

# 4. Create database & run schema
mysql -u root -p < schema.sql

# 5. Start Node.js API server
npm start
# → http://localhost:3001

# 6. Start XAMPP (Apache + MySQL), then open:
# → http://localhost/Edu-Planning/
```

### Installation (Docker)

```bash
# Clone & configure
git clone https://github.com/Mohammed-ES/Edu-Planning.git
cd Edu-Planning
cp .env.example .env  # edit with your values

# Launch all services
docker-compose up -d

# Access
# Frontend: http://localhost:8080
# API:      http://localhost:3001
# DB:       localhost:3307
```

---

## 🔄 How It Works

### AI Planning Data Flow

```
  Browser (recommendations.php)
       │
       │  AJAX POST /api/generate-plan
       │  { modules: [{name, notes}], days: 7 }
       ↓
  Node.js Express (server.js :3001)
       │
       │  Builds structured prompt
       │  Sends to Gemini 2.5 Flash
       ↓
  Google Gemini API
       │
       │  Analyzes notes for difficulty
       │  Returns 7-day JSON schedule
       ↓
  Node.js — parses & validates JSON
       │
       │  Returns { success: true, data: {...} }
       ↓
  PHP (recommendations.php)
       │
       │  Saves to revision_plans (MySQL)
       │  Displays calendar view
       ↓
  Student reviews plan & studies 🎓
```

### Priority Mapping (AI)

| Note Content | Priority | Daily Duration |
|---|---|---|
| "difficult / struggle / weak" | 🔴 Haute | 150–180 min |
| "good / solid" | 🟡 Moyenne | 100–130 min |
| "excellent / strong" | 🟢 Basse | 60–90 min |

---

## 📊 Database Schema

```
users(id, username, email, password_hash, role, created_at, updated_at)
  │
  ├── modules(id, user_id, module_name, created_at, updated_at)
  │       │
  │       └── notes(id, module_id, note_value, description, created_at)
  │
  ├── revision_plans(id, user_id, plan_data[JSON], start_date, end_date,
  │                  used_note_ids[JSON], selected_modules[JSON], created_at)
  │
  ├── ai_recommendations(id, user_id, recommendation[JSON], created_at)
  │
  └── logs(id, user_id, action, created_at)
```

> Full SQL schema in [`schema.sql`](schema.sql)

---

## 🌐 API Endpoints (Node.js — Port 3001)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/` | API status & version |
| `POST` | `/api/generate-plan` | Generate 7-day AI schedule |
| `GET` | `/api/revision-plan/:module` | Schedule for a specific module |
| `GET` | `/health` | Health check (JSON) |

**Example request:**
```json
POST http://localhost:3001/api/generate-plan
{
  "modules": [
    { "name": "Mathematics", "notes": "Struggling with integration" },
    { "name": "Physics", "notes": "Good understanding of mechanics" }
  ]
}
```

---

## 🐛 Troubleshooting

| Error | Fix |
|-------|-----|
| `Cannot connect to API server` | Run `npm start`, check port 3001 is free |
| `Database connection failed` | Verify DB credentials in `.env`, MySQL running |
| `Invalid Gemini API key` | Check `GEMINI_API_KEY` in `.env`, validate in AI Studio |
| `Slow AI generation (>30s)` | Reduce notes/modules, check internet connection |
| `Session expired` | Re-login, timeout is 1hr by default |

Run **`health_check.php`** to automatically diagnose all components:
```
http://localhost/Edu-Planning/health_check.php
```

---

## 📚 Documentation

| File | Content |
|------|---------|
| `README.md` | Overview, quick start, API reference |
| `ARCHITECTURE.md` | Full system architecture, data flows, file locations |
| `schema.sql` | Database structure & relationships |
| `.env.example` | Environment variable template |
| `.gitignore` | Git exclusions (`.env`, `node_modules`, etc.) |

---

## 🤝 Contributing

This is an academic project. To contribute:
1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Test your changes thoroughly
4. Submit a pull request with documentation

---

## 📝 Project Info

- **Institution**: Université Cadi Ayyad — Marrakech, Morocco
- **Stack**: PHP + Node.js + MySQL + Google Gemini AI
- **License**: ISC
- **Security**: Follows OWASP best practices

---

**Last Updated**: April 2, 2026  
**Version**: 2.2  
**Status**: ✅ Production Ready

Built with ❤️ for smarter studying at UCA
