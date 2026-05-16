# System Design: Edu-Planning

This document outlines the high-level architecture and system design of the Edu-Planning application.

## 1. System Architecture

Edu-Planning is built with a hybrid monolithic/microservice architecture:
1.  **Monolithic Core (PHP):** Handles all user-facing interactions, authentication, session management, and CRUD operations for modules and study plans.
2.  **AI Microservice (Node.js):** An isolated Node/Express application responsible for communicating with external AI providers safely, preventing timeout issues in PHP and keeping API keys secure.

## 2. Component Breakdown

### Frontend Layer
-   **Routing:** Handled procedurally via PHP files (`index.php`, `dashboard.php`, etc.).
-   **UI Presentation:** Utilizes a custom glassmorphism design system built with CSS3 and Bootstrap 5.3 for grid management.
-   **Interactive Elements:** Vanilla JavaScript manages DOM manipulation, custom cursor effects, and asynchronous requests.
-   **Data Visualization:** Chart.js is used to render real-time progress of modules and study plans. Three.js renders 3D elements on the landing page.

### Backend Layer (PHP)
-   **Authentication:** `include/auth.php` manages sessions.
-   **Database Abstraction:** `include/config.php` and `include/connectiondb.php` use PDO to connect to a MySQL/MariaDB database, protecting against SQL injection via prepared statements.
-   **AI Integration Manager:** `include/ai_api.php` acts as the bridge to the AI logic, managing the fallback strategy between Gemini and Open Router.

### AI Microservice Layer (Node.js)
-   **Express Server (`server.js`):** Receives structured data from the PHP backend.
-   **Internal Auth:** Validates an `INTERNAL_API_SECRET` to ensure only the PHP backend can make requests.
-   **Rate Limiting:** Prevents abuse by limiting AI generations per user/IP.

## 3. Data Model (Database Schema)

-   **Users Table:** Stores credentials and profile data.
-   **Modules Table:** Stores academic subjects, difficulty, exam dates, and progress metrics. Related to the User ID.
-   **Study Plans Table:** Stores the generated 7-day JSON plan from the AI. Related to the User ID.
-   **Study Plan Tasks Table:** Breaks down the JSON plan into individual, trackable tasks. Related to the Plan ID and User ID.

## 4. Security Considerations

-   **Authentication:** Session-based auth with secure password hashing.
-   **SQL Injection:** Mitigated via PDO prepared statements.
-   **API Key Protection:** External AI keys are stored securely in `.env` and never exposed to the frontend.
-   **Internal API Protection:** The Node.js service requires a Bearer token (`INTERNAL_API_SECRET`) and only listens on `127.0.0.1`.
