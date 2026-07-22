<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Get all statistics
$total_students = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student'")->fetch_assoc()['total'];
$total_quizzes = $conn->query("SELECT COUNT(*) as total FROM quiz_results")->fetch_assoc()['total'];
$avg_score = $conn->query("SELECT AVG(score/total)*100 as avg FROM quiz_results")->fetch_assoc()['avg'];
$avg_score = round($avg_score, 1);

// Get module completion statistics
$module_stats = [];
$modules = ['earthquake', 'flood', 'fire', 'cyclone', 'drought', 'tsunami', 'lightening'];
$module_names = [
    'earthquake' => 'Earthquake Safety',
    'flood' => 'Flood Preparedness',
    'fire' => 'Fire Safety',
    'cyclone' => 'Cyclone Preparedness',
    'drought' => 'Drought Management',
    'tsunami' => 'Tsunami Safety',
    'lightening' => 'Lightning Safety'
];

foreach ($modules as $module) {
    $completed = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM quiz_results WHERE disaster='$module'")->fetch_assoc()['count'];
    $avg = $conn->query("SELECT AVG(score/total)*100 as avg FROM quiz_results WHERE disaster='$module'")->fetch_assoc()['avg'];
    $module_stats[$module] = [
        'name' => $module_names[$module],
        'completed' => $completed,
        'avg_score' => round($avg, 1)
    ];
}

// Get daily/weekly activity
$daily_activity = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = $conn->query("SELECT COUNT(*) as count FROM quiz_results WHERE DATE(created_at)='$date'")->fetch_assoc()['count'];
    $daily_activity[] = ['date' => $date, 'count' => $count];
}

