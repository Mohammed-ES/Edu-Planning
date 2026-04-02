# 🏗️ Architecture Complète Edu-Planning

> Guide détaillé de l'architecture web et localisation des éléments importants

---

## 📊 Vue d'ensemble globale

```
┌────────────────────────────────────────────────────────────────────┐
│                          CLIENTS UTILISATEURS                      │
│                         (Navigateurs Web)                          │
└────────┬─────────────────────────────────────────────────┬─────────┘
         │ HTTP/HTTPS                                      │
         │                                                 │
    ┌────▼──────────────────────┐           ┌──────────────▼──────┐
    │   COUCHE PRESENTATION     │           │  COUCHE API/BACKEND │
    │  (PHP + Frontend Assets)  │           │   (Node.js Express) │
    │  Ports: 80, 8080, 8000    │           │   Port: 3001        │
    │                           │           │                     │
    │  ├─ index.php             │           │  ├─ server.js       │
    │  ├─ login.php             │           │  ├─ package.json    │
    │  ├─ dashboard.php         │           │  └─ node_modules/   │
    │  ├─ modules.php           │ REST API  │                     │
    │  ├─ planning.php  ◄───────┼─────────  ┤  POST /api/generate │
    │  ├─ profile.php           │           │  GET  /api/revision │
    │  └─ assets/               │           │  GET  /health       │
    │     ├─ app.js             │           │                     │
    │     ├─ style.css          │           └─────────┬───────────┘
    │     └─ animations.css     │                     │
    └──────────┬────────────────┘                     │
               │                                      │
               │ PDO/SQL                      Google API Call
               │                                      │
    ┌──────────▼────────────────┐         ┌──────────▼──────────┐
    │   COUCHE DONNÉES          │         │   SERVICES EXTERNES │
    │   (MySQL Database)        │         │                     │
    │   Port: 3307 (Docker)     │         │  Google Gemini API  │
    │   Port: 3306 (Local)      │         │  ├─ Modèle: 2.5     │
    │                           │         │  ├─ Flash           │
    │  ├─ users                 │         │  └─ Analyse IA      │
    │  ├─ modules               │         │                     │
    │  ├─ notes                 │         │  Google Calendar    │
    │  ├─ revision_plans        │         │  ( Future - Non   |
    │  │                        │         │   implémenté)       │
    │  ├─ ai_recommendations    │         │                     │
    │  └─ logs                  │         │  GitHub API         │
    │                           │         │  (MCP)              │
    └───────────────────────────┘         └─────────────────────┘
```

---

## 🔌 Architecture détaillée en 3 couches

### 1️⃣ COUCHE PRÉSENTATION (Frontend - PHP)

**Fichiers:** `/index.php`, `/login.php`, `/register.php`, `/dashboard.php`, `/modules.php`, `/planning.php`, `/recommendations.php`, `/profile.php`, `/logs.php`

**Ports:** 
- `80` — Apache local
- `8080` — Docker container
- `8000` — Alternative

**Technologies:**
- PHP 8.2 (serveur)
- HTML5 (structure)
- CSS3 (styles) — `assets/style.css`, `assets/animations.css`
- JavaScript ES6+ — `assets/app.js` (client-side)

**Fonctionnalités:**
- ✓ Rendu serveur (Server-Side Rendering)
- ✓ Gestion sessions PHP
- ✓ Authentification utilisateurs
- ✓ Interface utilisateur responsif
- ✓ Formulaires interactifs

**Flux requête utilisateur:**
```
Utilisateur clicks → HTML form → POST/GET → PHP handler → Query DB → Returns HTML
```

---

### 2️⃣ COUCHE API BACKEND (Node.js)

**Fichier:** `/server.js`

**Port:** `3001`

**Technologies:**
- Node.js 16+ LTS
- Express.js 5.2.1
- @google/generative-ai (SDK)

**Endpoints disponibles:**

| Endpoint | Méthode | Port | Fonction |
|----------|---------|------|----------|
| `/` | GET | 3001 | Status API |
| `/api/generate-plan` | POST | 3001 | Générer planning 7j |
| `/api/revision-plan/:module` | GET | 3001 | Planning par module |
| `/health` | GET | 3001 | Santé API |

