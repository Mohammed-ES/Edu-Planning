# 🏗️ Architecture Complète — Edu-Planning

> Guide de référence de l'architecture technique, des flux de données et de la localisation des fichiers clés.
>
> *Université Cadi Ayyad (UCA) — Academic Project*

---

## 📊 Vue d'ensemble (Architecture 3 couches)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                       UTILISATEURS (Navigateurs Web)                    │
└───────────────────┬────────────────────────────────┬────────────────────┘
                    │ HTTP (port 80 / 8080)           │ HTTP (port 80 / 8080)
                    ▼                                 ▼
┌────────────────────────────────┐     ┌─────────────────────────────────┐
│     COUCHE PRÉSENTATION        │     │       COUCHE API / BACKEND      │
│   (PHP 8.2 + Apache / Docker)  │     │     (Node.js 16+ · Express 5)   │
│   Ports: 80 (local), 8080 (D)  │     │     Port: 3001                  │
│                                │     │                                 │
│  index.php      planning.php   │     │  server.js                      │
│  login.php      recom...php    │     │  ├─ GET  /                      │
│  register.php   profile.php    │─────│→ ├─ POST /api/generate-plan     │
│  dashboard.php  logs.php       │     │  ├─ GET  /api/revision-plan/:m  │
│  modules.php    health_...php  │     │  └─ GET  /health                │
│                                │     │                                 │
│  assets/                       │     │  Dépendances:                   │
│   ├─ style.css                 │     │   @google/generative-ai 0.24.1  │
│   ├─ animations.css            │     │   express 5.2.1                 │
│   └─ app.js (AJAX/DOM)         │     │                                 │
└────────────┬───────────────────┘     └───────────────┬─────────────────┘
             │ PDO / SQL                               │ HTTPS / REST
             │ (port 3306 local / 3307 Docker)         │
             ▼                                         ▼
