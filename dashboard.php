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
                    <span style="font-weight: 500;">Hello, <?php echo $username; ?></span>
                    <div style="width:40px;height:40px;background:linear-gradient(135deg, var(--gold-primary), var(--gold-light));border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--heading-color);font-weight:bold;font-size:16px;">
                        <?php echo $initial; ?>
                    </div>
                </div>
            </div>

            <div class="content-padding">

                <!-- Stat Cards -->
                <div class="row" style="gap: 5px; margin-bottom: 30px; display: flex; flex-wrap: wrap;">
                    <div style="display: flex; flex: 0 0 calc(25% - 3.75px);">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-book-open"></i></div>
                            <div class="stat-value" data-count="<?php echo $total_modules; ?>">0</div>
                            <div class="stat-label">Total Modules</div>
                        </div>
                    </div>
                    <div style="display: flex; flex: 0 0 calc(25% - 3.75px);">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                            <div class="stat-value" data-count="<?php echo $avg_progress; ?>">0</div>
                            <div class="stat-label">Avg Progress %</div>
                        </div>
                    </div>
                    <div style="display: flex; flex: 0 0 calc(25% - 3.75px);">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                            <div class="stat-value" data-count="<?php echo $total_sessions; ?>">0</div>
                            <div class="stat-label">Planned Sessions</div>
                        </div>
                    </div>
                    <div style="display: flex; flex: 0 0 calc(25% - 3.75px);">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="stat-value" data-count="<?php echo $modules_completed; ?>">0</div>
                            <div class="stat-label">Completed Modules</div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row g-4 mb-5">
                    <!-- Doughnut Chart -->
                    <div class="col-lg-6">
                        <div class="diagram-card scroll-reveal">
                            <div class="section-header">
                                <div class="section-logo"><i class="fas fa-chart-pie"></i></div>
                                Module Mastery Overview
                            </div>
                            <canvas id="progressChart" style="max-height: 300px;"></canvas>
                        </div>
                    </div>

                    <!-- Progress bars -->
                    <div class="col-lg-6">
                        <div class="diagram-card scroll-reveal">
                            <div class="section-header">
                                <div class="section-logo"><i class="fas fa-tasks"></i></div>
                                Academic Milestones
                            </div>
                            <div style="padding: 16px 0;">
                                <div style="margin-bottom: 22px;">
                                    <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:14px;">
                                        <span style="font-weight:600; color:var(--heading-color);">Average module progress</span>
                                        <span style="font-weight:700; color:var(--gold-primary);"><?php echo $modules_pct; ?>%</span>
                                    </div>
                                    <div class="progress-bar-custom">
                                        <div class="progress-fill" style="background:linear-gradient(90deg,var(--gold-primary),var(--gold-light)); width:<?php echo $modules_pct; ?>%;"></div>
                                    </div>
                                </div>

                                <div style="margin-bottom: 22px;">
                                    <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:14px;">
                                        <span style="font-weight:600; color:var(--heading-color);">Planned sessions</span>
                                        <span style="font-weight:700; color:var(--gold-primary);"><?php echo $total_sessions; ?></span>
                                    </div>
                                    <div class="progress-bar-custom">
                                        <div class="progress-fill" style="background:linear-gradient(90deg,var(--gold-primary),var(--gold-mid)); width:<?php echo $sessions_pct; ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Dashboard Structure -->
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
                                <div class="text-center text-muted mt-4 p-3" style="border: 1px dashed var(--border-color); border-radius: 8px;">
                                    <i class="fas fa-check-double mb-2" style="font-size: 24px; color: var(--gold-light);"></i>
                                    <p class="m-0">No recent plans generated.</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush mt-3">
                                    <?php foreach($recent_plans as $idx => $plan): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center" style="border-bottom: 1px solid var(--border-color); padding: 12px 0; background: transparent;">
                                        <div>
                                            <h6 class="m-0" style="font-weight: 700; color: var(--heading-color);">Study Plan <?= $idx + 1 ?></h6>
                                            <small class="text-muted"><i class="fas fa-clock me-1"></i> <?= date('M j, Y • H:i', strtotime($plan['created_at'])) ?></small>
                                        </div>
                                        <a href="generate_plan.php?view_plan=<?= $plan['id'] ?>" class="btn btn-sm btn-outline-secondary" style="border-color: var(--border-color); color: var(--gold-primary);">
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
                                <div class="empty-state text-center mt-4">
                                    <i class="fas fa-inbox text-muted" style="font-size: 32px;"></i>
                                    <h4 class="mt-3" style="font-family: 'Playfair Display', serif; font-size: 20px; color: var(--heading-color); font-weight: 700;">No modules added</h4>
                                    <a href="modules/add.php" class="btn btn-primary mt-3 btn-sm">
                                        <i class="fas fa-plus me-1"></i>Add a module
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="row g-3 mt-2">
                                    <?php foreach (array_slice($modules, 0, 4) as $i => $mod): ?>
                                        <div class="col-sm-6">
                                            <div class="module-card p-3" style="border: 1px solid var(--border-color); border-radius: 8px; transition: all 0.2s;">
                                                <div class="module-title" style="font-weight: 700; font-size: 14px; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($mod['module_name']); ?></div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span style="font-size: 12px; color: var(--text-muted);"><?= (int)$mod['progress'] ?>%</span>
                                                    <a href="modules/view.php?id=<?php echo $mod['id']; ?>" style="font-size:12px; color:var(--gold-primary); font-weight:600; text-decoration:none;">
                                                        View <i class="fas fa-arrow-right" style="font-size:10px;"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if(count($modules) > 4): ?>
                                    <div class="text-center mt-3">
                                        <a href="module.php" style="font-size: 13px; color: var(--gold-primary); font-weight: 600; text-decoration: none;">View all modules <i class="fas fa-long-arrow-alt-right"></i></a>
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
    </script>
    <script src="js/dashboard.js"></script>

    <!-- App JS (includes all animations, welcome screen, counter setup) -->
    <script src="js/app.js"></script>
</body>
</html>


