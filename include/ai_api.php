<?php
/**
 * ai_api.php — Gemini API integration for study plan generation
 */

require_once __DIR__ . '/config.php';

/**
 * Builds a structured prompt from modules
 * @param array $modules The user's modules
 * @return string Formatted prompt for AI
 */
function build_study_prompt(array $modules): string
{
    if (empty($modules)) {
        return "Create a general study plan for a student with balanced revisions over 7 days. Return ONLY valid JSON format.";
    }

    $prompt = "You are an expert study planner. Create a detailed 7-day revision plan for the student with these modules:\n\n";

    foreach ($modules as $module) {
        $exam_date = new DateTime($module['exam_date']);
        $today = new DateTime();
        $days_until_exam = $today->diff($exam_date)->days;

        $prompt .= "- **{$module['module_name']}**\n";
        $prompt .= "  Teacher: {$module['teacher']}\n";
        $prompt .= "  Difficulty: {$module['difficulty']}\n";
        $prompt .= "  Career Importance: {$module['career_importance']}\n";
        $prompt .= "  Progress: {$module['progress']}%\n";
        $prompt .= "  Understanding Level: {$module['understanding_level']}\n";
        $prompt .= "  Exam in: {$days_until_exam} days\n\n";
    }

    $prompt .= "INSTRUCTIONS:\n";
    $prompt .= "1. Top priority: Modules with the closest exam date\n";
    $prompt .= "2. HARD and LOW PROGRESS modules require more time\n";
    $prompt .= "3. HIGH importance modules must be fully covered\n";
    $prompt .= "4. You MUST return ONLY a valid JSON object matching this schema exactly:\n";
    $prompt .= "   {\"planning\": [ {\"day\": \"Day 1\", \"date\": \"YYYY-MM-DD\", \"modules\": [\"Module Name 1\", \"Module Name 2\"], \"hours\": 4, \"tips\": \"...\"} ] }\n";
    $prompt .= "   Provide 7 items in the planning array (Day 1 to Day 7). Start Day 1 with tomorrow's date.\n";
    $prompt .= "5. The plan must be REALISTIC and feasible in 7 days\n";

    return $prompt;
}

/**
 * Check whether a Gemini API key or local Gemini server is available.
 * Returns true when at least one mechanism is available.
 */
function check_gemini_config(): bool
{
    return (defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY));
}

/**
 * Check whether an Open Router API key is available.
 */
function check_open_router_config(): bool
{
    return (defined('OPEN_ROUTER_API_KEY') && !empty(OPEN_ROUTER_API_KEY));
}

/**
 * Call Open Router API to generate a plan.
 * Returns ['success'=>bool, 'data'=>array|null, 'error'=>string|null]
 */
function call_open_router_api(array $modules): array
{
    if (!defined('OPEN_ROUTER_API_KEY') || empty(OPEN_ROUTER_API_KEY)) {
        return ['success' => false, 'data' => null, 'error' => 'OPEN_ROUTER_API_KEY is not configured in config.php'];
    }

    $url = 'https://openrouter.ai/api/v1/chat/completions';

    $prompt = build_study_prompt($modules);

    $payload = [
        "model" => "gpt-3.5-turbo",
        "messages" => [
            [
                "role" => "user",
                "content" => $prompt
            ]
        ],
        "temperature" => 0.7
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPEN_ROUTER_API_KEY,
        ],
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        error_log("Open Router API cURL Error: {$curl_error}");
        return ['success' => false, 'data' => null, 'error' => "Connection error to Open Router API: {$curl_error}"];
    }

    $data = json_decode($response, true);
    if ($http_code !== 200) {
        $err = $data['error']['message'] ?? 'Unknown Open Router API error';
        error_log("Open Router API Error: " . json_encode($data));
        return ['success' => false, 'data' => null, 'error' => (string)$err];
    }

    if (isset($data['choices'][0]['message']['content'])) {
        $json_str = $data['choices'][0]['message']['content'];
        
        $json_str = preg_replace('/^```json\s*/i', '', $json_str);
        $json_str = preg_replace('/```$/', '', trim($json_str));
        
        $plan_data = json_decode($json_str, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return ['success' => true, 'data' => $plan_data, 'error' => null];
        } else {
            return ['success' => false, 'data' => null, 'error' => 'Failed to parse AI response as JSON from Open Router'];
        }
    }

    return ['success' => false, 'data' => null, 'error' => 'Invalid response structure from Open Router API'];
}

