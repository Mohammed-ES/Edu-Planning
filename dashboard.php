<?php
require_once __DIR__ . '/include/auth.php';
require_login();

// Fetch stats
$user_id = $_SESSION['user_id'];

// 1. Total modules
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM modules WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_modules = $stmt->fetch()['total'];

$avg_progress = 0;

// 2. Planned sessions (stored revision plans)
$stmt = $pdo->prepare("SELECT COUNT(*) as sessions FROM study_plans WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_sessions = $stmt->fetch()['sessions'];

// 3. Days remaining (extracted from latest study_plans record if exists)
$days_remaining = 0;
if ($total_sessions > 0) {
    $stmt = $pdo->prepare("SELECT generated_plan FROM study_plans WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $latest_rec = $stmt->fetch();
    if ($latest_rec) {
        $rec_data = json_decode($latest_rec['generated_plan'], true);
        if (isset($rec_data['planning']) && is_array($rec_data['planning'])) {
            $last_day = end($rec_data['planning']);
            if (isset($last_day['date'])) {
                $max_date = $last_day['date'];
                $days_remaining = max(0, (strtotime($max_date) - time()) / (60 * 60 * 24));
                $days_remaining = floor($days_remaining);
            }
        }
    }
}

// Fetch modules
$stmt = $pdo->prepare("
    SELECT m.id, m.module_name, m.teacher, m.difficulty, m.career_importance, m.progress, m.understanding_level, m.exam_date
    FROM modules m
    WHERE m.user_id = ?
    ORDER BY m.created_at DESC
");
$stmt->execute([$user_id]);
$modules = $stmt->fetchAll();

// Count modules by progress buckets for dashboard visuals
$modules_completed = 0;
$modules_in_progress = 0;

// Fetch name
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$username = htmlspecialchars($user['name'] ?? 'User');
$initial = strtoupper(substr($username, 0, 1));

// Get all study plans with their progress
$stmt = $pdo->prepare("SELECT id, created_at FROM study_plans WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$all_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

$plans_data = [];
$plan_count = 0;
foreach ($all_plans as $plan) {
    $plan_count++;
    $stmt = $pdo->prepare('
        SELECT 
            SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed,
            COUNT(*) as total
        FROM study_plan_tasks
        WHERE plan_id = ? AND user_id = ?
    ');
    $stmt->execute([$plan['id'], $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $completed = (int)($result['completed'] ?? 0);
    $total = (int)($result['total'] ?? 0);
    $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;
    
    $plans_data[] = [
        'id' => $plan['id'],
        'name' => 'Plan ' . $plan_count,
        'percentage' => $percentage
    ];
}

// Prepare module progress data
$modules_progress_data = [];
foreach ($modules as $module) {
    $modules_progress_data[] = [
        'name' => htmlspecialchars($module['module_name']),
        'progress' => (int)$module['progress']
    ];
}

// Calculate percentages for progress bars
$total_progress = 0;
foreach ($modules as $module_stats) {
    $p = (int)$module_stats['progress'];
    $total_progress += $p;
    if ($p >= 100) {
        $modules_completed++;
    } elseif ($p > 0) {
        $modules_in_progress++;
    }
}
$avg_progress = $total_modules > 0 ? (int) floor($total_progress / $total_modules) : 0;

$modules_pct  = $avg_progress;
$sessions_pct = min(100, $total_sessions * 20);
$days_pct     = min(100, max(0, $days_remaining) * 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Edu-Planning</title>
    <link rel="icon" type="image/png" href="assets/images/universite-cadi-ayyad.png">
    <!-- Google Fonts - Enhanced Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js for progress chart -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/dashboard-inline.css">
</head>
<body>

    <!-- Welcome Screen -->
    <div id="welcome-screen" class="welcome-overlay" role="status" aria-label="Loading">
        <div class="welcome-content">
            <div class="welcome-logo-wrapper">
                <div class="ring ring-1"></div>
                <div class="ring ring-2"></div>
                <div class="ring ring-3"></div>
                <div class="welcome-logo-inner">
                    <img src="assets/images/universite-cadi-ayyad.png" alt="UCA">
                </div>
            </div>
            <h1 class="welcome-title">Welcome</h1>
            <div class="welcome-divider"></div>
            <p class="welcome-name"><?php echo $username; ?></p>
            <p class="welcome-subtitle">Your Edu-Planning academic space</p>
            <div class="welcome-progress-bar">
                <div class="welcome-progress-fill"></div>
            </div>
            <p class="welcome-loading-text">LOADING YOUR SPACE</p>
        </div>
    </div>

    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="logo-area">
                <img src="assets/images/universite-cadi-ayyad.png" alt="Université Cadi Ayyad">
                <span>Edu Planning</span>
            </div>
            <ul class="components">
                <li class="active"><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="module.php"><i class="fas fa-book"></i> Modules</a></li>
                <li><a href="planning.php"><i class="fas fa-calendar"></i> Schedule</a></li>
                <li><a href="generate_plan.php"><i class="fas fa-wand-magic-sparkles"></i> AI Plan</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-navbar">
                <h2 class="page-title m-0">Dashboard</h2>
                <div class="d-flex align-items-center gap-3">
                    <span class="navbar-user-greeting">Hello, <?php echo $username; ?></span>
                    <div class="navbar-avatar">
                        <?php echo $initial; ?>
                    </div>
                </div>
            </div>

            <div class="content-padding">

                <!-- Stat Cards -->
                <div class="stat-cards-row">
                    <div class="stat-card-wrapper">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-book-open"></i></div>
                            <div class="stat-value" data-count="<?php echo $total_modules; ?>">0</div>
                            <div class="stat-label">Total Modules</div>
                        </div>
                    </div>
                    <div class="stat-card-wrapper">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                            <div class="stat-value" data-count="<?php echo $avg_progress; ?>">0</div>
                            <div class="stat-label">Avg Progress %</div>
                        </div>
                    </div>
                    <div class="stat-card-wrapper">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                            <div class="stat-value" data-count="<?php echo $total_sessions; ?>">0</div>
                            <div class="stat-label">Planned Sessions</div>
                        </div>
                    </div>
                    <div class="stat-card-wrapper">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="stat-value" data-count="<?php echo $modules_completed; ?>">0</div>
                            <div class="stat-label">Completed Modules</div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row g-4 mb-5">
                    <!-- Module Progress Chart -->
                    <div class="col-lg-6">
                        <div class="diagram-card scroll-reveal">
                            <div class="section-header">
                                <div class="section-logo"><i class="fas fa-chart-bar"></i></div>
                                Module Progress
                            </div>
                            <canvas id="moduleProgressChart" class="chart-canvas"></canvas>
                        </div>
                    </div>

                    <!-- Progress bars -->
                    <div class="col-lg-6">
                        <div class="diagram-card scroll-reveal">
                            <div class="section-header">
                                <div class="section-logo"><i class="fas fa-tasks"></i></div>
                                Academic Milestones
                            </div>
                            <div class="progress-section">
                                <div class="progress-section-item">
                                    <div class="progress-labels">
                                        <span class="progress-label-left">Average module progress</span>
                                        <span class="progress-label-right"><?php echo $modules_pct; ?>%</span>
                                    </div>
                                    <div class="progress-bar-custom">
                                        <div class="progress-fill" style="background:linear-gradient(90deg,var(--gold-primary),var(--gold-light)); width:<?php echo $modules_pct; ?>%;"></div>
                                    </div>
                                </div>

                                <div class="progress-section-item">
                                    <div class="progress-labels">
                                        <span class="progress-label-left">Planned sessions</span>
                                        <span class="progress-label-right"><?php echo $total_sessions; ?></span>
                                    </div>
                                    <div class="progress-bar-custom">
                                        <div class="progress-fill" style="background:linear-gradient(90deg,var(--gold-primary),var(--gold-mid)); width:<?php echo $sessions_pct; ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Your Study Tasks Progress -->
                <div class="row g-4 mb-5">
                    <div class="col-lg-12">
                        <div class="diagram-card scroll-reveal">
                            <div class="section-header">
                                <div class="section-logo"><i class="fas fa-chart-line"></i></div>
                                Your Study Tasks Progress
                            </div>
                            <canvas id="tasksProgressChart" class="chart-canvas-large"></canvas>
                        </div>
                    </div>
                </div>
                <div class="row g-4 mt-2 scroll-reveal">
                    <!-- Recent Plans -->
                    <div class="col-lg-6">
                        <div class="diagram-card" style="height: 100%;">
                            <div class="section-header">
                                <div class="section-logo"><i class="fas fa-calendar-alt"></i></div>
                                Recent Plans
                            </div>
                            <?php 
                            $stmt = $pdo->prepare("SELECT id, created_at FROM study_plans WHERE user_id = ? ORDER BY created_at DESC LIMIT 4");
                            $stmt->execute([$user_id]);
                            $recent_plans = $stmt->fetchAll();
                            ?>
                            <?php if(empty($recent_plans)): ?>
                                <div class="text-center text-muted mt-4 p-3 empty-plans-state">
                                    <i class="fas fa-check-double mb-2 empty-state-icon"></i>
                                    <p class="m-0">No recent plans generated.</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush mt-3">
                                    <?php foreach($recent_plans as $idx => $plan): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center plan-item">
                                        <div>
                                            <h6 class="plan-item-title">Study Plan <?= $idx + 1 ?></h6>
                                            <small class="plan-item-date"><i class="fas fa-clock me-1"></i> <?= date('M j, Y • H:i', strtotime($plan['created_at'])) ?></small>
                                        </div>
                                        <a href="generate_plan.php?view_plan=<?= $plan['id'] ?>" class="btn btn-sm plan-view-btn">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Modules Overview -->
                    <div class="col-lg-6">
                        <div class="diagram-card" style="height: 100%;">
                            <div class="section-header">
                                <div class="section-logo"><i class="fas fa-book"></i></div>
                                My Modules
                            </div>
                            <?php if (empty($modules)): ?>
                                <div class="empty-modules-state mt-4">
                                    <i class="fas fa-inbox text-muted empty-modules-icon"></i>
                                    <h4 class="mt-3 empty-modules-title">No modules added</h4>
                                    <a href="modules/add.php" class="btn btn-primary mt-3 btn-sm">
                                        <i class="fas fa-plus me-1"></i>Add a module
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="module-cards-grid">
                                    <?php foreach (array_slice($modules, 0, 4) as $i => $mod): ?>
                                        <div>
                                            <div class="module-item p-3">
                                                <div class="module-title"><?php echo htmlspecialchars($mod['module_name']); ?></div>
                                                <div class="module-item-row">
                                                    <span class="module-progress-text"><?= (int)$mod['progress'] ?>%</span>
                                                    <a href="modules/view.php?id=<?php echo $mod['id']; ?>" class="module-view-link">
                                                        View <i class="fas fa-arrow-right module-arrow-icon"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if(count($modules) > 4): ?>
                                    <div class="text-center mt-3">
                                        <a href="module.php" class="modules-view-all-link">View all modules <i class="fas fa-long-arrow-alt-right"></i></a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div><!-- /content-padding -->
        </div><!-- /main-content -->
    </div><!-- /wrapper -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Progress Chart Script -->
    <script>
        const DASHBOARD_AVG_PROGRESS = <?php echo isset($avg_progress) ? $avg_progress : 0; ?>;
        const MODULE_PROGRESS_DATA = <?php echo json_encode($modules_progress_data); ?>;
        const TASKS_PROGRESS_DATA = <?php echo json_encode($plans_data); ?>;
    </script>
    <script src="js/dashboard.js"></script>

    <!-- App JS (includes all animations, welcome screen, counter setup) -->
    <script src="js/app.js"></script>
</body>
</html>


