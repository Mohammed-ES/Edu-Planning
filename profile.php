<?php
require_once 'auth.php';
require_login();
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid token.";
    } else {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
        $stmt->execute([$email, $username, $user_id]);
        if ($stmt->fetch()) {
            $error = "Email or username already taken.";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            if ($stmt->execute([$username, $email, $user_id])) {
                logAction($user_id, 'profile_updated', $pdo);
                $success = "Profile updated successfully.";
            } else {
                $error = "Error updating profile.";
            }
        }
    }
}

// Get user data & statistics
$stmt = $pdo->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM modules WHERE user_id = ?");
$stmt->execute([$user_id]);
$modules_count = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM ai_recommendations WHERE user_id = ?");
$stmt->execute([$user_id]);
$plans_count = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM logs WHERE user_id = ?");
$stmt->execute([$user_id]);
$activities_count = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Edu-Planning</title>    <link rel="icon" type="image/png" href="assets/images/universite-cadi-ayyad.png">    <!-- Google Fonts - Enhanced Typography -->
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

        /* Stats Cards */
        .stat-card {
            background: var(--white);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border-top: 3px solid var(--gold-primary);
            transition: all 0.3s ease;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.2);
        }

        .stat-value {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 800;
            color: var(--heading-color);
            margin: 12px 0;
        }

        .stat-label {
            font-size: 14px;
            color: var(--text-primary);
            font-weight: 600;
            opacity: 0.8;
        }

        /* Section Headers */
        .section-header {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 800;
            color: var(--heading-color);
            margin-bottom: 25px;
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

        /* Form Card */
        .form-card {
            background: var(--white);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            animation: slideUp 0.6s ease-out;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            color: var(--heading-color);
            margin-bottom: 8px;
            display: block;
            font-size: 14px;
        }

        .form-control {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 15px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .form-control:focus {
            border-color: var(--gold-primary);
            box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.15);
            outline: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-light));
            color: var(--heading-color);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            justify-content: center;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background: linear-gradient(135deg, var(--gold-light), var(--gold-primary));
            color: var(--heading-color);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.3);
            text-decoration: none;
        }

        .alert {
            border-radius: 8px;
            border: none;
            padding: 15px 16px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideUp 0.4s ease-out;
        }

        .alert-danger {
            background: #fdecea;
            border-left: 4px solid #c0392b;
            color: #922b21;
        }

        .alert-success {
            background: #eafaf1;
            border-left: 4px solid #27ae60;
            color: #1e8449;
        }

        .alert-info {
            background: #eaf2f8;
            border-left: 4px solid #2980b9;
            color: #1e5a8e;
        }

        .read-only {
            background: #f9f9f9;
            padding: 12px 15px;
            border-radius: 8px;
            color: #666;
            border: 1px solid #e0e0e0;
            font-size: 14px;
        }

        .row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
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

            .sidebar .logo-area {
                margin-bottom: 20px;
                padding-bottom: 15px;
            }

            .content-padding {
                padding: 20px;
            }

            .page-title {
                font-size: 24px;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-value {
                font-size: 28px;
            }

            .section-header {
                font-size: 18px;
            }

            .form-card {
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
                <li><a href="modules.php"><i class="fas fa-book"></i> Modules & Notes</a></li>
                <li><a href="planning.php"><i class="fas fa-calendar"></i> Schedule</a></li>
                <li><a href="recommendations.php"><i class="fas fa-star"></i> Recommendations</a></li>
                <li class="active"><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <div class="top-navbar">
                <h2 class="page-title m-0">My Profile</h2>
                <div class="d-flex align-items-center gap-3">
                    <span style="font-weight: 500;">Hello, <?php echo htmlspecialchars($user['username']); ?></span>
                    <div style="width:40px;height:40px;background:linear-gradient(135deg, var(--gold-primary), var(--gold-light));border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--heading-color);font-weight:bold;font-size:16px;">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-padding">
                <!-- Alerts -->
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <!-- Forms Row -->
                <div style="display: grid; grid-template-columns: 1fr; gap: 25px;">
                    <!-- Personal Information Section -->
                    <div>
                        <div class="form-card">
                            <div class="section-header">
                                <div class="section-logo"><i class="fas fa-user-edit"></i></div>
                                Personal Information
                            </div>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                
                                <div class="form-group">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Account Information Section -->
                    <div>
                        <div class="form-card">
                            <div class="section-header">
                                <div class="section-logo"><i class="fas fa-info-circle"></i></div>
                                Account Information
                            </div>
                            <div class="form-group">
                                <label class="form-label">User ID</label>
                                <div class="read-only"><?php echo htmlspecialchars($user_id); ?></div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Account Created</label>
                                <div class="read-only">
                                    <?php 
                                    $date = new DateTime($user['created_at']);
                                    echo $date->format('d/m/Y - H:i');
                                    ?>
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-shield-alt"></i> 
                                <span>Account secured by session authentication</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/app.js"></script>
</body>
</html>



