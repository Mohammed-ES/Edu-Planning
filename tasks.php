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
$plan_id = (int)($_GET['plan'] ?? 0);

if (!$plan_id) {
    header('Location: generate_plan.php');
    exit;
}

// Verify plan belongs to user
$plan_stmt = $pdo->prepare('SELECT id, generated_plan FROM study_plans WHERE id = ? AND user_id = ?');
$plan_stmt->execute([$plan_id, $user_id]);
$plan = $plan_stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    header('Location: generate_plan.php');
    exit;
}

// Get tasks for this plan
$tasks = get_plan_tasks($pdo, $plan_id, $user_id);
$progress = get_plan_progress($pdo, $plan_id, $user_id);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'toggle_task') {
        $task_id = (int)($_POST['task_id'] ?? 0);
        $completed = (int)($_POST['completed'] ?? 0) === 1;
        
        $toggle_result = toggle_task_completion($pdo, $task_id, $user_id, $completed);
        
        if ($toggle_result['success']) {
            $new_progress = get_plan_progress($pdo, $plan_id, $user_id);
            echo json_encode([
                'success' => true,
                'progress' => $new_progress
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => $toggle_result['error']
            ]);
        }
        exit;
    }
}

$plan_data = json_decode($plan['generated_plan'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Plan Tasks - Edu Planning</title>
    <link rel="icon" type="image/png" href="assets/images/universite-cadi-ayyad.png">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/generate_plan.css">
    <style>
        .task-card {
            border: 1px solid var(--border-color);
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            position: relative;
        }

        .task-card.completed {
            background: rgba(40, 167, 69, 0.08);
            border-color: #28a745;
        }

        .task-card.completed .task-title,
        .task-card.completed .task-modules {
            opacity: 0.6;
            text-decoration: line-through;
        }

        .task-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .task-title {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: var(--heading-color);
            margin: 0;
            font-size: 20px;
        }

        .task-date {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
        }

        .task-modules {
            margin: 10px 0;
            font-size: 14px;
            color: var(--text-color);
        }

        .task-modules strong {
            color: var(--heading-color);
        }

        .task-hours {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .task-tips {
            background: rgba(184, 134, 11, 0.08);
            border-left: 3px solid var(--gold-primary);
            padding: 10px 15px;
            margin-top: 12px;
            border-radius: 4px;
            font-size: 13px;
            color: var(--heading-color);
        }

        .task-checkbox {
            width: 24px;
            height: 24px;
            cursor: pointer;
        }

        .completed-badge {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-left: 10px;
            text-transform: uppercase;
        }

        .progress-section {
            background: linear-gradient(135deg, rgba(184, 134, 11, 0.1), rgba(184, 134, 11, 0.05));
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
        }

        .progress-number {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            font-weight: 900;
            color: var(--gold-primary);
        }

        .progress-label {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 8px;
        }

        .progress-bar-custom {
            background: linear-gradient(90deg, var(--gold-primary) 0%, var(--gold-light) 100%);
            border-radius: 20px;
            height: 8px;
            margin-top: 15px;
            overflow: hidden;
        }

        .progress-bar-custom-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--gold-primary) 0%, var(--gold-light) 100%);
            transition: width 0.5s ease;
        }

        .task-loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--gold-primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-button:hover {
            color: var(--heading-color);
            text-decoration: underline;
        }

        :root {
            --gold-primary: #b8860b;
            --gold-light: #daa520;
            --heading-color: #1a1a1a;
            --text-color: #333;
            --text-muted: #666;
            --border-color: #e0e0e0;
        }
    </style>
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
            <h2 class="page-title m-0">Study Tasks</h2>
            <div class="d-flex align-items-center gap-3">
                <span style="font-weight:500;">Hello, <?= htmlspecialchars($user['name']) ?></span>
                <div style="width:40px;height:40px;background:linear-gradient(135deg, var(--gold-primary), var(--gold-light));border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--heading-color);font-weight:bold;font-size:16px;">
                    <?= htmlspecialchars(strtoupper(substr($user['name'], 0, 1))) ?>
                </div>
            </div>
        </div>

        <div class="content-padding">
            <a href="generate_plan.php" class="back-button">
                <i class="fas fa-arrow-left me-2"></i>Back to Plans
            </a>

            <div class="mb-4">
                <h1 style="font-family:'Playfair Display',serif;font-weight:900;color:var(--heading-color);margin:0;font-size:42px;">Your Study Tasks</h1>
                <p class="plan-subtitle" style="margin-top:8px;">Track your daily study progress. Mark tasks as done to see your advancement.</p>
            </div>

            <!-- Progress Section -->
            <div class="progress-section">
                <div class="progress-number" id="progressPercentage"><?= $progress['percentage'] ?>%</div>
                <div class="progress-label">Overall Progress</div>
                <div style="font-size: 14px; color: var(--text-color); margin-top: 8px;">
                    <span id="completedCount"><?= $progress['completed'] ?></span> of 
                    <span id="totalCount"><?= $progress['total'] ?></span> days completed
                </div>
                <div class="progress-bar-custom">
                    <div class="progress-bar-custom-fill" id="progressFill" style="width: <?= $progress['percentage'] ?>%"></div>
                </div>
            </div>

            <!-- Tasks List -->
            <div class="row g-4">
                <div class="col-12">
                    <?php if (empty($tasks)): ?>
                        <div class="panel-card p-4" style="text-align: center;">
                            <div style="font-size:56px;color:rgba(184,134,11,0.35);margin-bottom:10px;">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <div style="font-weight:900;color:var(--heading-color);margin-bottom:6px;font-size:18px;">No tasks found</div>
                            <div style="font-size:14px;color:var(--text-muted);">Tasks will appear here when you generate a study plan.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <?php
                            $modules = json_decode($task['modules'], true);
                            $task_date = new DateTime($task['task_date']);
                                $is_completed = (bool)$task['completed'];
                            ?>
                            <div class="task-card <?= $is_completed ? 'completed' : '' ?>" data-task-id="<?= $task['id'] ?>">
                                <div class="task-header">
                                    <div style="flex: 1;">
                                        <div>
                                            <h3 class="task-title">
                                                <?= htmlspecialchars($task['day_name']) ?>
                                                <?php if ($is_completed): ?>
                                                    <span class="completed-badge"><i class="fas fa-check me-1"></i>Done</span>
                                                <?php endif; ?>
                                            </h3>
                                            <div class="task-date"><?= htmlspecialchars($task_date->format('M j')) ?></div>
                                        </div>
                                    </div>
                                    <div style="flex-shrink: 0;">
                                        <input 
                                            type="checkbox" 
                                            class="task-checkbox task-toggle" 
                                            data-task-id="<?= $task['id'] ?>"
                                            <?= $is_completed ? 'checked' : '' ?>
                                        >
                                    </div>
                                </div>

                                <div class="task-modules">
                                    <strong><i class="fas fa-book me-1"></i>Modules:</strong>
                                    <?= htmlspecialchars(implode(', ', $modules)) ?>
                                </div>

                                <div class="task-hours">
                                    <strong><i class="fas fa-clock me-1"></i>Duration:</strong>
                                    <?= (int)$task['hours'] ?> hours
                                </div>

                                <?php if ($task['tips']): ?>
                                    <div class="task-tips">
                                        <strong><i class="fas fa-lightbulb me-1"></i>Tips:</strong>
                                        <?= htmlspecialchars($task['tips']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('.task-toggle').forEach(checkbox => {
        checkbox.addEventListener('change', async function() {
            const taskId = this.dataset.taskId;
            const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
            
            taskCard.classList.add('task-loading');

            try {
                const response = await fetch('tasks.php?plan=<?= $plan_id ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=toggle_task&task_id=${taskId}&completed=${this.checked ? 1 : 0}`
                });

                const data = await response.json();

                if (data.success) {
                    // Update progress
                    document.getElementById('progressPercentage').textContent = data.progress.percentage + '%';
                    document.getElementById('completedCount').textContent = data.progress.completed;
                    document.getElementById('totalCount').textContent = data.progress.total;
                    document.getElementById('progressFill').style.width = data.progress.percentage + '%';

                    // Update task card UI
                    if (this.checked) {
                        taskCard.classList.add('completed');
                    } else {
                        taskCard.classList.remove('completed');
                    }

                    // Re-display the badge
                    updateTaskBadge(taskCard, this.checked);
                } else {
                    console.error('Error:', data.error);
                    this.checked = !this.checked; // Revert checkbox
                }
            } catch (error) {
                console.error('Error:', error);
                this.checked = !this.checked; // Revert checkbox
            } finally {
                taskCard.classList.remove('task-loading');
            }
        });
    });

    function updateTaskBadge(taskCard, isCompleted) {
        const title = taskCard.querySelector('.task-title');
        const existingBadge = title.querySelector('.completed-badge');
        
        if (isCompleted && !existingBadge) {
            const badge = document.createElement('span');
            badge.className = 'completed-badge';
            badge.innerHTML = '<i class="fas fa-check me-1"></i>Done';
            title.appendChild(badge);
        } else if (!isCompleted && existingBadge) {
            existingBadge.remove();
        }
    }
</script>
</body>
</html>
