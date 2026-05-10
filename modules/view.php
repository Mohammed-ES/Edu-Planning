<?php
require_once __DIR__ . '/_bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM modules WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $user_id]);
$module = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$module) {
    header('Location: ../module.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Module - Edu Planning</title>
    <link rel="icon" type="image/png" href="../assets/images/universite-cadi-ayyad.png">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/modules_view.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="wrapper">
    <nav class="sidebar">
        <div class="logo-area"><img src="../assets/images/universite-cadi-ayyad.png" alt="Université Cadi Ayyad"><span>Edu Planning</span></div>
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
            <h2 class="page-title m-0">Module Details</h2>
            <div class="d-flex align-items-center gap-3">
                <span style="font-weight:500;">Hello, <?= htmlspecialchars($username) ?></span>
                <div style="width:40px;height:40px;background:linear-gradient(135deg, var(--gold-primary), var(--gold-light));border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--heading-color);font-weight:bold;font-size:16px;"><?= module_initial($username) ?></div>
            </div>
        </div>
        <div class="content-padding">
            <div class="mb-3">
                <a href="../module.php" style="color: var(--text-muted); text-decoration: none; font-weight: 600;"><i class="fas fa-arrow-left me-2"></i> View: <?= htmlspecialchars($module['module_name']) ?></a>
            </div>
            <div class="details-shell">
                <div class="details-shell-header">
                    <div>
                        <h3><?= htmlspecialchars($module['module_name']) ?></h3>
                        <div class="header-meta">
                            <span><i class="far fa-calendar-alt me-1"></i> Created on <?= date('F j, Y', strtotime($module['created_at'] ?? 'now')) ?></span>
                        </div>
                    </div>
                    <div class="id-badge">ID: #<?= (int)$module['id'] ?></div>
                </div>
                <div class="details-shell-body">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <div class="info-card">
                                <div class="info-label">Teacher</div>
                                <div class="info-value"><i class="far fa-user"></i> <?= htmlspecialchars($module['teacher'] ?: 'Not Assigned') ?></div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-label">Exam Date</div>
                                <div class="info-value"><i class="far fa-calendar-check"></i> <?= date('F j, Y', strtotime($module['exam_date'])) ?></div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-label">Attributes</div>
                                <div class="attr-row">
                                    <span class="attr-name">Difficulty</span>
                                    <span class="attr-badge <?= strtolower($module['difficulty']) ?>"><?= ucfirst(strtolower($module['difficulty'])) ?></span>
                                </div>
                                <div class="attr-row">
                                    <span class="attr-name">Importance</span>
                                    <span class="attr-badge <?= strtolower($module['career_importance']) ?>"><?= ucfirst(strtolower($module['career_importance'])) ?></span>
                                </div>
                                <div class="attr-row">
                                    <span class="attr-name">Understanding</span>
                                    <span class="attr-badge <?= strtolower($module['understanding_level']) ?>"><?= ucfirst(strtolower($module['understanding_level'])) ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-5">
                            <div class="progress-card">
                                <div class="info-label" style="margin-bottom: 0;">Course Progress</div>
                                <?php 
                                    $prog = (int)$module['progress']; 
                                    $msg = $prog >= 100 ? "You're all set!" : ($prog >= 50 ? "You are over halfway there!" : "Keep going, you can do this!");
                                ?>
                                <div class="circular-progress" style="--progress: <?= $prog ?>%;">
                                    <div class="progress-value"><?= $prog ?>%</div>
                                </div>
                                <div class="progress-msg"><?= $msg ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-3">
                        <div class="col-md-6">
                            <a href="edit.php?id=<?= (int)$module['id'] ?>" class="btn-action btn-edit-module">
                                <i class="fas fa-pen"></i> Edit Module
                            </a>
                        </div>
                        <div class="col-md-6">
                            <?php $csrf = htmlspecialchars($_SESSION['csrf_token']); ?>
                            <button type="button" class="btn-action btn-delete-module" onclick="confirmDelete(<?= (int)$module['id'] ?>, '<?= $csrf ?>')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <form id="deleteForm" method="post" action="delete.php" style="display:none;">
                <input type="hidden" name="csrf_token" id="deleteCsrf">
                <input type="hidden" name="id" id="deleteId">
            </form>
        </div>
    </div>
</div>

<script src="../js/modules-shared.js"></script>
</body>
</html>