**Flux requête API:**
```
Frontend (AJAX) → POST /api/generate-plan → Express Router → Gemini API → JSON Response
                  ├─ Modules data
                  └─ Analyse IA → Planning structuré
```

**Démarrage:**
```bash
cd C:\xampp\htdocs\Edu-Planning
npm start
# Output: 🚀 Gemini API started at http://localhost:3001
```

---

### 3️⃣ COUCHE DONNÉES (MySQL Database)

**Serveur:** MySQL 8.0

**Port:**
- `3307` — Docker
- `3306` — Local XAMPP

**Base de données:** `edu_planning`

**Identifiants:**
```
Host:     localhost
User:     root
Password: (À CONFIGURER dans .env)
Port:     3307 (Docker) ou 3306 (Local)
Database: edu_planning
```

**Tables:**

| Table | Lignes | Clé Primaire | Fonction |
|-------|--------|-------------|----------|
| `users` | ∞ | id | Comptes utilisateurs |
| `modules` | ∞ | id | Matières/sujets |
| `notes` | ∞ | id | Notes par module |
| `revision_plans` | ∞ | id | Plannings générés |
| `ai_recommendations` | ∞ | id | Historique recommandations |
| `logs` | ∞ | id | Audit/logs actions |

**Schéma:**
```
users(id, username, email, password_hash, role, created_at, updated_at)
  ├─ 1:N modules(id, user_id, module_name, created_at, updated_at)
  │   └─ 1:N notes(id, module_id, note_value, description, created_at)
  └─ 1:N revision_plans(id, user_id, plan_data[JSON], start_date, end_date)
```

---

## 📁 Arborescence avec localisation