/**
 * Call Gemini API directly to generate a plan.
 * Returns ['success'=>bool, 'data'=>array|null, 'error'=>string|null]
 */
function call_gemini_api(array $modules): array
{
    if (!defined('GEMINI_API_KEY') || empty(GEMINI_API_KEY)) {
        return ['success' => false, 'data' => null, 'error' => 'GEMINI_API_KEY is not configured in config.php'];
    }

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . GEMINI_API_KEY;

    $prompt = build_study_prompt($modules);

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ],
        "generationConfig" => [
            "responseMimeType" => "application/json"
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        error_log("Gemini API cURL Error: {$curl_error}");
        return ['success' => false, 'data' => null, 'error' => "Connection error to Gemini API: {$curl_error}"];
    }

    $data = json_decode($response, true);
    if ($http_code !== 200) {
        $err = $data['error']['message'] ?? 'Unknown Gemini API error';
        return ['success' => false, 'data' => null, 'error' => (string)$err];
    }

    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        $json_str = $data['candidates'][0]['content']['parts'][0]['text'];
        
        $json_str = preg_replace('/^```json\s*/i', '', $json_str);
        $json_str = preg_replace('/```$/', '', trim($json_str));
        
        $plan_data = json_decode($json_str, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return ['success' => true, 'data' => $plan_data, 'error' => null];
        } else {
            return ['success' => false, 'data' => null, 'error' => 'Failed to parse AI response as JSON'];
        }
    }

    return ['success' => false, 'data' => null, 'error' => 'Invalid response structure from Gemini API'];
}

/**
 * Call AI API with automatic fallback from Gemini to Open Router
 * Tries Gemini first, and if it fails (quota exceeded or other error), automatically tries Open Router
 * Returns ['success'=>bool, 'data'=>array|null, 'error'=>string|null, 'api_used'=>string]
 */
function call_ai_api_with_fallback(array $modules): array
{
    $api_used = null;
    
    // Try Gemini first
    if (check_gemini_config()) {
        error_log("Attempting to call Gemini API");
        $gemini_result = call_gemini_api($modules);
        
        if ($gemini_result['success']) {
            error_log("Gemini API succeeded");
            return [
                'success' => true,
                'data' => $gemini_result['data'],
                'error' => null,
                'api_used' => 'Gemini'
            ];
        }
        
        error_log("Gemini API failed: " . ($gemini_result['error'] ?? 'Unknown error'));
        // If Gemini fails (quota, invalid key, etc.), try fallback
    }
    
    // Fallback to Open Router
    if (check_open_router_config()) {
        error_log("Attempting to call Open Router API as fallback");
        $open_router_result = call_open_router_api($modules);
        
        if ($open_router_result['success']) {
            error_log("Open Router API succeeded");
            return [
                'success' => true,
                'data' => $open_router_result['data'],
                'error' => null,
                'api_used' => 'Open Router'
            ];
        }
        
        error_log("Open Router API failed: " . ($open_router_result['error'] ?? 'Unknown error'));
        return [
            'success' => false,
            'data' => null,
            'error' => 'Both Gemini and Open Router APIs failed. ' . ($open_router_result['error'] ?? 'Unknown error'),
            'api_used' => null
        ];
    }
    
    // No fallback available
    return [
        'success' => false,
        'data' => null,
        'error' => 'No API keys configured. Please set GEMINI_API_KEY or OPEN_ROUTER_API_KEY in config.php',
        'api_used' => null
    ];
}

/**
 * Generates and saves a study plan
 * @param PDO $pdo
 * @param int $user_id
 * @param array $modules
 * @return array ['success' => bool, 'plan' => string|null, 'error' => string|null]
 */
function generate_and_save_study_plan(PDO $pdo, int $user_id, array $modules): array
{
    // Use AI API with automatic fallback
    $api_result = call_ai_api_with_fallback($modules);

    if (!$api_result['success']) {
        return [
            'success' => false,
            'plan' => null,
            'error' => $api_result['error'],
        ];
    }

    // The server returns a structured plan array. Store as JSON text in DB.
    $plan_array = $api_result['data'];
    $generated_plan_json = json_encode($plan_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    // Save to database
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO study_plans (user_id, generated_plan, created_at) VALUES (?, ?, NOW())'
        );
        $stmt->execute([$user_id, $generated_plan_json]);

        return [
            'success' => true,
            'plan' => $generated_plan_json,
            'error' => null,
        ];

    } catch (PDOException $e) {
        error_log('DB Error saving study plan: ' . $e->getMessage());
        return [
            'success' => false,
            'plan'    => null,
            'error'   => 'Plan generated but could not be saved. Please try again.',
        ];
    }
}

