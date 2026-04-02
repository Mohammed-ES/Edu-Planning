# 🎓 Edu-Planning 

> **Intelligent Study Planning with AI**
>
> Transform your study notes into personalized 7-day revision plans powered by Google Gemini AI.
>
> *Academic Project - Université Cadi Ayyad*

---

## 📌 What is Edu-Planning?

**Edu-Planning** helps students optimize their revision schedules through intelligent AI analysis. Input your study notes and module observations, let Gemini AI analyze patterns, and receive personalized 7-day revision schedules.

### 🎯 Core Objective

Transform **raw student observations** → **AI analysis** → **optimized revision plans**

```
Student Journey:
┌─────────────────────────────────────────────────────┐
│  1️⃣ Create Module  →  2️⃣ Add Notes  →  3️⃣ AI Plans  │
└─────────────────────────────────────────────────────┘
```

---

## ✨ Key Features

| Feature | Purpose | Status |
|---------|---------|--------|
| 🔐 Secure Authentication | Session-based login system | ✓ |
| 📚 Module Management | Create & organize subjects | ✓ |
| 📝 Study Notes | Record difficulties & observations | ✓ |
| 🤖 AI Planning | Generate intelligent schedules | ✓ |
| 📊 Dashboard | Track study progress | ✓ |
| 📆 Calendar View | Visualize your plan | ✓ |

---

## 🏗️ Technology Stack

### Frontend Layer
```
┌─────────────────────────────────────────┐
│  PHP 8.2  │  HTML5  │  CSS3  │  JS ES6+ │
│  Bootstrap 5  │  FontAwesome 6           │
└─────────────────────────────────────────┘
```
- **Server-Side Rendering** via PHP
- **Responsive Design** with Bootstrap
- **Smooth Animations** with CSS + JavaScript
- **Session Management** for user authentication

### Backend Layer
```
┌──────────────────────────────────┐
│  Node.js 16+ LTS  │  Express 5.2 │
│  GoogleGenerativeAI SDK          │
└──────────────────────────────────┘
```
- **REST API Server** on Port 3001
- **AI Integration** with Gemini 2.5 Flash
- **JSON Processing** for planning data

### Data Layer
```
┌────────────────────┐
│  MySQL 8.0         │
│  Ports: 3306/3307  │
│  6 Tables          │
└────────────────────┘
```
- **Relational Database** for user data
- **JSON Storage** for complex planning objects
- **Indexed Queries** for performance

---

## 🔒 Security & Configuration

### 🛡️ Security Principles

1. **API Keys Protection**
   - ✓ Never hardcoded in source
   - ✓ Loaded from `.env` file
   - ✓ Environment variables only

2. **Database Credentials**
   - ✓ Stored in `.env` (git-ignored)
   - ✓ PDO prepared statements (prevent SQL injection)
   - ✓ Password hashing with PHP `password_hash()`

3. **Frontend Security**
   - ✓ Session-based authentication
   - ✓ XSS protection via `htmlspecialchars()`
   - ✓ CSRF token validation

4. **Code Quality**
   - ✓ No hardcoded credentials
   - ✓ No exposed API keys
   - ✓ Audit logging of all actions

### ⚙️ Configuration

Create `.env` file in project root (git-ignored):

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3307
DB_USER=root
DB_PASS=your_password_here
DB_NAME=edu_planning

# API Configuration
API_SERVER=http://localhost:3001
API_TIMEOUT=30

# Google APIs (from Google AI Studio)
GEMINI_API_KEY=YOUR_KEY_HERE

# Session Configuration
SESSION_TIMEOUT=3600
SESSION_SECURE=false
```

**⚠️ Important**: Never commit `.env` to Git. Add to `.gitignore`:
```
.env
.env.local
.env.*.local
config.php
```

---

## 🔐 Sessions & Cookies

### 📚 What are Sessions & Cookies?

#### Sessions

**Definition**: Server-side mechanism to maintain user state across multiple HTTP requests.

```
Session = Server's memory of who you are