// Get top performing students
$top_students = $conn->query("
    SELECT u.name, u.email, COUNT(r.id) as quizzes_taken, AVG(r.score/r.total)*100 as avg_score
    FROM users u
    JOIN quiz_results r ON u.id = r.user_id
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY avg_score DESC
    LIMIT 10
");

// Get recent activity
$recent_activity = $conn->query("
    SELECT u.name, r.disaster, r.score, r.total, r.created_at
    FROM quiz_results r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
    LIMIT 20
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Disaster Ready India</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }

        .analytics-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .analytics-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-title h1 {
            font-size: 1.8em;
            margin-bottom: 5px;
        }

        .header-title p {
            opacity: 0.9;
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .stat-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #1e3c72;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
        }

        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .chart-card h3 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        canvas {
            max-height: 300px;
            width: 100%;
        }

        /* Module Performance */
        .modules-performance {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .modules-performance h3 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }

        .module-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            transition: all 0.3s ease;
        }

        .module-item:hover {
            transform: translateX(5px);
            background: #e8f0fe;
        }

        .module-name {
            font-weight: bold;
            color: #1e3c72;
            margin-bottom: 10px;
        }

        .module-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        .progress-bar {
            background: #e0e0e0;
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
            margin: 10px 0;
        }

        .progress-fill {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            height: 100%;
            transition: width 1s ease;
        }

        /* Recent Activity */
        .recent-activity {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .activity-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .activity-item:hover {
            background: #f8f9fa;
        }

        .activity-user {
            font-weight: 600;
            color: #333;
        }

        .activity-disaster {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            background: #e8f0fe;
            color: #1e3c72;
        }

        .activity-score {
            font-weight: bold;
        }

        .score-high {
            color: #27ae60;
        }

        .score-medium {
            color: #f39c12;
        }

        .score-low {
            color: #e74c3c;
        }

        .activity-date {
            color: #999;
            font-size: 0.8em;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .charts-section {
                grid-template-columns: 1fr;
            }
            
            .analytics-header {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="analytics-container">
        <div class="analytics-header">
            <div class="header-title">
                <h1><i class="fas fa-chart-line"></i> Analytics Dashboard</h1>
                <p>Comprehensive insights into student performance and module engagement</p>
            </div>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?php echo $total_students; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📝</div>
                <div class="stat-number"><?php echo $total_quizzes; ?></div>
                <div class="stat-label">Total Quiz Attempts</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-number"><?php echo $avg_score; ?>%</div>
                <div class="stat-label">Overall Average Score</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏆</div>
                <div class="stat-number"><?php echo $top_students->num_rows; ?></div>
                <div class="stat-label">Top Performers</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-section">
            <div class="chart-card">
                <h3><i class="fas fa-calendar-week"></i> Weekly Activity</h3>
                <canvas id="weeklyChart"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> Module Completion Rate</h3>
                <canvas id="completionChart"></canvas>
            </div>
        </div>

        <!-- Module Performance -->
        <div class="modules-performance">
            <h3><i class="fas fa-book"></i> Module Performance</h3>
            <div class="module-grid">
                <?php foreach ($module_stats as $key => $module): ?>
                    <div class="module-item">
                        <div class="module-name">
                            <?php 
                                $icons = [
                                    'earthquake' => '🌍',
                                    'flood' => '🌊',
                                    'fire' => '🔥',
                                    'cyclone' => '🌀',
                                    'drought' => '💧',
                                    'tsunami' => '🌊',
                                    'lightening' => '⚡'
                                ];
                                echo $icons[$key] . ' ' . $module['name'];
                            ?>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo ($module['completed'] / $total_students) * 100; ?>%"></div>
                        </div>
                        <div class="module-stats">
                            <span>📊 <?php echo $module['completed']; ?>/<?php echo $total_students; ?> completed</span>
                            <span>⭐ Avg Score: <?php echo $module['avg_score']; ?>%</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Top Students -->
        <div class="modules-performance">
            <h3><i class="fas fa-trophy"></i> Top Performing Students</h3>
            <div class="module-grid">
                <?php while ($student = $top_students->fetch_assoc()): ?>
                    <div class="module-item">
                        <div class="module-name">
                            <i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($student['name']); ?>
                        </div>
                        <div class="module-stats">
                            <span>📝 <?php echo $student['quizzes_taken']; ?> quizzes</span>
                            <span class="score-high">⭐ <?php echo round($student['avg_score'], 1); ?>% average</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $student['avg_score']; ?>%; background: linear-gradient(90deg, #27ae60, #2ecc71);"></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="recent-activity">
            <h3><i class="fas fa-history"></i> Recent Activity</h3>
            <div class="activity-list">
                <?php while ($activity = $recent_activity->fetch_assoc()): 
                    $percentage = ($activity['score'] / $activity['total']) * 100;
                    $scoreClass = $percentage >= 70 ? 'score-high' : ($percentage >= 50 ? 'score-medium' : 'score-low');
                ?>
                    <div class="activity-item">
                        <div class="activity-user">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($activity['name']); ?>
                        </div>
                        <div class="activity-disaster">
                            <?php 
                                $icons = [
                                    'earthquake' => '🌍',
                                    'flood' => '🌊',
                                    'fire' => '🔥',
                                    'cyclone' => '🌀',
                                    'drought' => '💧',
                                    'tsunami' => '🌊',
                                    'lightening' => '⚡'
                                ];
                                echo $icons[$activity['disaster']] . ' ' . ucfirst($activity['disaster']);
                            ?>
                        </div>
                        <div class="activity-score <?php echo $scoreClass; ?>">
                            <?php echo $activity['score']; ?>/<?php echo $activity['total']; ?> (<?php echo round($percentage); ?>%)
                        </div>
                        <div class="activity-date">
                            <i class="fas fa-clock"></i> <?php echo date('M d, H:i', strtotime($activity['created_at'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script>
        // Weekly Activity Chart
        const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
        new Chart(weeklyCtx, {
            type: 'line',
            data: {
                labels: [<?php foreach ($daily_activity as $day) echo "'" . date('M d', strtotime($day['date'])) . "', "; ?>],
                datasets: [{
                    label: 'Quiz Attempts',
                    data: [<?php foreach ($daily_activity as $day) echo $day['count'] . ", "; ?>],
                    borderColor: '#1e3c72',
                    backgroundColor: 'rgba(30, 60, 114, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

        // Module Completion Chart
        const completionCtx = document.getElementById('completionChart').getContext('2d');
        new Chart(completionCtx, {
            type: 'bar',
            data: {
                labels: [<?php foreach ($module_stats as $module) echo "'" . $module['name'] . "', "; ?>],
                datasets: [
                    {
                        label: 'Completion Rate (%)',
                        data: [<?php foreach ($module_stats as $module) echo round(($module['completed'] / $total_students) * 100, 1) . ", "; ?>],
                        backgroundColor: 'rgba(30, 60, 114, 0.8)',
                        borderColor: '#1e3c72',
                        borderWidth: 1
                    },
                    {
                        label: 'Average Score (%)',
                        data: [<?php foreach ($module_stats as $module) echo $module['avg_score'] . ", "; ?>],
                        backgroundColor: 'rgba(46, 204, 113, 0.8)',
                        borderColor: '#27ae60',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Percentage (%)'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>