<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/connectiondb.php';
require_once __DIR__ . '/include/ai_api.php';
require_login();

$user = current_user($pdo);
if (!$user) {
    header('Location: login.php');
    exit;
}

$user_id = (int)$user['id'];
$generated_plan = null;
$error_message = null;
$success_message = null;

$stmt = $pdo->prepare('SELECT id, module_name, difficulty, progress, exam_date, teacher, career_importance, understanding_level FROM modules WHERE user_id = ? ORDER BY exam_date ASC');
$stmt->execute([$user_id]);
$user_modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token.';
    } elseif (isset($_POST['generate_plan'])) {
        $selected_ids = isset($_POST['selected_modules']) ? array_filter(explode(',', $_POST['selected_modules'])) : [];
        $modules_to_plan = [];
        if (empty($selected_ids)) {
            $modules_to_plan = $user_modules; // Default to all if none specifically selected
        } else {
            foreach ($user_modules as $m) {
                if (in_array((string)$m['id'], $selected_ids)) {
                    $modules_to_plan[] = $m;
                }
            }
        }

        if (empty($modules_to_plan)) {
            $error_message = 'No modules selected. Add or select modules first.';
        } else {
            $result = generate_and_save_study_plan($pdo, $user_id, $modules_to_plan);
            if ($result['success']) {
                // Get the last inserted plan ID
                $plan_id_stmt = $pdo->prepare('SELECT LAST_INSERT_ID() as plan_id');
                $plan_id_stmt->execute();
                $plan_id_row = $plan_id_stmt->fetch(PDO::FETCH_ASSOC);
                $last_plan_id = $plan_id_row['plan_id'] ?? null;
                
                // Create tasks from the generated plan
                if ($last_plan_id) {
                    $plan_data = is_array($result['plan']) ? $result['plan'] : json_decode($result['plan'], true);
                    $tasks_result = create_tasks_from_plan($pdo, $last_plan_id, $user_id, $plan_data);
                    if (!$tasks_result['success']) {
                        error_log("Failed to create tasks: " . $tasks_result['error']);
                    }
                }
                
                $generated_plan = is_array($result['plan']) ? json_encode($result['plan'], JSON_PRETTY_PRINT) : $result['plan'];
                $success_message = 'Study plan generated successfully. <a href="tasks.php?plan=' . $last_plan_id . '" class="alert-link">View Tasks & Progress</a>';
            } else {
                $error_message = 'Generation failed: ' . $result['error'];
            }
        }
    } elseif (isset($_POST['delete_plan'])) {
        $del_id = (int)$_POST['plan_id'];
        $del_stmt = $pdo->prepare("DELETE FROM study_plans WHERE id = ? AND user_id = ?");
        if ($del_stmt->execute([$del_id, $user_id])) {
            $success_message = "Study plan deleted successfully.";
        } else {
            $error_message = "Failed to delete study plan.";
        }
    }
}

if (isset($_GET['view_plan'])) {
    $view_id = (int)$_GET['view_plan'];
    $vstmt = $pdo->prepare("SELECT generated_plan FROM study_plans WHERE id = ? AND user_id = ?");
    $vstmt->execute([$view_id, $user_id]);
    if ($row = $vstmt->fetch()) {
        $generated_plan = $row['generated_plan'];
    }
}

$previous_plans = get_recent_study_plans($pdo, $user_id);

