<?php
require_once __DIR__ . '/include/auth.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid security token.";
    } else {
        $username         = trim($_POST['username']);
        $email            = trim($_POST['email']);
        $password         = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];

        if (empty($username) || empty($email) || empty($password)) {
            $error = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address.";
        } elseif (strlen($password) < 8) {
            $error = "Password must contain at least 8 characters.";
        } elseif ($password !== $password_confirm) {
            $error = "Passwords do not match.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR name = ?");
            $stmt->execute([$email, $username]);
            if ($stmt->fetch()) {
                $error = "This email address or name is already in use.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                if ($stmt->execute([$username, $email, $hash])) {
                    $user_id = $pdo->lastInsertId();
                    logAction($user_id, 'register_success', $pdo);
                    $success = "Registration successful! You can now sign in.";
                } else {
                    $error = "An error occurred during registration.";
                }
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
    <title>Sign Up — Edu-Planning UCA</title>
    <link rel="icon" type="image/png" href="assets/images/universite-cadi-ayyad.png">
    <meta name="description" content="Create your Edu-Planning account and join the academic platform at Université Cadi Ayyad.">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,900;1,600&family=Roboto:wght@300;400;500;600&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Animations CSS removed (file not present) -->

    <link rel="stylesheet" href="css/register.css">
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
                <p class="image-subtitle">Join our academic community</p>
                <div class="auth-image-deco-line"></div>
            </div>
        </div>

        <!-- ── RIGHT: Form Panel ── -->
        <div class="auth-form-section">
            <div class="auth-container">
                <div class="auth-card">
                    <div class="corner-ornament top-left"></div>
                    <div class="corner-ornament bottom-right"></div>

                    <div class="auth-header">
                        <h1 class="auth-title">Create an Account</h1>
                        <p class="auth-subtitle">Join Edu-Planning UCA</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                        <div style="text-align:center; margin-top:10px;">
                            <a href="login.php" class="auth-link">
                                <i class="fas fa-arrow-right" style="font-size:12px;"></i> Sign in now
                            </a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="register.php" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <div class="form-group fg-1">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-user input-icon"></i>
                                    <input type="text" id="username" name="username" class="form-input"
                                        placeholder="Your username"
                                        required
                                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                </div>
                            </div>

                            <div class="form-group fg-2">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input type="email" id="email" name="email" class="form-input"
                                        placeholder="votre@email.com"
                                        required
                                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                            </div>

                            <div class="form-group fg-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-wrapper" style="position: relative;">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input type="password" id="password" name="password" class="form-input"
                                        placeholder="Min. 8 characters"
                                        required style="padding-right: 40px;">
                                    <button type="button" onclick="togglePassword('password', this)" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #888; cursor: pointer; padding: 0;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <!-- Password strength bar -->
                                <div class="strength-wrapper" id="strength-wrapper">
                                    <div class="strength-bar">
                                        <div class="strength-fill"></div>
                                    </div>
                                    <div class="strength-label"></div>
                                </div>
                            </div>

                            <div class="form-group fg-4">
                                <label for="password_confirm" class="form-label">Confirm password</label>
                                <div class="input-wrapper" style="position: relative;">
                                    <i class="fas fa-shield-alt input-icon"></i>
                                    <input type="password" id="password_confirm" name="password_confirm" class="form-input"
                                        placeholder="Repeat your password"
                                        required style="padding-right: 40px;">
                                    <button type="button" onclick="togglePassword('password_confirm', this)" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #888; cursor: pointer; padding: 0;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group fg-5">
                                <button type="submit" class="btn-register" id="register-btn">
                                    Sign Up
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="auth-footer">
                        <p>Already have an account? <a href="login.php" class="auth-link">Sign in</a></p>
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
