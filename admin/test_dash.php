<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    // Also check for user role
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
        header("Location: ../login.php");
        exit();
    }
}

// Debug: Print session variables
error_log("Admin Dashboard - Session: " . print_r($_SESSION, true));

include("../config/db.php");

// Get statistics
$total_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student'")->fetch_assoc()['total'];
$total_quizzes = $conn->query("SELECT COUNT(*) as total FROM quiz_results")->fetch_assoc()['total'];
$avg_score = $conn->query("SELECT AVG(score/total)*100 as avg FROM quiz_results")->fetch_assoc()['avg'];
$avg_score = round($avg_score, 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Disaster Ready India</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }

        /* Admin Container */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h2 {
            font-size: 1.5em;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 0.8em;
            opacity: 0.8;
        }

        .admin-info {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .admin-avatar {
            width: 80px;
            height: 80px;
            background: #ffd700;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 2em;
            font-weight: bold;
            color: #1e3c72;
        }

        .admin-name {
            font-size: 1.1em;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .admin-role {
            font-size: 0.8em;
            opacity: 0.8;
        }

        .nav-menu {
            padding: 20px 0;
        }

        .nav-item {
            padding: 12px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            padding-left: 30px;
        }

        .nav-item.active {
            background: rgba(255,255,255,0.15);
            color: #ffd700;
            border-right: 3px solid #ffd700;
        }

        .nav-item i {
            width: 24px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }

        /* Top Bar */
        .top-bar {
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .page-title h1 {
            font-size: 1.5em;
            color: #333;
        }

        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-info h3 {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 8px;
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #1e3c72;
        }

        .stat-icon {
            font-size: 2.5em;
            opacity: 0.5;
        }

        /* Welcome Message */
        .welcome-card {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .welcome-card h2 {
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 1000;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Disaster Ready</h2>
                <p>Admin Panel</p>
            </div>
            
            <div class="admin-info">
                <div class="admin-avatar">
                    <?php echo strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)); ?>
                </div>
                <div class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></div>
                <div class="admin-role">Administrator</div>
            </div>
            
            <div class="nav-menu">
                <a href="#" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="users.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
                <a href="results.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Quiz Results</span>
                </a>
                <a href="add_alert.php" class="nav-item">
                    <i class="fas fa-bell"></i>
                    <span>Send Alert</span>
                </a>
                <a href="modules.php" class="nav-item">
                    <i class="fas fa-book"></i>
                    <span>Manage Modules</span>
                </a>
                <a href="../login.php" class="nav-item" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1>Admin Dashboard</h1>
                </div>
                <a href="../login.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
            
            <div class="welcome-card">
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>!</h2>
                <p>Manage your disaster preparedness training platform from here.</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total Students</h3>
                        <div class="stat-number"><?php echo $total_users; ?></div>
                    </div>
                    <div class="stat-icon">👥</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total Quiz Attempts</h3>
                        <div class="stat-number"><?php echo $total_quizzes; ?></div>
                    </div>
                    <div class="stat-icon">📊</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Average Score</h3>
                        <div class="stat-number"><?php echo $avg_score; ?>%</div>
                    </div>
                    <div class="stat-icon">📈</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Active Modules</h3>
                        <div class="stat-number">5</div>
                    </div>
                    <div class="stat-icon">📚</div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function logout() {
            // You can add confirmation here if needed
            return true;
        }
    </script>
</body>
</html>