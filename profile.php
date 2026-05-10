<?php
require_once __DIR__ . '/include/auth.php';
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
        $new_password = $_POST['new_password'] ?? '';
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR name = ?) AND id != ?");
        $stmt->execute([$email, $username, $user_id]);
        if ($stmt->fetch()) {
            $error = "Email or name already taken.";
        } else {
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                $execute_params = [$username, $email, $hashed_password, $user_id];
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $execute_params = [$username, $email, $user_id];
            }
            if ($stmt->execute($execute_params)) {
                logAction($user_id, 'profile_updated', $pdo);
                $success = "Profile updated successfully.";
            } else {
                $error = "Error updating profile.";
            }
        }
    }
}

// Get user data & statistics (created_at may not exist in old schema)
$user_created_at = null;
try {
    $stmt = $pdo->prepare("SELECT name, email, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $user_created_at = $user['created_at'] ?? null;
} catch (PDOException $e) {
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM modules WHERE user_id = ?");
$stmt->execute([$user_id]);
$modules_count = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM study_plans WHERE user_id = ?");
$stmt->execute([$user_id]);
$plans_count = $stmt->fetch()['total'];

$activities_count = 0;
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
    <link rel="stylesheet" href="css/profile.css">
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
                <li><a href="planning.php"><i class="fas fa-calendar"></i> Schedule</a></li>
                <li><a href="generate_plan.php"><i class="fas fa-wand-magic-sparkles"></i> AI Plan</a></li>
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
                    <span style="font-weight: 500;">Hello, <?php echo htmlspecialchars($user['name']); ?></span>
                    <div style="width:40px;height:40px;background:linear-gradient(135deg, var(--gold-primary), var(--gold-light));border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--heading-color);font-weight:bold;font-size:16px;">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
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

                <div class="row g-4">
                    <!-- Left Column: User Profile & Stats -->
                    <div class="col-lg-4">
                        <div class="form-card text-center mb-4" style="padding: 40px 20px;">
                            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--gold-primary), var(--gold-light)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--heading-color); font-weight: bold; font-size: 32px; margin: 0 auto 16px;">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                            <h4 style="font-family: 'Playfair Display', serif; font-weight: 800; color: var(--heading-color); margin-bottom: 4px;"><?php echo htmlspecialchars($user['name']); ?></h4>
                            <p class="text-muted" style="font-size: 14px; margin-bottom: 24px;"><?php echo htmlspecialchars($user['email']); ?></p>
                            
                            <div class="d-flex justify-content-center gap-3">
                                <div class="text-center">
                                    <div style="font-size: 24px; font-weight: 700; color: var(--gold-primary);"><?php echo $modules_count; ?></div>
                                    <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Modules</div>
                                </div>
                                <div style="width: 1px; background: var(--border-color);"></div>
                                <div class="text-center">
                                    <div style="font-size: 24px; font-weight: 700; color: var(--gold-primary);"><?php echo $plans_count; ?></div>
                                    <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">AI Plans</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-card">
                            <div class="section-header">
                                <div class="section-logo"><i class="fas fa-shield-alt"></i></div>
                                Account Security
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label" style="font-size: 12px; color: var(--text-muted);">Account ID</label>
                                <div style="font-size: 14px; font-weight: 600; color: var(--heading-color);">#<?php echo htmlspecialchars($user_id); ?></div>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label" style="font-size: 12px; color: var(--text-muted);">Member Since</label>
                                <div style="font-size: 14px; font-weight: 600; color: var(--heading-color);">
                                    <?php
                                    if (!empty($user_created_at)) {
                                        echo (new DateTime($user_created_at))->format('F j, Y');
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Edit Form -->
                    <div class="col-lg-8">
                        <div class="form-card h-100">
                            <div class="section-header">
                                <div class="section-logo"><i class="fas fa-user-edit"></i></div>
                                Edit Profile
                            </div>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                
                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label class="form-label">Full Name</label>
                                            <div class="input-group">
                                                <span class="input-group-text" style="background: transparent; border-right: none; color: var(--gold-primary);"><i class="fas fa-user"></i></span>
                                                <input type="text" name="username" class="form-control" style="border-left: none; padding-left: 0;" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label class="form-label">Email Address</label>
                                            <div class="input-group">
                                                <span class="input-group-text" style="background: transparent; border-right: none; color: var(--gold-primary);"><i class="fas fa-envelope"></i></span>
                                                <input type="email" name="email" class="form-control" style="border-left: none; padding-left: 0;" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-4 mb-4">
                                    <div class="col-12">
                                        <div class="form-group mb-0">
                                            <label class="form-label">New Password (leave blank to keep current)</label>
                                            <div class="input-group">
                                                <span class="input-group-text" style="background: transparent; border-right: none; color: var(--gold-primary);"><i class="fas fa-lock"></i></span>
                                                <input type="password" name="new_password" class="form-control" style="border-left: none; padding-left: 0;" placeholder="Enter new password">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert" style="background: rgba(184, 134, 11, 0.05); border: 1px dashed var(--gold-primary); border-radius: 8px;">
                                    <i class="fas fa-info-circle" style="color: var(--gold-primary);"></i> 
                                    <span style="font-size: 14px; margin-left: 8px; color: var(--text-primary);">Updates to your profile will reflect immediately across all dashboard statistics and modules.</span>
                                </div>

                                <div class="mt-4 text-end">
                                    <button type="submit" class="btn btn-primary px-4 py-2" style="font-weight: 600;">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/app.js"></script>
</body>
</html>



