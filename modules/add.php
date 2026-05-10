<?php
require_once __DIR__ . '/_bootstrap.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid token.';
    } else {
        $module_name = trim($_POST['module_name'] ?? '');
        $teacher = trim($_POST['teacher'] ?? '');
        $difficulty = strtoupper($_POST['difficulty'] ?? 'MEDIUM');
        $career_importance = strtoupper($_POST['career_importance'] ?? 'MEDIUM');
        $progress = max(0, min(100, (int)($_POST['progress'] ?? 0)));
        $understanding_level = strtoupper($_POST['understanding_level'] ?? 'MEDIUM');
        $exam_date = $_POST['exam_date'] ?? '';

        // Validate date format
        $dateObj = DateTime::createFromFormat('Y-m-d', $exam_date);
        if ($module_name === '' || $exam_date === '') {
            $error = 'Module name and exam date are required.';
        } elseif (!$dateObj || $dateObj->format('Y-m-d') !== $exam_date) {
            $error = 'Please provide a valid exam date.';
        } elseif (!in_array($difficulty, ['EASY', 'MEDIUM', 'HARD'], true)) {
            $error = 'Invalid difficulty value.';
        } elseif (!in_array($career_importance, ['LOW', 'MEDIUM', 'HIGH'], true)) {
            $error = 'Invalid career importance value.';
        } elseif (!in_array($understanding_level, ['LOW', 'MEDIUM', 'HIGH'], true)) {
            $error = 'Invalid understanding level value.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO modules (user_id, module_name, teacher, difficulty, career_importance, progress, understanding_level, exam_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $ok = $stmt->execute([$user_id, $module_name, $teacher, $difficulty, $career_importance, $progress, $understanding_level, $exam_date]);
            if ($ok) {
                header('Location: ../module.php');
                exit;
            }
            $error = 'Failed to add module.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Module - Edu Planning</title>
    <link rel="icon" type="image/png" href="../assets/images/universite-cadi-ayyad.png">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/modules_add.css">
</head>
<body>
<div class="wrapper">
    <nav class="sidebar">
        <div class="logo-area">
            <img src="../assets/images/universite-cadi-ayyad.png" alt="Université Cadi Ayyad">
            <span>Edu Planning</span>
        </div>
        <ul class="components">
            <li><a href="../dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li class="active"><a href="../module.php"><i class="fas fa-book"></i> Modules</a></li>
            <li><a href="../planning.php"><i class="fas fa-calendar"></i> Schedule</a></li>
            <li><a href="../generate_plan.php"><i class="fas fa-wand-magic-sparkles"></i> AI Plan</a></li>
            <li><a href="../profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    <div class="main-content">
        <div class="top-navbar">
            <h2 class="page-title m-0">Add Module</h2>
            <div class="d-flex align-items-center gap-3">
                <span style="font-weight: 500;">Hello, <?= htmlspecialchars($username) ?></span>
                <div style="width:40px;height:40px;background:linear-gradient(135deg, var(--gold-primary), var(--gold-light));border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--heading-color);font-weight:bold;font-size:16px;"><?= module_initial($username) ?></div>
            </div>
        </div>
        <div class="content-padding">
            <div class="mb-3">
                <a href="../module.php" style="color: var(--text-muted); text-decoration: none; font-weight: 600;"><i class="fas fa-arrow-left me-2"></i> Add Module</a>
            </div>
            <div class="form-shell">
                <div class="form-shell-header">
                    <div>
                        <h3><i class="fas fa-plus-circle"></i> Create New Module</h3>
                        <p>Fill in the details to add a module to your study plan.</p>
                    </div>
                </div>
                <div class="form-shell-body">
                    <?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">Module name *</label>
                                <input class="form-control" name="module_name" required placeholder="e.g., Advanced Algorithms">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teacher</label>
                                <input class="form-control" name="teacher" placeholder="Optional">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Difficulty *</label>
                                <select class="form-select" name="difficulty">
                                    <option>Select difficulty...</option>
                                    <option value="EASY">EASY</option>
                                    <option value="MEDIUM" selected>MEDIUM</option>
                                    <option value="HARD">HARD</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Career importance *</label>
                                <select class="form-select" name="career_importance">
                                    <option>Select importance...</option>
                                    <option value="LOW">LOW</option>
                                    <option value="MEDIUM" selected>MEDIUM</option>
                                    <option value="HIGH">HIGH</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Progress (%) *</label>
                                <input class="form-control" type="number" name="progress" min="0" max="100" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Understanding level *</label>
                                <select class="form-select" name="understanding_level">
                                    <option>Select level...</option>
                                    <option value="LOW">LOW</option>
                                    <option value="MEDIUM" selected>MEDIUM</option>
                                    <option value="HIGH">HIGH</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Exam date *</label>
                                <input class="form-control" type="date" name="exam_date" required>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button class="btn-action btn-save" type="submit"><i class="far fa-save"></i> Create Module</button>
                            <a class="btn-action btn-cancel" href="../module.php"><i class="fas fa-times"></i> Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
