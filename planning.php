<?php
require_once __DIR__ . '/include/auth.php';
require_login();

$user_id = $_SESSION['user_id'];

// Fetch name for display only
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$username = htmlspecialchars($user['name'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revision Schedule - Edu-Planning</title>    <link rel="icon" type="image/png" href="assets/images/universite-cadi-ayyad.png">    <!-- Google Fonts - Enhanced Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/planning.css">
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
                    <span style="font-weight: 500;">Hello, <?php echo $username; ?></span>
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


            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/app.js"></script>
    <script src="js/planning.js"></script>
</body>
</html>



