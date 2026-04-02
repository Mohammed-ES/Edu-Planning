<?php
require_once 'auth.php';

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
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            if ($stmt->fetch()) {
                $error = "This email address or username is already in use.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
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
    <!-- Animations CSS -->
    <link rel="stylesheet" href="assets/animations.css">

    <style>
        :root {
            --primary-dark:  #5C2E0E;
            --primary:       #8B4513;
            --accent:        #C8962E;
            --accent-light:  #D4A843;
            --bg-warm:       #FAF6F0;
            --text-dark:     #2C1A0E;
            --white:         #FFFFFF;
            --border:        #E0D8CF;
            --muted:         #7A7A7A;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            height: 100%;
            font-family: 'Roboto', sans-serif;
            background: var(--bg-warm);
        }

        /* ── SPLIT LAYOUT ── */
        .auth-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* LEFT — Image Panel */
        .auth-image-section {
            flex: 1;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-image-bg {
            position: absolute;
            inset: 0;
            background-image: url('https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=800&h=1200&fit=crop');
            background-size: cover;
            background-position: center;
            animation: kenBurns 22s ease-in-out infinite;
            will-change: transform;
        }

        .auth-image-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(92,46,14,0.78) 0%, rgba(139,69,19,0.60) 100%);
            z-index: 1;
        }

        .auth-particles {
            position: absolute;
            inset: 0;
            z-index: 1;
            pointer-events: none;
        }

        .auth-image-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: var(--white);
            padding: 40px;
            max-width: 420px;
        }

        .auth-image-logo {
            width: 72px; height: 72px;
            background: rgba(200,150,46,0.15);
            border: 2px solid rgba(200,150,46,0.5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            backdrop-filter: blur(4px);
            animation: fadeInUp 0.7s ease 0.2s both;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .auth-image-logo:hover {
            background: rgba(200,150,46,0.25);
            border-color: rgba(200,150,46,0.8);
            transform: scale(1.08) translateY(-3px);
        }

        .auth-image-logo img {
            width: 46px; height: 46px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }

        .auth-image-content h2 {
            font-family: 'Playfair Display', serif;
            font-size: 42px;
            font-weight: 900;
            font-style: italic;
            line-height: 1.2;
            margin-bottom: 12px;
            animation: fadeInLeft 0.8s ease 0.3s both;
        }

        .auth-image-content h2 a {
            color: var(--white);
            text-decoration: none;
        }

        .auth-image-content .image-subtitle {
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            font-size: 18px;
            color: var(--accent-light);
            margin-bottom: 20px;
            animation: fadeIn 0.8s ease 0.7s both;
        }

        .auth-image-deco-line {
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            margin: 0 auto;
            animation: lineExpand 0.9s ease 0.9s both;
            width: 0;
        }

        /* RIGHT — Form Panel */
        .auth-form-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-warm);
            padding: 32px 28px;
            overflow-y: auto;
        }

        .auth-container {
            width: 100%;
            max-width: 400px;
            animation: fadeInRight 0.8s cubic-bezier(0.22,1,0.36,1) 0.2s both;
        }

        /* Card shimmer border */
        .auth-card {
            background: var(--white);
            border-radius: 20px;
            padding: 36px 38px;
            position: relative;
            box-shadow: 0 20px 60px rgba(139,69,19,0.15);
            border: 2px solid transparent;
            background-clip: padding-box;
            background-color: var(--white);
        }

        .auth-card::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 22px;
            background: linear-gradient(90deg, #C8962E 0%, #D4A843 50%, #C8962E 100%);
            background-size: 200% auto;
            animation: shimmerGold 3s linear infinite;
            z-index: -1;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 22px;
        }

        .auth-logo-circle {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            box-shadow: 0 8px 24px rgba(200,150,46,0.3);
            animation: zoomIn 0.7s ease 0.2s both;
        }

        .auth-logo-circle img {
            width: 40px; height: 40px;
            object-fit: contain;
        }

        .auth-title {
            font-family: 'Playfair Display', serif;
            font-size: 30px;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 5px;
            animation: fadeInUp 0.6s ease 0.3s both;
        }

        .auth-subtitle {
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            font-size: 15px;
            color: var(--muted);
            animation: fadeIn 0.6s ease 0.4s both;
        }

        /* Form groups — staggered */
        .form-group { margin-bottom: 16px; position: relative; }
        .fg-1 { animation: fadeInUp 0.6s ease 0.4s both; }
        .fg-2 { animation: fadeInUp 0.6s ease 0.5s both; }
        .fg-3 { animation: fadeInUp 0.6s ease 0.6s both; }
        .fg-4 { animation: fadeInUp 0.6s ease 0.7s both; }
        .fg-5 { animation: fadeInUp 0.6s ease 0.8s both; }

        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 7px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper { position: relative; }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 13px;
            transition: color 0.3s ease;
            pointer-events: none;
            z-index: 1;
        }

        .form-input {
            width: 100%;
            padding: 13px 42px 13px 40px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Roboto', sans-serif;
            background: #FAFAF8;
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        .form-input::placeholder { color: #B0A090; }

        .form-input:focus {
            outline: none;
            border-color: var(--accent);
            background: #FFFDF8;
            box-shadow: 0 0 0 4px rgba(200,150,46,0.15);
        }

        .input-wrapper:focus-within .input-icon { color: var(--accent); }

        /* Password strength */
        .strength-wrapper {
            margin-top: 8px;
        }

        .strength-bar {
            height: 4px;
            background: #E0D8CF;
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            border-radius: 2px;
            width: 0%;
            transition: width 0.4s ease, background-color 0.4s ease;
        }

        .strength-label {
            font-size: 11px;
            margin-top: 4px;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .strength-wrapper.strength-weak   .strength-fill { width: 25%;  background: #C0392B; }
        .strength-wrapper.strength-fair   .strength-fill { width: 50%;  background: #E67E22; }
        .strength-wrapper.strength-good   .strength-fill { width: 75%;  background: #F39C12; }
        .strength-wrapper.strength-strong .strength-fill { width: 100%; background: #27AE60; }
        .strength-wrapper.strength-weak   .strength-label { color: #C0392B; }
        .strength-wrapper.strength-fair   .strength-label { color: #E67E22; }
        .strength-wrapper.strength-good   .strength-label { color: #F39C12; }
        .strength-wrapper.strength-strong .strength-label { color: #27AE60; }

        /* Alerts */
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeInUp 0.4s ease;
        }

        .alert-danger  { background: #FDECEA; border-left: 4px solid #C0392B; color: #922B21; }
        .alert-success { background: #EAFAF1; border-left: 4px solid #27AE60; color: #1E8449; }

        /* Register button */
        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #C8962E 0%, #D4A843 50%, #C8962E 100%);
            background-size: 200% auto;
            color: #1C0F07;
            border: none;
            border-radius: 10px;
            font-family: 'Roboto', sans-serif;
            font-weight: 700;
            font-size: 15px;
            letter-spacing: 0.8px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease 0.8s both, shimmerGold 2.5s linear 0.8s infinite, pulseCTA 2s ease-in-out 0.8s infinite;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 6px 20px rgba(200,150,46,0.35);
            margin-top: 6px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(200,150,46,0.5);
            color: #1C0F07;
        }

        /* Field validation */
        .field-valid .form-input   { border-color: #27AE60; }
        .field-invalid .form-input { border-color: #C0392B; animation: shake 0.4s ease; }

        .field-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 13px;
            pointer-events: none;
        }

        /* Auth footer */
        .auth-footer {
            text-align: center;
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid var(--border);
            font-size: 14px;
            animation: fadeIn 0.6s ease 1.1s both;
        }

        .auth-link {
            color: var(--accent);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .auth-link:hover { color: var(--primary-dark); text-decoration: underline; }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .auth-wrapper { flex-direction: column; }
            .auth-image-section { min-height: 35vh; flex: none; }
            .auth-image-content h2 { font-size: 28px; }
            .auth-form-section { padding: 28px 20px; flex: none; overflow: auto; }
            .auth-card { padding: 28px 22px; }
            .auth-title { font-size: 26px; }
        }
    </style>
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
                        <div class="auth-logo-circle">
                            <img src="assets/images/universite-cadi-ayyad.png" alt="UCA">
                        </div>
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
                                <div class="input-wrapper">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input type="password" id="password" name="password" class="form-input"
                                        placeholder="Min. 8 characters"
                                        required>
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
                                <div class="input-wrapper">
                                    <i class="fas fa-shield-alt input-icon"></i>
                                    <input type="password" id="password_confirm" name="password_confirm" class="form-input"
                                        placeholder="Repeat your password"
                                        required>
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
    <script src="assets/app.js"></script>
</body>
</html>
