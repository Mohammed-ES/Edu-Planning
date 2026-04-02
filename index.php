<?php
require_once 'auth.php';
if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu-Planning — Intelligent Academic Platform UCA</title>
    <link rel="icon" type="image/png" href="assets/images/universite-cadi-ayyad.png">
    <meta name="description" content="Edu-Planning, the intelligent academic platform of Université Cadi Ayyad. AI-generated personalized revision schedules, module tracking, and grade management.">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,900;1,600&family=Roboto:wght@300;400;500;600&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Animations CSS -->
    <link rel="stylesheet" href="assets/animations.css">

    <style>
        /* ── Color synchronization with welcome screen ── */
        :root {
            /* Browns — identical to welcome screen */
            --c-dark-bg    : #1C0A02;   /* darkest background */
            --c-dark-2     : #3D1C08;   /* deep brown */
            --c-primary    : #6B3410;   /* main medium brown */
            --c-primary-2  : #8B4513;   /* terracotta brown */
            --c-primary-3  : #A0651A;   /* light brown */
            
            /* Golds — identical to welcome screen */
            --c-gold       : #C8962E;   /* main gold */
            --c-gold-light : #D4A843;   /* light gold */
            
            /* Neutrals */
            --c-white      : #FFFFFF;
            --c-text-light : rgba(255,255,255,0.75);
            --c-text-muted : rgba(255,255,255,0.45);
            --c-bg-warm    : #FAF6F0;
            --c-bg-card    : #FFFFFF;
            --c-border     : #E0D8CF;
            
            /* Aliases for compatibility */
            --primary-dark:  #5C2E0E;     /* dark primary */
            --primary:       #8B4513;     /* primary color */
            --accent:        #C8962E;     /* accent gold */
            --accent-light:  #D4A843;     /* light accent */
            --bg-warm:       #FAF6F0;     /* warm background */
            --text-dark:     #2C1A0E;     /* dark text */
            --white:         #FFFFFF;     /* white */
            --deep:          #1C0F07;     /* deep dark */
            --body-dark:     #3D1C08;     /* body dark background */
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Roboto', sans-serif;
            color: var(--text-dark);
            background: var(--bg-warm);
            line-height: 1.7;
            overflow-x: hidden;
        }

        /* ════════════════════════════════════════
        NAVBAR
           ════════════════════════════════════════ */
        .navbar-premium {
            background: transparent;
            padding: 0;
            height: 72px;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            transition: background 0.4s ease, backdrop-filter 0.4s ease, box-shadow 0.4s ease;
            border-bottom: 1px solid transparent;
        }

        .navbar-premium .container {
            height: 72px;
            display: flex;
            align-items: center;
        }

        .navbar-premium.navbar-scrolled {
            background: rgba(28, 10, 2, 0.97);   /* --c-dark-bg */
            backdrop-filter: blur(12px);
            box-shadow: 0 4px 24px rgba(0,0,0,0.25);
            border-bottom-color: rgba(200,150,46,0.3);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--accent-light) !important;
            text-decoration: none;
            animation: fadeInLeft 0.6s ease both;
        }

        .navbar-brand img {
            width: 44px; height: 44px;
            object-fit: contain;
            filter: brightness(0) invert(1);
            animation: zoomIn 0.6s ease both;
        }

        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            font-size: 15px;
            padding: 8px 14px !important;
            position: relative;
            transition: color 0.3s ease;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 4px; left: 14px;
            width: 0; height: 2px;
            background: var(--accent);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after { width: calc(100% - 28px); }
        .nav-link:hover { color: var(--accent-light) !important; }

        .btn-nav-outline {
            color: var(--white);
            border: 2px solid rgba(255,255,255,0.7);
            border-radius: 8px;
            padding: 9px 22px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            letter-spacing: 0.3px;
        }

        .btn-nav-outline:hover {
            background: rgba(255,255,255,0.12);
            border-color: var(--white);
            color: var(--white);
        }

        .btn-nav-gold {
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            color: #1C0F07;
            border: none;
            border-radius: 8px;
            padding: 9px 22px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 14px rgba(200,150,46,0.35);
        }

        .btn-nav-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(200,150,46,0.5);
            color: #1C0F07;
        }

        .navbar-toggler {
            border-color: rgba(255,255,255,0.5);
            color: var(--white);
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.85%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* ════════════════════════════════════════
        HERO SECTION
           ════════════════════════════════════════ */
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            padding-top: 72px;
        }

        .hero-bg {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                135deg,
                #1C0A02 0%,
                #3D1C08 25%,
                #6B3410 50%,
                #8B4513 70%,
                #5C2E0E 100%
            );
            background-size: 300% 300%;
            animation: gradientShift 8s ease infinite;
            z-index: 0;
        }

        /* UCA Geometric SVG pattern overlay — same as welcome screen */
        .hero-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23C8962E' fill-opacity='0.06'%3E%3Cpath d='M40 0L49 31H80L56 50L65 80L40 62L15 80L24 50L0 31H31Z'/%3E%3C/g%3E%3C/svg%3E");
            background-repeat: repeat;
            background-size: 80px 80px;
            pointer-events: none;
        }

        /* Floating particles container */
        .hero-particles {
            position: absolute;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            overflow: hidden;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 1000px;
            width: 100%;
        }

        .hero-logo-badge {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: rgba(200,150,46,0.12);
            border: 1px solid rgba(200,150,46,0.35);
            border-radius: 40px;
            padding: 10px 20px;
            margin-bottom: 28px;
            animation: fadeInUp 0.7s ease 0.2s both;
            backdrop-filter: blur(4px);
        }

        .hero-logo-badge img {
            width: 34px; height: 34px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }

        .hero-logo-badge span {
            font-family: 'Roboto', sans-serif;
            font-size: 13px;
            font-weight: 500;
            color: var(--accent-light);
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 52px;
            font-weight: 900;
            color: var(--white);
            line-height: 1.15;
            margin-bottom: 24px;
            width: 100%;
        }

        /* Typewriter — handled purely by CSS, JS does NOT override */
        .typewriter-text {
            display: inline-block;
            overflow: hidden;
            white-space: nowrap;
            width: 0;
            max-width: 100%;
            border-right: 3px solid var(--accent);
            vertical-align: bottom;
            animation:
                typewriter 1.8s ease-out 0.4s forwards,
                blinkCursor 0.75s step-end infinite 2.2s;
        }

        @keyframes typewriter {
            from { 
                width: 0;
            }
            to { 
                width: 100%;
            }
        }
        
        @keyframes blinkCursor {
            0%,100% { border-right-color: #C8962E; }
            50%      { border-right-color: transparent; }
        }

        .hero-subtitle-text {
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            font-size: 22px;
            color: rgba(255,255,255,0.85);
            margin-bottom: 42px;
            line-height: 1.7;
            max-width: 640px;
            animation: fadeInUp 0.8s ease 1.8s both;
        }

        .hero-cta {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            animation: fadeInUp 0.8s ease 2.2s both;
        }

        .btn-hero-primary {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 50%, var(--accent) 100%);
            background-size: 200% auto;
            color: #1C0F07;
            border: none;
            border-radius: 40px;
            padding: 16px 40px;
            font-family: 'Roboto', sans-serif;
            font-weight: 700;
            font-size: 16px;
            letter-spacing: 0.5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            animation: shimmerGold 2.5s linear 2.2s infinite, pulseCTA 2s ease-in-out 2.2s infinite;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            box-shadow: 0 8px 28px rgba(200,150,46,0.4);
            position: relative;
            overflow: hidden;
        }

        .btn-hero-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 40px rgba(200,150,46,0.55);
            color: #1C0F07;
        }

        .btn-hero-secondary {
            background: transparent;
            color: var(--white);
            border: 2px solid rgba(255,255,255,0.7);
            border-radius: 40px;
            padding: 16px 40px;
            font-family: 'Roboto', sans-serif;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            animation: fadeInUp 0.8s ease 2.5s both;
        }

        .btn-hero-secondary:hover {
            background: rgba(255,255,255,0.12);
            border-color: var(--white);
            color: var(--white);
            transform: translateY(-3px);
        }

        /* Scroll indicator */
        .hero-scroll-hint {
            position: absolute;
            bottom: 32px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
            animation: float 2s ease-in-out infinite, fadeIn 0.8s ease 3s both;
            color: rgba(255,255,255,0.5);
            font-size: 12px;
            text-align: center;
            letter-spacing: 2px;
            text-transform: uppercase;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .hero-scroll-hint i { font-size: 18px; }

        /* ════════════════════════════════════════
        FEATURES SECTION
           ════════════════════════════════════════ */
        .section-spacing { padding: 100px 0; }

        .section-eyebrow {
            font-family: 'Roboto', sans-serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 14px;
            display: block;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 44px;
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 18px;
            line-height: 1.2;
        }

        .section-subtitle {
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            font-size: 19px;
            color: #7A7A7A;
            margin-bottom: 60px;
        }

        /* Feature cards */
        .feature-card {
            background: var(--white);
            border-radius: 16px;
            padding: 38px 28px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(139,69,19,0.07);
            border: 1px solid #E0D8CF;
            border-top: 3px solid var(--c-gold);
            transition: all 0.35s cubic-bezier(0.22,1,0.36,1);
            will-change: transform;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 18px 48px rgba(139,69,19,0.18);
            border-top-color: var(--c-gold-light);
        }

        .feature-icon {
            width: 70px; height: 70px;
            background: linear-gradient(135deg, var(--c-gold), var(--c-gold-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 26px;
            font-size: 28px;
            color: var(--white);
            box-shadow: 0 8px 24px rgba(200,150,46,0.28);
            transition: all 0.35s ease;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.15) rotate(5deg);
            box-shadow: 0 14px 36px rgba(200,150,46,0.45);
        }

        .feature-card h4 {
            font-family: 'Playfair Display', serif;
            font-size: 21px;
            color: var(--primary-dark);
            margin-bottom: 14px;
            font-weight: 700;
        }

        .feature-card p {
            color: #7A7A7A;
            line-height: 1.8;
            font-size: 15px;
        }

        /* ════════════════════════════════════════
        STATS SECTION \u2014 Same dark gradient as welcome/hero
           ════════════════════════════════════════ */
        .stats-section {
            background: linear-gradient(
                135deg,
                #1C0A02 0%,
                #3D1C08 25%,
                #6B3410 50%,
                #8B4513 70%,
                #5C2E0E 100%
            );
            background-size: 300% 300%;
            animation: gradientShift 8s ease infinite;
            color: var(--white);
            border-top: 4px solid var(--c-gold);
            border-bottom: 4px solid var(--c-gold);
            position: relative;
            overflow: hidden;
        }

        /* Geometric pattern overlay \u2014 same as welcome/hero */
        .stats-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23C8962E' fill-opacity='0.06'%3E%3Cpath d='M40 0L49 31H80L56 50L65 80L40 62L15 80L24 50L0 31H31Z'/%3E%3C/g%3E%3C/svg%3E");
            background-repeat: repeat;
            background-size: 80px 80px;
            pointer-events: none;
        }

        .stat-item { text-align: center; padding: 36px 20px; position: relative; z-index: 1; }

        .stat-number {
            font-family: 'Playfair Display', serif;
            font-size: 58px;
            font-weight: 700;
            color: var(--c-gold);
            margin-bottom: 12px;
            display: block;
            line-height: 1;
        }

        .stat-label {
            font-size: 15px;
            font-weight: 500;
            color: rgba(255,255,255,0.7);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ════════════════════════════════════════
        CTA SECTION
           ════════════════════════════════════════ */
        .cta-section-wrap {
            background: var(--bg-warm);
            padding: 100px 0;
        }

        .cta-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            border-radius: 24px;
            padding: 80px 60px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-card::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -20%;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(200,150,46,0.14) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .cta-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23C8962E' fill-opacity='0.05'%3E%3Cpath d='M40 0L49 31H80L56 50L65 80L40 62L15 80L24 50L0 31H31Z'/%3E%3C/g%3E%3C/svg%3E");
            background-repeat: repeat;
            background-size: 80px 80px;
            pointer-events: none;
        }

        .cta-card h2 {
            font-family: 'Playfair Display', serif;
            font-size: 42px;
            font-weight: 900;
            color: var(--white);
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .cta-card p {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }

        .cta-card .btn-cta-group {
            display: flex;
            gap: 18px;
            justify-content: center;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }

        .btn-cta-primary {
            background: linear-gradient(135deg, var(--c-gold), var(--c-gold-light));
            color: #1C0F07;
            border: none;
            border-radius: 40px;
            padding: 16px 38px;
            font-weight: 700;
            font-size: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 26px rgba(200,150,46,0.4);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-cta-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 38px rgba(200,150,46,0.55);
            color: #1C0F07;
        }

        .btn-cta-secondary {
            background: transparent;
            color: var(--white);
            border: 2px solid rgba(255,255,255,0.6);
            border-radius: 40px;
            padding: 16px 38px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .btn-cta-secondary:hover {
            background: rgba(255,255,255,0.12);
            border-color: rgba(255,255,255,0.9);
            color: var(--white);
            transform: translateY(-3px);
        }

        /* ════════════════════════════════════════
        FOOTER \u2014 Same dark background as welcome
           ════════════════════════════════════════ */
        .footer-premium {
            background: linear-gradient(135deg, #1C0A02 0%, #2C1A0E 100%);
            color: var(--white);
            padding: 60px 0 32px;
            border-top: 4px solid var(--c-gold);
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .footer-brand img {
            width: 38px; height: 38px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }

        .footer-brand span {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--c-gold-light);
        }

        .footer-col-title {
            color: var(--c-gold-light);
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 18px;
        }

        .footer-link {
            color: rgba(255,255,255,0.6);
            font-size: 14px;
            text-decoration: none;
            display: block;
            margin-bottom: 10px;
            transition: color 0.3s ease;
            position: relative;
        }

        .footer-link:hover { color: var(--c-gold); }

        .footer-bottom {
            text-align: center;
            padding-top: 28px;
            margin-top: 28px;
            border-top: 1px solid rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.38);
            font-size: 13px;
        }

        /* ════════════════════════════════════════
        RESPONSIVE
           ════════════════════════════════════════ */
        @media (max-width: 1024px) {
            .hero-title { font-size: 45px; }
            .typewriter-text { font-size: 45px; }
        }

        @media (max-width: 992px) {
            .hero-title { font-size: 40px; }
            .typewriter-text { font-size: 40px; }
        }

        @media (max-width: 768px) {
            .hero-title { font-size: 32px; }
            .typewriter-text { font-size: 32px !important; }
            .hero-subtitle-text { font-size: 17px; }
            .section-title { font-size: 30px; }
            .stat-number { font-size: 42px; }
            .cta-card { padding: 50px 28px; }
            .cta-card h2 { font-size: 28px; }
            .section-spacing { padding: 72px 0; }
            .hero-cta, .cta-card .btn-cta-group { flex-direction: column; align-items: center; }
            .btn-hero-primary, .btn-hero-secondary,
            .btn-cta-primary, .btn-cta-secondary { width: 100%; justify-content: center; }
        }

        @media (max-width: 480px) {
            .hero-title { font-size: 26px; }
            .typewriter-text { font-size: 26px !important; }
            .hero-subtitle-text { font-size: 16px; }
            .hero-content { max-width: 100%; padding: 0 16px; }
        }
    </style>
</head>
<body>

    <!-- ════════════════════════════════════════
        NAVBAR
        ════════════════════════════════════════ -->
    <nav class="navbar navbar-expand-lg navbar-premium" id="main-navbar">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/universite-cadi-ayyad.png" alt="UCA">
                Edu-Planning
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-1">
                    <li class="nav-item anim-fade-down delay-200">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item anim-fade-down delay-300">
                        <a class="nav-link" href="#stats">About</a>
                    </li>
                </ul>
                <div class="d-flex gap-2 ms-lg-4 anim-fade delay-500">
                    <a href="login.php" class="btn-nav-outline" id="nav-login">Login</a>
                    <a href="register.php" class="btn-nav-gold" id="nav-register">Sign Up</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- ════════════════════════════════════════
        HERO SECTION
        ════════════════════════════════════════ -->
    <section class="hero-section" id="hero">
        <div class="hero-bg"></div>
        <!-- Floating particles (generated by JS) -->
        <div class="hero-particles" id="hero-particles"></div>

        <div class="container position-relative" style="z-index:2;">
            <div class="hero-content">
                <!-- UCA badge -->
                <div class="hero-logo-badge">
                    <img src="assets/images/universite-cadi-ayyad.png" alt="UCA">
                    <span>Université Cadi Ayyad — Marrakech</span>
                </div>

                <!-- Typewriter title -->
                <h1 class="hero-title">
                    <span class="typewriter-text" id="hero-typewriter">Intelligent Academic Planning</span>
                </h1>

                <!-- Subtitle -->
                <p class="hero-subtitle-text">
                    Generate a <em>personalized</em> revision schedule based on your academic performance and optimize your success at UCA.
                </p>

                <!-- CTA buttons -->
                <div class="hero-cta">
                    <a href="register.php" class="btn-hero-primary" id="cta-main">
                        <i class="fas fa-rocket"></i> Get Started Now
                    </a>
                    <a href="login.php" class="btn-hero-secondary" id="cta-secondary">
                        <i class="fas fa-sign-in-alt"></i> Access My Space
                    </a>
                </div>
            </div>
        </div>

        <!-- Scroll hint -->
        <div class="hero-scroll-hint">
            <i class="fas fa-chevron-down"></i>
            <span>Discover</span>
        </div>
    </section>

    <!-- ════════════════════════════════════════
    FEATURES SECTION
    ════════════════════════════════════════ -->
    <section class="section-spacing" id="features">
        <div class="container">
            <div class="text-center mb-2">
                <span class="section-eyebrow">Our Platform</span>
                <h2 class="section-title">Key Features</h2>
                <p class="section-subtitle">A complete solution designed to optimize your academic journey at UCA</p>
            </div>

            <div class="row g-4 mt-2">
                <div class="col-md-4">
                    <div class="feature-card scroll-reveal delay-100">
                        <div class="feature-icon"><i class="fas fa-book-open"></i></div>
                        <h4>Complete Management</h4>
                        <p>Add your modules, enter detailed notes and remarks, and analyze your academic progress in real-time.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-card scroll-reveal delay-200">
                        <div class="feature-icon"><i class="fas fa-brain"></i></div>
                        <h4>Generative AI</h4>
                        <p>Advanced AI analyzes your performance and generates a personalized 7-day revision schedule tailored to your needs.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-card scroll-reveal delay-300">
                        <div class="feature-icon"><i class="fas fa-magnifying-glass"></i></div>
                        <h4>Detailed Learning Analytics</h4>
                        <p>Get comprehensive insights into your learning patterns and academic performance.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-card scroll-reveal delay-200">
                        <div class="feature-icon"><i class="fas fa-user-circle"></i></div>
                        <h4>Comprehensive User Profile</h4>
                        <p>Manage your account settings, preferences, and keep track of your personal learning objectives.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-card scroll-reveal delay-300">
                        <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                        <h4>Progress Analytics</h4>
                        <p>Track your improvement with detailed analytics and visualizations of your learning journey.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-card scroll-reveal delay-400">
                        <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                        <h4>Guaranteed Security</h4>
                        <p>Protected data, secure CSRF authentication, and complete respect for your privacy.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════
        STATS SECTION
        ════════════════════════════════════════ -->
    <section class="stats-section section-spacing" id="stats">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="stat-item scroll-reveal delay-100">
                        <span class="stat-number" data-target="1000" data-suffix="+">1000+</span>
                        <div class="stat-label">Registered Students</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item scroll-reveal delay-200">
                        <span class="stat-number" data-target="5000" data-suffix="+">5000+</span>
                        <div class="stat-label">Schedules Generated</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item scroll-reveal delay-300">
                        <span class="stat-number" data-target="98" data-suffix="%">98%</span>
                        <div class="stat-label">Satisfaction</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item scroll-reveal delay-400">
                        <span class="stat-number">24/7</span>
                        <div class="stat-label">Available Support</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════
        CTA SECTION
        ════════════════════════════════════════ -->
    <section class="cta-section-wrap">
        <div class="container">
            <div class="cta-card scroll-reveal">
                <h2>Ready to Transform Your Academic Success?</h2>
                <p>Join thousands of UCA students who are optimizing their learning with AI</p>
                <div class="btn-cta-group">
                    <a href="register.php" class="btn-cta-primary">
                        <i class="fas fa-rocket"></i> Create My Account
                    </a>
                    <a href="login.php" class="btn-cta-secondary">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════
        FOOTER
        ════════════════════════════════════════ -->
    <footer class="footer-premium">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="footer-brand anim-fade delay-200">
                        <img src="assets/images/universite-cadi-ayyad.png" alt="UCA">
                        <span>Edu-Planning</span>
                    </div>
                    <p style="color:rgba(255,255,255,0.55); font-size:14px; line-height:1.8;">
                        Intelligent academic platform developed for Université Cadi Ayyad of Marrakech. Powered by Google Gemini AI.
                    </p>
                </div>
                <div class="col-md-4 offset-md-1">
                    <p class="footer-col-title">Quick Links</p>
                    <a href="#features" class="footer-link">Features</a>
                    <a href="#stats" class="footer-link">About</a>
                    <a href="login.php" class="footer-link">Login</a>
                    <a href="register.php" class="footer-link">Sign Up</a>
                </div>
                <div class="col-md-3">
                    <p class="footer-col-title">Contact &amp; Support</p>
                    <p style="color:rgba(255,255,255,0.55); font-size:14px; margin-bottom:10px;">
                        <i class="fas fa-envelope" style="color:#C8962E; margin-right:8px;"></i>support@edu-planning.uca.ma
                    </p>
                    <p style="color:rgba(255,255,255,0.55); font-size:14px;">
                        <i class="fas fa-map-marker-alt" style="color:#C8962E; margin-right:8px;"></i>Marrakech, Maroc
                    </p>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2026 Université Cadi Ayyad — Marrakech. All rights reserved. | <strong style="color:rgba(255,255,255,0.6);">Edu-Planning</strong></p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Smooth scroll for anchor links -->
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                var href = this.getAttribute('href');
                if (href !== '#') {
                    e.preventDefault();
                    var target = document.querySelector(href);
                    if (target) {
                        var offset = 80;
                        var top = target.getBoundingClientRect().top + window.scrollY - offset;
                        window.scrollTo({ top: top, behavior: 'smooth' });
                    }
                }
            });
        });

        // Override stat-number "24/7" — don't animate since it's not a numeric counter
        var el247 = document.querySelector('[data-target="0"]');
        if (el247) {
            el247.textContent = '24/7';
            el247.removeAttribute('data-target');
        }
    </script>

    <!-- App JS (scroll reveal, particles, navbar scroll, cursor, counters) -->
    <script src="assets/app.js"></script>
</body>
</html>
