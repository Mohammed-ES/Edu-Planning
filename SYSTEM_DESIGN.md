# System Design — Edu-Planning

> AI-Powered Academic Planning Platform | Université Cadi Ayyad, Marrakech

---

## Table of Contents

1. [Overview](#1-overview)
2. [High-Level Architecture](#2-high-level-architecture)
3. [Component Breakdown](#3-component-breakdown)
4. [Data Flow Diagrams](#4-data-flow-diagrams)
5. [Database Design](#5-database-design)
6. [Security Architecture](#6-security-architecture)
7. [API Design](#7-api-design)
8. [File & Folder Structure](#8-file--folder-structure)
9. [Scalability Considerations](#9-scalability-considerations)
10. [Deployment Architecture](#10-deployment-architecture)

---

## 1. Overview

Edu-Planning is a **multi-tier SaaS web application** that provides AI-generated, personalized 7-day revision schedules for university students based on their module metadata (difficulty, progress, exam dates, understanding level).

### Core Capabilities

| Capability | Implementation |
|-----------|---------------|
| User Authentication | PHP sessions + CSRF tokens + bcrypt passwords |
| Module Management | Full CRUD via PHP/PDO + MySQL |
| AI Plan Generation | PHP → Google Gemini REST API (direct call) |
| Study Calendar | Vanilla JS calendar rendering exam dates |
| Dashboard Analytics | Chart.js doughnut + progress bars |
| Animated Entry | Canvas particles + CSS animations |

---

## 2. High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                          BROWSER (Client)                        │
│  HTML + Vanilla CSS + Bootstrap 5 + Vanilla JS + Chart.js       │
└───────────────────┬─────────────────────────────────────────────┘
                    │  HTTP/HTTPS
                    ▼
┌─────────────────────────────────────────────────────────────────┐
│                      WEB LAYER (Apache / XAMPP)                  │
│                                                                   │
│   ┌─────────────────────────────────────────────────────────┐   │
│   │                  PHP Application Layer                   │   │
│   │                                                           │   │
│   │  index.php   login.php   register.php   dashboard.php   │   │
│   │  module.php  planning.php  generate_plan.php  profile.php│   │
│   │  Welcome.php  logout.php                                 │   │
│   │                                                           │   │
│   │  ┌─────────────────────────────────────────────────┐    │   │
│   │  │               include/ (Shared Layer)            │    │   │
│   │  │  config.php  auth.php  ai_api.php  connectiondb  │    │   │
│   │  └─────────────────────────────────────────────────┘    │   │
│   │                                                           │   │
│   │  ┌─────────────────────────────────────────────────┐    │   │
│   │  │           modules/ (CRUD Sub-pages)              │    │   │
│   │  │   add.php  edit.php  view.php  delete.php        │    │   │
│   │  │   _bootstrap.php                                  │    │   │
│   │  └─────────────────────────────────────────────────┘    │   │
│   └─────────────────────────────────────────────────────────┘   │
│                                                                   │
│   ┌─────────────────────────────────────────────────────────┐   │
│   │         Node.js Express Microservice (server.js)         │   │
│   │         Bound to 127.0.0.1:3001 (internal only)         │   │
│   │         Protected by Bearer token auth + rate limiting   │   │
│   └───────────────────────────┬─────────────────────────────┘   │
└───────────────────────────────┼─────────────────────────────────┘
                                │
             ┌──────────────────┼──────────────────┐
             │                  │                   │
             ▼                  ▼                   ▼
  ┌──────────────────┐ ┌──────────────┐ ┌──────────────────────┐
  │   MySQL Database │ │ Google Gemini│ │  PHP error_log /     │
  │   (edu_planning) │ │   REST API   │ │  Audit Logging       │
  │   3 tables       │ │  (External)  │ │                      │
  └──────────────────┘ └──────────────┘ └──────────────────────┘
```

---

## 3. Component Breakdown

### 3.1 Frontend Layer

```
css/
├── index.css          Landing page (hero, navbar, features, footer)
├── login.css          Auth pages (split-panel layout)
├── register.css       Auth pages
├── dashboard.css      Dashboard (sidebar, stat cards, charts)
├── module.css         Module list (card grid)
├── modules_add.css    Add module form
├── modules_edit.css   Edit module form
├── modules_view.css   Module detail view
├── generate_plan.css  AI plan generator
├── planning.css       Calendar view
├── profile.css        User profile
├── welcome.css        Animated entry screen
├── animations.css     Shared animation keyframes
└── style.css          Legacy global styles

js/
├── app.js             Master JS: scroll reveal, counters, cursor,
│                      particles, welcome screen, form validation
├── index.js           Landing page smooth scroll
├── dashboard.js       Chart.js doughnut chart init
├── planning.js        Vanilla JS calendar engine
├── generate_plan.js   Plan output helper
├── module.js          Module list delete confirmation
├── modules-shared.js  Shared delete dialog (view + edit pages)
├── login.js           Session storage reset on login page
└── welcome.js         Welcome screen particle canvas + animation
```

### 3.2 Backend Layer (PHP)

```
Page            Route           Auth Required   Key Actions
──────────────  ──────────────  ──────────────  ─────────────────────────────
Welcome.php     /Welcome.php    No              Animated entry, redirect → index
index.php       /index.php      No (→ dashboard if logged in)  Landing page
login.php       /login.php      No (→ dashboard if logged in)  Auth + rate limit
register.php    /register.php   No              Register + bcrypt hash
logout.php      /logout.php     No              Destroy session + cookie
dashboard.php   /dashboard.php  YES             Stats, charts, recent plans
module.php      /module.php     YES             Module list
modules/add     /modules/add    YES             Create module (CSRF)
modules/edit    /modules/edit   YES             Update module (CSRF)
modules/view    /modules/view   YES             Module detail
modules/delete  /modules/delete YES (POST only) Delete module (CSRF)
planning.php    /planning.php   YES             Calendar view
generate_plan   /generate_plan  YES             Generate + view + delete plans
profile.php     /profile.php    YES             Edit profile + password
```

### 3.3 Shared Include Layer

```
include/config.php
├── load_env()         — Zero-dependency .env parser
├── Security headers   — Sent on every response
├── Session hardening  — HttpOnly, SameSite, strict mode
└── PDO connection     — Exception mode, UTF-8, no emulated prepares

include/auth.php
├── verify_csrf_token()           — CSRF guard (hash_equals)
├── is_logged_in()                — Session check
├── require_login()               — Auth gate (redirect if not logged in)
├── current_user(PDO)             — Fetch user from DB
├── check_login_rate_limit()      — IP-based rate limit check
├── increment_login_attempt()     — Increment attempt counter
├── reset_login_attempts()        — Clear counter on success
├── get_remaining_lockout_seconds() — Time until unlock
└── logAction(user_id, action, PDO) — Audit log to error_log

include/ai_api.php
├── build_study_prompt(modules[]) — Builds Gemini prompt from module data
├── check_gemini_config()         — Validates API key is set
├── call_gemini_api(modules[])    — Direct Gemini REST call via cURL
├── generate_and_save_study_plan(PDO, user_id, modules[]) — Full pipeline
└── get_recent_study_plans(PDO, user_id) — Fetch 5 most recent plans

include/connectiondb.php
└── require_once config.php       — Thin compatibility wrapper
```

### 3.4 Node.js Microservice (server.js)

The Express server is an optional AI microservice, currently **standby** (PHP calls Gemini directly). It is production-hardened for future use:

```
Middleware Stack:
  express.json({ limit: '100kb' })   — Payload cap
  requireInternalAuth()               — Bearer token check
  rateLimitMiddleware()               — 10 req/IP/15min

Routes:
  GET  /                              — Status info (public)
  GET  /health                        — Health check (public)
  POST /api/generate-plan             — AI plan generation (protected)
```

---

## 4. Data Flow Diagrams

### 4.1 Authentication Flow

```
User submits login form
        │
        ▼
  verify_csrf_token()
        │ fail → "Invalid security token"
        ▼ pass
  check_login_rate_limit()
        │ fail → "Too many attempts, try in X min"
        ▼ pass
  SELECT user WHERE email=? OR name=?
        │ not found
        │   └─ increment_login_attempt() → "Invalid credentials"
        ▼ found
  password_verify(input, hash)
        │ fail → increment_login_attempt() → "Invalid credentials"
        ▼ pass
  session_regenerate_id(true)        ← Prevents session fixation
  $_SESSION['user_id'] = $user['id']
  reset_login_attempts()
  logAction('login_success')
        │
        ▼
  Redirect → dashboard.php
```

### 4.2 AI Plan Generation Flow

```
User selects modules + clicks "Generate AI Plan"
        │
        ▼
  verify_csrf_token()  ← NEW (was missing before audit)
        │
        ▼
  Fetch selected modules from DB (WHERE user_id = ?)
        │
        ▼
  build_study_prompt(modules[])
        │  Builds structured prompt with:
        │  - Module name, teacher, difficulty
        │  - Career importance, progress %, understanding level
        │  - Days until exam (computed)
        ▼
  cURL POST → Gemini API (gemini-2.5-flash)
        │  Headers: Content-Type: application/json
        │  Timeout: 60 seconds
        │  responseMimeType: application/json
        ▼
  Parse JSON response
        │ fail → error_log + return ['success'=>false, 'error'=>...]
        ▼ pass
  INSERT INTO study_plans (user_id, generated_plan, created_at)
        │ fail → return ['success'=>false] ← FIXED (was success=true before)
        ▼ pass
  Render 7-day timeline in browser
```

### 4.3 Request Lifecycle

```
HTTP Request
    │
    ▼
Apache receives request
    │
    ▼
PHP page loads
    │
    ├─ require_once include/config.php
    │       ├─ load_env('.env')
    │       ├─ Send security headers
    │       ├─ Configure session
    │       └─ Open PDO connection
    │
    ├─ require_once include/auth.php
    │       ├─ session_start()
    │       └─ Generate CSRF token if missing
    │
    ├─ require_login() [if protected page]
    │       └─ Redirect to login.php if not logged in
    │
    ├─ Business Logic (queries, AI calls, form handling)
    │
    └─ Render HTML response
```

---

## 5. Database Design

### 5.1 Entity-Relationship Diagram

```
┌─────────────────────────────────┐
│             users               │
├─────────────────────────────────┤
│ id          INT (PK, AUTO)      │
│ name        VARCHAR(100)        │
│ email       VARCHAR(100) UNIQUE │
│ password    VARCHAR(255)        │
│ created_at  TIMESTAMP           │
└──────────────┬──────────────────┘
               │ 1
               │
       ┌───────┴────────┐
       │                │
       │ N              │ N
┌──────▼──────────────┐ ┌────────────────────────┐
│       modules        │ │      study_plans        │
├─────────────────────┤ ├────────────────────────┤
│ id INT (PK)         │ │ id INT (PK)            │
│ user_id INT (FK)    │ │ user_id INT (FK)       │
│ module_name VARCHAR │ │ generated_plan TEXT    │
│ teacher VARCHAR     │ │ created_at TIMESTAMP   │
│ difficulty ENUM     │ └────────────────────────┘
│ career_importance   │   Index: (user_id, created_at DESC)
│ progress INT 0-100  │
│ understanding_level │
│ exam_date DATE      │
│ created_at TIMESTAMP│
└─────────────────────┘
  Index: (user_id, exam_date ASC)
```

### 5.2 Table Details

#### `users`
| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | INT | PK, AUTO_INCREMENT | — |
| name | VARCHAR(100) | NOT NULL | Display name |
| email | VARCHAR(100) | NOT NULL, UNIQUE | Login identifier |
| password | VARCHAR(255) | NOT NULL | bcrypt hash (cost 10+) |
| created_at | TIMESTAMP | DEFAULT NOW() | Member since |

#### `modules`
| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | INT | PK, AUTO_INCREMENT | — |
| user_id | INT | FK → users.id, CASCADE | Owner |
| module_name | VARCHAR(150) | NOT NULL | Subject name |
| teacher | VARCHAR(100) | NULL | Optional |
| difficulty | ENUM('EASY','MEDIUM','HARD') | NOT NULL | AI priority input |
| career_importance | ENUM('LOW','MEDIUM','HIGH') | NOT NULL | AI priority input |
| progress | INT | DEFAULT 0, CHECK 0-100 | Study progress % |
| understanding_level | ENUM('LOW','MEDIUM','HIGH') | NOT NULL | AI priority input |
| exam_date | DATE | NOT NULL | Drives AI prioritization |
| created_at | TIMESTAMP | DEFAULT NOW() | — |

**Index:** `idx_modules_user_exam (user_id, exam_date ASC)`
→ Optimizes the most frequent query: all modules for a user sorted by upcoming exam

#### `study_plans`
| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | INT | PK, AUTO_INCREMENT | — |
| user_id | INT | FK → users.id, CASCADE | Owner |
| generated_plan | TEXT | — | Full JSON from Gemini |
| created_at | TIMESTAMP | DEFAULT NOW() | — |

**Index:** `idx_study_plans_user_date (user_id, created_at DESC)`
→ Optimizes dashboard "recent plans" query

---

## 6. Security Architecture

### 6.1 Defense-in-Depth Model

```
Layer 1 — Network
├── Node.js bound to 127.0.0.1 (not exposed externally)
└── HTTPS recommended (Secure cookie flag auto-activates)

Layer 2 — HTTP Headers (every PHP response)
├── X-Frame-Options: DENY
├── X-Content-Type-Options: nosniff
├── X-XSS-Protection: 1; mode=block
├── Referrer-Policy: strict-origin-when-cross-origin
└── Permissions-Policy: geolocation=(), microphone=(), camera=()

Layer 3 — Session
├── HttpOnly: 1 (JS cannot read session cookie)
├── SameSite: Strict (CSRF prevention at cookie level)
├── use_strict_mode: 1 (reject uninitialized session IDs)
├── gc_maxlifetime: 3600 (1-hour expiry)
├── session_regenerate_id(true) on login (fixation prevention)
└── Full session destroy on logout

Layer 4 — Authentication
├── bcrypt password hashing (PASSWORD_BCRYPT)
├── CSRF token on every state-changing form (hash_equals comparison)
├── Brute-force: 5 attempts/IP/15 min (session-based)
└── Timing-safe comparison for CSRF (hash_equals)

Layer 5 — Input Validation
├── All ENUM inputs whitelisted (in_array strict)
├── Date format validated (DateTime::createFromFormat)
├── Progress clamped to 0-100 (max/min)
├── Email validated (FILTER_VALIDATE_EMAIL)
├── All output escaped (htmlspecialchars)
└── All DB queries use prepared statements (PDO, no emulation)

Layer 6 — AI API Protection
├── Gemini key in .env only (never in code)
├── Node.js API requires internal Bearer token
├── Node.js rate-limited (10 req/IP/15 min)
├── AI request timeout: 30 seconds
└── Payload capped at 100kb

Layer 7 — Error Handling
├── DB errors logged to error_log (never shown to user)
├── Generic 503 shown on DB failure
└── AI errors return sanitized messages
```

### 6.2 CSRF Protection Flow

```
Server generates token:
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32))

Every form embeds:
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

On POST:
  verify_csrf_token($_POST['csrf_token'] ?? '')
  └── hash_equals($_SESSION['csrf_token'], $submitted_token)
       ├── true  → proceed
       └── false → "Invalid security token" (no processing)
```

---

## 7. API Design

### 7.1 PHP Internal Endpoints

These are standard PHP pages, not a REST API. All protected by session auth.

| Method | URL | Auth | Action |
|--------|-----|------|--------|
| GET | `/index.php?welcome` | No | Landing page |
| POST | `/login.php` | No | Authenticate user |
| POST | `/register.php` | No | Create account |
| GET | `/logout.php` | Session | Destroy session |
| GET | `/dashboard.php` | Session | Dashboard stats |
| GET | `/module.php` | Session | Module list |
| GET/POST | `/modules/add.php` | Session+CSRF | Create module |
| GET/POST | `/modules/edit.php?id=N` | Session+CSRF | Update module |
| GET | `/modules/view.php?id=N` | Session | View module |
| POST | `/modules/delete.php` | Session+CSRF | Delete module |
| GET | `/planning.php` | Session | Calendar |
| GET/POST | `/generate_plan.php` | Session+CSRF | AI plan |
| GET | `/generate_plan.php?view_plan=N` | Session | View saved plan |
| GET/POST | `/profile.php` | Session+CSRF | Edit profile |

### 7.2 Node.js REST API (server.js)

Base URL: `http://127.0.0.1:3001` (internal only)

#### `GET /health`
```json
{ "status": "Gemini API OK", "timestamp": "2026-05-10T14:30:00.000Z" }
```

#### `POST /api/generate-plan`
**Headers required:**
```
Authorization: Bearer <INTERNAL_API_SECRET>
Content-Type: application/json
```

**Request body:**
```json
{
  "modules": [
    {
      "module_name": "Algorithms",
      "difficulty": "HARD",
      "progress": 40,
      "understanding_level": "LOW",
      "exam_date": "2026-06-10",
      "teacher": "Prof. Ahmed",
      "career_importance": "HIGH"
    }
  ]
}
```

**Success response (200):**
```json
{
  "success": true,
  "data": {
    "planning": [
      {
        "jour": 1,
        "date": "2026-05-11",
        "total_minutes": 240,
        "sessions": [
          {
            "order": 1,
            "module": "Algorithms",
            "time_start": "09:00",
            "duration_minutes": 180,
            "priorite": "haute",
            "topics": ["Sorting algorithms", "Big-O notation"],
            "description": "Focus on high-difficulty areas"
          }
        ]
      }
    ]
  }
}
```

**Error responses:**
```
400 — { "error": "Invalid format: non-empty 'modules' array required" }
401 — { "error": "Unauthorized" }
429 — { "error": "Too many requests. Try again later." }
504 — { "success": false, "error": "AI request timed out" }
500 — { "success": false, "error": "AI service unavailable" }
```

---

## 8. File & Folder Structure

```
Edu-Planning-v1/
│
├── .env                    ← Real credentials (NEVER commit)
├── .env.example            ← Template for setup
├── .gitignore              ← Secrets, node_modules, assets excluded
├── .gitattributes          ← Line ending config
│
├── README.md               ← Setup & usage documentation
├── SYSTEM_DESIGN.md        ← This file
│
├── package.json            ← Node.js config (main: server.js)
├── package-lock.json
├── server.js               ← Express + Gemini microservice
│
├── db/
│   └── schema.sql          ← MySQL schema with indexes + seed data
│
├── include/                ← PHP shared layer (no HTML output)
│   ├── config.php          ← .env loader, headers, session, PDO
│   ├── auth.php            ← Session, CSRF, rate limit, audit log
│   ├── ai_api.php          ← Gemini API client
│   └── connectiondb.php    ← Thin wrapper (delegates to config.php)
│
├── modules/                ← Module CRUD sub-pages
│   ├── _bootstrap.php      ← Auth + shared helpers for modules/
│   ├── add.php
│   ├── edit.php
│   ├── view.php
│   └── delete.php
│
├── css/                    ← Per-page stylesheets
│   ├── index.css
│   ├── login.css
│   ├── register.css
│   ├── dashboard.css
│   ├── module.css
│   ├── modules_add.css
│   ├── modules_edit.css
│   ├── modules_view.css
│   ├── generate_plan.css
│   ├── planning.css
│   ├── profile.css
│   ├── welcome.css
│   ├── animations.css
│   └── style.css
│
├── js/                     ← Per-page JavaScript
│   ├── app.js              ← Master: scroll reveal, particles, cursor
│   ├── index.js            ← Landing page smooth scroll
│   ├── dashboard.js        ← Chart.js setup
│   ├── planning.js         ← Calendar engine
│   ├── generate_plan.js    ← Plan output helper
│   ├── module.js           ← Module list delete dialog
│   ├── modules-shared.js   ← Shared delete dialog (view + edit)
│   ├── login.js            ← Session storage reset
│   └── welcome.js          ← Canvas particles + animation
│
├── assets/
│   └── images/
│       ├── universite-cadi-ayyad.png
│       └── icons_3d/       ← 3D STL model for landing page
│
├── index.php               ← Landing page (public)
├── Welcome.php             ← Animated entry screen
├── login.php               ← Authentication
├── register.php            ← Account creation
├── logout.php              ← Session destroy
├── dashboard.php           ← Main dashboard (protected)
├── module.php              ← Module list (protected)
├── planning.php            ← Calendar view (protected)
├── generate_plan.php       ← AI plan generator (protected)
└── profile.php             ← User profile (protected)
```

---

## 9. Scalability Considerations

### Current State (Single Server)
The current architecture is suitable for **~100–500 concurrent users** on a single XAMPP/Apache server.

### Bottlenecks & Mitigation

| Bottleneck | Impact | Mitigation |
|-----------|--------|-----------|
| Gemini API latency (1–10s per call) | User waits on page load | Add async generation + polling, or WebSocket streaming |
| No AI response caching | Same query hits API repeatedly | Cache plans by hash(modules) in APCu or Redis |
| Session-based rate limiting | Resets if server restarts | Move rate limit state to Redis or DB table |
| No per-user AI quota | Unlimited API calls per user | Add daily_ai_calls counter to users table |
| Single DB server | Failure = full outage | Add read replica + connection pool (PgBouncer / ProxySQL) |
| PHP synchronous cURL | Blocks PHP worker thread during AI call | Use ReactPHP or offload to Node.js queue |

### Future Scaling Path

```
Phase 1 (Current): Single PHP+MySQL server
Phase 2: Add Redis for session storage + rate limiting + caching
Phase 3: Separate DB server with read replica
Phase 4: Move AI calls to async job queue (Redis Queue / BullMQ)
Phase 5: Containerize with Docker + deploy to VPS/cloud
Phase 6: Add CDN for static assets (css/, js/, images/)
```

---

## 10. Deployment Architecture

### Local Development (Current)

```
XAMPP (Windows)
├── Apache 2.4          → http://localhost/Edu-Planning-v1/
├── MySQL 8 (port 3307) → Database
└── Node.js 20          → http://127.0.0.1:3001 (npm run dev)
```

### Production (Recommended VPS Setup)

```
Internet
    │ HTTPS (Let's Encrypt)
    ▼
Nginx (reverse proxy + SSL termination)
    │
    ├─── PHP-FPM (php8.2-fpm)
    │         └─── Edu-Planning PHP application
    │
    └─── Proxy /api/* → Node.js (pm2 managed, port 3001)
                             └─── Gemini AI calls

MySQL 8 (separate server or managed DB)
    └─── edu_planning database
```

### Environment Variables Required

```env
# Database
DB_HOST=localhost
DB_PORT=3306
DB_USER=edu_user
DB_PASS=<strong_password>
DB_NAME=edu_planning

# AI
GEMINI_API_KEY=<your_gemini_key>

# Internal security
INTERNAL_API_SECRET=<64_char_random_hex>

# Node.js
PORT=3001
```

### Checklist Before Production Deploy

- [ ] Rotate Gemini API key (old key was in git history)
- [ ] Set `INTERNAL_API_SECRET` to a cryptographically random 64-char string
- [ ] Enable HTTPS (Let's Encrypt)
- [ ] Remove seed INSERT statements from `db/schema.sql`
- [ ] Set PHP `display_errors = Off` in `php.ini`
- [ ] Set PHP `error_log` to a secure writable path
- [ ] Add rate limiting at Nginx level (limit_req_zone)
- [ ] Configure MySQL user with minimal privileges (no SUPER, no DROP)
- [ ] Set up automated DB backups
- [ ] Run `npm audit` and update any vulnerable packages
- [ ] Add per-user daily AI plan generation quota

---

*Document maintained alongside the codebase. Update this file when making architectural changes.*
