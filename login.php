<?php
require_once __DIR__ . '/include/auth.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token.";
    } elseif (!check_login_rate_limit()) {
        $remaining = get_remaining_lockout_seconds();
        $minutes   = ceil($remaining / 60);
        $error     = "Too many failed login attempts. Please try again in {$minutes} minute(s).";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = "All fields are required.";
        } else {
            $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ? OR name = ? LIMIT 1");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $verified = false;
            if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {
                $verified = true;
            }

            if ($verified) {
                // Regenerate session ID on login to prevent session fixation
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                reset_login_attempts();
                logAction($user['id'], 'login_success', $pdo);
                header("Location: dashboard.php");
                exit;
            } else {
                increment_login_attempt();
                if ($user) {
                    logAction($user['id'], 'login_failed', $pdo);
                } else {
                    logAction(null, 'login_failed', $pdo);
                }
                $error = "Invalid credentials.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Edu-Planning UCA</title>
    <link rel="icon" type="image/png" href="assets/images/universite-cadi-ayyad.png">
    <meta name="description" content="Sign in to your Edu-Planning academic space at Université Cadi Ayyad.">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,900;1,600&family=Roboto:wght@300;400;500;600&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Animations CSS removed (file not present) -->

    <link rel="stylesheet" href="css/login.css">
    <script src="js/login.js"></script>
</head>
<body>
    <div class="auth-wrapper">

        <!-- ── LEFT: Image Panel ── -->
        <div class="auth-image-section">
            <div class="auth-image-bg"></div>
            <div class="auth-image-overlay"></div>
            <div class="auth-particles"></div>

            <div class="auth-image-content">
                <a href="index.php" class="auth-image-logo">
                    <img src="assets/images/universite-cadi-ayyad.png" alt="UCA">
                </a>
                <h2><a href="index.php">Hello<br>Edu-Planning</a></h2>
                <p class="image-subtitle">Your intelligent academic space</p>
                <div class="auth-image-deco-line"></div>
            </div>
        </div>

        <!-- ── RIGHT: Form Panel ── -->
        <div class="auth-form-section">
            <div class="auth-container">
                <div class="auth-card">
                    <!-- Corner ornaments -->
                    <div class="corner-ornament top-left"></div>
                    <div class="corner-ornament bottom-right"></div>

                    <div class="auth-header">
                        <h1 class="auth-title">Login</h1>
                        <p class="auth-subtitle">Access your Edu-Planning space</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <div class="form-group">
                            <label for="username" class="form-label">Username or Email</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user input-icon"></i>
                                <input
                                    type="text"
                                    id="username"
                                    name="username"
                                    class="form-input"
                                    placeholder="Your username"
                                    required
                                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                >
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-wrapper" style="position: relative;">
                                <i class="fas fa-lock input-icon"></i>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-input"
                                    placeholder="••••••••"
                                    required
                                    style="padding-right: 40px;"
                                >
                                <button type="button" onclick="togglePassword('password', this)" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #888; cursor: pointer; padding: 0;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn-login" id="login-btn">
                                Sign In
                            </button>
                        </div>
                    </form>

                    <div class="auth-footer">
                        <p>Don't have an account? <a href="register.php" class="auth-link">Create one</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- App JS -->
    <script src="js/app.js"></script>
    <script>
    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    </script>
</body>
</html>