// Count total plans for accurate plan numbering
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM study_plans WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_plans_count = (int)($stmt->fetch()['total'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Study Plan - Edu Planning</title>
    <link rel="icon" type="image/png" href="assets/images/universite-cadi-ayyad.png">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/generate_plan.css">
    <link rel="stylesheet" href="css/generate-plan-inline.css">
    <link rel="stylesheet" href="css/generate-plan-schedule.css">
</head>
<body>
<div class="wrapper">
    <nav class="sidebar">
        <div class="logo-area">
            <img src="assets/images/universite-cadi-ayyad.png" alt="Université Cadi Ayyad">
            <span>Edu Planning</span>
        </div>
        <ul class="components">
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="module.php"><i class="fas fa-book"></i> Modules</a></li>
            <li><a href="planning.php"><i class="fas fa-calendar"></i> Schedule</a></li>
            <li class="active"><a href="generate_plan.php"><i class="fas fa-wand-magic-sparkles"></i> AI Plan</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <div class="top-navbar">
            <h2 class="page-title m-0">AI Study Plan</h2>
            <div class="d-flex align-items-center gap-3">
                <span class="generate-plan-greeting">Hello, <?= htmlspecialchars($user['name']) ?></span>
                <div class="generate-plan-avatar">
                    <?= htmlspecialchars(strtoupper(substr($user['name'], 0, 1))) ?>
                </div>
            </div>
        </div>
        <div class="content-padding">
            <div class="mb-4">
                <h1 class="plan-title">Generate Your Plan</h1>
                <p class="plan-subtitle">Use AI to create a personalized study schedule based on your modules.</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= $success_message ?></div>
            <?php endif; ?>
            <?php if (!check_gemini_config()): ?>
                <div class="alert alert-danger"><i class="fas fa-circle-info me-2"></i>Please set <code>GEMINI_API_KEY</code> in <code>config.php</code>.</div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Left: Modules -->
                <div class="col-lg-4">
                    <div class="panel-card p-4 h-100 d-flex flex-column">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="fas fa-layer-group modules-panel-icon"></i>
                            <h3 class="m-0 modules-panel-title">Your Modules</h3>
                        </div>

                        <?php if (empty($user_modules)): ?>
                            <div class="empty-hero">
                                <div style="font-size:44px;color:var(--gold-primary);margin-bottom:10px;"><i class="fas fa-inbox"></i></div>
                                <div style="font-weight:800;color:var(--heading-color);margin-bottom:6px;">No modules found</div>
                                <div style="font-size:13px;">Add modules to generate your plan.</div>
                                <a class="btn btn-primary mt-3" href="modules/add.php"><i class="fas fa-plus me-2"></i>Add Module</a>
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-3 modules-list-container" id="modulesList">
                                <?php foreach ($user_modules as $m): ?>
                                    <?php
                                    $pill = $m['difficulty'] === 'HARD' ? 'pill-hard' : ($m['difficulty'] === 'MEDIUM' ? 'pill-medium' : 'pill-easy');
                                    ?>
                                    <div class="module-item selectable-module" onclick="toggleModule(this, <?= (int)$m['id'] ?>)">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <p class="module-item-title"><?= htmlspecialchars($m['module_name']) ?></p>
                                            <span class="pill <?= $pill ?>"><?= htmlspecialchars($m['difficulty']) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mt-2 module-meta-text">
                                            <span>Exam: <?= htmlspecialchars(date('M d', strtotime($m['exam_date']))) ?></span>
                                            <span>Prog: <?= (int)$m['progress'] ?>%</span>
                                        </div>
                                        <div class="progress-bg mt-2">
                                            <div class="progress-fill" style="width: <?= (int)$m['progress'] ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="modules-selected-info">
                                <span id="selectedCount">0</span> module(s) selected (default: all).
                            </div>

                            <form method="post" class="mt-3" id="planForm">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="selected_modules" id="selectedModules" value="">
                                <button class="btn btn-primary w-100" type="submit" name="generate_plan" <?= !check_gemini_config() ? 'disabled' : '' ?>>
                                    <i class="fas fa-wand-magic-sparkles me-2"></i>Generate AI Plan
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right: Plan -->
                <div class="col-lg-8">
                    <div class="panel-card p-4 h-100">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="fas fa-book-open schedule-panel-icon"></i>
                            <h3 class="m-0 schedule-panel-title">Your Study Schedule</h3>
                        </div>

                        <?php if ($generated_plan): ?>
                            <?php $plan_data = json_decode($generated_plan, true); ?>
                            <?php if (isset($plan_data['planning'])): ?>
                                <div class="timeline-container">
                                <?php foreach ($plan_data['planning'] as $day): ?>
                                    <div class="card mb-3 timeline-item">
                                        <div class="card-body">
                                            <h5 class="timeline-day-title">
                                                <i class="fas fa-calendar-day me-2"></i><?= htmlspecialchars($day['day']) ?>
                                            </h5>
                                            <p class="timeline-item-meta mb-2"><strong><i class="fas fa-book me-1"></i> Modules:</strong> <?= htmlspecialchars(implode(', ', $day['modules'])) ?></p>
                                            <p class="timeline-item-meta mb-2"><strong><i class="fas fa-clock me-1"></i> Duration:</strong> <?= htmlspecialchars($day['hours']) ?> hours</p>
                                            <div class="timeline-tips">
                                                <strong><i class="fas fa-lightbulb me-1"></i> Tips:</strong> <?= htmlspecialchars($day['tips']) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="plan-output"><pre><?= htmlspecialchars($generated_plan) ?></pre></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-hero" style="margin-top:10px;">
                                <div class="empty-hero-icon waiting">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <div class="empty-hero-title">Waiting for generation</div>
                                <div class="empty-hero-subtitle">Click the button on the left to create your personalized study plan.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($previous_plans)): ?>
                <div class="panel-card p-4 mt-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="fas fa-clock-rotate-left recent-plans-icon"></i>
                        <h3 class="m-0 recent-plans-title">Recent Plans</h3>
                    </div>

                    <div class="row g-3">
                        <?php foreach ($previous_plans as $idx => $plan): ?>
                            <?php $plan_number = $total_plans_count - $idx; ?>
                            <div class="col-md-4">
                                <div class="plan-card">
                                    <div class="plan-meta"><i class="fas fa-calendar me-1"></i><?= htmlspecialchars(date('M j, Y • H:i', strtotime($plan['created_at']))) ?></div>
                                    <h5 class="mt-2">Study Plan <?= (int)$plan_number ?></h5>
                                    <div class="d-flex gap-2 mt-3">
                                        <a href="generate_plan.php?view_plan=<?= $plan['id'] ?>" class="btn btn-outline-secondary flex-grow-1">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                        <a href="tasks.php?plan=<?= $plan['id'] ?>" class="btn btn-view-tasks flex-grow-1">
                                            <i class="fas fa-tasks me-1"></i>Tasks
                                        </a>
                                        <form method="POST" style="margin: 0;" class="delete-plan-form">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="delete_plan" value="1">
                                            <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                                            <button type="button" class="btn btn-outline-danger px-3" onclick="confirmDelete(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="js/generate_plan.js"></script>
    <script src="js/generate-plan-modules.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</html>
