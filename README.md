# Edu-Planning — Intelligent Academic Platform

Edu-Planning is a modern, AI-powered academic workspace engineered specifically for Université Cadi Ayyad (UCA) students. The platform replaces fragmented notes and scattered deadlines with a unified, intelligent dashboard that keeps students in complete control of their educational journey.

## 🌟 Key Features

*   **Smart Module Tracking:** Centralize coursework, monitor difficulty levels, track comprehension, and manage the academic pipeline in one intuitive dashboard.
*   **Adaptive AI Planning:** Generates highly personalized 7-day revision schedules using Google Gemini and Open Router (with automatic fallback), adapting to study pace and ensuring optimal focus for upcoming deadlines.
*   **Visual Analytics:** Interactive charts and progress indicators (powered by Chart.js) provide immediate insights into academic performance.
*   **Premium User Experience:** A dynamic UI with glassmorphism elements, custom mouse followers, 3D interactive elements (Three.js), and smooth CSS/JS animations for a high-end feel.
*   **Deadline Management:** Prioritizes upcoming exams and milestones to ensure focus is always directed where it matters most.
*   **Secure Profiles:** Total control over academic data with robust user authentication and personalized profile management.

## 🏗️ Architecture & Technology Stack

The project utilizes a decoupled client-server approach with a PHP-driven frontend/backend for the user portal and an isolated Node.js/Express service for AI interactions.

### Technologies Used

| Category | Technology | Usage |
| :--- | :--- | :--- |
| **Frontend Core** | HTML5, CSS3, Vanilla JS | Structure, styling, and client-side logic |
| **CSS Framework** | Bootstrap 5.3, Tailwind CSS | Responsive grid, utility classes, UI components |
| **Design System** | Glassmorphism, Custom CSS | Modern professional aesthetic (dark/gold) |
| **3D & Analytics** | Three.js, Chart.js | 3D object rendering, Progress charts |
| **Backend Core** | PHP 8+ | Routing, authentication, CRUD operations |
| **Database** | MySQL / MariaDB | Persistent storage via PDO |
| **AI Microservice**| Node.js, Express.js | API gateway for handling AI generation |
| **AI Providers** | Google Gemini, Open Router | Generative AI models (gemini-2.5-flash, gpt-3.5-turbo) |

### Frontend
*   **Core:** HTML5, CSS3, JavaScript (Vanilla JS for custom logic).
*   **Styling:** Custom CSS with a "Modern Professional" aesthetic (dark/gold accents, elegant typography), complemented by Bootstrap 5.3 for responsive grid layouts.
*   **Typography:** Playfair Display, Inter, Roboto, and Cormorant Garamond.
*   **Libraries:** 
    *   **Three.js:** For rendering 3D STL objects on the landing page.
    *   **Chart.js:** For rendering module and task progress analytics in the dashboard.
    *   **FontAwesome:** For vector icons.

### Backend (Core Application)
*   **Language:** PHP 8+
*   **Database:** MySQL / MariaDB (managed via PDO in `include/config.php` and `include/connectiondb.php`).
*   **Routing & Logic:** Procedural PHP spread across feature-specific endpoints:
    *   `index.php` - Landing page
    *   `dashboard.php` - Main user hub
    *   `module.php`, `modules/` - Module CRUD operations
    *   `planning.php`, `tasks.php`, `generate_plan.php` - Schedule management
    *   `login.php`, `register.php`, `logout.php` - Authentication (`include/auth.php`)

### AI Microservice
*   **Environment:** Node.js, Express.js (`server.js`)
*   **AI Integration:** `@google/generative-ai` SDK using the `gemini-2.5-flash` model.
*   **Security:** Internal authentication middleware requiring a Bearer token (`INTERNAL_API_SECRET`) to prevent unauthorized external requests. Rate limiting (max 10 requests per 15 mins per IP).
*   **Functionality:** Receives module metadata (difficulty, progress, exam dates) and returns a structured JSON 7-day study schedule with allocated priorities and session lengths.

## 📂 Project Structure

```text
c:\xampp\htdocs\Edu-Planning\
├── .env                # Environment variables (DB creds, Gemini API Key)
├── server.js           # Node.js API server for Gemini integration
├── package.json        # Node dependencies
├── index.php           # Public landing page
├── Welcome.php         # Intro animation screen
├── dashboard.php       # Protected user dashboard
├── module.php          # Module overview
├── planning.php        # Study plans overview
├── generate_plan.php   # AI Plan generation interface
├── tasks.php           # Task management
├── login.php           # User login
├── register.php        # User registration
├── include/            # Backend configuration & auth logic
│   ├── auth.php
│   ├── config.php
│   └── connectiondb.php
├── modules/            # Detailed module views (add, edit, view)
├── assets/             # Images, 3D icons (.stl)
├── css/                # Stylesheets (index.css, dashboard.css, etc.)
└── js/                 # Client-side scripts (app.js, dashboard.js, etc.)
```

## ⚙️ Installation & Setup

1. **Database Setup:** 
   Import the SQL schema to your MySQL/MariaDB server and configure the connection details in the `.env` file and/or `include/config.php`.
2. **Environment Variables:**
   Create a `.env` file based on `.env.example`:
   ```env
   GEMINI_API_KEY=your_gemini_api_key_here
   INTERNAL_API_SECRET=your_secure_random_string
   PORT=3001
   ```
3. **Node Dependencies:**
   Run `npm install` to install Express, dotenv, and the Google Generative AI SDK.
4. **Start the AI Server:**
   Run `npm start` (or `npm run dev` for watch mode) to launch the Gemini microservice on `http://127.0.0.1:3001`.
5. **Serve the PHP Application:**
   Host the directory using XAMPP/Apache. Ensure the server is pointed to the project root.
6. **Access:** Navigate to `http://localhost/Edu-Planning/`.
