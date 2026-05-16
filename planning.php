<?php
require_once __DIR__ . '/include/auth.php';
require_login();

$user_id = $_SESSION['user_id'];

// Fetch name for display only
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$username = htmlspecialchars($user['name'] ?? 'User');

// Fetch all exams for the user
$stmt = $pdo->prepare("
    SELECT id, module_name, teacher, exam_date, difficulty, career_importance, progress 
    FROM modules 
    WHERE user_id = ? 
    ORDER BY exam_date ASC
");
$stmt->execute([$user_id]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group exams by month for easier display
$exams_by_month = [];
foreach ($exams as $exam) {
    $exam_month = date('Y-m', strtotime($exam['exam_date']));
    if (!isset($exams_by_month[$exam_month])) {
        $exams_by_month[$exam_month] = [];
    }
    $exams_by_month[$exam_month][] = $exam;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revision Schedule - Edu-Planning</title>
    <link rel="icon" type="image/png" href="assets/images/universite-cadi-ayyad.png">
    <!-- Google Fonts - Enhanced Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/planning.css">
    <link rel="stylesheet" href="css/planning-modal.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="logo-area">
                <img src="assets/images/universite-cadi-ayyad.png" alt="Université Cadi Ayyad">
                <span>Edu Planning</span>
            </div>
            <ul class="components">
                <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="module.php"><i class="fas fa-book"></i> Modules</a></li>
                <li class="active"><a href="planning.php"><i class="fas fa-calendar"></i> Schedule</a></li>
                <li><a href="generate_plan.php"><i class="fas fa-wand-magic-sparkles"></i> AI Plan</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-navbar">
                <h2 class="page-title m-0">Revision Schedule</h2>
                <div class="d-flex align-items-center gap-3">
                    <span style="font-weight: 500;">Hello, <?php echo htmlspecialchars($username); ?></span>
                    <div style="width:40px;height:40px;background:linear-gradient(135deg, var(--gold-primary), var(--gold-light));border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--heading-color);font-weight:bold;font-size:16px;">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                </div>
            </div>

            <div class="content-padding">
                <!-- Calendar Section -->
                <div class="calendar-card">
                    <h3 style="font-family: 'Playfair Display', serif; color: var(--heading-color); font-weight: 800; margin-bottom: 28px; font-size: 28px;">
                        <i class="fas fa-calendar-alt me-3" style="color: var(--gold-primary);"></i>My Revision Calendar
                    </h3>

                    <!-- Calendar Month Selector -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; padding-bottom: 16px; border-bottom: 2px solid #e8e0d0;">
                        <h4 id="monthYear" style="font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; color: var(--heading-color); margin: 0;"></h4>
                        <div style="display: flex; gap: 10px;">
                            <button onclick="previousMonth()" style="background: linear-gradient(135deg, var(--heading-color), #7a4d2a); color: white; border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='translateY(0)';">
                                <i class="fas fa-chevron-left me-2"></i>Previous
                            </button>
                            <button onclick="nextMonth()" style="background: linear-gradient(135deg, var(--heading-color), #7a4d2a); color: white; border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='translateY(0)';">
                                Next<i class="fas fa-chevron-right ms-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Calendar Grid -->
                    <div style="background: white; padding: 28px; border-radius: 14px; box-shadow: 0 8px 20px rgba(107,68,35,0.1);">
                        <!-- Days of Week Header -->
                        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 0; margin-bottom: 8px;">
                            <div style="text-align: center; padding: 12px; font-weight: 700; color: var(--heading-color); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid var(--gold-primary);">Sun</div>
                            <div style="text-align: center; padding: 12px; font-weight: 700; color: var(--heading-color); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid var(--gold-primary);">Mon</div>
                            <div style="text-align: center; padding: 12px; font-weight: 700; color: var(--heading-color); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid var(--gold-primary);">Tue</div>
                            <div style="text-align: center; padding: 12px; font-weight: 700; color: var(--heading-color); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid var(--gold-primary);">Wed</div>
                            <div style="text-align: center; padding: 12px; font-weight: 700; color: var(--heading-color); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid var(--gold-primary);">Thu</div>
                            <div style="text-align: center; padding: 12px; font-weight: 700; color: var(--heading-color); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid var(--gold-primary);">Fri</div>
                            <div style="text-align: center; padding: 12px; font-weight: 700; color: var(--heading-color); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid var(--gold-primary);">Sat</div>
                        </div>

                        <!-- Calendar Days Grid -->
                        <div id="calendarGrid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; margin-top: 8px;"></div>
                    </div>
                </div>

                <!-- Exams Section -->
                <div class="calendar-card" style="margin-top: 40px;">
                    <h3 style="font-family: 'Playfair Display', serif; color: var(--heading-color); font-weight: 800; margin-bottom: 28px; font-size: 28px;">
                        <i class="fas fa-graduation-cap me-3" style="color: var(--danger);"></i>Upcoming Exams
                    </h3>

                    <?php if (empty($exams)): ?>
                        <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No exams scheduled yet. Add modules to get started.</div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($exams_by_month as $month => $month_exams): ?>
                                <?php 
                                    $month_obj = DateTime::createFromFormat('Y-m', $month);
                                    $month_name = $month_obj->format('F Y');
                                ?>
                                <div class="col-lg-6">
                                    <div style="background: var(--card-background); border: 1px solid var(--border-color); border-radius: 10px; padding: 16px;">
                                        <h5 style="font-weight: 700; color: var(--heading-color); margin-bottom: 12px;">
                                            <i class="fas fa-calendar-check" style="color: var(--gold-primary);"></i> <?= $month_name ?>
                                        </h5>
                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                            <?php foreach ($month_exams as $exam): ?>
                                                <?php
                                                    $exam_date = new DateTime($exam['exam_date']);
                                                    $days_until = (int)(($exam_date->getTimestamp() - time()) / (60 * 60 * 24));
                                                    $color_class = $days_until < 0 ? '#999' : ($days_until <= 7 ? '#DC2626' : 'var(--gold-primary)');
                                                ?>
                                                <button onclick="showExamDetails(<?= htmlspecialchars(json_encode($exam)) ?>)" style="background: transparent; border: 1px solid var(--border-color); border-radius: 6px; padding: 10px 12px; text-align: left; cursor: pointer; transition: all 0.2s; font-size: 13px;">
                                                    <div style="font-weight: 600; color: var(--heading-color); margin-bottom: 4px;">
                                                        <i class="fas fa-book-open" style="color: var(--gold-primary); margin-right: 6px;"></i><?= htmlspecialchars($exam['module_name']) ?>
                                                    </div>
                                                    <div style="color: var(--text-muted); font-size: 12px;">
                                                        <i class="fas fa-calendar" style="margin-right: 4px;"></i><?= $exam_date->format('M d, Y') ?>
                                                        <span style="color: <?= $color_class ?>; font-weight: 600;">
                                                            (<?= $days_until < 0 ? 'Passed' : ($days_until . ' days') ?>)
                                                        </span>
                                                    </div>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div><!-- /content-padding -->
        </div><!-- /main-content -->
    </div><!-- /wrapper -->

    <!-- Exam Modal -->
    <div id="examModal">
        <div id="examModalContent"></div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Calendar and Exams JavaScript -->
    <script src="js/planning.js"></script>
    <script src="js/planning-exams.js"></script>
</body>
</html>