```
C:\xampp\htdocs\Edu-Planning\
│
│                          📍 LOCALISATION CLÉS API ET SECRETS
│                          ═════════════════════════════════════
│
├── 🔐 config.php ◄─────────────── 📍 CLÉS API (non exposées)
│   │                              ├─ Line 11: GEMINI_API_KEY
│   │                              │   Valeur: À REMPLIR (voir .env)
│   │                              │
│   │                              └─ Connexion BD (à config)
│   │                                  ├─ DB_HOST: localhost
│   │                                  ├─ DB_PORT: 3307
│   │                                  ├─ DB_USER: root
│   │                                  └─ DB_PASS: (vide)
│   │
│   ├── config.php ◄───────── Inclus par tous les fichiers PHP
│   │                         Contient: $pdo (connexion MySQL)
│   │                                   logAction() (audit)
│   │
│   └── Utilisation:
│       require_once 'config.php';
│       // Accès: $pdo, GEMINI_API_KEY, etc.
│
├── 🔑 server.js ◄────────────────── 📍 CLÉS API (non exposées)
│   │                                ├─ Line 8: GoogleGenerativeAI()
│   │                                │   Valeur: À REMPLIR (via .env)
│   │                                │
│   │                                └─ PORT: 3001
│   │
│   ├── Routes:
│   │   ├─ GET  / ........................ Status API
│   │   ├─ POST /api/generate-plan ...... Générer planning
│   │   ├─ GET  /api/revision-plan/:mod. Planning par module
│   │   └─ GET  /health ................. Santé API
│   │
│   └── Dépendances: @google/generative-ai, express
│
├── 🔒 auth.php ◄─────────────────── Authentification PHP
│   │                                ├─ require_login()
│   │                                ├─ verify_password()
│   │                                └─ logAction($user_id, 'action')
│   │
│   └── Inclus par: dashboard.php, modules.php, planning.php, etc.
│
├── 🐘 auth-helper.php ◄────────── Fonctions helper auth
│   │                              ├─ Hash password
│   │                              ├─ Session management
│   │                              └─ Access control
│
├── ━━━━━━━━━━━━━━━━━ FICHIERS FRONTAUX PHP ━━━━━━━━━━━
│
├── index.php ◄───────────────── Page d'accueil
│   ├─ Contient: Présentation, formulaires de connexion/inscription
│   └─ Redirection: À login.php ou dashboard.php
│
├── login.php ◄──────────────── Page de connexion
│   ├─ Inclus: config.php, auth.php
│   ├─ POST /login → Valide identifiants
│   └─ Crée: $_SESSION['user_id']
│
├── register.php ◄───────────── Inscription nouvel utilisateur
│   ├─ POST register
│   ├─ Hash password avec password_hash()
│   └─ INSERT INTO users
│
├── logout.php ◄──────────────── Déconnexion
│   ├─ Détruit: $_SESSION
│   └─ Redirection: index.php
│
├── dashboard.php ◄───────────── 🎯 TABLEAU DE BORD PRINCIPAL
│   │                            ├─ Affiche statistiques
│   │                            │   ├─ Total modules
│   │                            │   ├─ Total notes
│   │                            │   ├─ Sessions planifiées
│   │                            │   └─ Jours restants
│   │                            │
│   │                            ├─ Liens rapides
│   │                            │   ├─ Ajouter un module
│   │                            │   ├─ Générer planning
│   │                            │   ├─ Voir recommandations
│   │                            │   └─ Mon profil
│   │                            │
│   │                            ├─ Lecture BDD
│   │                            │   ├─ SELECT COUNT(*) modules
│   │                            │   ├─ SELECT COUNT(*) notes
│   │                            │   └─ SELECT * FROM modules
│   │                            │
│   │                            └─ Inclus: config.php, auth.php
│
├── modules.php ◄────────────── Gestion modules/matières
│   │                          ├─ Affiche liste modules
│   │                          ├─ Ajouter/éditer/supprimer module
│   │                          ├─ Afficher notes par module
│   │                          ├─ Ajouter/éditer/supprimer note
│   │                          │
│   │                          └─ Requêtes SQL:
│   │                             ├─ SELECT * FROM modules WHERE user_id
│   │                             ├─ SELECT * FROM notes WHERE module_id
│   │                             ├─ INSERT INTO modules
│   │                             ├─ INSERT INTO notes
│   │                             └─ DELETE FROM notes/modules
│
├── planning.php ◄────────────── Affichage calendrier 
│   │                           ├─ Affiche une vue calendrier simple
│   │                           ├─ Navigation mois précédent/suivant
│   │                           ├─ Pas de génération ici
│   │                           ├─ Récupère dernière revision_plan
│   │                           └─ Affiche avec animations CSS
│
├── recommendations.php ◄──────── 🎯 GÉNÉRATION PLANNING IA
│   │                            ├─ Historique tous les plannings
│   │                            ├─ FORMULAIRE "Generate New Plan"
│   │                            ├── Sélection modules
│   │                            ├── Sélection notes optionnelles
│   │                            ├── Click "Generate" → API Gemini
│   │                            ├─ Suppression ancien planning
│   │                            └─ Affichage statistiques globales
│
├── profile.php ◄─────────────── Profil utilisateur
│   │                            ├─ Affiche données user
│   │                            ├─ Éditer profil
│   │                            └─ Changer mot de passe
│
├── logs.php ◄───────────────── Historique des actions
│   │                          ├─ SELECT * FROM logs WHERE user_id
│   │                          ├─ Timestamp - Action
│   │                          └─ Audit trail
│
├── health_check.php ◄───────── Vérification santé app
│   │                          ├─ Teste connexion BD
│   │                          ├─ Teste API Node.js
│   │                          └─ JSON response
│
├── ━━━━━━━━━━━━━━━━━ FICHIERS ASSETS (Frontend) ━━━━━━━━━━━━━
│
├── assets/
│   │
│   ├── app.js ◄──────────────── 📍 LOGIQUE JAVASCRIPT CLIENT
│   │   │                        ├─ AJAX calls
│   │   │                        ├─ POST /api/generate-plan
│   │   │                        ├─ Form validation
│   │   │                        ├─ DOM manipulation
│   │   │                        └─ Event listeners
│   │   │
│   │   └─ Utilisation:
│   │      document.getElementById('generate-btn').addEventListener('click', () => {
│   │        fetch('/api/generate-plan', { method: 'POST', body: JSON.stringify(data) })
│   │      })
│   │
│   ├── style.css ◄───────────── Styles principaux
│   │   ├─ UCA Design System colors
│   │   ├─ Responsive layout
│   │   ├─ Brun Terracotta: #8B4513
│   │   ├─ Brun Foncé: #5C2E0E
│   │   └─ Orange Chaud: #C0622A
│   │
│   ├── animations.css ◄──────── Animations fluides
│   │   ├─ Transitions
│   │   ├─ Keyframes
│   │   └─ Hover effects
│   │
│   └── images/
│       ├── universite-cadi-ayyad.png
│       └── (logos, icônes)
│
├── ━━━━━━━━━━━━━━━━━ FICHIERS CONFIGURATION & DÉPLOIEMENT ━━━━━━━
│
├── 🐳 docker-compose.yml ◄──── Orchestration Docker
│   │                           ├─ Service "web" (PHP 8.2 Apache)
│   │                           │  └─ Port 8080:80
│   │                           │
│   │                           ├─ Service "db" (MySQL 8.0)
│   │                           │  ├─ MYSQL_ROOT_PASSWORD: YOUR_PASSWORD_HERE
│   │                           │  ├─ MYSQL_DATABASE: edu_planning
│   │                           │  └─ Port 3307:3306
│   │                           │
│   │                           └─ Service "github-mcp"
│   │                              └─ Port 3000:8082
│   │
│   ├── package.json ◄────────── Dépendances Node.js
│   │   ├─ "dependencies": {
│   │   │    "express": "^5.2.1",
│   │   │    "@google/generative-ai": "^0.24.1"
│   │   │  }
│   │   │
│   │   └─ "scripts": { "start": "node server.js" }
│   │
│   ├── schema.sql ◄───────────── 📍 SCHÉMA BASE DE DONNÉES
│   │   │                         ├─ CREATE TABLE users
│   │   │                         ├─ CREATE TABLE modules
│   │   │                         ├─ CREATE TABLE notes
│   │   │                         ├─ CREATE TABLE revision_plans
│   │   │                         ├─ CREATE TABLE ai_recommendations
│   │   │                         └─ CREATE TABLE logs
│   │   │
│   │   └─ Importer:
│   │      mysql -u root edu_planning < schema.sql
│   │
│   └── .gitignore
│       ├─ node_modules/
│       ├─ config.php
│       └─ .env (si utilisé)
│
├── ━━━━━━━━━━━━━ FICHIERS TESTS & UTILITIES ━━━━━━━━━
│
├── gemini-test.js ◄─────────── Test API Gemini
│   └─ node gemini-test.js
│
├── list-models.js ◄──────────── Énumération modèles
│   └─ node list-models.js
│
├── api_gemini.php ◄─────────── (Legacy) Appels Gemini via PHP
│   └─ Garni pour compatibilité
│
├── ━━━━━ DOCUMENTATION & CONFIGURATION SYSTÈME ━━━━━
│
├── README.md ◄────────────────── Documentation principale
│   ├─ Installation
│   ├─ Configuration API
│   ├─ Guide d'utilisation
│   └─ Dépannage
│
├── UCA_Design_System.json ◄──── Système design academic
│   ├─ Palette couleurs
│   ├─ Typography
│   └─ Component specs
│
├── SOP_Nouvelle_Architecture.txt ◄─ Documentation architecture
│
├── context/
│   └─ schema.sql (copie référence)
│
└── ARCHITECTURE.md ◄────────────── 📍 CE FICHIER
    └─ Vue complète de l'architecture

```

