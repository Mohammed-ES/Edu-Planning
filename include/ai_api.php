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
 * Call to OpenRouter API
 * @param string $prompt The prompt to send
 * @return array ['success' => bool, 'content' => string, 'error' => string|null]
 */
/**
 * Check whether a Gemini API key or local Gemini server is available.
 * Returns true when at least one mechanism is available.
 */
function check_gemini_config(): bool
{
    return (defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY));
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
 * Generates and saves a study plan
 * @param PDO $pdo
 * @param int $user_id
 * @param array $modules
 * @return array ['success' => bool, 'plan' => string|null, 'error' => string|null]
 */
function generate_and_save_study_plan(PDO $pdo, int $user_id, array $modules): array
{
    // Prefer server-based Gemini call which expects modules array
    $api_result = call_gemini_api($modules);

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
 * Retrieves the 5 last generated plans
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
        LIMIT 5
    ');
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
