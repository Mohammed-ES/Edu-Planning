<?php
require_once 'auth.php';
require_login();

// Fetch stats
$user_id = $_SESSION['user_id'];

// 1. Total modules
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM modules WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_modules = $stmt->fetch()['total'];

// 2. Total remarks
$stmt = $pdo->prepare("SELECT COUNT(n.id) as total_notes FROM notes n JOIN modules m ON n.module_id = m.id WHERE m.user_id = ?");
$stmt->execute([$user_id]);
$total_notes = $stmt->fetch()['total_notes'];

// 3. Planned sessions (Generated Plans from ai_recommendations)
$stmt = $pdo->prepare("SELECT COUNT(*) as sessions FROM ai_recommendations WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_sessions = $stmt->fetch()['sessions'];

// 4. Days remaining (extracted from latest ai_recommendations plan_data if exists)
$days_remaining = 0;
if ($total_sessions > 0) {
    // Get the latest recommendation to extract end_date
    $stmt = $pdo->prepare("SELECT recommendation FROM ai_recommendations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $latest_rec = $stmt->fetch();
    if ($latest_rec) {
        $rec_data = json_decode($latest_rec['recommendation'], true);
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

// Fetch modules with their notes
$stmt = $pdo->prepare("
    SELECT m.id, m.module_name, MAX(n.note_value) as note_value
    FROM modules m
    LEFT JOIN notes n ON m.id = n.module_id
    WHERE m.user_id = ?
    GROUP BY m.id
");
$stmt->execute([$user_id]);
$modules = $stmt->fetchAll();

// Count modules that have at least one note
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT m.id) as modules_with_notes
    FROM modules m
    JOIN notes n ON m.id = n.module_id
    WHERE m.user_id = ?
");
$stmt->execute([$user_id]);
$modules_with_notes = $stmt->fetch()['modules_with_notes'] ?? 0;

// Fetch modules without notes
$stmt = $pdo->prepare("
    SELECT m.id, m.module_name
    FROM modules m
    LEFT JOIN notes n ON m.id = n.module_id
    WHERE m.user_id = ? AND n.id IS NULL
    ORDER BY m.module_name ASC
");
$stmt->execute([$user_id]);
$modules_without_notes = $stmt->fetchAll();

// Count modules without notes
$modules_without_notes_count = $total_modules - $modules_with_notes;

// Fetch username
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$username = htmlspecialchars($user['username'] ?? 'User');
$initial = strtoupper(substr($username, 0, 1));

// Calculate percentages for progress bars
$modules_pct  = $total_modules > 0 ? intval(($modules_with_notes / $total_modules) * 100) : 0;
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
            margin-bottom: 12px;
            padding-bottom: 12px;
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
            color: var(--white);
            font-weight: 600;
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
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes countPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.04); }
        }

        /* Stat cards */
        .stat-card {
            background: var(--card-bg);
            padding: 24px 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            transition: all 0.25s ease;
            animation: slideUp 0.35s ease-out both;
            border: 1px solid var(--border-color);
            border-top: 3px solid var(--gold-primary);
            min-height: 160px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 100%;
        }

        .stat-card:hover {
            border-color: var(--gold-primary);
            box-shadow: 0 4px 16px rgba(184,134,11,0.12);
            transform: translateY(-3px);
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            background: rgba(184,134,11,0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: var(--gold-primary);
            margin-bottom: 12px;
        }

        .stat-value {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            color: var(--heading-color);
            line-height: 1;
            margin-bottom: 6px;
        }

        .stat-value sup {
            font-size: 22px;
            color: var(--gold-primary);
            vertical-align: super;
        }

        .stat-label {
            font-size: 10px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        /* Diagram cards */
        .diagram-card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            border: 1px solid var(--border-color);
            border-top: 3px solid var(--gold-primary);
            animation: slideUp 0.35s ease-out both;
            transition: all 0.25s ease;
        }

        .diagram-card:hover {
            border-color: var(--gold-primary);
            box-shadow: 0 4px 16px rgba(184,134,11,0.12);
        }

        .section-header {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-weight: 600;
            color: var(--heading-color);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .section-logo {
            width: 32px;
            height: 32px;
            background: var(--gold-primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 16px;
            flex-shrink: 0;
        }

        /* Progress bars */
        .progress-bar-custom {
            height: 8px;
            background: var(--border-color);
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--gold-primary), var(--gold-light));
            border-radius: 20px;
            transition: width 1s ease-out;
            width: 0%;
        }

        /* Module cards */
        .module-card {
            background: var(--card-bg);
            padding: 18px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            border: 1px solid var(--border-color);
            border-left: 3px solid var(--gold-primary);
            transition: all 0.25s ease;
            animation: slideUp 0.35s ease-out both;
        }

        .module-card:hover {
            border-color: var(--gold-primary);
            border-left: 3px solid var(--gold-primary);
            box-shadow: 0 4px 16px rgba(184,134,11,0.12);
            transform: translateY(-2px);
        }

        .module-title {
            font-family: 'Playfair Display', serif;
            font-size: 15px;
            font-weight: 600;
            color: var(--heading-color);
            margin-bottom: 8px;
        }

        /* Empty state */
        .empty-state {
            padding: 40px;
            text-align: center;
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            animation: slideUp 0.35s ease-out;
        }

        .empty-state i {
            font-size: 48px;
            color: var(--gold-primary);
            margin-bottom: 15px;
            opacity: 0.8;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-mid));
            border: none;
            color: var(--white);
            font-weight: 700;
            border-radius: 8px;
            padding: 10px 20px;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--gold-mid), var(--gold-primary));
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(184,134,11,0.3);
            color: var(--white);
        }

        .btn-primary:active {
            transform: scale(0.97);
        }

        /* Responsive design */
        @media (max-width: 1024px) {
            .sidebar { width: 70px; }
            .logo-area span { display: none; }
            .main-content { margin-left: 70px; }
            .sidebar .components li a span { display: none; }
            .sidebar .components li a { padding: 10px 14px; justify-content: center; }
            .sidebar .components li a:hover { padding-left: 14px; }
            .content-padding { padding: 20px; }
        }

        @media (max-width: 768px) {
            .page-title { font-size: 22px; }
            .content-padding { padding: 15px; }
            .stat-value { font-size: 28px; }
            .section-header { font-size: 16px; }
        }

        /* Animation delays */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .scroll-reveal {
            animation: slideUp 0.35s ease-out;
        }

        /* Welcome Screen Styles — Enhanced */
        .welcome-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                135deg,
                #1C0A02 0%,
                #3D1C08 25%,
                #6B3410 50%,
                #8B4513 70%,
                #5C2E0E 100%
            );
            background-size: 300% 300%;
            animation: gradientShift 6s ease infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            overflow: hidden;
        }

        /* Geometric pattern overlay */
        .welcome-overlay::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23C8962E' fill-opacity='0.06'%3E%3Cpath d='M40 0L49 31H80L56 50L65 80L40 62L15 80L24 50L0 31H31Z'/%3E%3C/g%3E%3C/svg%3E");
            background-repeat: repeat;
            background-size: 80px 80px;
            pointer-events: none;
        }

        .welcome-content {
            text-align: center;
            color: #FFFFFF;
            position: relative;
            z-index: 2;
        }

        /* Logo wrapper with 3 concentric rings */
        .welcome-logo-wrapper {
            position: relative;
            width: 180px;
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 40px auto;
            animation: fadeIn 0.4s ease both;
            animation-delay: 0.2s;
        }

        /* Rings — improved visibility */
        .ring {
            position: absolute;
            border-radius: 50%;
            border: transparent solid;
        }

        /* Ring 1 — clockwise, gold top and right */
        .ring-1 {
            width: 170px;
            height: 170px;
            border: 2.5px solid transparent;
            border-top-color: #C8962E;
            border-right-color: #C8962E;
            animation: spinCW 2.5s linear infinite;
            filter: drop-shadow(0 0 4px rgba(200, 150, 46, 0.6));
        }

        /* Ring 2 — counter-clockwise, lighter gold */
        .ring-2 {
            width: 148px;
            height: 148px;
            border: 1.5px solid transparent;
            border-bottom-color: rgba(212, 168, 67, 0.7);
            border-left-color: rgba(212, 168, 67, 0.7);
            animation: spinCCW 3.5s linear infinite;
            filter: drop-shadow(0 0 3px rgba(212, 168, 67, 0.4));
        }

        /* Ring 3 — dashed, subtle */
        .ring-3 {
            width: 128px;
            height: 128px;
            border: 1px dashed rgba(200, 150, 46, 0.3);
            animation: spinCW 6s linear infinite;
        }

        /* Luminous points on ring-1 */
        .ring-1::before,
        .ring-1::after {
            content: '';
            position: absolute;
            width: 6px;
            height: 6px;
            background: #C8962E;
            border-radius: 50%;
            box-shadow: 0 0 8px #C8962E, 0 0 16px rgba(200, 150, 46, 0.5);
            top: -3px;
            left: 50%;
            transform: translateX(-50%);
        }
        .ring-1::after {
            top: auto;
            bottom: -3px;
        }

        /* Enhanced logo container — transparent with backdrop blur */
        .welcome-logo-inner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 110px;
            height: 110px;
            border-radius: 50%;
            overflow: hidden;
            /* Semi-transparent radial gradient */
            background: radial-gradient(
                circle at center,
                rgba(255, 255, 255, 0.18) 0%,
                rgba(255, 255, 255, 0.08) 60%,
                transparent 100%
            );
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 2px solid rgba(200, 150, 46, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            /* Soft golden glow */
            box-shadow:
                0 0 0 1px rgba(200, 150, 46, 0.2),
                0 0 25px rgba(200, 150, 46, 0.35),
                0 0 60px rgba(200, 150, 46, 0.12),
                inset 0 0 20px rgba(200, 150, 46, 0.08);
            animation: zoomIn 0.9s cubic-bezier(0.34, 1.56, 0.64, 1) both 0.3s,
                logoGlowEnhanced 2.5s ease-in-out infinite 1.2s;
        }

        /* Logo image — rendered white on dark background */
        .welcome-logo-inner img {
            width: 90px;
            height: 90px;
            object-fit: contain;
            filter: brightness(0) invert(1) drop-shadow(0 2px 8px rgba(200, 150, 46, 0.5));
            animation: zoomIn 0.9s cubic-bezier(0.34, 1.56, 0.64, 1) both 0.3s;
        }

        .welcome-title {
            font-family: 'Playfair Display', serif;
            font-size: 76px;
            font-weight: 900;
            color: #FFFFFF;
            letter-spacing: 3px;
            margin: 0;
            line-height: 1;
            text-shadow: 0 6px 40px rgba(0, 0, 0, 0.5);
            animation: blurReveal 1s cubic-bezier(0.22, 1, 0.36, 1) both;
            animation-delay: 0.9s;
        }

        .welcome-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #C8962E 40%, #D4A843 60%, transparent);
            margin: 16px auto;
            display: block;
            width: 160px;
            animation: lineFromCenter 0.8s cubic-bezier(0.22, 1, 0.36, 1) both;
            animation-delay: 1.3s;
        }

        .welcome-name {
            font-family: 'Playfair Display', serif;
            font-size: 34px;
            font-weight: 700;
            font-style: italic;
            color: #C8962E;
            display: block;
            margin: 0;
            text-shadow: 0 0 30px rgba(200, 150, 46, 0.5),
                         0 2px 10px rgba(0, 0, 0, 0.3);
            animation: nameRise 0.8s cubic-bezier(0.22, 1, 0.36, 1) both;
            animation-delay: 1.6s;
        }

        .welcome-subtitle {
            font-family: 'Roboto', sans-serif;
            font-size: 11px;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.55);
            letter-spacing: 4px;
            text-transform: uppercase;
            margin: 10px 0 0 0;
            animation: subtitleFade 0.8s ease both;
            animation-delay: 2s;
        }

        .welcome-progress-bar {
            width: 220px;
            height: 3px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 2px;
            margin: 32px auto 0;
            overflow: hidden;
            animation: fadeIn 0.5s ease both;
            animation-delay: 2.3s;
        }

        .welcome-progress-fill {
            height: 100%;
            width: 0%;
            border-radius: 2px;
            background: linear-gradient(
                90deg,
                #6B3410 0%,
                #C8962E 30%,
                #D4A843 50%,
                #C8962E 70%,
                #6B3410 100%
            );
            background-size: 200% auto;
            animation:
                barFill    1.8s cubic-bezier(0.4, 0, 0.2, 1) both 2.3s,
                barShimmer 1.2s linear 2.5s infinite;
            box-shadow: 0 0 8px rgba(200, 150, 46, 0.6), 0 0 16px rgba(200, 150, 46, 0.2);
        }

        .welcome-loading-text {
            font-family: 'Roboto', sans-serif;
            font-size: 10px;
            color: rgba(255, 255, 255, 0.3);
            letter-spacing: 3px;
            text-transform: uppercase;
            margin: 14px 0 0 0;
            animation: fadeIn 0.5s ease both 2.6s;
        }

        .welcome-loading-text::after {
            content: '';
            animation: dotsAppear 1.4s steps(4, end) 2.8s infinite;
        }
    </style>
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
                <li><a href="modules.php"><i class="fas fa-book"></i> Modules & Notes</a></li>
                <li><a href="planning.php"><i class="fas fa-calendar"></i> Schedule</a></li>
                <li><a href="recommendations.php"><i class="fas fa-star"></i> Recommendations</a></li>
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
                            <div class="stat-icon"><i class="fas fa-edit"></i></div>
                            <div class="stat-value" data-count="<?php echo $total_notes; ?>">0</div>
                            <div class="stat-label">Notes Entered</div>
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
                            <div class="stat-icon"><i class="fas fa-bookmark"></i></div>
                            <div class="stat-value" data-count="<?php echo $modules_without_notes_count; ?>">0</div>
                            <div class="stat-label">Without Notes</div>
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
                                Progress Status
                            </div>
                            <canvas id="progressChart" style="max-height: 300px;"></canvas>
                        </div>
                    </div>

                    <!-- Progress bars -->
                    <div class="col-lg-6">
                        <div class="diagram-card scroll-reveal">
                            <div class="section-header">
                                <div class="section-logo"><i class="fas fa-tasks"></i></div>
                                Achievement Summary
                            </div>
                            <div style="padding: 16px 0;">
                                <div style="margin-bottom: 22px;">
                                    <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:14px;">
                                        <span style="font-weight:600; color:var(--heading-color);">Modules with notes</span>
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

                <!-- Modules Overview -->
                <div class="scroll-reveal">
                    <div class="section-header">
                        <div class="section-logo"><i class="fas fa-book"></i></div>
                        My Modules
                    </div>

                    <?php if (empty($modules)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h4 style="margin-top: 15px; margin-bottom: 10px; font-family: 'Playfair Display', serif; font-size: 22px; color: var(--heading-color); font-weight: 700;">No modules added</h4>
                            <p class="text-muted">Start by adding a module to organize your revisions</p>
                            <a href="modules.php" class="btn btn-primary mt-3">
                                <i class="fas fa-plus me-2"></i>Add a module
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($modules as $i => $mod): ?>
                                <div class="col-md-4">
                                    <div class="module-card">
                                        <div class="module-title"><?php echo htmlspecialchars($mod['module_name']); ?></div>
                                        <div style="font-size:13px; color:#7A7A7A; line-height:1.6; margin-bottom:12px;">
                                            <i class="fas fa-quote-left" style="color:var(--gold-primary); margin-right:6px; font-size:11px;"></i>
                                            <em><?php echo htmlspecialchars(mb_strimwidth($mod['note_value'] ?? 'No note', 0, 60, "...")); ?></em>
                                        </div>
                                        <a href="modules.php?view=details&id=<?php echo $mod['id']; ?>" style="font-size:12px; color:var(--gold-primary); font-weight:600; text-decoration:none;">
                                            View details <i class="fas fa-arrow-right" style="font-size:10px;"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div>

            </div><!-- /content-padding -->
        </div><!-- /main-content -->
    </div><!-- /wrapper -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Progress Chart Script -->
    <script>
        function initProgressChart() {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not available, retrying...');
                setTimeout(initProgressChart, 500);
                return;
            }

            const progressChartCanvas = document.getElementById('progressChart');
            if (progressChartCanvas) {
                const totalModules      = <?php echo $total_modules; ?>;
                const modulesWithNotes  = <?php echo $modules_with_notes; ?>;
                const withoutNotes      = Math.max(0, totalModules - modulesWithNotes);

                new Chart(progressChartCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: ['With notes', 'Without notes'],
                        datasets: [{
                            data: [modulesWithNotes, withoutNotes],
                            backgroundColor: ['#D4AF37', '#6B4423'],
                            borderColor: '#FFFFFF',
                            borderWidth: 3,
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    font: { family: "'Inter', sans-serif", size: 13, weight: '500' },
                                    color: '#333333',
                                    padding: 18,
                                    usePointStyle: true,
                                    pointStyleWidth: 10
                                }
                            }
                        },
                        animation: { animateRotate: true, duration: 1200 }
                    }
                });
            }
        }

        // Initialize chart when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initProgressChart);
        } else {
            initProgressChart();
        }
    </script>

    <!-- App JS (includes all animations, welcome screen, counter setup) -->
    <script src="assets/app.js"></script>
</body>
</html>