---

## 🔑 Localisation des éléments importants

### 1️⃣ Clés API Google Gemini

**Fichier 1: `/config.php` (Frontend PHP)**
```php
Line 11:
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY'));
// À charger depuis .env via dotenv
```

**Fichier 2: `/server.js` (Backend Node.js)**
```javascript
Line 8:
const genAI = new GoogleGenerativeAI(process.env.GEMINI_API_KEY);

Line 160:
const MODEL = "gemini-2.5-flash";
```

**⚠️ Sécurité:**
- ✗ Ne JAMAIS committer les clés dans Git
- ✗ Ne JAMAIS les exposer au client
- ✓ Garder côté serveur Node.js
- ✓ Utiliser `.env` pour les variables sensibles

---

### 2️⃣ Connexion Base de Données

**Fichier: `/config.php` (Centralised)**

```php
Lines 4-7:
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3307');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'edu_planning');

// Valeurs chargées depuis .env (git-ignored)
```

**Interface d'accès:**
```bash
# Local (XAMPP)
mysql -h localhost -P 3306 -u root

# Docker
mysql -h localhost -P 3307 -u root -p
# Mot de passe (docker-compose): À CONFIGURER dans docker-compose.yml

# Tables
mysql> USE edu_planning;
mysql> SHOW TABLES;
```

