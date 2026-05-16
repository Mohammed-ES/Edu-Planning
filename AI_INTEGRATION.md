# AI Integration: Google Gemini & Open Router

The Edu-Planning application leverages Generative AI to create highly personalized, structured 7-day revision schedules for students based on their current academic progress, module difficulties, and upcoming exam dates.

## The Dual-Provider Fallback Strategy

To ensure high availability and reliability, the application implements a dual-provider strategy using both Google Gemini and Open Router. This logic is handled in `include/ai_api.php`.

### 1. Primary Provider: Google Gemini
-   **Model:** `gemini-2.5-flash`
-   **Mechanism:** The system first attempts to connect to the Gemini API using the `GEMINI_API_KEY`.
-   **Advantage:** Fast inference, highly capable of outputting structured JSON natively.

### 2. Fallback Provider: Open Router
-   **Model:** `gpt-3.5-turbo` (via Open Router API)
-   **Mechanism:** If the Gemini API fails (e.g., due to quota limits, network timeout, or invalid keys), the system automatically catches the error and switches to the Open Router API using `OPEN_ROUTER_API_KEY`.
-   **Advantage:** Acts as a safety net. Open Router provides access to numerous models, and we default to GPT-3.5-Turbo for reliable JSON generation.

## How It Works (The Workflow)

1.  **Data Collection:** The user selects the modules they want to study. The PHP backend gathers metadata (difficulty, progress, days until exam).
2.  **Prompt Engineering:** `include/ai_api.php` (`build_study_prompt()`) formats this data into a strict prompt instructing the AI to act as an expert study planner. It dictates the exact JSON structure required for the output.
3.  **API Call:**
    -   `call_ai_api_with_fallback()` is triggered.
    -   It attempts `call_gemini_api()`.
    -   If successful, it returns the parsed JSON.
    -   If it fails, it attempts `call_open_router_api()`.
4.  **Data Processing:** The returned JSON is validated. If it's valid, the backend saves the full JSON payload into the `study_plans` table.
5.  **Task Creation:** The JSON is parsed, and individual actionable tasks are created in the `study_plan_tasks` table so the user can track their daily progress in the dashboard.

## Configuring the APIs

To configure both APIs, update the `config.php` or your `.env` file:

```env
# Primary AI
GEMINI_API_KEY="your_gemini_api_key"

# Fallback AI
OPEN_ROUTER_API_KEY="your_open_router_api_key"
```

The system will automatically detect which keys are available and route traffic accordingly.
