<?php
require_once 'auth.php';
require_login();

$user_id = $_SESSION['user_id'];

// Check if viewing detailed planning
$view_details = false;
$detail_planning = null;
$detail_rec_id = null;
if (isset($_GET['view']) && $_GET['view'] === 'details' && isset($_GET['id'])) {
    $detail_rec_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT id, recommendation, created_at FROM ai_recommendations WHERE id = ? AND user_id = ?");
    $stmt->execute([$detail_rec_id, $user_id]);
    $detail_planning = $stmt->fetch();
    if ($detail_planning) {
        $view_details = true;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit;
    }

    $action = $_POST['action'];

    // Delete recommendation
    if ($action === 'delete_recommendation') {
        $id = (int)$_POST['recommendation_id'];
        $stmt = $pdo->prepare("DELETE FROM ai_recommendations WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$id, $user_id])) {
            logAction($user_id, 'recommendation_deleted', $pdo);
            echo json_encode(['success' => true, 'message' => 'Recommendation deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting recommendation']);
        }
        exit;
    }

    // Generate new recommendations (call Gemini API)
    if ($action === 'generate_recommendation') {
        // Redirect to api_gemini.php
        echo json_encode(['success' => true, 'redirect' => 'api_gemini.php']);
        exit;
    }
}

// Format minutes to readable duration string (e.g., "1h 30min", "2h", "45min")
function formatDuration($minutes) {
    $minutes = (int)$minutes;
    if ($minutes <= 0) return '0 min';
    
    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;
    
    if ($hours === 0) {
        return $mins . ' min';
    } elseif ($mins === 0) {
        return $hours . 'h';
    } else {
        return $hours . 'h ' . $mins . 'min';
    }
}