/**
 * Retrieves the 50 last generated plans
 * @param PDO $pdo
 * @param int $user_id
 * @return array Generated plans
 */
function get_recent_study_plans(PDO $pdo, int $user_id): array
{
    $stmt = $pdo->prepare('
        SELECT id, generated_plan, created_at
        FROM study_plans
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 50
    ');
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Creates individual tasks from a study plan
 * @param PDO $pdo
 * @param int $plan_id
 * @param int $user_id
 * @param array $plan_data The parsed plan JSON
 * @return array ['success' => bool, 'error' => string|null]
 */
function create_tasks_from_plan(PDO $pdo, int $plan_id, int $user_id, array $plan_data): array
{
    try {
        if (!isset($plan_data['planning']) || !is_array($plan_data['planning'])) {
            return ['success' => false, 'error' => 'Invalid plan structure'];
        }

        $stmt = $pdo->prepare('
            INSERT INTO study_plan_tasks 
            (plan_id, user_id, day_number, day_name, task_date, modules, hours, tips, completed)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
        ');

        foreach ($plan_data['planning'] as $index => $day) {
            $day_number = $index + 1;
            $day_name = $day['day'] ?? "Day {$day_number}";
            $task_date = $day['date'] ?? date('Y-m-d', strtotime("+{$index} days"));
            $modules = json_encode($day['modules'] ?? []);
            $hours = (int)($day['hours'] ?? 0);
            $tips = $day['tips'] ?? '';

            $stmt->execute([
                $plan_id,
                $user_id,
                $day_number,
                $day_name,
                $task_date,
                $modules,
                $hours,
                $tips
            ]);
        }

        return ['success' => true, 'error' => null];

    } catch (PDOException $e) {
        error_log('DB Error creating tasks: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to create tasks from plan'];
    }
}

/**
 * Retrieves tasks for a specific study plan
 * @param PDO $pdo
 * @param int $plan_id
 * @param int $user_id
 * @return array Tasks for the plan
 */
function get_plan_tasks(PDO $pdo, int $plan_id, int $user_id): array
{
    $stmt = $pdo->prepare('
        SELECT id, day_number, day_name, task_date, modules, hours, tips, completed, completed_at
        FROM study_plan_tasks
        WHERE plan_id = ? AND user_id = ?
        ORDER BY day_number ASC
    ');
    $stmt->execute([$plan_id, $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Marks a task as completed or incomplete
 * @param PDO $pdo
 * @param int $task_id
 * @param int $user_id
 * @param bool $completed
 * @return array ['success' => bool, 'error' => string|null]
 */
function toggle_task_completion(PDO $pdo, int $task_id, int $user_id, bool $completed): array
{
    try {
        $stmt = $pdo->prepare('
            UPDATE study_plan_tasks 
            SET completed = ?, completed_at = ?
            WHERE id = ? AND user_id = ?
        ');
        
        $completed_at = $completed ? date('Y-m-d H:i:s') : null;
        $stmt->execute([$completed ? 1 : 0, $completed_at, $task_id, $user_id]);

        return ['success' => true, 'error' => null];

    } catch (PDOException $e) {
        error_log('DB Error toggling task: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to update task'];
    }
}

/**
 * Calculates progress percentage for a plan
 * @param PDO $pdo
 * @param int $plan_id
 * @param int $user_id
 * @return array ['completed' => int, 'total' => int, 'percentage' => int]
 */
function get_plan_progress(PDO $pdo, int $plan_id, int $user_id): array
{
    $stmt = $pdo->prepare('
        SELECT 
            SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed,
            COUNT(*) as total
        FROM study_plan_tasks
        WHERE plan_id = ? AND user_id = ?
    ');
    $stmt->execute([$plan_id, $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $completed = (int)($result['completed'] ?? 0);
    $total = (int)($result['total'] ?? 0);
    $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

    return [
        'completed' => $completed,
        'total' => $total,
        'percentage' => $percentage
    ];
}

?>
