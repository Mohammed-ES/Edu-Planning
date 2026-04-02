<?php
require_once 'auth.php';
require_login();
$user_id = $_SESSION['user_id'];

// Check if viewing detail page
$view = $_GET['view'] ?? 'list';
$current_module = null;
$current_module_notes = [];

if ($view === 'details') {
    $module_id = (int)($_GET['id'] ?? 0);
    
    // Fetch module details
    $stmt = $pdo->prepare("SELECT id, module_name FROM modules WHERE id = ? AND user_id = ?");
    $stmt->execute([$module_id, $user_id]);
    $current_module = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_module) {
        header('Location: modules.php');
        exit;
    }
    
    // Fetch all notes for this module
    $stmt = $pdo->prepare("
        SELECT id, note_value, DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') as created_at 
        FROM notes 
        WHERE module_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$module_id]);
    $current_module_notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!verify_csrf_token($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit;
    }

    $action = $_POST['action'];

    if ($action === 'add_module') {
        $name = trim($_POST['module_name']);
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Module name required']);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO modules (user_id, module_name) VALUES (?, ?)");
        if ($stmt->execute([$user_id, $name])) {
            logAction($user_id, 'module_added', $pdo);
            echo json_encode(['success' => true, 'message' => 'Module added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error adding module']);
        }
        exit;
    }

    if ($action === 'delete_module') {
        $id = (int)$_POST['module_id'];
        $stmt = $pdo->prepare("DELETE FROM modules WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$id, $user_id])) {
            logAction($user_id, 'module_deleted', $pdo);
            echo json_encode(['success' => true, 'message' => 'Module deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting module']);
        }
        exit;
    }

    if ($action === 'delete_note') {
        $note_id = (int)$_POST['note_id'];
        // Verify note belongs to user's module
        $stmt = $pdo->prepare("
            SELECT n.id FROM notes n
            JOIN modules m ON n.module_id = m.id
            WHERE n.id = ? AND m.user_id = ?
        ");
        $stmt->execute([$note_id, $user_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Invalid note']);
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ?");
        if ($stmt->execute([$note_id])) {
            logAction($user_id, 'note_deleted', $pdo);
            echo json_encode(['success' => true, 'message' => 'Note deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting note']);
        }
        exit;
    }

    if ($action === 'edit_note') {
        $note_id = (int)$_POST['note_id'];
        $note_value = trim($_POST['note_value']);

        if (empty($note_value)) {
            echo json_encode(['success' => false, 'message' => 'Note cannot be empty']);
            exit;
        }

        // Verify note belongs to user's module
        $stmt = $pdo->prepare("
            SELECT n.id FROM notes n
            JOIN modules m ON n.module_id = m.id
            WHERE n.id = ? AND m.user_id = ?
        ");
        $stmt->execute([$note_id, $user_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Invalid note']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE notes SET note_value = ? WHERE id = ?");
        if ($stmt->execute([$note_value, $note_id])) {
            logAction($user_id, 'note_edited', $pdo);
            echo json_encode(['success' => true, 'message' => 'Note updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating note']);
        }
        exit;
    }

    if ($action === 'add_note') {
        $module_id = (int)$_POST['module_id'];
        $note = trim($_POST['note_value']);
        
        // Ensure module belongs to user
        $stmt = $pdo->prepare("SELECT id FROM modules WHERE id = ? AND user_id = ?");
        $stmt->execute([$module_id, $user_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Module invalide']);
            exit;
        }

        try {
            $pdo->exec("ALTER TABLE notes MODIFY note_value TEXT");
        } catch(Exception $e) {}

        $stmt = $pdo->prepare("INSERT INTO notes (module_id, note_value) VALUES (?, ?)");
        if ($stmt->execute([$module_id, $note])) {
            logAction($user_id, 'note_added', $pdo);
            echo json_encode(['success' => true, 'message' => 'Note added']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error adding note']);
        }
        exit;
    }
}

// Fetch modules list (only if not on detail page or for sidebar)
$stmt = $pdo->prepare("
    SELECT m.id, m.module_name, COUNT(n.id) as note_count
    FROM modules m 
    LEFT JOIN notes n ON m.id = n.module_id 
    WHERE m.user_id = ?
    GROUP BY m.id
    ORDER BY m.id DESC
");
$stmt->execute([$user_id]);
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch username
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$username = htmlspecialchars($user['username'] ?? 'User');
$csrf = htmlspecialchars($_SESSION['csrf_token']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modules & Notes - Edu-Planning</title>
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

        /* Module Cards */
        .module-card {
            background: var(--white);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            animation: slideUp 0.6s ease-out both;
            border-top: 3px solid var(--gold-primary);
        }

        .module-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.15);
        }

        .module-title {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--heading-color);
            margin-bottom: 12px;
        }

        .empty-state {
            padding: 40px;
            text-align: center;
            background: var(--white);
            border-radius: 15px;
            color: var(--text-primary);
            animation: slideUp 0.6s ease-out;
        }

        .empty-state i {
            font-size: 48px;
            color: var(--gold-primary);
            margin-bottom: 15px;
        }

        /* Toast Messages */
        .alert {
            animation: slideDown 0.4s ease-out !important;
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

        /* Modal */
        .modal-header {
            background: linear-gradient(135deg, var(--heading-color), var(--sidebar-med));
            color: var(--white);
        }

        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }

        .form-control:focus {
            border-color: var(--gold-primary);
            box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.25);
        }

        /* Search Bar */
        .input-group-text {
            border: 1px solid #e0e0e0;
            color: var(--heading-color);
        }

        .form-control {
            border: 1px solid #e0e0e0;
            font-size: 14px;
        }

        /* Module List View */
        .module-list-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .module-list-item {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            border-left: 5px solid var(--gold-primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            animation: slideUp 0.5s ease-out both;
        }

        .module-list-item:hover {
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.2);
            transform: translateX(5px);
        }

        .module-list-info {
            flex: 1;
        }

        .module-list-name {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--heading-color);
            margin-bottom: 6px;
        }

        .module-list-count {
            font-size: 13px;
            color: var(--text-primary);
        }

        .module-list-count i {
            color: var(--gold-primary);
            margin-right: 6px;
        }

        .module-list-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .badge-count {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-light));
            color: var(--heading-color);
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
        }

        .btn-mod-delete {
            background: #ff5252;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .btn-mod-delete:hover {
            background: #ff1744;
            transform: scale(1.05);
        }

        /* Detail View - Enhanced Styling */
        .detail-header {
            background: linear-gradient(135deg, var(--heading-color) 0%, #7a4d2a 100%);
            color: var(--white);
            padding: 36px;
            border-radius: 14px;
            margin-bottom: 36px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 12px 40px rgba(107, 68, 35, 0.18);
            position: relative;
            overflow: hidden;
        }

        .detail-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(212, 175, 55, 0.06);
            pointer-events: none;
        }

        .detail-header-left {
            position: relative;
            z-index: 1;
            flex: 1;
        }

        .detail-header-left h2 {
            font-family: 'Playfair Display', serif;
            font-size: 34px;
            font-weight: 800;
            margin: 0;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .detail-header-left p {
            margin: 0;
            opacity: 0.95;
            font-size: 14px;
            font-weight: 500;
        }

        .btn-back {
            background: var(--white);
            color: var(--heading-color);
            border: none;
            padding: 11px 22px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            font-size: 13.5px;
        }

        .btn-back:hover {
            transform: translateX(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
            background: var(--page-bg);
        }

        .btn-delete-detail {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border: none;
            padding: 11px 22px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.25);
            font-size: 13.5px;
        }

        .btn-delete-detail:hover {
            background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(231, 76, 60, 0.35);
        }

        .notes-container {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .note-card {
            background: var(--white);
            padding: 22px;
            border-radius: 10px;
            border-left: 5px solid var(--gold-primary);
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
            animation: slideUp 0.6s ease-out both;
            transition: all 0.3s ease;
            position: relative;
        }

        .note-card:hover {
            box-shadow: 0 6px 20px rgba(107, 68, 35, 0.1);
            transform: translateY(-2px);
        }

        .note-card:nth-child(2) { animation-delay: 0.1s; }
        .note-card:nth-child(3) { animation-delay: 0.2s; }
        .note-card:nth-child(4) { animation-delay: 0.3s; }
        .note-card:nth-child(5) { animation-delay: 0.4s; }

        .note-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 14px;
            padding-bottom: 12px;
            border-bottom: 1.5px solid #f0f0f0;
            gap: 10px;
        }

        .note-header strong {
            color: var(--heading-color);
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            font-weight: 700;
        }

        .note-date {
            font-size: 12px;
            color: #aaa;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .note-content {
            color: var(--text-primary);
            line-height: 1.75;
            font-size: 14px;
            white-space: normal;
            word-break: break-word;
            margin-bottom: 0;
            font-family: 'Inter', sans-serif;
        }

        .btn-note-delete {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .btn-note-delete:hover {
            background: #c0392b;
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(231, 76, 60, 0.25);
        }

        .btn-note-edit {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-light));
            color: var(--heading-color);
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .btn-note-edit:hover {
            background: linear-gradient(135deg, var(--gold-light), var(--gold-primary));
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(212, 175, 55, 0.25);
        }

        .notes-empty {
            text-align: center;
            padding: 70px 40px;
            background: linear-gradient(135deg, rgba(107, 68, 35, 0.02) 0%, rgba(212, 175, 55, 0.03) 100%);
            border-radius: 10px;
            color: var(--text-primary);
            border: 2px dashed var(--gold-light);
        }

        .notes-empty i {
            font-size: 56px;
            color: var(--gold-light);
            margin-bottom: 12px;
            display: block;
            opacity: 0.8;
        }

        /* Modal Styling */
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 12px 40px rgba(107, 68, 35, 0.2);
            background: white;
            overflow: hidden;
        }

        .modal-header {
            border: none;
            padding: 0 !important;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1) !important;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .modal-header .btn-close:hover {
            opacity: 1;
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
                <li class="active"><a href="modules.php"><i class="fas fa-book"></i> Modules & Notes</a></li>
                <li><a href="planning.php"><i class="fas fa-calendar"></i> Schedule</a></li>
                <li><a href="recommendations.php"><i class="fas fa-star"></i> Recommendations</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-navbar">
                <h2 class="page-title m-0">Module Management</h2>
                <div class="d-flex align-items-center gap-3">
                    <span style="font-weight: 500;">Hello, <?php echo $username; ?></span>
                    <div style="width:40px;height:40px;background:linear-gradient(135deg, var(--gold-primary), var(--gold-light));border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--heading-color);font-weight:bold;font-size:16px;">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                </div>
            </div>

            <div class="content-padding">
                <?php if ($view === 'details' && $current_module): ?>
                    <!-- DETAIL VIEW -->
                    <div class="detail-header">
                        <div class="detail-header-left">
                            <h2><i class="fas fa-book-open me-3"></i><?php echo htmlspecialchars($current_module['module_name']); ?></h2>
                            <p>Manage your notes and academic remarks</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="modules.php" class="btn-back">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </a>
                            <button class="btn-delete-detail" onclick="deleteModuleFromDetail(<?php echo $current_module['id']; ?>)">
                                <i class="fas fa-trash-alt me-2"></i>Delete Module
                            </button>
                        </div>
                    </div>

                    <!-- Notes Section -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; gap: 20px;">
                        <h4 style="margin: 0; font-family: 'Playfair Display', serif; color: var(--heading-color); font-weight: 700; font-size: 20px;">
                            <i class="fas fa-sticky-note me-2" style="color: var(--gold-primary);"></i>My Notes <span style="font-size: 16px; font-weight: 600; color: #999;">(<?php echo count($current_module_notes); ?>)</span>
                        </h4>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNoteModal" onclick="setCurrentModule(<?php echo $current_module['id']; ?>, '<?php echo addslashes(htmlspecialchars($current_module['module_name'])); ?>')" style="white-space: nowrap; padding: 10px 20px; font-weight: 600; font-size: 13.5px;">
                            <i class="fas fa-plus me-1"></i>Add a Note
                        </button>
                    </div>

                    <?php if (empty($current_module_notes)): ?>
                        <div class="notes-empty">
                            <i class="fas fa-inbox"></i>
                            <h5 style="font-family: 'Playfair Display', serif; font-size: 22px; color: var(--heading-color); margin-top: 15px; margin-bottom: 10px; font-weight: 700;">No notes yet</h5>
                            <p class="text-muted">Start by adding notes to remember the important points of this module.</p>
                        </div>
                    <?php else: ?>
                        <div class="notes-container">
                            <?php foreach ($current_module_notes as $index => $note): ?>
                                <div class="note-card">
                                    <div class="note-header">
                                        <div>
                                            <strong style="color: var(--heading-color); font-family: 'Playfair Display', serif; font-size: 16px;">
                                                Note #<?php echo $index + 1; ?>
                                            </strong>
                                        </div>
                                        <div class="d-flex gap-2 align-items-center">
                                            <span class="note-date"><?php echo $note['created_at']; ?></span>
                                            <button class="btn-note-edit" onclick="editNoteFromDetail(<?php echo $note['id']; ?>, `<?php echo addslashes($note['note_value']); ?>`)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn-note-delete" onclick="deleteNoteFromDetail(<?php echo $note['id']; ?>)">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                    <div class="note-content">
                                        <?php echo htmlspecialchars($note['note_value']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- LIST VIEW -->
                    <div class="row align-items-center mb-5">
                        <div class="col-md-8">
                            <h3 style="font-family: 'Playfair Display', serif; color: var(--heading-color); font-weight: 800; margin-bottom: 8px;">
                                <i class="fas fa-book me-2" style="color: var(--gold-primary);"></i>My Study Modules
                            </h3>
                            <p class="text-muted small">Click on a module to see all details</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModuleModal">
                                <i class="fas fa-plus me-2"></i><strong>Add a Module</strong>
                            </button>
                        </div>
                    </div>

                    <!-- Search Bar -->
                    <div class="mb-4">
                        <div class="input-group" style="max-width: 400px;">
                            <span class="input-group-text" style="background: var(--white); border: 2px solid var(--page-bg);">
                                <i class="fas fa-search" style="color: var(--heading-color);"></i>
                            </span>
                            <input type="text" class="form-control" id="searchModule" placeholder="Search for a module..." style="border: 2px solid var(--page-bg);">
                        </div>
                    </div>

                    <!-- Modules List -->
                    <?php if (empty($modules)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox fa-4x mb-3"></i>
                            <h4>No modules added</h4>
                            <p class="text-muted mt-2">Start by adding your first study modules to organize your revision.</p>
                            <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addModuleModal">
                                <i class="fas fa-plus me-2"></i>Create My First Module
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="module-list-container" id="modulesContainer">
                            <?php foreach ($modules as $mod): ?>
                                <div class="module-list-item module-col" data-name="<?php echo strtolower(htmlspecialchars($mod['module_name'])); ?>" onclick="window.location.href='modules.php?view=details&id=<?php echo $mod['id']; ?>'">
                                    <div class="module-list-info">
                                        <div class="module-list-name">
                                            <i class="fas fa-book-open" style="color: var(--gold-primary); margin-right: 12px;"></i><?php echo htmlspecialchars($mod['module_name']); ?>
                                        </div>
                                        <div class="module-list-count">
                                            <i class="fas fa-sticky-note"></i><?php echo $mod['note_count']; ?> <?php echo $mod['note_count'] === 1 ? 'note' : 'notes'; ?>
                                        </div>
                                    </div>
                                    <div class="module-list-actions">
                                        <span class="badge-count"><?php echo $mod['note_count']; ?></span>
                                        <button class="btn-mod-delete" onclick="event.stopPropagation(); deleteModule(<?php echo $mod['id']; ?>)" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Module Modal -->
    <div class="modal fade" id="addModuleModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; border:none; box-shadow:0 20px 60px rgba(107,68,35,0.25); overflow:hidden;">
          <!-- Header -->
          <div style="background: linear-gradient(135deg, var(--heading-color) 0%, #7a4d2a 100%); padding: 32px 28px; position: relative;">
            <div>
              <h5 style="margin: 0 0 6px 0; font-family: 'Playfair Display', serif; font-size: 26px; font-weight: 800; color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">Add a Module</h5>
              <p style="margin: 0; font-size: 13px; color: rgba(255,255,255,0.85); font-weight: 500;">Create a new study module</p>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 16px; right: 16px; opacity: 0.7; transition: opacity 0.3s ease;"></button>
          </div>
          <!-- Body -->
          <div style="padding: 28px;">
            <form id="addModuleForm">
                <div>
                    <label style="display: block; color: var(--heading-color); font-weight: 700; font-size: 13px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Module Name</label>
                    <input type="text" class="form-control" name="module_name" placeholder="Ex: Calculus, Mathematics..." required style="border: 2px solid #e8e0d0; padding: 12px 16px; font-size: 14px; border-radius: 8px; background: #fafaf8; transition: all 0.3s ease;" onfocus="this.style.borderColor='var(--gold-primary)'; this.style.boxShadow='0 0 0 3px rgba(212,175,55,0.1)';" onblur="this.style.borderColor='#e8e0d0'; this.style.boxShadow='none';">
                </div>
            </form>
          </div>
          <!-- Footer -->
          <div style="background: #f8f7f5; border-top: 1px solid #e8e0d0; padding: 18px 28px; display: flex; gap: 10px;">
            <button type="button" class="btn btn-sm" data-bs-dismiss="modal" style="flex: 1; font-weight: 600; color: var(--heading-color); background: white; border: 1.5px solid #d4c5b9; border-radius: 8px; padding: 10px; transition: all 0.3s ease;" onmouseover="this.style.background='#f5f0eb'; this.style.borderColor='var(--heading-color)';" onmouseout="this.style.background='white'; this.style.borderColor='#d4c5b9';">
              <i class="fas fa-times me-1"></i>Annuler
            </button>
            <button type="button" class="btn btn-sm" onclick="submitModule()" style="flex: 1; font-weight: 700; color: white; background: linear-gradient(135deg, var(--heading-color), #7a4d2a); border: none; border-radius: 8px; padding: 10px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(107,68,35,0.2);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(107,68,35,0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(107,68,35,0.2)';">
              <i class="fas fa-plus me-1"></i>Create
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Note Modal -->
    <div class="modal fade" id="addNoteModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:14px; border:none; box-shadow:0 20px 60px rgba(107,68,35,0.25); overflow:hidden;">
          <!-- Header with Icon -->
          <div style="background: linear-gradient(135deg, var(--heading-color) 0%, #7a4d2a 100%); padding: 28px 28px 24px; position: relative;">
            <div style="display: flex; align-items: center; gap: 16px;">
              <div style="width: 56px; height: 56px; background: rgba(255,255,255,0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fas fa-sticky-note" style="font-size: 28px; color: var(--gold-primary);"></i>
              </div>
              <div>
                <h5 style="margin: 0 0 4px 0; font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 800; color: white;">New Note</h5>
                <p id="modalModuleLabel" style="margin: 0; font-size: 12px; color: rgba(255,255,255,0.8); font-weight: 500;"></p>
              </div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 16px; right: 16px; opacity: 0.7; transition: opacity 0.3s ease;"></button>
          </div>
          <!-- Body -->
          <div style="padding: 28px;">
            <form id="addNoteForm">
                <input type="hidden" name="module_id" id="noteModuleId">
                <div>
                    <label style="display: block; color: var(--heading-color); font-weight: 700; font-size: 13px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Your Note</label>
                    <textarea class="form-control" name="note_value" rows="6" placeholder="Write your important note..." required style="border: 2px solid #e8e0d0; padding: 14px 16px; font-size: 14px; border-radius: 8px; background: #fafaf8; font-family: 'Inter', sans-serif; resize: vertical; min-height: 140px; transition: all 0.3s ease;" onfocus="this.style.borderColor='var(--gold-primary)'; this.style.boxShadow='0 0 0 3px rgba(212,175,55,0.1)';" onblur="this.style.borderColor='#e8e0d0'; this.style.boxShadow='none';"></textarea>
                    <small style="display: block; margin-top: 8px; color: #999; font-size: 12px;">Be detailed to better remember</small>
                </div>
            </form>
          </div>
          <!-- Footer -->
          <div style="background: #f8f7f5; border-top: 1px solid #e8e0d0; padding: 18px 28px; display: flex; gap: 10px;">
            <button type="button" class="btn btn-sm" data-bs-dismiss="modal" style="flex: 1; font-weight: 600; color: var(--heading-color); background: white; border: 1.5px solid #d4c5b9; border-radius: 8px; padding: 10px; transition: all 0.3s ease;" onmouseover="this.style.background='#f5f0eb'; this.style.borderColor='var(--heading-color)';" onmouseout="this.style.background='white'; this.style.borderColor='#d4c5b9';">
              <i class="fas fa-times me-1"></i>Annuler
            </button>
            <button type="button" class="btn btn-sm" onclick="submitNote()" style="flex: 1; font-weight: 700; color: white; background: linear-gradient(135deg, var(--heading-color), #7a4d2a); border: none; border-radius: 8px; padding: 10px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(107,68,35,0.2);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(107,68,35,0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(107,68,35,0.2)';">
              <i class="fas fa-plus me-1"></i>Add
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Note Modal -->
    <div class="modal fade" id="editNoteModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:14px; border:none; box-shadow:0 20px 60px rgba(107,68,35,0.25); overflow:hidden;">
          <!-- Header with Icon -->
          <div style="background: linear-gradient(135deg, var(--heading-color) 0%, #7a4d2a 100%); padding: 28px 28px 24px; position: relative;">
            <div style="display: flex; align-items: center; gap: 16px;">
              <div style="width: 56px; height: 56px; background: rgba(255,255,255,0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fas fa-edit" style="font-size: 28px; color: var(--gold-primary);"></i>
              </div>
              <div>
                <h5 style="margin: 0 0 4px 0; font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 800; color: white;">Edit Note</h5>
                <p style="margin: 0; font-size: 12px; color: rgba(255,255,255,0.8); font-weight: 500;">Update your note details</p>
              </div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 16px; right: 16px; opacity: 0.7; transition: opacity 0.3s ease;"></button>
          </div>
          <!-- Body -->
          <div style="padding: 28px;">
            <form id="editNoteForm">
                <input type="hidden" name="note_id" id="editNoteId">
                <div>
                    <label style="display: block; color: var(--heading-color); font-weight: 700; font-size: 13px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Updated Note</label>
                    <textarea class="form-control" name="note_value" id="editNoteValue" rows="6" placeholder="Update your note..." required style="border: 2px solid #e8e0d0; padding: 14px 16px; font-size: 14px; border-radius: 8px; background: #fafaf8; font-family: 'Inter', sans-serif; resize: vertical; min-height: 140px; transition: all 0.3s ease;" onfocus="this.style.borderColor='var(--gold-primary)'; this.style.boxShadow='0 0 0 3px rgba(212,175,55,0.1)';" onblur="this.style.borderColor='#e8e0d0'; this.style.boxShadow='none';"></textarea>
                    <small style="display: block; margin-top: 8px; color: #999; font-size: 12px;">Update your important note details</small>
                </div>
            </form>
          </div>
          <!-- Footer -->
          <div style="background: #f8f7f5; border-top: 1px solid #e8e0d0; padding: 18px 28px; display: flex; gap: 10px;">
            <button type="button" class="btn btn-sm" data-bs-dismiss="modal" style="flex: 1; font-weight: 600; color: var(--heading-color); background: white; border: 1.5px solid #d4c5b9; border-radius: 8px; padding: 10px; transition: all 0.3s ease;" onmouseover="this.style.background='#f5f0eb'; this.style.borderColor='var(--heading-color)';" onmouseout="this.style.background='white'; this.style.borderColor='#d4c5b9';">
              <i class="fas fa-times me-1"></i>Annuler
            </button>
            <button type="button" class="btn btn-sm" onclick="submitEditNote()" style="flex: 1; font-weight: 700; color: white; background: linear-gradient(135deg, var(--gold-primary), #e8c547); border: none; border-radius: 8px; padding: 10px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(212,175,55,0.2);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(212,175,55,0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(212,175,55,0.2)';">
              <i class="fas fa-check me-1"></i>Update
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirmation Modal - Delete Module -->
    <div class="modal fade" id="confirmDeleteModule" tabindex="-1" aria-labelledby="confirmDeleteModuleLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content" style="border-radius:12px; border:none; box-shadow:0 12px 40px rgba(107,68,35,0.2);">
          <div class="modal-header" style="background: linear-gradient(135deg, var(--heading-color) 0%, #7a4d2a 100%); border: none; padding: 20px 24px; position: relative;">
            <div style="width: 100%; text-align: center;">
              <div style="font-size: 44px; display: inline-flex; align-items: center; justify-content: center; width: 54px; height: 54px; background:rgba(255,255,255,0.95); border-radius:50%; color:#e74c3c; margin-bottom: 10px;">
                <i class="fas fa-exclamation-triangle"></i>
              </div>
              <h5 style="margin: 0 0 4px 0; color: white; font-size: 20px; font-family: 'Playfair Display', serif; font-weight: 800;">Delete This Module</h5>
            </div>
            <button type="button" class="btn-close btn-close-white position-absolute" style="top: 12px; right: 12px;" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" style="padding: 18px 24px;">
            <div style="background: linear-gradient(135deg, rgba(255,193,7,0.12) 0%, rgba(255,152,0,0.08) 100%); border-left: 5px solid var(--gold-primary); padding: 10px 14px; border-radius: 8px; margin-bottom: 14px;">
              <span style="font-size: 13px; color: var(--heading-color); font-weight: 500;"><strong>Warning!</strong> All associated notes will be deleted.</span>
            </div>
            <p style="margin: 0; font-size: 13px; color: var(--text-primary);">Continue?</p>
          </div>
          <div class="modal-footer" style="background: var(--page-bg); border-top: 1px solid #e8e8e8; padding: 14px 24px; gap: 8px;">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal" style="font-weight: 600; font-size: 13px;">
              <i class="fas fa-times me-1"></i>Cancel
            </button>
            <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteModule()" style="font-weight: 600; background: #e74c3c; border: none; font-size: 13px;">
              <i class="fas fa-trash-alt me-1"></i>Delete
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirmation Modal - Delete Note -->
    <div class="modal fade" id="confirmDeleteNote" tabindex="-1" aria-labelledby="confirmDeleteNoteLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content" style="border-radius:12px; border:none; box-shadow:0 12px 40px rgba(107,68,35,0.2);">
          <div class="modal-header" style="background: linear-gradient(135deg, var(--heading-color) 0%, #7a4d2a 100%); border: none; padding: 20px 24px; position: relative;">
            <div style="width: 100%; text-align: center;">
              <div style="font-size: 44px; display: inline-flex; align-items: center; justify-content: center; width: 54px; height: 54px; background:rgba(255,255,255,0.95); border-radius:50%; color:#e74c3c; margin-bottom: 10px;">
                <i class="fas fa-exclamation-triangle"></i>
              </div>
              <h5 style="margin: 0 0 4px 0; color: white; font-size: 20px; font-family: 'Playfair Display', serif; font-weight: 800;">Delete This Note</h5>
            </div>
            <button type="button" class="btn-close btn-close-white position-absolute" style="top: 12px; right: 12px;" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" style="padding: 18px 24px;">
            <div style="background: linear-gradient(135deg, rgba(255,193,7,0.12) 0%, rgba(255,152,0,0.08) 100%); border-left: 5px solid var(--gold-primary); padding: 10px 14px; border-radius: 8px; margin-bottom: 14px;">
              <span style="font-size: 13px; color: var(--heading-color); font-weight: 500;"><strong>Warning!</strong> This note will be permanently deleted.</span>
            </div>
            <p style="margin: 0; font-size: 13px; color: var(--text-primary);">Are you sure?</p>
          </div>
          <div class="modal-footer" style="background: var(--page-bg); border-top: 1px solid #e8e8e8; padding: 14px 24px; gap: 8px;">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal" style="font-weight: 600; font-size: 13px;">
              <i class="fas fa-times me-1"></i>Cancel
            </button>
            <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteNote()" style="font-weight: 600; background: #e74c3c; border: none; font-size: 13px;">
              <i class="fas fa-trash-alt me-1"></i>Delete
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap JS & App JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/app.js"></script>
    <script>
        const csrfToken = '<?php echo $csrf; ?>';
        let pendingDeleteModuleId = null;
        let pendingDeleteNoteId = null;

        // Search filtering
        const searchInput = document.getElementById('searchModule');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const query = e.target.value.toLowerCase();
                document.querySelectorAll('.module-col').forEach(col => {
                    if (col.getAttribute('data-name').includes(query)) {
                        col.style.display = 'block';
                    } else {
                        col.style.display = 'none';
                    }
                });
            });
        }

        // Set current module for add note modal
        function setCurrentModule(moduleId, moduleName) {
            document.getElementById('noteModuleId').value = moduleId;
            document.getElementById('modalModuleLabel').innerText = `Module: ${moduleName}`;
        }

        // Add Module
        function submitModule() {
            const form = document.getElementById('addModuleForm');
            if(!form.reportValidity()) return;
            const formData = new FormData(form);
            formData.append('action', 'add_module');
            formData.append('csrf_token', csrfToken);

            fetch('modules.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message, 'error');
                }
            }).catch(() => showToast('Connection error', 'error'));
        }

        // Add Note
        function submitNote() {
            const form = document.getElementById('addNoteForm');
            if(!form.reportValidity()) return;
            const formData = new FormData(form);
            formData.append('action', 'add_note');
            formData.append('csrf_token', csrfToken);

            fetch('modules.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message, 'error');
                }
            }).catch(() => showToast('Connection error', 'error'));
        }

        // Delete Module
        function deleteModule(moduleId) {
            pendingDeleteModuleId = moduleId;
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmDeleteModule'));
            confirmModal.show();
        }

        // Delete Module from Detail View
        function deleteModuleFromDetail(moduleId) {
            pendingDeleteModuleId = moduleId;
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmDeleteModule'));
            confirmModal.show();
        }

        // Edit Note from Detail View
        function editNoteFromDetail(noteId, noteValue) {
            document.getElementById('editNoteId').value = noteId;
            document.getElementById('editNoteValue').value = noteValue;
            const editModal = new bootstrap.Modal(document.getElementById('editNoteModal'));
            editModal.show();
        }

        // Submit Edit Note
        function submitEditNote() {
            const form = document.getElementById('editNoteForm');
            if(!form.reportValidity()) return;
            const formData = new FormData(form);
            formData.append('action', 'edit_note');
            formData.append('csrf_token', csrfToken);

            fetch('modules.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showToast(data.message, 'error');
                }
            }).catch(() => showToast('Connection error', 'error'));
        }

        // Delete Note from Detail View
        function deleteNoteFromDetail(noteId) {
            pendingDeleteNoteId = noteId;
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmDeleteNote'));
            confirmModal.show();
        }

        // Confirm Delete Module (executes the actual deletion)
        function confirmDeleteModule() {
            if(!pendingDeleteModuleId) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_module');
            formData.append('module_id', pendingDeleteModuleId);
            formData.append('csrf_token', csrfToken);

            fetch('modules.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message, 'error');
                }
            }).catch(() => showToast('Connection error', 'error'));
        }

        // Confirm Delete Note (executes the actual deletion)
        function confirmDeleteNote() {
            if(!pendingDeleteNoteId) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_note');
            formData.append('note_id', pendingDeleteNoteId);
            formData.append('csrf_token', csrfToken);

            fetch('modules.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message, 'error');
                }
            }).catch(() => showToast('Connection error', 'error'));
        }
    </script>
</body>
</html>