┌────────────────────────────────┐     ┌─────────────────────────────────┐
│       COUCHE DONNÉES           │     │      SERVICES EXTERNES          │
│    (MySQL 8.0 · edu_planning)  │     │                                 │
│                                │     │  ☁️  Google Gemini AI           │
│  users               6 tables  │     │       Modèle: gemini-2.5-flash  │
│  modules                       │     │       Prompt → JSON planning    │
│  notes                         │     │                                 │
│  revision_plans (JSON)         │     │  📅 Google Calendar (Futur)     │
│  ai_recommendations            │     │       Non implémenté            │
│  logs                          │     │                                 │
└────────────────────────────────┘     └─────────────────────────────────┘
```

---

## 📁 Arborescence complète avec descriptions

```
C:\xampp\htdocs\Edu-Planning\
│
├── ─────────────── FICHIERS PHP FRONTAUX ───────────────
│
├── index.php           → Page d'accueil (landing, hero, features)
│   └─ Pas d'authentification requise
│
├── login.php           → Formulaire de connexion
│   ├─ POST → Validation identifiants
│   ├─ password_verify() contre hash en BD
│   └─ Crée: $_SESSION['user_id']
│
├── register.php        → Inscription nouvel étudiant
│   ├─ POST → INSERT INTO users
│   ├─ password_hash() avant insertion
│   └─ Validation email unique / username unique
│
├── logout.php          → Déconnexion
│   ├─ session_destroy()
│   └─ Redirect → index.php
│
├── dashboard.php       → 🎯 Tableau de bord principal
│   ├─ SELECT COUNT(*) modules WHERE user_id
│   ├─ SELECT COUNT(*) notes
│   ├─ SELECT COUNT(*) revision sessions
│   ├─ Statistiques + Quick links
│   └─ Inclus: config.php + auth.php (require_login)
│
├── modules.php         → Gestion modules & notes (CRUD complet)
│   ├─ Afficher liste des modules de l'utilisateur
│   ├─ Ajouter / Éditer / Supprimer module
│   ├─ Afficher notes par module
│   ├─ Ajouter / Éditer / Supprimer note
│   └─ Tables: modules + notes
│
├── planning.php        → Calendrier visuel
│   ├─ Récupère la dernière revision_plan depuis BD
│   ├─ Navigation mois précédent / suivant
│   ├─ Parsing plan_data (JSON) → affichage sessions
│   └─ Couleurs selon priorité (haute/moyenne/basse)
│
├── recommendations.php → 🤖 Générateur de planning IA
│   ├─ Historique tous les plannings de l'utilisateur
│   ├─ FORMULAIRE: Sélection modules + notes
│   ├─ AJAX POST → server.js /api/generate-plan
│   ├─ INSERT INTO revision_plans (plan_data JSON)
│   ├─ INSERT INTO ai_recommendations
│   └─ Suppression d'anciens plannings
│
├── profile.php         → Profil utilisateur
│   ├─ Afficher données compte
│   ├─ Modifier: username, email
│   └─ Changer mot de passe (password_verify + hash)
│
├── logs.php            → Historique des actions
│   ├─ SELECT * FROM logs WHERE user_id
│   └─ Tableau trié par created_at DESC
│
├── health_check.php    → Diagnostics système
│   ├─ Teste connexion MySQL
│   ├─ Vérifie existence des 6 tables
│   ├─ Vérifie GEMINI_API_KEY
│   ├─ Vérifie présence fichiers critiques
│   └─ Retourne résumé HTML avec statuts
│
├── ─────────────── FICHIERS AUTH & CONFIG ──────────────
│
├── config.php          → 📍 Configuration centrale
│   ├─ Charge variables .env (via getenv())
│   ├─ Définit constantes:
│   │   ├─ DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME
│   │   └─ GEMINI_API_KEY, API_SERVER, SESSION_TIMEOUT
│   ├─ Crée $pdo (connexion PDO MySQL)
│   └─ Définit logAction($user_id, $action)
│
├── auth.php            → Helper authentification
│   ├─ require_login() → redirect si non connecté
│   └─ Inclus en haut de toutes les pages protégées
│
├── auth-helper.php     → Fonctions auxiliaires auth
│   ├─ Hash password
│   ├─ Gestion de session
│   └─ Contrôle d'accès
│
├── ─────────────── BACKEND NODE.JS ─────────────────────
│
├── server.js           → 🟢 API Express.js (port 3001)
│   ├─ ESM (import/export)
│   ├─ GoogleGenerativeAI(process.env.GEMINI_API_KEY)
│   ├─ Modèle: gemini-2.5-flash
│   │
│   ├─ GET  /                      → Statut API
│   ├─ POST /api/generate-plan     → Planning 7 jours
│   ├─ GET  /api/revision-plan/:m  → Planning par module
│   └─ GET  /health                → Health check JSON
│
├── package.json        → Config Node.js
│   ├─ "type": "module"  (ESM)
│   ├─ "start": "node server.js"
│   └─ Deps: express@^5.2.1, @google/generative-ai@^0.24.1
│
├── node_modules/       → ⛔ Git-ignoré
│
├── ─────────────── BASE DE DONNÉES ─────────────────────
│
├── schema.sql          → 📍 Schéma MySQL complet
│   ├─ CREATE TABLE users
│   ├─ CREATE TABLE modules
│   ├─ CREATE TABLE notes
│   ├─ CREATE TABLE revision_plans
│   ├─ CREATE TABLE ai_recommendations
│   └─ CREATE TABLE logs
│
├── ─────────────── ASSETS FRONTEND ─────────────────────
│
├── assets/
│   ├─ style.css        → Styles principaux (UCA Design System)
│   │   ├─ Variables CSS couleurs UCA
│   │   ├─ Layout responsive
│   │   └─ Composants (nav, cards, buttons, modals)
│   │
│   ├─ animations.css   → Animations & transitions
│   │   ├─ @keyframes (fadeIn, slideIn, pulse...)
│   │   ├─ Scroll-reveal
│   │   └─ Hover effects
│   │
│   ├─ app.js           → Logique JavaScript client
│   │   ├─ AJAX POST /api/generate-plan
│   │   ├─ Form validation
│   │   ├─ DOM manipulation & event listeners
│   │   └─ Calendar rendering
│   │
│   └─ images/          → Logos & icônes UCA
│
├── ─────────────── CONFIGURATION & DÉPLOIEMENT ─────────
│
├── .env                → ⛔ Git-ignoré (SECRETS)
│   ├─ DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME
│   ├─ GEMINI_API_KEY
│   └─ SESSION_TIMEOUT, SESSION_SECURE
│
├── .env.example        → ✅ Template public (sans valeurs)
│
├── .gitignore          → Exclusions Git
│   ├─ .env, .env.local
│   ├─ node_modules/
│   └─ config.php (si local override)
│
├── docker-compose.yml  → Orchestration Docker
│   ├─ Service "web"    → PHP 8.2 Apache (port 8080:80)
│   ├─ Service "db"     → MySQL 8.0     (port 3307:3306)
│   └─ Service "github-mcp"             (port 3000:8082)
│
└── ARCHITECTURE.md     → 📍 CE FICHIER
```

---

## 🔑 Localisation des éléments clés

### Clés API & Secrets

| Élément | Fichier | Détail |
|---------|---------|--------|
| 🔑 Gemini API Key | `.env` → `config.php` | `GEMINI_API_KEY` via `getenv()` |
| 🗄️ DB Credentials | `.env` → `config.php` | `DB_HOST`, `DB_USER`, `DB_PASS` |
| 🤖 Gemini Model | `server.js` ligne 58 | `"gemini-2.5-flash"` |
| 🚀 API Port | `server.js` ligne 125 | `process.env.PORT \|\| 3001` |

### Fichiers critiques (ne pas supprimer)

| Fichier | Rôle |
|---------|------|
| `config.php` | Inclus par tous les fichiers PHP — connexion BD + env |
| `auth.php` | `require_login()` — protection de toutes les pages |
| `server.js` | Seul point d'accès à l'API Gemini |
| `schema.sql` | Référence de la structure MySQL |
| `.env.example` | Template pour les nouveaux développeurs |

---

## 🔄 Flux de données — Générer un Planning IA

```
  Étape 1 │ recommendations.php
           │ Utilisateur sélectionne modules + notes
           │ Click "Generate Plan"
           ↓

  Étape 2 │ assets/app.js (Client)
           │ Collecte données form → JSON
           │ fetch('http://localhost:3001/api/generate-plan', {
           │   method: 'POST',
           │   body: JSON.stringify({ modules: [...] })
           │ })
           ↓

  Étape 3 │ server.js — Express POST /api/generate-plan
           │ Valide format { modules: Array }
           │ Construit prompt structuré
           │ Appelle genAI.getGenerativeModel({ model: 'gemini-2.5-flash' })
           ↓

  Étape 4 │ Google Gemini API (externe)
           │ Analyse notes par module
           │ Priorité: difficult→haute | good→moyenne | excellent→basse
           │ Génère 7 objets jour (planning JSON)
           ↓

  Étape 5 │ server.js — Nettoyage & validation
           │ Supprime markdown (```json)
           │ JSON.parse(rawText)
           │ res.json({ success: true, data: {...} })
           ↓

  Étape 6 │ recommendations.php — Réception & sauvegarde
           │ INSERT INTO revision_plans (plan_data JSON)
           │ INSERT INTO ai_recommendations
           │ logAction($user_id, 'plan_generated')
           ↓

  Étape 7 │ planning.php — Affichage
           │ SELECT plan_data FROM revision_plans (dernière)
           │ Parse JSON → affiche calendrier 7 jours
           │ Sessions colorées par priorité
```

---

## 📊 Tables MySQL — Schéma détaillé

### `users`
```sql
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(100) UNIQUE NOT NULL,
    email         VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,           -- bcrypt via password_hash()
    role          ENUM('student','admin') DEFAULT 'student',
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### `modules`
```sql
CREATE TABLE modules (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,                    -- FK → users.id
    module_name  VARCHAR(150) NOT NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### `notes`
```sql
CREATE TABLE notes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    module_id   INT NOT NULL,                     -- FK → modules.id
    note_value  TEXT NOT NULL,                    -- Observations étudiant
    description VARCHAR(255),
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
);
```

### `revision_plans`
```sql
CREATE TABLE revision_plans (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,                -- FK → users.id
    plan_data        JSON NOT NULL,               -- Planning complet 7 jours
    start_date       DATE,
    end_date         DATE,
    used_note_ids    JSON,                        -- [1, 3, 5, ...]
    selected_modules JSON,                        -- ["Math", "Physics"]
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### `ai_recommendations`
```sql
CREATE TABLE ai_recommendations (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    recommendation JSON NOT NULL,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### `logs`
```sql
CREATE TABLE logs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT,
    action     VARCHAR(255) NOT NULL,             -- 'login', 'module_added', etc.
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Relations:**
```
users 1──N modules 1──N notes
users 1──N revision_plans
users 1──N ai_recommendations
users 1──N logs
```

---

## 🌐 API Reference (Node.js — Port 3001)

### `POST /api/generate-plan`

**Request:**
```json
{
  "modules": [
    {
      "name": "Mathematics",
      "notes": "Struggling with integration and limits"
    },
    {
      "name": "Physics",
      "notes": "Good understanding of mechanics"
    }
  ]
}
```

**Response (success):**
```json
{
  "success": true,
  "data": {
    "planning": [
      {
        "jour": 1,
        "date": "2026-04-02",
        "total_minutes": 240,
        "sessions": [
          {
            "order": 1,
            "module": "Mathematics",
            "time_start": "09:00",
            "duration_minutes": 150,
            "priorite": "haute",
            "topics": ["Integration", "Limits"],
            "description": "Deep review of difficult concepts"
          }
        ]
      }
    ]
  }
}
```

### `GET /health`

```json
{ "status": "Gemini API OK", "timestamp": "2026-04-02T22:00:00.000Z" }
```

### `GET /api/revision-plan/:module`

Returns a module-specific schedule (legacy — kept for backward compatibility).

---

## 🔌 Ports & Accès

| Service | URL Locale (XAMPP) | URL Docker |
|---------|-------------------|-----------|
| Frontend PHP | `http://localhost/Edu-Planning/` | `http://localhost:8080` |
| Node.js API | `http://localhost:3001` | `http://localhost:3001` |
| API Health | `http://localhost:3001/health` | `http://localhost:3001/health` |
| MySQL | `localhost:3306` | `localhost:3307` |
| Diagnostics | `http://localhost/Edu-Planning/health_check.php` | `http://localhost:8080/health_check.php` |

---

## 🚀 Démarrage

### Local (XAMPP)

```bash
# 1. XAMPP Control Panel → Apache: START, MySQL: START

# 2. Terminal — démarrer API Node.js
cd C:\xampp\htdocs\Edu-Planning
npm start
# → 🚀 Gemini API started at http://localhost:3001

# 3. Navigateur
# → http://localhost/Edu-Planning/
# → http://localhost:3001/health  (vérifier API)
```

### Docker

```bash
cd C:\xampp\htdocs\Edu-Planning
docker-compose up -d

# Vérifier les services
docker-compose ps

# Accès
# Frontend:  http://localhost:8080
# Backend:   http://localhost:3001
# Database:  localhost:3307
```

---

## 🔒 Sécurité — Checklist

| Check | Statut | Détail |
|-------|--------|--------|
| Gemini API Key côté serveur uniquement | ✅ | `server.js` + `.env` |
| Mots de passe hashés bcrypt | ✅ | `password_hash()` PHP |
| SQL Injection protection | ✅ | PDO prepared statements |
| XSS protection | ✅ | `htmlspecialchars()` sur tout output |
| CSRF tokens | ✅ | Validés sur tous les formulaires |
| Session: HttpOnly cookie | ✅ | `PHPSESSID` inaccessible via JS |
| Session: SameSite=Strict | ✅ | Protection CSRF navigateur |
| Session timeout 1h | ✅ | `SESSION_TIMEOUT=3600` |
| `.env` git-ignoré | ✅ | Dans `.gitignore` |
| `node_modules/` git-ignoré | ✅ | Dans `.gitignore` |
| Audit logs | ✅ | Table `logs` — toutes actions tracées |
| HTTPS en production | ⚠️ | À configurer (SESSION_SECURE=true) |
| Rate limiting API | ⚠️ | Non implémenté — futur |

---

## 🎨 UCA Design System — Couleurs

```css
/* Palette principale UCA */
--uca-brun-terracotta: #8B4513;
--uca-brun-fonce:      #5C2E0E;
--uca-orange-chaud:    #C0622A;
--uca-beige-clair:     #F5E6D3;

/* États */
--priorite-haute:      #dc3545;  /* Rouge — Difficultés */
--priorite-moyenne:    #ffc107;  /* Jaune — Moyen */
--priorite-basse:      #28a745;  /* Vert — Maîtrisé */
```

---

## 📝 Résumé des localisations

| Élément | Fichier | Ligne / Clé |
|---------|---------|-------------|
| 🔑 Gemini API Key | `.env` → `config.php` | `GEMINI_API_KEY` |
| 🤖 Modèle Gemini | `server.js` | Ligne 58 |
| 🚪 Port API | `server.js` | Ligne 125 |
| 🗄️ Connexion BD | `config.php` | Lignes 4–10 |
| 📊 Schéma BD | `schema.sql` | Tout le fichier |
| 🎯 Dashboard | `dashboard.php` | — |
| 🔐 Auth | `auth.php` | `require_login()` |
| 🎨 Styles | `assets/style.css` | Tout le fichier |
| ✨ Animations | `assets/animations.css` | Tout le fichier |
| 💻 JS Client | `assets/app.js` | Tout le fichier |
| 🐳 Docker | `docker-compose.yml` | — |
| 📦 Deps Node | `package.json` | — |

---

**Dernière mise à jour:** 2 avril 2026  
**Version:** 2.2  
**Type:** Projet Académique — Université Cadi Ayyad  
**Statut:** ✅ Production Ready
