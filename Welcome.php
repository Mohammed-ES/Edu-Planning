<?php
// Welcome.php - High-end animated entryway for Edu-Planning
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Edu-Planning</title>
    <link rel="icon" type="image/png" href="assets/images/universite-cadi-ayyad.png">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/welcome.css">
</head>
<body>
    <!-- 3D Perspective Floor Grid -->
    <div class="bg-grid"></div>

    <!-- Interactive Particle Background -->
    <canvas id="particles" class="particles"></canvas>

    <div class="welcome-container">
        <div class="logo-wrapper">
            <div class="ring ring-1"></div>
            <div class="ring ring-2"></div>
            <div class="ring ring-3"></div>
            <img src="assets/images/universite-cadi-ayyad.png" alt="Edu-Planning" class="logo-img">
        </div>
        
        <div class="title">EDU-PLANNING</div>
        <div class="subtitle">System Initialization</div>
        
        <div class="progress-line-container">
            <div class="progress-line"></div>
        </div>
    </div>

    <script src="js/welcome.js?v=2"></script>
</body>
</html>