How it works:
Browser                          Server
  │                               │
  ├─ Sends login (username, pwd) ─→ │
  │                               │
  │ ← Creates session (random ID) ─┤
  │   Stores in /tmp/sess_xxxxx   │
  │                               │
  ├─ Sends PHPSESSID cookie ────→ │
  │   (identifies the session)     │
  │                               │
  ├─ Each request includes ──────→ │
  │   PHPSESSID cookie             │
  │   Server recognizes user       │
  │                               │
```

**Key Points:**
- `$_SESSION['user_id']` = User data stored server-side
- `PHPSESSID` = Cookie that identifies the session
- Session file = `/tmp/sess_[PHPSESSID]`
- Secure = Data never sent to browser

#### Cookies

**Definition**: Small text files stored on client browser that are sent with every HTTP request.

```
Cookie = Browser's memory of server preferences

Structure:
┌──────────────────────────────────┐
│ Name: PHPSESSID                  │
│ Value: abc123def456ghi789...     │
│ Expires: (end of session)        │
│ HttpOnly: ✓ (JS cannot access)   │
│ Secure: ✓ (HTTPS only)           │
│ SameSite: Strict (no CSRF)       │
└──────────────────────────────────┘
```

**Key Points:**
- Stored in browser memory
- Sent automatically with each request
- HttpOnly = JavaScript cannot steal it
- Max size = 4KB
- Domain-specific (cannot be read by other sites)

### Key Differences

| Aspect | Sessions | Cookies |
|--------|----------|---------|
| **Location** | Server memory | Browser storage |
| **Size** | Unlimited | 4KB max |
| **Security** | High (server-side) | Medium (client-side) |
| **Expiry** | Browser close | Configurable |
| **User Access** | Server-side only | User can see/modify |
| **Use Case** | Authentication | Preferences, tracking |

---

### 🔐 Session Management

**How Authentication Works:**

```
Login Page
    ↓
Credentials Validation (auth.php)
    ↓
Password Hash Comparison (password_verify)
    ↓
Session Created ($_SESSION['user_id'])
    ↓
Redirect to Dashboard
```

### Session Storage

- **Type**: Server-side PHP Sessions
- **Storage**: `/tmp` (Linux/Mac) or `%TEMP%` (Windows)
- **Identifier**: `PHPSESSID` cookie
- **Timeout**: 3600 seconds (configurable)
- **Scope**: Per user per browser

### Cookie Configuration

```php
// Session cookies - HTTP Only (secure)
session_set_cookie_params([
    'httponly' => true,     // JavaScript cannot access
    'secure' => false,      // true in production (HTTPS only)
    'samesite' => 'Strict'  // CSRF protection
]);
```

**Cookie Details:**
- Name: `PHPSESSID`
- Value: Random 26-character string
- Expires: At end of session
- HttpOnly: ✓ (prevents XSS theft)
- Secure: ✗ (set to true in production)
- SameSite: Strict (prevents CSRF)

### Security Features

1. **Authentication Check**
   ```php
   require_once 'auth.php';
   require_login();
   // Ensures user is authenticated
   ```

2. **Session Regeneration**
   - New session ID on login
   - Prevents session fixation attacks

3. **Automatic Logout**
   - 1 hour idle timeout
   - Force re-login after timeout

4. **CSRF Token Validation**
   - Generated per form
   - Validated on submission
   - Prevents cross-site attacks

### User Session Lifecycle

| Stage | Action | Data |
|-------|--------|------|
| **Login** | `$_SESSION['user_id'] = $user_id` | User ID stored |
| **Active** | Session maintained across requests | User remains logged in |
| **Idle** | Timeout after 3600 seconds | Automatic logout |
| **Logout** | `session_destroy()` | Session erased |

### Testing Authentication

```bash
# Check current session (in PHP):
var_dump($_SESSION);

# Check session in logs:
tail -f logs.php

# Verify PHPSESSID cookie:
# Browser → DevTools → Application → Cookies
```



---

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Node.js 16+ LTS  
- MySQL 8.0
- Git

### Installation (5 Steps)

```bash
# 1. Navigate to project
cd c:\xampp\htdocs\Edu-Planning

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install