// Fetch all recommendations
$stmt = $pdo->prepare("SELECT id, recommendation, created_at FROM ai_recommendations WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$recommendations = $stmt->fetchAll();

// Fetch all notes with module names for the notes selection
$stmt = $pdo->prepare("
    SELECT n.id, n.note_value, n.created_at, m.module_name 
    FROM notes n
    JOIN modules m ON n.module_id = m.id
    WHERE m.user_id = ? 
    ORDER BY m.module_name, n.created_at DESC
");
$stmt->execute([$user_id]);
$all_notes = $stmt->fetchAll();

// Fetch username
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$username = htmlspecialchars($user['username'] ?? 'User');
$csrf = htmlspecialchars($_SESSION['csrf_token']);

// Calculate global statistics from all recommendations
$total_hours = 0;
$total_modules_planned = array();
$avg_daily_hours = 0;
$recommendations_count = count($recommendations);
$total_days_all = 0;

if ($recommendations_count > 0) {
    foreach ($recommendations as $rec) {
        $rec_data = json_decode($rec['recommendation'], true);
        if (!empty($rec_data['planning'])) {
            $rec_total_minutes = 0;
            $rec_days = count($rec_data['planning']);
            $total_days_all += $rec_days;
            
            foreach ($rec_data['planning'] as $day) {
                if (!empty($day['sessions'])) {
                    foreach ($day['sessions'] as $session) {
                        // Support both 'duration_minutes' and 'duree_minutes'
                        $duration = (int)($session['duration_minutes'] ?? $session['duree_minutes'] ?? 0);
                        $rec_total_minutes += $duration;
                        $module_name = $session['module'] ?? '';
                        if ($module_name && !in_array($module_name, $total_modules_planned)) {
                            $total_modules_planned[] = $module_name;
                        }
                    }
                }
            }
            $total_hours += $rec_total_minutes;
        }
    }
    // Calculate average: total minutes / total days across all plans
    if ($total_days_all > 0) {
        $avg_daily_hours = round($total_hours / ($total_days_all * 60), 1);
    }
}

$total_hours = round($total_hours / 60, 1);
$total_modules_count = count($total_modules_planned);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommendations - Edu-Planning</title>
    <link rel="icon" type="image/png" href="assets/images/universite-cadi-ayyad.png">
    <!-- Google Fonts - Enhanced Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --gold-primary: #B8860B;
            --gold-mid: #D4A017;
            --gold-light: #F5C842;
            --gold-pale: #FDF3D0;
            --sidebar-dark: #1C0A00;
            --sidebar-med: #2D1000;
            --sidebar-hover: #4A2000;
            --page-bg: #F5F0E8;
            --card-bg: #FFFFFF;
            --heading-color: #2C1800;
            --text-primary: #3D2C00;
            --text-muted: #8B7355;
            --border-color: #E8DCC8;
            --danger: #DC2626;
            --info-bg: #EFF6FF;
            --info-border: #BFDBFE;
            --white: #FFFFFF;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--page-bg);
            color: var(--text-primary);
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 220px;
            background: linear-gradient(180deg, var(--sidebar-dark), var(--sidebar-med));
            color: var(--white);
            padding: 20px 16px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 12px rgba(0,0,0,0.12);
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo-area img {
            width: 52px;
            height: 52px;
            object-fit: contain;
            filter: brightness(0) invert(1);
            flex-shrink: 0;
        }

        .logo-area span {
            font-family: 'Playfair Display', serif;
            font-size: 14px;
            font-weight: 700;
            white-space: nowrap;
            letter-spacing: 0.2px;
            color: var(--gold-light);
        }

        .sidebar .components {
            list-style: none;
            padding: 0;
        }

        .sidebar .components li {
            margin-bottom: 8px;
        }

        .sidebar .components li:nth-child(3) {
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar .components li a {
            color: rgba(255,255,255,0.65);
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background 0.2s ease, color 0.2s ease;
            font-size: 13px;
            font-weight: 500;
        }

        .sidebar .components li a i {
            width: 18px;
            height: 18px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .sidebar .components li a:hover {
            background: rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.9);
        }

        .sidebar .components li.active a {
            background: var(--gold-primary);
            color: var(--heading-color);
            font-weight: 700;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 220px;
        }

        .top-navbar {
            background: var(--white);
            padding: 16px 28px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--heading-color);
        }

        .content-padding {
            padding: 30px;
        }

        /* Animations */
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideUpExit {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-20px); }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Recommendation Cards */
        .rec-card {
            background: var(--white);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-left: 4px solid var(--gold-primary);
            margin-bottom: 20px;
            transition: all 0.3s ease;
            animation: slideUp 0.6s ease-out;
        }

        .rec-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.2);
        }

        .rec-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .rec-header h5 {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--heading-color);
            margin: 0;
        }

        .rec-timestamp {
            font-size: 12px;
            color: #999;
            margin-top: 4px;
        }

        .delete-btn {
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            padding: 5px;
            transition: color 0.3s;
            font-size: 16px;
        }

        .delete-btn:hover {
            color: #d32f2f;
        }

        .rec-planning {
            background: #f5f5f5;
            border-radius: 8px;
            padding: 15px;
            margin-top: 12px;
            font-size: 13px;
            border: 1px solid #e8e8e8;
        }

        .rec-planning strong {
            color: var(--heading-color);
            display: block;
            margin-bottom: 10px;
        }

        .rec-planning > div > div {
            margin: 8px 0;
            padding: 10px;
            background: white;
            border-left: 3px solid var(--gold-primary);
            border-radius: 4px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
            animation: slideUp 0.6s ease-out;
        }

        .empty-state i {
            font-size: 48px;
            color: var(--gold-primary);
            margin-bottom: 15px;
        }

        .empty-state h5 {
            font-family: 'Playfair Display', serif;
            color: var(--heading-color);
            font-weight: 700;
            margin-bottom: 10px;
        }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-light));
            color: var(--heading-color);
            border: none;
            font-weight: 700;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--gold-light), var(--gold-primary));
            color: var(--heading-color);
        }

        .generate-btn {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-light));
            color: var(--heading-color);
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 700;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .generate-btn:hover {
            background: linear-gradient(135deg, var(--gold-light), var(--gold-primary));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.3);
        }

        /* Section Header */
        .section-header {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            font-weight: 800;
            color: var(--heading-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--heading-color);
            font-size: 18px;
            flex-shrink: 0;
        }

        /* Alert */
        .alert-info {
            background: #f0f4ff;
            border: 1px solid #d4daff;
            color: #3b5998;
            border-radius: 8px;
            padding: 16px;
        }

        .alert-info strong { color: var(--heading-color); }
        .alert-info ul { padding-left: 20px; margin: 0; }
        .alert-info li { margin-bottom: 6px; }

        /* Modal */
        .modal-header {
            background: linear-gradient(135deg, var(--heading-color), var(--sidebar-med));
            color: var(--white);
            border: none;
            padding: 28px;
        }

        .modal-header .modal-title {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 700;
        }

        .modal-body {
            padding: 30px;
        }

        .modal-body h6 {
            font-family: 'Playfair Display', serif;
            color: var(--heading-color);
            font-weight: 700;
            margin-bottom: 12px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #e8e8e8;
        }

        .btn-group {
            gap: 8px;
            display: flex;
            flex-wrap: wrap;
        }

        .btn-group .btn {
            flex: 1;
            min-width: 120px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 10px 16px;
            font-size: 13px;
        }

        .btn-group .btn-check:checked + .btn {
            background: linear-gradient(135deg, var(--heading-color), var(--sidebar-med));
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(107, 68, 35, 0.2);
        }

        .btn-group .btn-outline-primary {
            color: var(--heading-color);
            border: 2px solid var(--heading-color);
        }

        .btn-group .btn-outline-primary:hover {
            background: #f5f5f5;
            color: var(--heading-color);
        }

        .btn-group .btn-outline-success {
            color: #27ae60;
            border: 2px solid #27ae60;
        }

        .btn-group .btn-outline-success:hover {
            background: #f0f8f4;
            color: #27ae60;
        }

        .form-check-input {
            border: 2px solid #ddd;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--gold-primary);
            border-color: var(--gold-primary);
        }

        .form-check-label {
            font-size: 13px;
            color: var(--text-primary);
            cursor: pointer;
        }

        #modules-group {
            background: #f9f9f9;
            padding: 12px;
            border-radius: 8px;
            max-height: 250px;
            overflow-y: auto;
        }

        #modules-group .form-check {
            padding: 8px 0;
            border-bottom: 1px solid #e8e8e8;
        }

        #modules-group .form-check:last-child {
            border-bottom: none;
        }

        .info-box {
            background: linear-gradient(135deg, #f0f4ff, #f8fbff);
            border: 1px solid #d4daff;
            border-radius: 8px;
            padding: 18px;
            font-size: 13px;
            line-height: 1.8;
            color: var(--text-primary);
        }

        .info-box strong { color: var(--heading-color); }

        .info-list {
            display: grid;
            gap: 10px;
            margin-top: 10px;
        }

        .info-item {
            display: flex;
            gap: 10px;
        }

        .info-item i {
            color: #27ae60;
            font-weight: bold;
            flex-shrink: 0;
        }

        /* Enhanced Modal Styling */
        .config-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 18px;
            border: 1px solid #e8e8e8;
            transition: all 0.3s ease;
        }

        .config-section:hover {
            box-shadow: 0 4px 16px rgba(107, 68, 35, 0.08);
            border-color: var(--gold-primary);
        }

        .config-section h6 {
            display: flex;
            align-items: center;
            margin-bottom: 16px !important;
            color: var(--heading-color);
            font-weight: 700;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .config-section h6 i {
            margin-right: 8px;
            color: var(--gold-primary);
            font-size: 16px;
        }

        /* Enhanced Planning Display */
        .planning-day-card {
            background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 16px;
            border: 1px solid #e8e8e8;
            border-left: 5px solid var(--gold-primary);
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .planning-day-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.15);
            border-left-color: var(--heading-color);
        }

        .planning-day-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
        }

        .planning-day-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            color: var(--heading-color);
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .planning-day-title i {
            color: var(--gold-primary);
            font-size: 16px;
        }

        .planning-day-stats {
            display: flex;
            gap: 10px;
            font-size: 12px;
        }

        .planning-day-stat {
            display: flex;
            align-items: center;
            gap: 4px;
            background: #f5f5f5;
            padding: 4px 10px;
            border-radius: 20px;
            color: #666;
            font-weight: 600;
        }

        .planning-day-stat i {
            color: var(--gold-primary);
        }

        .planning-session {
            background: white;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            border-left: 4px solid var(--gold-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
        }

        .planning-session:last-child {
            margin-bottom: 0;
        }

        .planning-session:hover {
            background: #fafafa;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .planning-session-info {
            flex: 1;
        }

        .planning-session-module {
            font-weight: 700;
            color: var(--heading-color);
            font-size: 13px;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .planning-session-module i {
            color: var(--gold-primary);
        }

        .planning-session-duration {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #666;
        }

        .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .priority-haute {
            background: #ffebee;
            color: #d32f2f;
        }

        .priority-haute i {
            color: #d32f2f;
        }

        .priority-moyenne {
            background: #fff3e0;
            color: #f57c00;
        }

        .priority-moyenne i {
            color: #f57c00;
        }

        .priority-basse {
            background: #e8f5e9;
            color: #388e3c;
        }

        .priority-basse i {
            color: #388e3c;
        }

        /* Global Statistics Container */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--white) 0%, #f9f9f9 100%);
            border: 1px solid #e8e8e8;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .stat-card:hover {
            box-shadow: 0 4px 16px rgba(107, 68, 35, 0.12);
            border-color: var(--gold-primary);
            transform: translateY(-2px);
        }

        .stat-icon {
            font-size: 28px;
            color: var(--gold-primary);
            margin-bottom: 12px;
        }

        .stat-value {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--heading-color);
            margin: 8px 0;
        }

        .stat-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #999;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                position: fixed;
                left: 0;
            }

            .main-content {
                margin-left: 70px;
            }

            .sidebar span {
                display: none;
            }

            .content-padding {
                padding: 20px;
            }

            .section-header {
                font-size: 20px;
            }
        }
    </style>
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
                <li><a href="modules.php"><i class="fas fa-book"></i> Modules & Notes</a></li>
                <li><a href="planning.php"><i class="fas fa-calendar"></i> Schedule</a></li>
                <li class="active"><a href="recommendations.php"><i class="fas fa-star"></i> Recommendations</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-navbar">
                <h2 class="page-title m-0">Recommendations</h2>
                <div class="d-flex align-items-center gap-3">
                    <span style="font-weight: 500;">Hello, <?php echo $username; ?></span>
                    <div style="width:40px;height:40px;background:linear-gradient(135deg, var(--gold-primary), var(--gold-light));border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--heading-color);font-weight:bold;font-size:16px;">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                </div>
            </div>

            <div class="content-padding">
                <!-- Global Statistics - Hide when viewing details -->
                <?php if (!$view_details && $recommendations_count > 0): ?>
                    <div class="stats-container">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-hourglass-end"></i></div>
                            <div class="stat-value"><?php echo $total_hours; ?></div>
                            <div class="stat-label">Total Hours</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-book"></i></div>
                            <div class="stat-value"><?php echo $total_modules_count; ?></div>
                            <div class="stat-label">Modules Covered</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-clock"></i></div>
                            <div class="stat-value"><?php echo $avg_daily_hours; ?>h</div>
                            <div class="stat-label">Daily Average</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                            <div class="stat-value"><?php echo $recommendations_count; ?></div>
                            <div class="stat-label">Generated Plans</div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Section Header - Hide when viewing details -->
                <?php if (!$view_details): ?>
                <div class="row mb-4 align-items-center">
                    <div class="col">
                        <div class="section-header">
                            <div class="section-logo"><i class="fas fa-book"></i></div>
                            Your Study Plans
                        </div>
                        <small style="color: #999;">Click on any plan to view complete details and revisions schedule</small>
                    </div>
                    <div class="col-auto">
                        <button class="generate-btn" onclick="generateNewRecommendation()">
                            <i class="fas fa-wand-magic-sparkles"></i> Generate Recommendation
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recommendations List -->
                <div id="recommendations-container">
                    <?php if (empty($recommendations)): ?>
                        <div class="empty-state">
                            <i class="fas fa-brain fa-3x mb-3" style="color:#ddd;"></i>
                            <h5>No recommendations generated</h5>
                            <p class="text-muted">AI will analyze your modules and notes to generate personalized revision schedules.</p>
                            <button class="btn btn-primary mt-3" onclick="generateNewRecommendation()">
                                <i class="fas fa-sparkles"></i> Generate Now
                            </button>
                        </div>
                    <?php else: ?>
                        <?php 
                        // Check if viewing details
                        if ($view_details && $detail_planning) {
                            $rec_data = json_decode($detail_planning['recommendation'], true);
                            $date_formatted = date('d/m/Y - H:i', strtotime($detail_planning['created_at']));
                            $total_plannings = count($recommendations);
                            $detail_planning_num = $total_plannings - array_search($detail_planning['id'], array_column($recommendations, 'id'));
                            
                            // Calculate total sessions and duration for detail view
                            $detail_total_sessions = 0;
                            $detail_total_minutes = 0;
                            if (!empty($rec_data['planning'])) {
                                foreach ($rec_data['planning'] as $day) {
                                    if (!empty($day['sessions'])) {
                                        $detail_total_sessions += count($day['sessions']);
                                        foreach ($day['sessions'] as $session) {
                                            $duration = (int)($session['duration_minutes'] ?? $session['duree_minutes'] ?? 0);
                                            $detail_total_minutes += $duration;
                                        }
                                    }
                                }
                            }
                            $detail_total_hours = round($detail_total_minutes / 60, 1);
                        ?>
                            <!-- DETAILED VIEW -->
                            <div style="margin-bottom: 24px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                    <div>
                                        <h3 style="color: var(--heading-color); font-weight: 700; margin: 0;"><i class="fas fa-bookmark" style="color: var(--gold-primary); margin-right: 10px;"></i>Planning <?php echo $detail_planning_num; ?></h3>
                                        <p style="color: #999; margin-top: 4px;"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($date_formatted); ?></p>
                                    </div>
                                    <a href="recommendations.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to List</a>
                                </div>

                                <div class="alert alert-info mb-4" style="border-radius: 10px;">
                                    <strong><i class="fas fa-chart-bar me-2"></i>Schedule Summary:</strong><br>
                                    <small style="margin-top: 8px; display: block;">
                                        <span style="display: inline-block; margin-right: 16px;"><i class="fas fa-calendar-days"></i> <?php echo count($rec_data['planning'] ?? []); ?> days</span>
                                        <span style="display: inline-block; margin-right: 16px;"><i class="fas fa-book"></i> <span id="total-sessions-detail"><?php echo $detail_total_sessions; ?></span> sessions</span>
                                        <span style="display: inline-block;"><i class="fas fa-hourglass-end"></i> <span id="total-time-detail"><?php echo $detail_total_hours; ?>h</span> total</span>
                                    </small>
                                </div>
                        <?php 
                            if (!empty($rec_data['planning'])) {
                                foreach ($rec_data['planning'] as $day):
                                    if (empty($day['sessions'])) continue;
                                    $dayDate = new DateTime($day['date']);
                                    $dayName = $dayDate->format('l');
                                    $dayTotalMins = 0;
                        ?>
                                <div class="planning-day-card mb-4" style="background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden;">
                                    <div style="background: linear-gradient(135deg, var(--gold-primary), #f0d662); padding: 16px 20px; color: #333; font-weight: 600;">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <i class="fas fa-calendar-day" style="margin-right: 10px;"></i>
                                                <strong>Day <?php echo $day['jour']; ?></strong> - <?php echo htmlspecialchars($dayName); ?>
                                                <small style="display: block; color: #555; font-weight: 400; margin-top: 4px;"><?php echo $dayDate->format('Y-m-d'); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="padding: 16px 20px;">
                        <?php 
                                        foreach ($day['sessions'] as $session):
                                            $duration = (int)($session['duration_minutes'] ?? $session['duree_minutes'] ?? 0);
                                            $dayTotalMins += $duration;
                                            $priority = $session['priorite'] ?? 'moyenne';
                                            $priorityColor = $priority === 'haute' ? '#dc3545' : ($priority === 'basse' ? '#28a745' : '#ffc107');
                                            $priorityLabel = $priority === 'haute' ? '<i class="fas fa-arrow-up"></i> High' : ($priority === 'basse' ? '<i class="fas fa-arrow-down"></i> Low' : '<i class="fas fa-minus"></i> Normal');
                        ?>
                                        <div style="margin-bottom: 16px; padding: 12px; background-color: #f9f9f9; border-radius: 8px; border-left: 4px solid <?php echo $priorityColor; ?>;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                                <strong style="color: var(--heading-color); font-size: 15px;"><i class="fas fa-book-open" style="margin-right: 8px; color: <?php echo $priorityColor; ?>;"></i><?php echo htmlspecialchars($session['module']); ?></strong>
                                                <div style="display: flex; gap: 10px; align-items: center;">
                                                    <span style="background-color: <?php echo $priorityColor; ?>; color: white; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500;"><?php echo $priorityLabel; ?></span>
                                                    <span style="color: #666; font-size: 14px; white-space: nowrap;"><i class="fas fa-hourglass-end"></i> <?php echo formatDuration($duration); ?></span>
                                                </div>
                                            </div>
                        <?php 
                                            if (!empty($session['topics']) && is_array($session['topics'])):
                        ?>
                                            <div style="margin-bottom: 8px;">
                                                <strong style="color: var(--heading-color); font-size: 13px; display: block; margin-bottom: 6px;"><i class="fas fa-book-open" style="color: var(--gold-primary); margin-right: 6px;"></i>Topics to Review:</strong>
                                                <div style="display: flex; flex-wrap: wrap; gap: 6px; padding-left: 20px;">
                                        <?php 
                                                    foreach ($session['topics'] as $topic):
                        ?>
                                                    <span style="background-color: #ffd54f; color: #333; padding: 4px 10px; border-radius: 12px; font-size: 12px;"><?php echo htmlspecialchars($topic); ?></span>
                                        <?php 
                                                    endforeach;
                        ?>
                                                </div>
                                            </div>
                        <?php 
                                            endif;
                                            if (!empty($session['description'])):
                        ?>
                                            <div style="color: #666; font-size: 13px; padding: 8px; background-color: #fff; border-radius: 4px; margin-top: 8px;">
                                                <i class="fas fa-lightbulb" style="color: #ffc107; margin-right: 6px;"></i>
                                                <strong>Notes:</strong> <?php echo htmlspecialchars($session['description']); ?>
                                            </div>
                        <?php 
                                            endif;
                        ?>
                                        </div>
                        <?php 
                                        endforeach;
                        ?>
                                    </div>
                                </div>
                        <?php 
                                endforeach;
                            }
                        ?>
                            </div>
                        <?php 
                        } else {
                            // LIST VIEW
                        ?>
                        <?php 
                        $total_plannings = count($recommendations);
                        $planning_number = $total_plannings;
                        foreach ($recommendations as $rec): 
                            $rec_data = json_decode($rec['recommendation'], true);
                            $date_formatted = date('d/m/Y - H:i', strtotime($rec['created_at']));
                        ?>
                            <div class="rec-card" data-rec-id="<?php echo $rec['id']; ?>" style="cursor: pointer; transition: all 0.3s ease;">
                                <a href="recommendations.php?view=details&id=<?php echo $rec['id']; ?>" style="text-decoration: none; color: inherit;">
                                    <div class="rec-header">
                                        <div style="display: flex; align-items: center; gap: 16px; width: 100%;">
                                            <div>
                                                <h5 style="margin:0; color:var(--heading-color); display: flex; align-items: center; gap: 8px;">
                                                    <i class="fas fa-bookmark" style="color: var(--gold-primary); font-size: 18px;"></i>Planning <?php echo $planning_number; ?>
                                                </h5>
                                            </div>
                                            <div class="rec-timestamp" style="white-space: nowrap;">
                                                <i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($date_formatted); ?>
                                            </div>
                                        </div>
                                        <div style="display: flex; gap: 10px; align-items: center;">
                                            <button class="delete-btn" title="Delete" onclick="event.preventDefault(); event.stopPropagation(); deleteRecommendation(<?php echo $rec['id']; ?>)" style="cursor: pointer;">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php 
                        $planning_number--;
                        endforeach; 
                        }
                        ?>
                    <?php endif; ?>
                </div>

                <!-- Footer Help -->
                <div class="alert alert-info mt-4" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>How it Works:</strong> The AI analyzes your recent modules and academic notes to create a tailored schedule. The more details you add to your notes, the more precise the recommendations will be.
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Configuration Recommandation - STEP BY STEP -->
    <div class="modal fade" id="configRecommendationModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <!-- Modal Header with Stepper -->
                <div class="modal-header" style="border-bottom: 2px solid #f0f0f0;">
                    <div style="width: 100%; display: flex; align-items: center; gap: 20px;">
                        <!-- Stepper Progress -->
                        <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                            <div class="stepper-circle" style="width: 40px; height: 40px; border-radius: 50%; background-color: var(--gold-primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px;">1</div>
                            <div style="width: 20px; height: 2px; background-color: #ddd;"></div>
                            <div class="stepper-circle" style="width: 40px; height: 40px; border-radius: 50%; background-color: #ddd; color: #999; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px;">2</div>
                            <div style="width: 20px; height: 2px; background-color: #ddd;"></div>
                            <div class="stepper-circle" style="width: 40px; height: 40px; border-radius: 50%; background-color: #ddd; color: #999; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px;">3</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                </div>

                <div class="modal-body">
                    <!-- Step 1: Revision Period -->
                    <div id="step-content-1" style="display: block;">
                        <h5 style="margin-bottom: 24px;"><i class="fas fa-calendar-check" style="color: var(--gold-primary); margin-right: 10px;"></i>Step 1: Revision Period</h5>
                        <small class="text-muted d-block mb-4">Optimized 7-day intensive revision schedule</small>
                        <div style="background: linear-gradient(135deg, rgba(212,175,55,0.1) 0%, rgba(212,175,55,0.05) 100%); padding: 24px; border-radius: 12px; border-left: 4px solid var(--gold-primary); margin-bottom: 20px;">
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <i class="fas fa-calendar-alt" style="font-size: 32px; color: var(--gold-primary);"></i>
                                <div>
                                    <strong style="color: var(--heading-color); font-size: 18px; display: block; margin-bottom: 6px;">7-Day Intensive Schedule</strong>
                                    <small style="color: #666; line-height: 1.5;">The AI will analyze all your notes and create a personalized daily plan with precise timing (hours & minutes). This intensive schedule is optimized for exam preparation.</small>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="duration" value="7">
                    </div>

                    <!-- Step 2: Module Selection -->
                    <div id="step-content-2" style="display: none;">
                        <h5 style="margin-bottom: 24px;"><i class="fas fa-list-check" style="color: #28a745; margin-right: 10px;"></i>Step 2: Module Selection</h5>
                        <small class="text-muted d-block mb-4">Choose the modules to include in the schedule</small>
                        <div class="btn-group w-100 mb-4" role="group">
                            <input type="radio" class="btn-check" name="module_selection" id="mod_all" value="all" checked onchange="toggleModuleSelection()">
                            <label class="btn btn-outline-success" for="mod_all" style="padding: 12px 20px; font-weight: 500;">
                                <i class="fas fa-check me-2"></i> All modules
                            </label>
                            <input type="radio" class="btn-check" name="module_selection" id="mod_selected" value="selected" onchange="toggleModuleSelection()">
                            <label class="btn btn-outline-success" for="mod_selected" style="padding: 12px 20px; font-weight: 500;">
                                <i class="fas fa-hand-pointer me-2"></i> Custom selection
                            </label>
                        </div>

                        <!-- Modules Checkboxes -->
                        <div id="modules-group" style="display: none; max-height: 350px; overflow-y: auto; border: 1px solid #e0e0e0; padding: 16px; border-radius: 8px; background-color: #fafafa;">
                            <?php
                            $modules = $pdo->prepare("SELECT id, module_name FROM modules WHERE user_id = ? ORDER BY module_name");
                            $modules->execute([$user_id]);
                            $modules_list = $modules->fetchAll();
                            
                            if (empty($modules_list)): ?>
                                <p class="text-muted mb-0"><em>No modules available. Add modules first.</em></p>
                            <?php else: ?>
                                <?php foreach ($modules_list as $m): ?>
                                    <div class="form-check mb-3 p-3" style="background-color: white; border-radius: 6px; border-left: 3px solid var(--gold-primary);">
                                        <input class="form-check-input" type="checkbox" name="modules" value="<?php echo htmlspecialchars($m['module_name']); ?>" id="mod_<?php echo $m['id']; ?>" style="width: 20px; height: 20px; cursor: pointer;">
                                        <label class="form-check-label" for="mod_<?php echo $m['id']; ?>" style="cursor: pointer; font-size: 15px; margin-left: 10px; font-weight: 500; color: var(--heading-color); line-height: 1.5;">
                                            <i class="fas fa-book" style="color: var(--gold-primary); margin-right: 8px; font-size: 14px;"></i>
                                            <?php echo htmlspecialchars($m['module_name']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted d-block mt-3"><i class="fas fa-lightbulb me-2" style="color: #666;"></i>The AI analyzes your notes to optimize priorities and study time.</small>
                    </div>

                    <!-- Step 3: Notes Selection -->
                    <div id="step-content-3" style="display: none;">
                        <h5 style="margin-bottom: 24px;"><i class="fas fa-sticky-note" style="color: #0056b3; margin-right: 10px;"></i>Step 3: Notes Selection</h5>
                        <small class="text-muted d-block mb-4">By default, all notes are used. Choose specific notes to focus on certain topics.</small>
                        <div class="btn-group w-100 mb-4" role="group">
                            <input type="radio" class="btn-check" name="notes_selection" id="notes_all" value="all" checked onchange="toggleNotesSelection()">
                            <label class="btn btn-outline-primary" for="notes_all" style="padding: 12px 20px; font-weight: 500;">
                                <i class="fas fa-check me-2"></i> All notes
                            </label>
                            <input type="radio" class="btn-check" name="notes_selection" id="notes_selected" value="selected" onchange="toggleNotesSelection()">
                            <label class="btn btn-outline-primary" for="notes_selected" style="padding: 12px 20px; font-weight: 500;">
                                <i class="fas fa-hand-pointer me-2"></i> Custom selection
                            </label>
                        </div>

                        <!-- Notes Checkboxes -->
                        <div id="notes-group" style="display: none; max-height: 350px; overflow-y: auto; border: 1px solid #e0e0e0; padding: 16px; border-radius: 8px; margin-bottom: 15px; background-color: #fafafa;">
                            <?php
                            if (empty($all_notes)): ?>
                                <p class="text-muted mb-0"><em>No notes available. Add notes to your modules first.</em></p>
                            <?php else: 
                                $current_module = '';
                                foreach ($all_notes as $note):
                                    if ($current_module !== $note['module_name']):
                                        if ($current_module !== ''):
                                            echo '</div>';
                                        endif;
                                        echo '<div style="margin-bottom: 14px;"><strong style="color: var(--heading-color); font-size: 14px; display: block; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px solid #e0e0e0;">' . htmlspecialchars($note['module_name']) . '</strong>';
                                        $current_module = $note['module_name'];
                                    endif;
                                    $note_preview = htmlspecialchars(substr($note['note_value'], 0, 50));
                                    if (strlen($note['note_value']) > 50) $note_preview .= '...';
                            ?>
                                    <div class="form-check ms-3 mb-2 p-2" style="background-color: white; border-radius: 4px; border-left: 2px solid #28a745;">
                                        <input class="form-check-input" type="checkbox" name="selected_notes" value="<?php echo $note['id']; ?>" id="note_<?php echo $note['id']; ?>" style="width: 18px; height: 18px; cursor: pointer;">
                                        <label class="form-check-label" for="note_<?php echo $note['id']; ?>" style="font-size: 13px; cursor: pointer; margin-left: 8px; color: #333; line-height: 1.4;">
                                            <strong style="color: var(--heading-color); display: block; margin-bottom: 4px;"><?php echo $note_preview; ?></strong>
                                            <small style="color: #888;"><?php echo date('d/m/Y H:i', strtotime($note['created_at'])); ?></small>
                                        </label>
                                    </div>
                            <?php 
                                endforeach;
                                if ($current_module !== '') echo '</div>';
                            endif; ?>
                        </div>
                        <small class="text-muted d-block"><i class="fas fa-lightbulb me-2" style="color: #666;"></i>More comprehensive notes = more accurate AI analysis and better recommendations.</small>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer" style="border-top: 2px solid #f0f0f0;">
                    <button type="button" id="btn-back" class="btn btn-secondary" onclick="previousStep()" style="display: none;">
                        <i class="fas fa-arrow-left me-2"></i> Back
                    </button>
                    <button type="button" id="btn-continue" class="btn btn-primary" onclick="nextStep()">
                        <i class="fas fa-arrow-right me-2"></i> Continue
                    </button>
                    <button type="button" id="btn-generate" class="btn btn-success" onclick="confirmGenerateRecommendation()" style="display: none;">
                        <i class="fas fa-play me-2"></i> Generate Schedule
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Delete Confirmation (Not used - delete uses window.confirm instead) -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/app.js"></script>
    <script>
        // Global variable for delete confirmation
        let modalRecommendationId = null;

        // ===== DELETE RECOMMENDATION (FIXED) =====
        function deleteRecommendation(id) {
            // Use window.confirm for simple, reliable confirmation
            if (confirm('Are you sure you want to delete this recommendation? This action cannot be undone.')) {
                const csrfToken = '<?php echo $csrf; ?>';
                
                fetch('recommendations.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'delete_recommendation',
                        recommendation_id: id,
                        csrf_token: csrfToken
                    }).toString()
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        // Remove the card from DOM
                        const card = document.querySelector(`[data-rec-id="${id}"]`);
                        if (card) {
                            setTimeout(() => card.remove(), 500);
                        }
                    } else {
                        showToast((data.message || 'Error deleting'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Delete error:', error);
                    showToast('Error: ' + error.message, 'error');
                });
            }
        }

        // ===== GENERATE NEW RECOMMENDATION =====
        function generateNewRecommendation() {
            // Reset to step 1
            currentStep = 1;
            updateStepDisplay();
            const modal = new bootstrap.Modal(document.getElementById('configRecommendationModal'));
            modal.show();
        }

        // ===== GLOBAL VARIABLE FOR CURRENT STEP =====
        let currentStep = 1;

        // ===== UPDATE STEP DISPLAY =====
        function updateStepDisplay() {
            // Hide all steps
            document.getElementById('step-content-1').style.display = 'none';
            document.getElementById('step-content-2').style.display = 'none';
            document.getElementById('step-content-3').style.display = 'none';

            // Show current step
            document.getElementById(`step-content-${currentStep}`).style.display = 'block';

            // Update button visibility
            const backBtn = document.getElementById('btn-back');
            const continueBtn = document.getElementById('btn-continue');
            const generateBtn = document.getElementById('btn-generate');

            if (currentStep === 1) {
                backBtn.style.display = 'none';
                continueBtn.style.display = 'inline-block';
                generateBtn.style.display = 'none';
            } else if (currentStep === 2) {
                backBtn.style.display = 'inline-block';
                continueBtn.style.display = 'inline-block';
                generateBtn.style.display = 'none';
            } else if (currentStep === 3) {
                backBtn.style.display = 'inline-block';
                continueBtn.style.display = 'none';
                generateBtn.style.display = 'inline-block';
            }

            // Update stepper circles
            updateStepperCircles();
        }

        // ===== NEXT STEP =====
        function nextStep() {
            if (currentStep < 3) {
                currentStep++;
                updateStepDisplay();
            }
        }

        // ===== PREVIOUS STEP =====
        function previousStep() {
            if (currentStep > 1) {
                currentStep--;
                updateStepDisplay();
            }
        }

        // ===== UPDATE STEPPER CIRCLES =====
        function updateStepperCircles() {
            const circles = document.querySelectorAll('.stepper-circle');
            circles.forEach((circle, index) => {
                const stepNum = index + 1;
                if (stepNum <= currentStep) {
                    circle.style.backgroundColor = 'var(--gold-primary)';
                    circle.style.color = 'white';
                } else {
                    circle.style.backgroundColor = '#ddd';
                    circle.style.color = '#999';
                }
            });
        }

        // ===== SHOW TOAST NOTIFICATION =====
        function showToast(msg, type) {
            const bgColor = type === 'error' ? '#d32f2f' : (type === 'info' ? '#1976d2' : '#4caf50');
            const msgHtml = `<div id="toast-${Date.now()}" class="toast-alert" role="alert" style="
                animation: slideDown 0.4s ease-out;
                margin-bottom: 20px;
                padding: 16px 20px;
                background-color: ${bgColor};
                color: white;
                border-radius: 10px;
                font-weight: 500;
                font-size: 15px;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                position: relative;
            ">
                <span>${msg}</span>
                <button type="button" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer; padding: 0; margin: 0; display: flex; align-items: center;" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>`;
            const container = document.querySelector('.content-padding');
            if (container) {
                container.insertAdjacentHTML('afterbegin', msgHtml);
                const timeout = type === 'error' ? 10000 : 8000;
                setTimeout(() => {
                    const alert = container.querySelector('.toast-alert');
                    if (alert) {
                        alert.style.animation = 'slideUpExit 0.4s ease-out';
                        setTimeout(() => alert.remove(), 400);
                    }
                }, timeout);
            }
        }


        function confirmGenerateRecommendation() {
            // Mark all steps complete visually
            currentStep = 3;
            updateStepperCircles();
            
            const durationInput = document.querySelector('input[name="duration"]');
            const days = durationInput ? durationInput.value : '7';
            
            const moduleSelectionRadio = document.querySelector('input[name="module_selection"]:checked');
            const moduleSelection = moduleSelectionRadio ? moduleSelectionRadio.value : 'all';
            
            const notesSelectionRadio = document.querySelector('input[name="notes_selection"]:checked');
            const notesSelection = notesSelectionRadio ? notesSelectionRadio.value : 'all';
            
            let modules = [];
            let selectedNotes = [];

            if (moduleSelection === 'selected') {
                const checkboxes = document.querySelectorAll('input[name="modules"]:checked');
                if (checkboxes.length === 0) {
                    showToast('Please select at least one module', 'error');
                    return;
                }
                modules = Array.from(checkboxes).map(cb => cb.value);
            }

            if (notesSelection === 'selected') {
                const noteCheckboxes = document.querySelectorAll('input[name="selected_notes"]:checked');
                if (noteCheckboxes.length === 0) {
                    showToast('Please select at least one note', 'error');
                    return;
                }
                selectedNotes = Array.from(noteCheckboxes).map(cb => cb.value);
            }

            showToast('Generating your schedule...', 'info');
            
            const formData = new URLSearchParams({
                csrf_token: '<?php echo $csrf; ?>',
                duration_days: days,
                module_selection: moduleSelection,
                modules: JSON.stringify(modules),
                notes_selection: notesSelection,
                selected_notes: JSON.stringify(selectedNotes)
            });

            fetch('api_gemini.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(r => r.json())
            .then(d => {
                if (d.success && d.preview) {
                    showPreviewModal(d.data, d.plan_json, d.metadata);
                    const modal = bootstrap.Modal.getInstance(document.getElementById('configRecommendationModal'));
                    modal.hide();
                } else if (!d.preview) {
                    showToast('Schedule generated successfully!', 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('configRecommendationModal'));
                    modal.hide();
                    setTimeout(() => location.reload(), 2000);
                } else {
                    const errorMsg = d.message || 'Generation error';
                    showToast(errorMsg, 'error');
                }
            })
            .catch(e => showToast('Network error: ' + e.message, 'error'));
        }

        // Show preview modal
        function showPreviewModal(planData, planJson, metadata) {
            const previewHtml = generatePreviewHTML(planData, metadata);
            
            const modalHtml = `
            <div class="modal fade" id="previewModal" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header" style="background: linear-gradient(135deg, var(--heading-color), var(--sidebar-med)); color: white;">
                            <h5 class="modal-title"><i class="fas fa-calendar-alt me-2"></i>Schedule Preview</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="background-color: #fafafa;">
                            ${previewHtml}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="btn-confirm-save" data-plan="${planJson.replace(/"/g, '&quot;').replace(/'/g, '&#39;')}">
                                <i class="fas fa-check me-2"></i>Confirm & Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            `;
            
            // Remove existing preview modal if any
            const oldModal = document.getElementById('previewModal');
            if (oldModal) oldModal.remove();
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Show preview modal
            const modal = new bootstrap.Modal(document.getElementById('previewModal'));
            modal.show();
            
            // Attach click handler to confirm button
            document.getElementById('btn-confirm-save').addEventListener('click', function() {
                const planData = this.getAttribute('data-plan');
                confirmAndSavePlan(planData);
            });
            
            showToast('Preview ready - review and confirm to save', 'success');
        }

        // Generate preview HTML
        function generatePreviewHTML(planData, metadata) {
            if (!planData.planning) return '<p>No plan data</p>';
            
            let html = `
            <div class="preview-container">
                <div class="alert alert-info mb-4" style="border-radius: 10px;">
                    <strong><i class="fas fa-chart-bar me-2"></i>Schedule Summary:</strong><br>
                    <small><i class="fas fa-calendar-days me-1"></i>${metadata.total_days} days | <i class="fas fa-clock me-1"></i>${metadata.total_sessions} sessions | <i class="fas fa-hourglass-end me-1"></i>${formatTime(metadata.total_minutes)} total</small>
                </div>
            `;
            
            planData.planning.forEach(day => {
                html += `
                <div class="planning-day-card mb-3">
                    <div class="planning-day-header">
                        <h6><i class="fas fa-calendar-day"></i> Day ${day.jour}</h6>
                        <small class="text-muted">${new Date(day.date).toLocaleDateString()}</small>
                    </div>
                    <div class="session-list">
                `;
                
                day.sessions.forEach(session => {
                    const duration = session.duration_minutes || 0;
                    const hours = Math.floor(duration / 60);
                    const mins = duration % 60;
                    const timeStr = hours > 0 ? (mins > 0 ? `${hours}h ${mins}m` : `${hours}h`) : `${mins}m`;
                    const priority = session.priorite || 'moyenne';
                    
                    html += `
                    <div class="session-preview p-2 mb-2" style="border-left: 4px solid ${priority === 'haute' ? '#dc3545' : priority === 'basse' ? '#28a745' : '#ffc107'}; background-color: #f9f9f9;">
                        <strong>${session.module}</strong> | <em>${timeStr}</em> | <span class="badge bg-${priority === 'haute' ? 'danger' : priority === 'basse' ? 'success' : 'warning'}">${priority}</span>
                        ${session.topics && session.topics.length > 0 ? '<br><small class="text-muted"><i class="fas fa-book-open" style="color: var(--gold-primary); margin-right: 6px;"></i>' + session.topics.join(', ') + '</small>' : ''}
                        ${session.description ? '<br><small class="text-muted"><i class="fas fa-lightbulb" style="color: #ffc107; margin-right: 6px;"></i>' + session.description + '</small>' : ''}
                    </div>
                    `;
                });
                
                html += `</div></div>`;
            });
            
            html += `</div>`;
            return html;
        }

        // Helper to format time
        function formatTime(minutes) {
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            if (hours > 0) {
                return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
            }
            return `${mins}m`;
        }

        // ===== VIEW PLANNING DETAIL =====
        function viewPlanningDetail(planData, dateFormatted, planningNumber) {
            if (!planData.planning) {
                showToast('No planning data available', 'error');
                return;
            }

            let totalSessions = 0;
            let totalMinutes = 0;

            let detailHtml = `
            <div class="modal fade" id="planningDetailModal" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header" style="background: linear-gradient(135deg, var(--heading-color), var(--sidebar-med)); color: white;">
                            <h5 class="modal-title"><i class="fas fa-bookmark me-2"></i>Planning ${planningNumber} - Complete Details</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="background-color: #fafafa; max-height: calc(100vh - 200px); overflow-y: auto;">
                            <div class="alert alert-info mb-4" style="border-radius: 10px;">
                                <strong><i class="fas fa-calendar-alt me-2"></i>Generated:</strong> ${dateFormatted}<br>
                                <small style="margin-top: 8px; display: inline-block;"><i class="fas fa-chart-bar me-2"></i><span id="detail-summary"></span></small>
                            </div>
            `;

            planData.planning.forEach((day, idx) => {
                if (!day.sessions) return;

                const dayDate = new Date(day.date);
                const dayName = dayDate.toLocaleDateString('en-US', { weekday: 'long' });
                let dayTotalMins = 0;

                day.sessions.forEach(session => {
                    const duration = (session.duration_minutes || session.duree_minutes || 0);
                    dayTotalMins += duration;
                    totalMinutes += duration;
                    totalSessions++;
                });

                detailHtml += `
                <div class="planning-day-card mb-4" style="background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden;">
                    <div class="planning-day-header" style="background: linear-gradient(135deg, var(--gold-primary), #f0d662); padding: 16px 20px; color: #333; font-weight: 600;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <i class="fas fa-calendar-day" style="margin-right: 10px;"></i>
                                <strong>Day ${day.jour}</strong> - ${dayName}
                                <small style="display: block; color: #555; font-weight: 400; margin-top: 4px;">${dayDate.toLocaleDateString()}</small>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 14px; color: #555;"><i class="fas fa-clock"></i> ${formatTime(dayTotalMins)}</div>
                                <div style="font-size: 14px; color: #555;"><i class="fas fa-book"></i> ${day.sessions.length} session${day.sessions.length > 1 ? 's' : ''}</div>
                            </div>
                        </div>
                    </div>
                    <div style="padding: 16px 20px;">
                `;

                day.sessions.forEach(session => {
                    const duration = session.duration_minutes || session.duree_minutes || 0;
                    const priority = session.priorite || 'moyenne';
                    const priorityLabel = priority === 'haute' ? '<i class="fas fa-arrow-up"></i> High' : priority === 'basse' ? '<i class="fas fa-arrow-down"></i> Low' : '<i class="fas fa-minus"></i> Normal';
                    const priorityColor = priority === 'haute' ? '#dc3545' : priority === 'basse' ? '#28a745' : '#ffc107';

                    detailHtml += `
                    <div style="margin-bottom: 16px; padding: 12px; background-color: #f9f9f9; border-radius: 8px; border-left: 4px solid ${priorityColor};">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <strong style="color: var(--heading-color); font-size: 15px;"><i class="fas fa-book-open" style="margin-right: 8px; color: ${priorityColor};"></i>${session.module}</strong>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <span style="background-color: ${priorityColor}; color: white; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500;">${priorityLabel}</span>
                                <span style="color: #666; font-size: 14px; white-space: nowrap;"><i class="fas fa-hourglass-end"></i> ${formatTime(duration)}</span>
                            </div>
                        </div>
                    `;

                    if (session.topics && session.topics.length > 0) {
                        detailHtml += `
                        <div style="margin-bottom: 8px;">
                            <strong style="color: var(--heading-color); font-size: 13px; display: block; margin-bottom: 6px;"><i class="fas fa-book-open" style="color: var(--gold-primary); margin-right: 6px;"></i>Topics to Review:</strong>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; padding-left: 20px;">
                        `;
                        session.topics.forEach(topic => {
                            detailHtml += `<span style="background-color: #ffd54f; color: #333; padding: 4px 10px; border-radius: 12px; font-size: 12px;">${topic}</span>`;
                        });
                        detailHtml += `</div></div>`;
                    }

                    if (session.description) {
                        detailHtml += `
                        <div style="color: #666; font-size: 13px; padding: 8px; background-color: #fff; border-radius: 4px; margin-top: 8px;">
                            <i class="fas fa-lightbulb" style="color: #ffc107; margin-right: 6px;"></i>
                            <strong>Notes:</strong> ${session.description}
                        </div>`;
                    }

                    detailHtml += `</div>`;
                });

                detailHtml += `</div></div>`;
            });

            // Calculate summary
            const totalDays = planData.planning.length;
            detailHtml = detailHtml.replace('id="detail-summary"></span>', `id="detail-summary"></span>
                <span style="display: inline-block; margin-right: 8px;"><i class="fas fa-calendar-days"></i> ${totalDays} days</span>
                <span style="display: inline-block; margin-right: 8px;">|</span>
                <span style="display: inline-block; margin-right: 8px;"><i class="fas fa-book"></i> ${totalSessions} sessions</span>
                <span style="display: inline-block; margin-right: 8px;">|</span>
                <span style="display: inline-block;"><i class="fas fa-hourglass-end"></i> ${formatTime(totalMinutes)}</span>`);

            detailHtml += `
                        </div>
                    </div>
                </div>
            </div>
            `;

            // Remove existing detail modal if any
            const oldModal = document.getElementById('planningDetailModal');
            if (oldModal) oldModal.remove();

            document.body.insertAdjacentHTML('beforeend', detailHtml);

            // Show detail modal
            const modal = new bootstrap.Modal(document.getElementById('planningDetailModal'));
            modal.show();
        }

        // Confirm and save plan
        function confirmAndSavePlan(planJsonString) {
            showToast('Saving your schedule...', 'info');
            
            const formData = new URLSearchParams({
                csrf_token: '<?php echo $csrf; ?>',
                confirm_save: '1',
                plan_data: planJsonString
            });

            fetch('api_gemini.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    showToast('Schedule saved successfully!', 'success');
                    
                    // Close preview modal
                    bootstrap.Modal.getInstance(document.getElementById('previewModal')).hide();
                    
                    // Reload page
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(d.message || 'Error saving', 'error');
                }
            })
            .catch(e => showToast('Network error: ' + e.message, 'error'));
        }

        // Show/hide modules based on selection
        function toggleModuleSelection() {
            const selection = document.querySelector('input[name="module_selection"]:checked').value;
            const modulesGroup = document.getElementById('modules-group');
            if (selection === 'selected') {
                modulesGroup.style.display = 'block';
                // Filter notes when modules are selected
                filterNotesByModules();
            } else {
                modulesGroup.style.display = 'none';
                // Show all notes when "all modules" selected
                showAllNotes();
            }
        }

        // Filter notes to show only notes from selected modules
        function filterNotesByModules() {
            const selectedModules = Array.from(document.querySelectorAll('input[name="modules"]:checked')).map(cb => cb.value);
            
            if (selectedModules.length === 0) {
                showAllNotes();
                return;
            }
            
            // Hide all module groups
            const moduleGroups = document.querySelectorAll('#notes-group > div');
            moduleGroups.forEach(group => {
                const moduleName = group.querySelector('strong').textContent;
                if (selectedModules.includes(moduleName)) {
                    group.style.display = 'block';
                } else {
                    group.style.display = 'none';
                }
            });
        }

        // Show all notes (all modules)
        function showAllNotes() {
            const moduleGroups = document.querySelectorAll('#notes-group > div');
            moduleGroups.forEach(group => {
                group.style.display = 'block';
            });
        }

        // Re-filter notes when module checkboxes change
        document.addEventListener('change', function(e) {
            if (e.target.name === 'modules') {
                filterNotesByModules();
            }
        });

        // Show/hide notes based on selection
        function toggleNotesSelection() {
            const selection = document.querySelector('input[name="notes_selection"]:checked').value;
            const notesGroup = document.getElementById('notes-group');
            if (selection === 'selected') {
                notesGroup.style.display = 'block';
            } else {
                notesGroup.style.display = 'none';
            }
        }
    </script>

</body>
</html>



