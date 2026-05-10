<?php
require_once __DIR__ . '/modules/_bootstrap.php';

$stmt = $pdo->prepare('
    SELECT id, module_name, teacher, difficulty, career_importance, progress, understanding_level, exam_date
    FROM modules
    WHERE user_id = ?
    ORDER BY exam_date ASC, id DESC
');
$stmt->execute([$user_id]);
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modules - Edu Planning</title>
    <link rel="icon" type="image/png" href="assets/images/universite-cadi-ayyad.png">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/module.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            <li class="active"><a href="module.php"><i class="fas fa-book"></i> Modules</a></li>
            <li><a href="planning.php"><i class="fas fa-calendar"></i> Schedule</a></li>
            <li><a href="generate_plan.php"><i class="fas fa-wand-magic-sparkles"></i> AI Plan</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <div class="top-navbar">
            <h2 class="page-title m-0">Module Management</h2>
            <div class="d-flex align-items-center gap-3">
                <span style="font-weight: 500;">Hello, <?= htmlspecialchars($username) ?></span>
                <div style="width:40px;height:40px;background:linear-gradient(135deg, var(--gold-primary), var(--gold-light));border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--heading-color);font-weight:bold;font-size:16px;">
                    <?= module_initial($username) ?>
                </div>
            </div>
        </div>

        <div class="content-padding">
            <div class="mb-4">
                <h3 class="page-title" style="font-size: 32px; margin-bottom: 8px;">My Academic Modules</h3>
                <p style="color: var(--text-muted); font-size: 15px;">Manage your curriculum and track exam preparation progress.</p>
            </div>

            <div class="row g-4">
                <?php foreach ($modules as $module): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="module-card">
                            <div class="mc-header">
                                <div>
                                    <div class="mc-difficulty"><?= htmlspecialchars($module['difficulty']) ?> DIFFICULTY</div>
                                    <h4 class="mc-title" title="<?= htmlspecialchars($module['module_name']) ?>"><?= htmlspecialchars($module['module_name']) ?></h4>
                                </div>
                                <div class="mc-icon"><i class="fas fa-book-open"></i></div>
                            </div>
                            
                            <div class="mc-info-row">
                                <div>
                                    <div class="mc-label">Next Exam</div>
                                    <div class="mc-date"><?= date('M d, Y', strtotime($module['exam_date'])) ?></div>
                                </div>
                                <div class="mc-badge"><?= ucfirst(strtolower($module['understanding_level'])) ?></div>
                            </div>
                            
                            <div class="progress-section">
                                <div class="mc-progress-header">
                                    <span>Course Progress</span>
                                    <span><?= (int)$module['progress'] ?>%</span>
                                </div>
                                <div class="mc-progress-bar">
                                    <div class="mc-progress-fill" style="width: <?= (int)$module['progress'] ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="mc-actions">
                                <a class="mc-btn mc-btn-view" href="modules/view.php?id=<?= (int)$module['id'] ?>"><i class="fas fa-eye"></i> View</a>
                                <a class="mc-btn mc-btn-edit" href="modules/edit.php?id=<?= (int)$module['id'] ?>"><i class="fas fa-pen"></i> Edit</a>
                                <?php $csrf = htmlspecialchars($_SESSION['csrf_token']); ?>
                                <button type="button" class="mc-btn mc-btn-delete" onclick="confirmDelete(<?= (int)$module['id'] ?>, '<?= $csrf ?>')"><i class="fas fa-trash"></i> Delete</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="col-md-6 col-lg-4">
                    <a href="modules/add.php" class="add-module-card">
                        <div class="add-icon-circle"><i class="fas fa-plus"></i></div>
                        <div class="text-center">
                            <h4 class="add-module-title">Add New Module</h4>
                            <div class="add-module-text">Expand your curriculum</div>
                        </div>
                    </a>
                </div>
            </div>
            
            <form id="deleteForm" method="post" action="modules/delete.php" style="display:none;">
                <input type="hidden" name="csrf_token" id="deleteCsrf">
                <input type="hidden" name="id" id="deleteId">
            </form>
        </div>
    </div>
</div>

<script src="js/module.js"></script>
</body>
</html>