# 4. Setup database
mysql -u root -p < schema.sql

# 5. Start services
# Terminal 1:
npm start

# Terminal 2:
# Open browser: http://localhost/Edu-Planning/dashboard.php
```

### Environment Setup

```bash
# Copy environment template
cp .env.example .env

# Edit .env with your values
# - Add Gemini API Key
# - Configure database credentials
```

---

## 📖 How It Works

### User Workflow

```
🎯 STUDENT JOURNEY

Step 1: Login/Register
       └─→ Session created, dashboard displayed

Step 2: Create Modules
       └─→ Add subjects (Math, Physics, etc.)

Step 3: Add Study Notes
       └─→ Record difficulties & observations
       └─→ Notes stored in database

Step 4: Generate Plan (via Recommendations)
       └─→ Select modules
       └─→ Choose notes to analyze
       └─→ Submit to Gemini AI

Step 5: AI Analysis (Backend)
       └─→ Gemini analyzes patterns
       └─→ Creates 7-day schedule
       └─→ Saved in revision_plans table

Step 6: Review & Study
       └─→ View calendar
       └─→ Follow daily sessions
       └─→ Track progress
```

---

## 🔄 Data Flow

```
FRONTEND          API              AI SERVICE      DATABASE
═════════         ═══              =══════════     ════════

User Input ─────→ POST /generate ─→ Gemini API ──→ revision_plans
  modules          /plan            Analyzes        (stores plan)
  notes                             patterns        ai_recommendations
                                                    (stores response)
                 ← JSON Response ←─────── ────────
                                                    
Display Plan ←─ JSON Data ←──────────────────────  
Calendar
Sessions
```

---

## 📊 Database Overview

**Core Tables:**
- `users` — Student accounts
- `modules` — Subject/course data  
- `notes` — Student observations
- `revision_plans` — Generated schedules
- `ai_recommendations` — AI analysis logs
- `logs` — Activity audit trail

**For full schema details**, see `schema.sql` or [ARCHITECTURE.md](ARCHITECTURE.md)

---

## 🐛 Troubleshooting

### API Connection Issues
```
❌ "Cannot connect to API server"
→ Check: npm start running on Port 3001
→ Check: Firewall not blocking Port 3001
→ Check: http://localhost:3001/health returns OK
```

### Database Issues
```
❌ "Database connection failed"
→ Check: MySQL running
→ Check: Credentials in .env are correct
→ Run: mysql -u root -p
→ Verify: Database edu_planning exists
```

### Gemini API Issues
```
❌ "Invalid Gemini API key"
→ Check: .env has GEMINI_API_KEY set
→ Check: Key is active in Google AI Studio
→ Try: Regenerate a new key
→ Check: Network connection working
```

### Slow Performance
```
❌ "Gemini analysis takes >30 seconds"
→ Reduce: Number of notes being analyzed
→ Reduce: Number of modules selected
→ Check: Internet connection stability
```

---

## 📚 Documentation

| Document | Content |
|----------|---------|
| **README.md** | This file - Overview & quick start |
| **ARCHITECTURE.md** | Complete system architecture & file locations |
| **schema.sql** | Database structure & relationships |
| **.gitignore** | Files to exclude from Git |

---

## ⚡ Performance Tips

1. **Limit Analysis Scope**
   - Select 2-3 modules per plan generation
   - Include only recent, relevant notes

2. **Database Optimization**
   - Indexes on `user_id` and date fields
   - Archive old plans periodically

3. **Frontend Performance**
   - Lazy-load calendar months
   - Cache generated plans locally
   - Compress images and assets

---

## 🤝 Contributing

This is an academic project. For improvements:
1. Create a feature branch
2. Test thoroughly
3. Document changes
4. Submit for review

---

## 📝 Notes

- Built for **academic use** at Université Cadi Ayyad
- Uses **open-source technologies**
- Follows **security best practices**
- Maintains **clean code standards**

---

**Last Updated**: March 29, 2026  
**Version**: 2.1  
**Status**: Production Ready ✓

Built with ❤️ for smarter studying
