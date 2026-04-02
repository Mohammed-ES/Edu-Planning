<?php
require_once 'auth.php';
require_login();

$user_id = $_SESSION['user_id'];

// Fetch username for display only
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$username = htmlspecialchars($user['username'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revision Schedule - Edu-Planning</title>    <link rel="icon" type="image/png" href="assets/images/universite-cadi-ayyad.png">    <!-- Google Fonts - Enhanced Typography -->
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
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Planning Cards */
        .planning-card {
            background: var(--white);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            animation: slideUp 0.6s ease-out;
        }

        /* Calendar Card */
        .calendar-card {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            animation: slideUp 0.6s ease-out;
        }

        /* Calendar Day Cell */
        .calendar-cell {
            background: var(--page-bg);
            padding: 16px;
            border-radius: 8px;
            min-height: 80px;
            text-align: center;
            font-size: 14px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            color: var(--text-primary);
        }

        .calendar-cell:hover {
            background: linear-gradient(135deg, rgba(212,175,55,0.15), rgba(232,197,71,0.1));
            border-color: var(--gold-primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(212,175,55,0.2);
        }

        .calendar-cell.today {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-light));
            color: var(--heading-color);
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(212,175,55,0.3);
        }

        .calendar-cell.other-month {
            color: #ccc;
            background: #fafaf8;
            cursor: not-allowed;
        }

        .calendar-cell.other-month:hover {
            background: #fafaf8;
            border-color: transparent;
            transform: none;
            box-shadow: none;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .calendar-day {
            background: var(--white);
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s;
            animation: slideUp 0.6s ease-out both;
        }

        .calendar-day:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
            border-color: var(--gold-primary);
        }

        .calendar-day-header {
            background: linear-gradient(135deg, var(--heading-color), var(--sidebar-med));
            color: var(--white);
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 12px;
            font-weight: bold;
            text-align: center;
            font-size: 14px;
        }

        .calendar-session {
            background: #f9f9f9;
            padding: 10px;
            margin: 8px 0;
            border-radius: 5px;
            border-left: 4px solid var(--gold-primary);
            font-size: 13px;
        }

        .calendar-session-title {
            font-weight: bold;
            color: var(--heading-color);
            margin-bottom: 4px;
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

        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--heading-color), var(--sidebar-med));
            border: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--sidebar-med), var(--heading-color));
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(107, 68, 35, 0.3);
        }

        /* Toast Messages */
        .alert {
            animation: slideDown 0.4s ease-out !important;
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
                <li><a href="modules.php"><i class="fas fa-book"></i> Modules & Notes</a></li>
                <li class="active"><a href="planning.php"><i class="fas fa-calendar"></i> Schedule</a></li>
                <li><a href="recommendations.php"><i class="fas fa-star"></i> Recommendations</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-navbar">
                <h2 class="page-title m-0">Revision Schedule</h2>
                <div class="d-flex align-items-center gap-3">
                    <span style="font-weight: 500;">Hello, <?php echo $username; ?></span>
                    <div style="width:40px;height:40px;background:linear-gradient(135deg, var(--gold-primary), var(--gold-light));border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--heading-color);font-weight:bold;font-size:16px;">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                </div>
            </div>

            <div class="content-padding">
                <!-- Calendar Section -->
                <div class="calendar-card">
                    <h3 style="font-family: 'Playfair Display', serif; color: var(--heading-color); font-weight: 800; margin-bottom: 28px; font-size: 28px;">
                        <i class="fas fa-calendar-alt me-3" style="color: var(--gold-primary);"></i>My Revision Calendar
                    </h3>

                    <!-- Calendar Month Selector -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; padding-bottom: 16px; border-bottom: 2px solid #e8e0d0;">
                        <h4 id="monthYear" style="font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; color: var(--heading-color); margin: 0;"></h4>
                        <div style="display: flex; gap: 10px;">
                            <button onclick="previousMonth()" style="background: linear-gradient(135deg, var(--heading-color), #7a4d2a); color: white; border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='translateY(0)';">
                                <i class="fas fa-chevron-left me-2"></i>Previous
                            </button>
                            <button onclick="nextMonth()" style="background: linear-gradient(135deg, var(--heading-color), #7a4d2a); color: white; border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='translateY(0)';">
                                Next<i class="fas fa-chevron-right ms-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Calendar Grid -->
                    <div style="background: white; padding: 28px; border-radius: 14px; box-shadow: 0 8px 20px rgba(107,68,35,0.1);">
                        <!-- Days of Week Header -->
                        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 0; margin-bottom: 8px;">
                            <div style="text-align: center; padding: 12px; font-weight: 700; color: var(--heading-color); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid var(--gold-primary);">Sun</div>
                            <div style="text-align: center; padding: 12px; font-weight: 700; color: var(--heading-color); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid var(--gold-primary);">Mon</div>
                            <div style="text-align: center; padding: 12px; font-weight: 700; color: var(--heading-color); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid var(--gold-primary);">Tue</div>
                            <div style="text-align: center; padding: 12px; font-weight: 700; color: var(--heading-color); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid var(--gold-primary);">Wed</div>
                            <div style="text-align: center; padding: 12px; font-weight: 700; color: var(--heading-color); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid var(--gold-primary);">Thu</div>
                            <div style="text-align: center; padding: 12px; font-weight: 700; color: var(--heading-color); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid var(--gold-primary);">Fri</div>
                            <div style="text-align: center; padding: 12px; font-weight: 700; color: var(--heading-color); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid var(--gold-primary);">Sat</div>
                        </div>

                        <!-- Calendar Days Grid -->
                        <div id="calendarGrid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; margin-top: 8px;"></div>
                    </div>


                </div>


            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/app.js"></script>
    <script>
        let currentDate = new Date();

        // Initialize calendar on page load
        document.addEventListener('DOMContentLoaded', function() {
            renderCalendar();
        });

        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            // Update month/year display
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                               'July', 'August', 'September', 'October', 'November', 'December'];
            document.getElementById('monthYear').textContent = `${monthNames[month]} ${year}`;
            
            // Get first day of month and number of days
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();
            
            // Clear grid
            const grid = document.getElementById('calendarGrid');
            grid.innerHTML = '';
            
            // Add days from previous month
            for (let i = firstDay - 1; i >= 0; i--) {
                const cell = document.createElement('div');
                cell.className = 'calendar-cell other-month';
                cell.textContent = daysInPrevMonth - i;
                grid.appendChild(cell);
            }
            
            // Add days of current month
            const today = new Date();
            for (let day = 1; day <= daysInMonth; day++) {
                const cell = document.createElement('div');
                cell.className = 'calendar-cell';
                cell.textContent = day;
                
                // Highlight today
                if (year === today.getFullYear() && 
                    month === today.getMonth() && 
                    day === today.getDate()) {
                    cell.classList.add('today');
                }
                
                grid.appendChild(cell);
            }
            
            // Add days from next month
            const totalCells = grid.children.length;
            const remainingCells = 42 - totalCells; // 6 rows × 7 days
            for (let day = 1; day <= remainingCells; day++) {
                const cell = document.createElement('div');
                cell.className = 'calendar-cell other-month';
                cell.textContent = day;
                grid.appendChild(cell);
            }
        }

        function previousMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        }

        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        }

        function showToast(msg, type) {
            const alertClass = type === 'error' ? 'danger' : type;
            const alertHtml = `<div class="alert alert-${alertClass} alert-dismissible fade show" role="alert">
                ${msg}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
            const container = document.querySelector('.content-padding');
            if (container) {
                container.insertAdjacentHTML('afterbegin', alertHtml);
                setTimeout(() => {
                    const alert = container.querySelector('.alert');
                    if (alert) alert.remove();
                }, 5000);
            }
        }
    </script>
</body>
</html>