---

### 3️⃣ Identifiants de service (Docker)

**Fichier: `/docker-compose.yml`**

```yaml
Services:
├─ web (PHP Apache)
│  ├─ Port: 8080:80
│  └─ Accès: http://localhost:8080
│
├─ db (MySQL 8.0)
│  ├─ Port: 3307:3306
│  ├─ Root password: À CONFIGURER
│  ├─ Database: edu_planning
│  └─ Credentials: Dans .env (git-ignored)
│
└─ Voir docker-compose.yml pour les services
   (ne pas exposer les mots de passe)
```

---

### 4️⃣ Ports et Accès

| Service | Port Local | Port Docker | Description |
|---------|-----------|-----------|-----------|
| Frontend PHP | 80 | 8080 | XAMPP/Apache |
| Backend API | 3001 | 3001 | Node.js Express |
| Database | 3306 | 3307 | MySQL 8.0 |


**Accès complets:**

```
Frontend:  http://localhost/Edu-Planning/ (XAMPP)
           http://localhost:8080 (Docker)
           
Backend:   http://localhost:3001
           http://localhost:3001/health
           http://localhost:3001/api/generate-plan
           
Database:  localhost:3306 (local) / localhost:3307 (Docker)
           user: root
           database: edu_planning
```

---

## 🔄 Flux de données complet

### Workflow: Générer un Planning

```
ÉTAPE 1: USER INTERACTION
        │
        └─→ dashboard.php ← Utilisateur clique "Générer planning"
            │
            ├─ Récupère modules sélectionnés
            └─ Click EventListener → app.js

ÉTAPE 2: FRONTEND (app.js)
        │
        └─→ Prepare data JSON:
            {
              "modules": [
                {"name": "Math", "notes": "Difficultés intégrales"},
                {"name": "Physics", "notes": "Bonne compréhension"}
              ]
            }
            │
            └─ AJAX POST /api/generate-plan (http://localhost:3001)

ÉTAPE 3: API BACKEND (server.js)
        │
        └─→ POST /api/generate-plan
            ├─ Reçoit modules JSON
            ├─ Valide format
            │
            └─→ Construit PROMPT Gemini:
                "Analyze modules and generate 7-day schedule..."
                │
                └─ Envoie au Google Gemini API

ÉTAPE 4: ANALYSE IA (Google Gemini)
        │
        └─→ Claude analyse:
            ├─ Extrait difficultés (difficult → haute priorité)
            ├─ Extrait points forts (good → basse priorité)
            ├─ Génère 7 jours structurés
            │
            └─ Retourne JSON:
                {
                  "planning": [
                    {
                      "jour": 1,
                      "date": "2026-03-30",
                      "sessions": [...]
                    },
                    ...7 objets
                  ]
                }

ÉTAPE 5: TRAITEMENT RÉPONSE (server.js)
        │
        └─→ Nettoie markdown si présent
            ├─ Parse JSON
            ├─ Valide structure
            │
            └─→ Retourne res.json({success: true, data: {...}})

ÉTAPE 6: AFFICHAGE FRONTEND (app.js + planning.php)
        │
        └─→ Reçoit réponse JSON
            ├─ Affiche les sessions par jour
            ├─ Couleurs selon priorité
            ├─ Animations CSS
            │
            └─→ ⏳ Bouton "Exporter vers Google Calendar" (Non implémenté - Futur)

ÉTAPE 7: SAUVEGARDE (config.php + MySQL)
        │
        └─→ INSERT INTO revision_plans
            ├ user_id
            ├ plan_data (JSON entier)
            ├ start_date
            └ end_date
                └ SAVED: Historique conservé dans BD
```

---

## 📊 Tables MySQL détaillées

### users

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student','admin') DEFAULT 'student',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

Index: username, email
```

Utilisation: Authentification, Session $_SESSION['user_id']

---

### modules

```sql
CREATE TABLE modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module_name VARCHAR(150) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

Index: user_id
```

Utilisation: Stocke les matières de chaque étudiant

---

### notes

```sql
CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    note_value TEXT NOT NULL,  ← Observations de l'étudiant
    description VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
);

Index: module_id, module_id+created_at
```

Utilisation: Analyses par Gemini pour évaluer priorité

---

### revision_plans

```sql
CREATE TABLE revision_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_data JSON NOT NULL,    ← Planning complet structuré
    start_date DATE,
    end_date DATE,
    used_note_ids JSON,         ← [1, 3, 5, ...]
    selected_modules JSON,      ← ["Math", "Physics", ...]
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

Index: user_id, user_id+created_at (DESC)
```

Utilisation: Historique plannings générés

---

### ai_recommendations

```sql
CREATE TABLE ai_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    recommendation JSON NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

Utilisation: Backup recommandations IA

---

### logs

```sql
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,  ← 'login', 'module_added', 'plan_generated'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

Index: user_id
```

Utilisation: Audit trail - toutes les actions tracées

---

## 🚀 Configuration de démarrage

### Démarrage local (XAMPP)

```bash
# 1. XAMPP Control Panel
   ├─ Apache: START
   └─ MySQL: START

# 2. Terminal - Démarrer API Node.js
   cd C:\xampp\htdocs\Edu-Planning
   npm start

# 3. Navigateur
   http://localhost/Edu-Planning/
   http://localhost:3001/health  (vérifier API)
```

### Démarrage Docker

```bash
# 1. Terminal
   cd C:\xampp\htdocs\Edu-Planning
   docker-compose up -d

# 2. Vérifier services
   docker-compose ps

# 3. Accès
   Frontend:  http://localhost:8080
   Backend:   http://localhost:3001
   Database:  localhost:3307
```

### Variables d'environnement

**À créer: `.env.local` (git ignored)**

```bash
# ⚠️ NE JAMAIS COMMITTER CE FICHIER DANS GIT!
# Créer .env.local (git-ignored)

# Google APIs
GEMINI_API_KEY=YOUR_API_KEY_HERE

# Node.js API
NODE_ENV=development
API_PORT=3001
API_HOST=localhost

# Base de données
DB_HOST=localhost
DB_PORT=3307
DB_USER=root
DB_PASS=YOUR_PASSWORD_HERE
DB_NAME=edu_planning

# Session
SESSION_TIMEOUT=3600
SESSION_SECURE=false
```

---

## 🔒 Sécurité - Checklist

- [ ] Clé Gemini = jamais en client
- [ ] Mot de passe BD = hashé (config.php)
- [ ] HTTPS en production
- [ ] CORS configuré si cross-domain
- [ ] SQL Injection ← Prepared statements (PDO)
- [ ] XSS ← htmlspecialchars() sur affichage
- [ ] CSRF ← Tokens validation
- [ ] Rate limiting ← API throttling
- [ ] Logs audit = tous les accès tracés

---

## 📝 Résumé des localisations clés

| Élément | Fichier | Ligne | Type |
|---------|---------|-------|------|
| 🔑 Gemini API Key | .env | N/A | Environment |
| 🔄 Model Gemini | server.js | 160 | JavaScript |
| 🔄 Connexion BD | config.php | 4-7 | PHP |
| 📊 Schéma BD | schema.sql | N/A | SQL |
| 🎨 Design System | UCA_Design_System.json | N/A | JSON |
| 🚀 Router API | server.js | 30-150 | JavaScript |
| 🎯 Dashboard | dashboard.php | N/A | PHP |
| 🔐 Authentification | auth.php | N/A | PHP |
| 🎨 Styles | assets/style.css | N/A | CSS |
| ✨ Animations | assets/animations.css | N/A | CSS |
| 💻 Frontend Logic | assets/app.js | N/A | JavaScript |
| 🐳 Docker Config | docker-compose.yml | N/A | YAML |
| 📦 Dépendances | package.json | N/A | JSON |

---

**Dernière mise à jour:** 29 mars 2026  
**Type:** Academic Project - Université Cadi Ayyad  
**Statut:** Production Ready ✓
